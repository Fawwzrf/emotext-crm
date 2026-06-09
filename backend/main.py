from fastapi import FastAPI, Depends, HTTPException, BackgroundTasks, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from pydantic import BaseModel
from typing import List, Optional
from ai_service import predict_sentiment_and_intent
from rag_service import get_smart_suggestion
import hashlib
import secrets
import time
import threading
import os
import urllib.request
import json
from dotenv import load_dotenv
from slowapi import Limiter, _rate_limit_exceeded_handler
from slowapi.util import get_remote_address
from slowapi.errors import RateLimitExceeded
import logging

# Konfigurasi Logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    handlers=[
        logging.FileHandler("app.log", encoding="utf-8"),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger("emotext")

load_dotenv()
LARAVEL_URL = os.getenv("LARAVEL_URL", "http://127.0.0.1:8001")
INTERNAL_API_KEY = os.getenv("INTERNAL_API_KEY", "emotext_secret_internal_key_2026")

def trigger_laravel_broadcast(message_id: int):
    try:
        url = f"{LARAVEL_URL.rstrip('/')}/api/internal/broadcast"
        data = json.dumps({"message_id": message_id}).encode('utf-8')
        req = urllib.request.Request(url, data=data, headers={
            'Content-Type': 'application/json',
            'X-Internal-Api-Key': INTERNAL_API_KEY
        })
        urllib.request.urlopen(req, timeout=3)
    except Exception as e:
        logger.error(f"[WEBHOOK ERROR] Gagal mengirim siaran ke Laravel: {e}")

# Cache untuk mencegah Race Condition / Spam request
RECENT_MESSAGES = {}
recent_messages_lock = threading.Lock()

def check_and_set_recent(sender_id, message_text):
    key = f"{sender_id}:{message_text}"
    current_time = time.time()
    with recent_messages_lock:
        if key in RECENT_MESSAGES:
            # Jika ada request identik dalam 5 detik terakhir, tolak
            if current_time - RECENT_MESSAGES[key] < 5:
                return False
        RECENT_MESSAGES[key] = current_time
        
        # Cleanup otomatis (hapus key lama agar memori tidak penuh)
        keys_to_delete = [k for k, v in RECENT_MESSAGES.items() if current_time - v > 60]
        for k in keys_to_delete:
            del RECENT_MESSAGES[k]
            
        return True

# Import dari file database & models
from database import SessionLocal, engine
from models import Message, ManualCorrection

from sqlalchemy import Column, Integer, String, text
from sqlalchemy.orm import Session
from sqlalchemy import func

# ─── get_db dependency ──────────────────────────────────────────────────────
def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

# ─── Inisialisasi FastAPI ────────────────────────────────────────────────────
app = FastAPI(title="EmoText CRM API")

