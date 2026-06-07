from fastapi import FastAPI, Depends, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from pydantic import BaseModel
from typing import List, Optional
from ai_service import predict_sentiment_and_intent
from rag_service import get_smart_suggestion
import hashlib
import secrets

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
    admin_id: str


# ─── Endpoints ───────────────────────────────────────────────────────────────

@app.post("/analyze")
def analyze_message(
    data: AnalyzeRequest,
    db: Session = Depends(get_db),
    auth: dict = Depends(verify_api_key)
):
    # BUG-03 FIX: Validasi context tidak kosong
    if not data.context:
        raise HTTPException(status_code=422, detail="Field 'context' tidak boleh kosong")

    last_message = data.context[-1].text
    last_message_lower = last_message.lower()

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
    print(f"DEBUG: RAG suggestion untuk {intent} = {suggestion}")

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
        print("-> Pesan BARU berhasil disimpan ke DB.")
    else:
        print("-> Pesan LAMA terdeteksi (efek reload), diabaikan.")

    # 4. Hitung Health Score Kumulatif
    past_interactions = db.query(Message.sentiment).filter(
        Message.user_id   == auth["user_id"],
        Message.sender_id == data.sender_id
    ).all()

    total_score = 0
    for (snt,) in past_interactions:
        if snt == "positive":   total_score += 100
        elif snt == "negative": total_score += 30
        else:                   total_score += 70

    cumulative_score = int(total_score / len(past_interactions)) if past_interactions else 70

    if cumulative_score >= 80:   health_status = "Loyal"
    elif cumulative_score >= 50: health_status = "Good"
    else:                        health_status = "At Risk"

    print("=========================================")
    print(f"Pesan dari: {data.sender_name} | Interaksi ke-{len(past_interactions)}")
    print(f"[CHAT]: {data.context[-1].text}")
    print(f"[PREDICTION] Sentimen: {sentiment.upper()} | Intensi: {intent.upper()}")
    print(f"[LOYALTY] Cumulative Score: {cumulative_score} ({health_status.upper()})")
    print("=========================================\n")

    return {
        "sentiment":     sentiment,
        "intent":        intent,
        "confidence":    confidence,
        "health_score":  cumulative_score,
        "health_status": health_status,
        "suggestion":    suggestion
    }


@app.get("/dashboard-stats")
def get_dashboard_stats(
    db: Session = Depends(get_db),
    auth: dict = Depends(verify_api_key)
):
    user_id = auth["user_id"]

    total_messages = db.query(Message).filter(Message.user_id == user_id).count()

    intent_counts = db.query(
        Message.intent,
        func.count(Message.intent)
    ).filter(Message.user_id == user_id).group_by(Message.intent).all()
    intent_stats = {intent: count for intent, count in intent_counts}

    sentiment_counts = db.query(
        Message.sentiment,
        func.count(Message.sentiment)
    ).filter(Message.user_id == user_id).group_by(Message.sentiment).all()
    sentiment_stats = {sentiment: count for sentiment, count in sentiment_counts}

    recent_customers = db.query(Message.sender_name, Message.sentiment, Message.intent)\
        .filter(Message.user_id == user_id)\
        .order_by(Message.id.desc())\
        .limit(5)\
        .all()

    return {
        "summary": {
            "total_interactions": total_messages,
            "top_intent": max(intent_stats, key=intent_stats.get) if intent_stats else "N/A"
        },
        "intents":    intent_stats,
        "sentiments": sentiment_stats,
        "recent_activity": [
            {"name": name, "sentiment": snt, "intent": intnt}
            for name, snt, intnt in recent_customers
        ]
    }


@app.get("/health-score/{sender_id:path}")
def get_health_score(
    sender_id: str,
    db: Session = Depends(get_db),
    auth: dict = Depends(verify_api_key)
):
    past_interactions = db.query(Message.sentiment).filter(
        Message.user_id   == auth["user_id"],
        Message.sender_id == sender_id
    ).all()

    total_score = 0
    for (snt,) in past_interactions:
        if snt == "positive":   total_score += 100
        elif snt == "negative": total_score += 30
        else:                   total_score += 70

    cumulative_score = int(total_score / len(past_interactions)) if past_interactions else 70

    return {
        "sender_id":          sender_id,
        "health_score":       cumulative_score,
        "interactions_count": len(past_interactions)
    }


@app.post("/feedback")
def save_feedback(
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