# pyrefly: ignore [missing-import]
import os
from dotenv import load_dotenv
load_dotenv()  # HARUS di sini — sebelum import database/ai_service agar DATABASE_URL tersedia

from fastapi import FastAPI, Depends, HTTPException, BackgroundTasks, Request, File, UploadFile, Form
from fastapi.middleware.cors import CORSMiddleware
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from pydantic import BaseModel
from typing import List, Optional
from ai_service import predict_sentiment_and_intent
import threading
import hashlib
import time
import urllib.request
import json
from slowapi import Limiter, _rate_limit_exceeded_handler
from slowapi.util import get_remote_address
from slowapi.errors import RateLimitExceeded
import logging
import asyncio

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
LARAVEL_URL = os.getenv("LARAVEL_URL", "http://127.0.0.1:8001")
INTERNAL_API_KEY = os.getenv("INTERNAL_API_KEY", "")


def trigger_laravel_broadcast(message_id: int):
    # Skip jika LARAVEL_URL adalah localhost — tidak bisa dijangkau dari HF Cloud
    if any(h in LARAVEL_URL for h in ['127.0.0.1', 'localhost', '0.0.0.0']):
        logger.info(f"[WEBHOOK] Melewati broadcast (LARAVEL_URL adalah localhost, tidak bisa dijangkau dari cloud)")
        return
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


# ─── Cache in-memory untuk mencegah Race Condition ───────────────────────────
RECENT_MESSAGES: dict = {}
recent_messages_lock = threading.Lock()

def check_and_set_recent(user_id: int, sender_id: str, message_text: str) -> bool:
    key = f"{user_id}:{sender_id}:{message_text}"
    current_time = time.time()
    with recent_messages_lock:
        if key in RECENT_MESSAGES and current_time - RECENT_MESSAGES[key] < 5:
            return False
        RECENT_MESSAGES[key] = current_time
        # Cleanup otomatis
        stale = [k for k, v in RECENT_MESSAGES.items() if current_time - v > 60]
        for k in stale:
            del RECENT_MESSAGES[k]
        return True


# ─── Import DB & Models ───────────────────────────────────────────────────────
from database import SessionLocal, engine
from models import Message, ManualCorrection
from sqlalchemy import Column, Integer, String, text, func, select, case
from sqlalchemy.orm import Session
from sqlalchemy.ext.asyncio import AsyncSession

async def get_db():
    async with SessionLocal() as db:
        yield db


# ─── FastAPI App ───────────────────────────────────────────────────────────────
from contextlib import asynccontextmanager
import threading

@asynccontextmanager
async def lifespan(app: FastAPI):
    """Lifespan handler: pra-muat LLM dan FAISS di background saat server start."""
    from rag_service import _load_llm, _load_local_rag_index
    logger.info("[STARTUP] Memuat LLM dan FAISS di background agar generasi pertama lebih cepat...")
    threading.Thread(target=_load_local_rag_index, daemon=True).start()
    threading.Thread(target=_load_llm, daemon=True).start()
    yield  # ── server berjalan ──
    logger.info("[SHUTDOWN] Server berhenti.")

app = FastAPI(title="EmoText CRM API", lifespan=lifespan)

@app.get("/ping")
async def ping():
    """Health check tanpa database — untuk diagnosa startup."""
    return {"status": "ok", "message": "Server is running"}

@app.get("/db-test")
async def db_test():
    """Cek koneksi database — hanya untuk diagnosa, hapus di production."""
    try:
        from database import engine
        from sqlalchemy import text as sa_text
        async with engine.connect() as conn:
            result = await conn.execute(sa_text("SELECT 1 as val"))
            row = result.fetchone()
            return {"status": "ok", "db_result": row[0]}
    except Exception as e:
        return {"status": "error", "error": str(e), "type": type(e).__name__}


limiter = Limiter(key_func=get_remote_address)
app.state.limiter = limiter
app.add_exception_handler(RateLimitExceeded, _rate_limit_exceeded_handler)