limiter = Limiter(key_func=get_remote_address)
app.state.limiter = limiter
app.add_exception_handler(RateLimitExceeded, _rate_limit_exceeded_handler)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["https://web.whatsapp.com", "http://localhost:8001", "http://127.0.0.1:8001"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

security_scheme = HTTPBearer()

# ─── Auth: Validasi api_token ke tabel users (Laravel) ──────────────────────
def verify_api_key(
    credentials: HTTPAuthorizationCredentials = Depends(security_scheme),
    db: Session = Depends(get_db)
):
    """
    BUG-01 FIXED: token divalidasi ke tabel users.api_token di database.
    BUG-04 FIXED: auth dipindah ke Laravel. FastAPI hanya memverifikasi token yang
                  sudah digenerate Laravel.
    """
    token = credentials.credentials
    if not token:
        raise HTTPException(status_code=401, detail="Unauthorized: Missing API Key")

    # Cari user berdasarkan api_token
    result = db.execute(
        text("SELECT id, subscription_status FROM users WHERE api_token = :token LIMIT 1"),
        {"token": token}
    ).fetchone()

    if not result:
        raise HTTPException(status_code=401, detail="Unauthorized: Invalid API Key")

    user_id, subscription_status = result

    # Cek status langganan — jika expired, tolak analisis baru
    if subscription_status == "expired":
        raise HTTPException(
            status_code=403,
            detail="Trial Anda telah berakhir. Silakan upgrade untuk melanjutkan analisis."
        )

    print(f"[AUTH] User ID {user_id} | Status: {subscription_status}")
    return {"user_id": user_id, "token": token}


# ─── Skema Input ─────────────────────────────────────────────────────────────
class MessageContext(BaseModel):
    text: str
    role: str

class AnalyzeRequest(BaseModel):
    sender_id: str
    sender_name: str
    context: List[MessageContext]
    timestamp: str
    message_type: str

class FeedbackRequest(BaseModel):
    message_text: str
    original_sentiment: Optional[str] = None
    corrected_sentiment: Optional[str] = None
    original_intent: Optional[str] = None
    corrected_intent: Optional[str] = None


# ─── Endpoints ───────────────────────────────────────────────────────────────

@app.post("/analyze")
@limiter.limit("30/minute")
def analyze_message(
    request: Request,
    data: AnalyzeRequest,
    background_tasks: BackgroundTasks,
    db: Session = Depends(get_db),
    auth: dict = Depends(verify_api_key)
):
    # BUG-03 FIX: Validasi context tidak kosong
    if not data.context:
        raise HTTPException(status_code=422, detail="Field 'context' tidak boleh kosong")

    last_message = data.context[-1].text
    last_message_lower = last_message.lower()

    # Cegah Race Condition: Blokir request identik jika dikirim serentak dalam < 5 detik
    if not check_and_set_recent(data.sender_id, last_message):
        logger.warning(f"[RACE CONDITION] Mengabaikan request duplikat dari {data.sender_name}")
        # Kembalikan response sukses tapi anggap sebagai duplikat agar ekstensi tidak error
        return {
            "sentiment": "neutral",
            "intent": "other",
            "confidence": 1.0,
            "health_score": 50,
            "health_status": "Duplicate",
            "suggestion": "Pesan ini diabaikan karena terdeteksi sebagai duplikat (race condition)."
        }

    # 1. Rule-based override
    if any(word in last_message_lower for word in ["rusak", "kecewa", "jelek", "lambat", "penipuan", "beda", "paket", "belum", "sampai", "estimasi"]):
        sentiment, intent, confidence = "negative", "complaint", 0.99
    elif data.message_type == "media" or "[media]" in last_message_lower:
        sentiment, intent, confidence = "neutral", "media", 1.0
    else:
        ai_result = predict_sentiment_and_intent(last_message)
        sentiment  = ai_result["sentiment"]
        intent     = ai_result["intent"]
        confidence = ai_result["confidence"]

    # 2. RAG suggestion
    suggestion = get_smart_suggestion(intent, sentiment)
    logger.debug(f"RAG suggestion untuk {intent} = {suggestion}")

    # 3. Cek duplikasi & simpan ke tabel 'messages' (selaras dengan Laravel)
    existing_msg = db.query(Message).filter(
        Message.sender_id == data.sender_id,
        Message.message == data.context[-1].text
    ).first()

    if not existing_msg:
        new_log = Message(
            user_id     = auth["user_id"],
            sender_id   = data.sender_id,
            sender_name = data.sender_name,
            message     = data.context[-1].text,  # field 'message', bukan 'message_text'
            sentiment   = sentiment,
            intent      = intent,
            confidence  = confidence
        )
        db.add(new_log)
        db.commit()
        db.refresh(new_log)
        logger.info(f"Pesan BARU dari {data.sender_name} berhasil disimpan ke DB.")
        
        # Trigger WebSockets di latar belakang
        background_tasks.add_task(trigger_laravel_broadcast, new_log.id)
    else:
        logger.info("Pesan LAMA terdeteksi (efek reload), diabaikan.")

    # 4. Hitung Health Score Kumulatif via SQL Aggregation
    from sqlalchemy import case, func
    stats = db.query(
        func.count(Message.id).label("total_msgs"),
        func.sum(
            case(
                (Message.sentiment == "positive", 100),
                (Message.sentiment == "negative", 30),
                else_=70
            )
        ).label("total_score")
    ).filter(
        Message.user_id   == auth["user_id"],
        Message.sender_id == data.sender_id
    ).first()

    total_msgs = stats.total_msgs or 0
    total_score = stats.total_score or 0

    cumulative_score = int(total_score / total_msgs) if total_msgs > 0 else 70

    if cumulative_score >= 80:   health_status = "Loyal"
    elif cumulative_score >= 50: health_status = "Good"
    else:                        health_status = "At Risk"

    logger.info(f"Pesan dari: {data.sender_name} | Interaksi ke-{total_msgs} | Sentimen: {sentiment.upper()} | Intensi: {intent.upper()} | Skor: {cumulative_score}")

    return {
        "sentiment":     sentiment,
        "intent":        intent,
        "confidence":    confidence,
        "health_score":  cumulative_score,
        "health_status": health_status,
        "suggestion":    suggestion
    }




@app.get("/health-score/{sender_id:path}")
def get_health_score(
    sender_id: str,
    db: Session = Depends(get_db),
    auth: dict = Depends(verify_api_key)
):
    from sqlalchemy import case, func
    stats = db.query(
        func.count(Message.id).label("total_msgs"),
        func.sum(
            case(
                (Message.sentiment == "positive", 100),
                (Message.sentiment == "negative", 30),
                else_=70
            )
        ).label("total_score")
    ).filter(
        Message.user_id   == auth["user_id"],
        Message.sender_id == sender_id
    ).first()

    total_msgs = stats.total_msgs or 0
    total_score = stats.total_score or 0

    cumulative_score = int(total_score / total_msgs) if total_msgs > 0 else 70

    return {
        "sender_id":          sender_id,
        "health_score":       cumulative_score,
        "interactions_count": total_msgs
    }


@app.post("/feedback")
@limiter.limit("20/minute")
def save_feedback(
    request: Request,
    data: FeedbackRequest,
    db: Session = Depends(get_db),
    auth: dict = Depends(verify_api_key)
):
    new_correction = ManualCorrection(
        message_text        = data.message_text,
        original_sentiment  = data.original_sentiment,
        corrected_sentiment = data.corrected_sentiment,
        original_intent     = data.original_intent,
        corrected_intent    = data.corrected_intent,
        admin_id            = str(auth["user_id"])
    )
    db.add(new_correction)
    db.commit()
    db.refresh(new_correction)

    print("=========================================")
    print(f"KOREKSI DITERIMA!")
    print(f"Pesan: {data.message_text}")
    if data.corrected_sentiment:
        print(f"[SENTIMEN] Lama: {data.original_sentiment} -> Baru: {data.corrected_sentiment}")
    if data.corrected_intent:
        print(f"[INTENSI] Lama: {data.original_intent} -> Baru: {data.corrected_intent}")
    print("=========================================\n")

    return {"status": "success", "correction_id": new_correction.id}