app.add_middleware(
    CORSMiddleware,
    allow_origins=[
        "https://web.whatsapp.com",
        "http://localhost:8001",
        "http://127.0.0.1:8001",
        "null",
    ],
    allow_origin_regex=r"chrome-extension://.*",
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

security_scheme = HTTPBearer()


# ─── Auth ──────────────────────────────────────────────────────────────────────
async def verify_api_key(
    credentials: HTTPAuthorizationCredentials = Depends(security_scheme),
    db: AsyncSession = Depends(get_db)
):
    token = credentials.credentials
    if not token:
        raise HTTPException(status_code=401, detail="Unauthorized: Missing API Key")

    hashed_token = hashlib.sha256(token.encode('utf-8')).hexdigest()
    result = await db.execute(
        text("SELECT id, subscription_status FROM users WHERE api_token = :token LIMIT 1"),
        {"token": hashed_token}
    )
    row = result.fetchone()
    if not row:
        raise HTTPException(status_code=401, detail="Unauthorized: Invalid API Key")

    user_id, subscription_status = row
    if subscription_status == "expired":
        raise HTTPException(status_code=403, detail="Trial Anda telah berakhir. Silakan upgrade.")

    return {"user_id": user_id, "token": token}


@app.post("/sync-rag")
async def sync_rag(auth: dict = Depends(verify_api_key)):
    """Memicu reload FAISS index dari database tanpa restart server."""
    from rag_service import force_reload_rag
    import asyncio
    
    # Reload di background agar tidak memblokir endpoint
    asyncio.create_task(asyncio.to_thread(force_reload_rag))
    return {"status": "success", "message": "RAG sync started in background"}


# ─── Schemas ───────────────────────────────────────────────────────────────────
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


# ─── Helpers ────────────────────────────────────────────────────────────────────
from fastapi import Header
from pypdf import PdfReader
import io

async def verify_internal(x_internal_api_key: str = Header(...)):
    if x_internal_api_key != INTERNAL_API_KEY:
        raise HTTPException(status_code=401, detail="Unauthorized")
    return True


def rule_based_classify(text_lower: str, message_type: str):
    """
    Klasifikasi cepat berbasis aturan. Mengembalikan (sentiment, intent, confidence)
    atau None jika tidak ada aturan yang cocok.
    """
    if message_type == "media" or "[media]" in text_lower:
        return "neutral", "media", 1.0
    if any(w in text_lower for w in ["rusak", "kecewa", "jelek", "lambat", "penipuan", "beda", "paket", "belum", "sampai", "estimasi"]):
        return "negative", "complaint", 0.99
    return None


async def compute_health_score(db: AsyncSession, user_id: int, sender_id: str) -> tuple:
    """Hitung health score kumulatif dari database."""
    stats_result = await db.execute(
        select(
            func.count(Message.id).label("total_msgs"),
            func.sum(
                case(
                    (Message.sentiment == "positive", 100),
                    (Message.sentiment == "negative", 30),
                    else_=70
                )
            ).label("total_score")
        ).filter(
            Message.user_id == user_id,
            Message.sender_id == sender_id
        )
    )
    stats = stats_result.first()
    total_msgs = stats.total_msgs or 0
    total_score = stats.total_score or 0
    cumulative_score = int(total_score / total_msgs) if total_msgs > 0 else 70

    if cumulative_score >= 80:   health_status = "Loyal"
    elif cumulative_score >= 50: health_status = "Good"
    else:                        health_status = "At Risk"

    return cumulative_score, health_status, total_msgs



# ─── Endpoints ─────────────────────────────────────────────────────────────────
@app.post("/sync-kb")
async def sync_knowledge_base(
    request: Request,
    background_tasks: BackgroundTasks,
    _: bool = Depends(verify_internal)
):
    from rag_service import _load_local_rag_index
    import rag_service
    
    rag_service._rag_loaded = False
    background_tasks.add_task(_load_local_rag_index)
    
    return {"status": "success", "message": "Sinkronisasi RAG dimulai di background"}


@app.post("/analyze")
@limiter.limit("300/minute")
async def analyze_message(
    request: Request,
    data: AnalyzeRequest,
    background_tasks: BackgroundTasks,
    db: AsyncSession = Depends(get_db),
    auth: dict = Depends(verify_api_key)
):
    """
    ARSITEKTUR FAST PATH / SLOW PATH:
    - Fast Path (<500ms): Cek DB cache → rule-based → ONNX inference → return badge
    - Slow Path (fire-and-forget): RAG Qwen inference di background, update DB async
    """
    if not data.context:
        raise HTTPException(status_code=422, detail="Field 'context' tidak boleh kosong")

    last_message = data.context[-1].text
    last_message_lower = last_message.lower()
    user_id = auth["user_id"]

    # ── 1. CACHE CHECK: Blokir duplikat dalam 5 detik ─────────────────────────
    if not check_and_set_recent(user_id, data.sender_id, last_message):
        logger.warning(f"[DEDUP] Mengabaikan duplikat dari {data.sender_name}")
        cumulative_score, health_status, _ = await compute_health_score(db, user_id, data.sender_id)
        return {
            "sentiment": "neutral",
            "intent": "other",
            "confidence": 1.0,
            "health_score": cumulative_score,
            "health_status": health_status,
            "message_id": None,
            "suggestion": None
        }

    # ── 2. DB CACHE: Pesan lama? Kembalikan instan dari database ──────────────
    existing_result = await db.execute(
        select(Message).filter(
            Message.user_id == user_id,
            Message.sender_id == data.sender_id,
            Message.message == last_message
        )
    )
    existing_msg = existing_result.scalars().first()

    if existing_msg:
        logger.info(f"[CACHE HIT] Pesan lama dari {data.sender_name}, return dari DB.")
        cumulative_score, health_status, _ = await compute_health_score(db, user_id, data.sender_id)
        return {
            "sentiment":     existing_msg.sentiment,
            "intent":        existing_msg.intent,
            "confidence":    existing_msg.confidence,
            "health_score":  cumulative_score,
            "health_status": health_status,
            "message_id":    existing_msg.id,
            "suggestion":    existing_msg.suggestion  # bisa None jika RAG belum selesai
        }

    # ── 3. FAST CLASSIFY: Rule-based (<1ms) ───────────────────────────────────
    rule_result = rule_based_classify(last_message_lower, data.message_type)

    if rule_result:
        sentiment, intent, confidence = rule_result
        logger.info(f"[RULE] {data.sender_name}: {intent}/{sentiment}")
    else:
        # ── 4. ONNX ML INFERENCE (hanya untuk pesan baru, ~50-200ms) ──────────
        ai_result = await predict_sentiment_and_intent(last_message, db)
        sentiment  = ai_result["sentiment"]
        intent     = ai_result["intent"]
        confidence = ai_result["confidence"]
        logger.info(f"[ONNX] {data.sender_name}: {intent}/{sentiment} ({confidence:.2f})")

    # ── 5. SIMPAN KE DB SEGERA (tanpa menunggu RAG) ────────────────────────────
    new_log = Message(
        user_id     = user_id,
        sender_id   = data.sender_id,
        sender_name = data.sender_name,
        message     = last_message,
        sentiment   = sentiment,
        intent      = intent,
        confidence  = confidence,
        suggestion  = None  # RAG akan diisi oleh background task
    )
    db.add(new_log)
    await db.commit()
    await db.refresh(new_log)
    logger.info(f"[DB] Pesan BARU dari {data.sender_name} disimpan (ID={new_log.id}).")

    # ── 6. FIRE-AND-FORGET: Broadcast WebSocket di background ───────────────────
    from database import SessionLocal as SL
    db_url = os.getenv("DATABASE_URL", "")
    # Broadcast WebSocket juga di background
    background_tasks.add_task(trigger_laravel_broadcast, new_log.id)

    # ── 7. HITUNG HEALTH SCORE DAN RETURN INSTAN ──────────────────────────────
    cumulative_score, health_status, total_msgs = await compute_health_score(db, user_id, data.sender_id)
    logger.info(f"[SCORE] {data.sender_name} | Interaksi ke-{total_msgs} | {sentiment.upper()} | {intent.upper()} | Skor: {cumulative_score}")

    return {
        "sentiment":     sentiment,
        "intent":        intent,
        "confidence":    confidence,
        "health_score":  cumulative_score,
        "health_status": health_status,
        "message_id":    new_log.id,  # Digunakan ekstensi untuk lazy-load suggestion saat diklik
        "suggestion":    None  # Badge muncul instan; suggestion di-fetch via /suggestion/{id}
    }


from fastapi.responses import StreamingResponse

@app.get("/suggestion/stream/{message_id}")
async def stream_suggestion(
    message_id: int,
    db: AsyncSession = Depends(get_db),
    auth: dict = Depends(verify_api_key)
):
    """
    Endpoint untuk men-stream hasil RAG token-by-token ke UI (seperti ChatGPT).
    """
    logger.info(f"[STREAM SUGGESTION] Extension meminta stream untuk message_id={message_id}")
    result = await db.execute(
        select(Message).filter(
            Message.id == message_id,
            Message.user_id == auth["user_id"]
        )
    )
    msg = result.scalars().first()
    if not msg:
        raise HTTPException(status_code=404, detail="Pesan tidak ditemukan.")
    
    # Jika sudah pernah di-generate sebelumnya, kirim teks utuh sekaligus
    if msg.suggestion:
        async def mock_stream():
            yield msg.suggestion
        return StreamingResponse(mock_stream(), media_type="text/plain")

    # Panggil stream_smart_suggestion dari rag_service
    from rag_service import stream_smart_suggestion
    
    # Fungsi wrapper untuk menangkap hasil akhir dan menyimpannya ke DB
    async def stream_and_save():
        full_text = ""
        # stream_smart_suggestion adalah generator synchronous, jadi kita bisa iterasi secara asinkron
        # Tapi karena llama_cpp memblokir thread saat menghasilkan setiap token,
        # kita harus iterasi lewat run_in_threadpool.
        from starlette.concurrency import run_in_threadpool
        
        gen = stream_smart_suggestion(msg.intent, msg.sentiment, msg.message, auth["user_id"])
        while True:
            try:
                chunk = await run_in_threadpool(next, gen, None)
                if chunk is None:
                    break
                full_text += chunk
                yield chunk
            except StopIteration:
                break
            except Exception as e:
                logger.error(f"[STREAM] Exception saat streaming: {e}")
                break
            
        # Simpan ke DB setelah stream selesai menggunakan sesi baru
        # karena sesi 'db' dari Depends sudah ditutup oleh FastAPI
        if full_text:
            from database import SessionLocal as SL
            async with SL() as new_db:
                res = await new_db.execute(select(Message).filter(Message.id == message_id))
                m = res.scalars().first()
                if m:
                    m.suggestion = full_text.strip()
                    await new_db.commit()

    return StreamingResponse(stream_and_save(), media_type="text/plain")

@app.get("/suggestion/{message_id}")
async def get_suggestion(
    message_id: int,
    db: AsyncSession = Depends(get_db),
    auth: dict = Depends(verify_api_key)
):
    """
    Endpoint khusus untuk mengambil suggestion RAG secara on-demand saat pesan diklik.
    """
    logger.info(f"[FETCH SUGGESTION] Extension meminta suggestion untuk message_id={message_id}")
    result = await db.execute(
        select(Message).filter(
            Message.id == message_id,
            Message.user_id == auth["user_id"]
        )
    )
    msg = result.scalars().first()
    if not msg:
        logger.warning(f"[FETCH SUGGESTION] Pesan {message_id} tidak ditemukan di DB.")
        raise HTTPException(status_code=404, detail="Pesan tidak ditemukan.")
    
    # Jika sudah pernah di-generate, kembalikan langsung dari cache DB
    if msg.suggestion:
        logger.info(f"[FETCH SUGGESTION] Ditemukan msg.suggestion={repr(msg.suggestion)} untuk {message_id}")
        return {"suggestion": msg.suggestion, "ready": True}
        
    # Jika belum, generate sekarang (On-Demand RAG)
    from rag_service import get_smart_suggestion
    logger.info(f"[RAG ON-DEMAND] Generate suggestion untuk {message_id}...")
    suggestion = await get_smart_suggestion(msg.intent, msg.sentiment, msg.message, None, auth["user_id"])
    
    # Simpan ke DB untuk cache agar klik berikutnya instan
    if suggestion:
        msg.suggestion = suggestion
        await db.commit()
        
    return {"suggestion": suggestion, "ready": True}


@app.get("/health-score/{sender_id:path}")
async def get_health_score(
    sender_id: str,
    db: AsyncSession = Depends(get_db),
    auth: dict = Depends(verify_api_key)
):
    cumulative_score, health_status, total_msgs = await compute_health_score(db, auth["user_id"], sender_id)
    return {
        "sender_id":          sender_id,
        "health_score":       cumulative_score,
        "interactions_count": total_msgs
    }


@app.post("/feedback")
@limiter.limit("100/minute")
async def save_feedback(
    request: Request,
    data: FeedbackRequest,
    db: AsyncSession = Depends(get_db),
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
    await db.commit()
    await db.refresh(new_correction)

    logger.info(f"[FEEDBACK] Koreksi diterima: {data.message_text[:50]}")
    return {"status": "success", "correction_id": new_correction.id}

