from fastapi import FastAPI, Depends, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from pydantic import BaseModel
from typing import List, Optional
from ai_service import predict_sentiment_and_intent
from rag_service import get_smart_suggestion

# 1. IMPORT DARI FILE DATABASE & MODELS YANG TELAH DIPISAH
from database import SessionLocal
from models import MessageLog, ManualCorrection

from sqlalchemy.orm import Session
from sqlalchemy import func

# 2. DEFINISIKAN DEPENDENCY get_db TERLEBIH DAHULU (Penting agar tidak Error 'not defined')
def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

# 3. INISIALISASI APLIKASI FASTAPI
app = FastAPI(title="EmoText CRM API")

# Konfigurasi CORS agar WhatsApp Web bisa mengakses API ini
app.add_middleware(
    CORSMiddleware,
    allow_origins=["https://web.whatsapp.com"], 
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Inisialisasi Skema Keamanan HTTP Bearer
security_scheme = HTTPBearer()

# Dependency untuk Validasi Token API Key Perusahaan
def verify_api_key(credentials: HTTPAuthorizationCredentials = Depends(security_scheme)):
    token = credentials.credentials
    print(f"[AUTH DEBUG] API Key diterima: '{token}'")
    if not token:
        print("[AUTH DEBUG] Gagal: Token kosong / tidak ada.")
        raise HTTPException(status_code=401, detail="Unauthorized: Missing API Key")
    # Validasi API key sederhana (Diberikan akses jika token DUMMY_KEY atau memiliki prefiks EMOTEXT_)
    if token != "DUMMY_KEY" and not token.startswith("EMOTEXT_") and len(token) < 8:
        print(f"[AUTH DEBUG] Gagal: Token '{token}' tidak memenuhi syarat (harus 'DUMMY_KEY', berawalan 'EMOTEXT_', atau >= 8 karakter).")
        raise HTTPException(status_code=401, detail="Unauthorized: Invalid API Key")
    print("[AUTH DEBUG] Akses Diterima.")
    return token

# Membuat Struktur Data Input (Validasi Request)
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

# 4. DAFTAR ENDPOINT
# Membuat Endpoint POST /analyze dengan Proteksi Token (Tanpa async agar thread PyTorch tidak memblokir server)
@app.post("/analyze")
def analyze_message(
    data: AnalyzeRequest, 
    db: Session = Depends(get_db),
    token: str = Depends(verify_api_key)
):
    last_message = data.context[-1].text
    last_message_lower = last_message.lower()
    
    # 1. RULE-BASED OVERRIDE (Pencegah AI Salah Tebak)
    # Saya menambahkan kata kunci paket, estimasi, dan belum agar keluhan pengiriman juga terdeteksi
    if any(word in last_message_lower for word in ["rusak", "kecewa", "jelek", "lambat", "penipuan", "beda", "paket", "belum", "sampai", "estimasi"]):
        sentiment = "negative"
        intent = "complaint"
        confidence = 0.99
    elif data.message_type == "media" or "[media]" in last_message_lower:
        sentiment = "neutral"
        intent = "media"
        confidence = 1.0
    else:
        # Panggil AI hanya jika bukan keluhan eksplisit atau media
        ai_result = predict_sentiment_and_intent(last_message)
        sentiment = ai_result["sentiment"]
        intent = ai_result["intent"]
        confidence = ai_result["confidence"]

    # Ambil suggestion dari RAG
    suggestion = get_smart_suggestion(intent, sentiment)
    print(f"DEBUG: RAG memberikan Suggestion untuk {intent} = {suggestion}")

    # 2. Simpan Pesan Baru ke Database Terlebih Dahulu
    # CEK DUPLIKASI (Mencegah pesan tersimpan ganda saat halaman di-reload)
    existing_msg = db.query(MessageLog).filter(
        MessageLog.sender_id == data.sender_id,
        MessageLog.message_text == data.context[-1].text
    ).first()

    if not existing_msg:
        # Jika belum ada di database, baru kita simpan
        new_log = MessageLog(
            sender_id=data.sender_id,
            sender_name=data.sender_name,
            message_text=data.context[-1].text,
            sentiment=sentiment,
            intent=intent,
            confidence=confidence
        )
        db.add(new_log)
        db.commit()
        db.refresh(new_log)
        print("-> Pesan BARU berhasil disimpan ke DB.")
    else:
        # Jika sudah ada, abaikan saja
        print("-> Pesan LAMA terdeteksi (efek reload), diabaikan.")

    # 3. ALGORITMA HEALTH SCORE KUMULATIF
    # Ambil SEMUA riwayat sentimen dari pelanggan ini
    past_interactions = db.query(MessageLog.sentiment).filter(MessageLog.sender_id == data.sender_id).all()
    
    total_score = 0
    for (snt,) in past_interactions:
        if snt == "positive":
            total_score += 100
        elif snt == "negative":
            total_score += 30
        else:
            total_score += 70
            
    # Hitung rata-rata
    if len(past_interactions) > 0:
        cumulative_score = int(total_score / len(past_interactions))
    else:
        cumulative_score = 70 # Default jika belum ada riwayat

    # Tentukan Status Loyalitas berdasarkan Rata-rata
    if cumulative_score >= 80:
        health_status = "Loyal"
    elif cumulative_score >= 50:
        health_status = "Good"
    else:
        health_status = "At Risk"

    # 4. Cetak ke Terminal
    print("=========================================")
    print(f"Pesan dari: {data.sender_name} | Interaksi ke-{len(past_interactions)}")
    print(f"[CHAT]: {data.context[-1].text}")
    print(f"[PREDICTION] Sentimen: {sentiment.upper()} | Intensi: {intent.upper()}")
    print(f"[LOYALTY] Cumulative Score: {cumulative_score} ({health_status.upper()})")
    print("=========================================\n")
    
    # 5. Kembalikan ke Ekstensi
    return {
        "sentiment": sentiment,
        "intent": intent,
        "confidence": confidence,
        "health_score": cumulative_score,
        "health_status": health_status,
        "suggestion": suggestion
    }

@app.get("/dashboard-stats")
def get_dashboard_stats(
        db: Session = Depends(get_db),
        token: str = Depends(verify_api_key)
    ):
    # 1. Hitung total pesan yang masuk
    total_messages = db.query(MessageLog).count()

    # 2. Hitung persentase Intensi (Order vs Complaint vs Inquiry, dll)
    intent_counts = db.query(
        MessageLog.intent, 
        func.count(MessageLog.intent)
    ).group_by(MessageLog.intent).all()
    
    # Ubah hasil query (list of tuples) menjadi dictionary agar mudah dibaca di Dashboard
    intent_stats = {intent: count for intent, count in intent_counts}

    # 3. Hitung tren Sentimen
    sentiment_counts = db.query(
        MessageLog.sentiment, 
        func.count(MessageLog.sentiment)
    ).group_by(MessageLog.sentiment).all()
    
    sentiment_stats = {sentiment: count for sentiment, count in sentiment_counts}

    # 4. Ambil daftar pelanggan terbaru (untuk melihat siapa saja yang aktif)
    recent_customers = db.query(MessageLog.sender_name, MessageLog.sentiment, MessageLog.intent)\
        .order_by(MessageLog.id.desc())\
        .limit(5)\
        .all()

    return {
        "summary": {
            "total_interactions": total_messages,
            "top_intent": max(intent_stats, key=intent_stats.get) if intent_stats else "N/A"
        },
        "intents": intent_stats,
        "sentiments": sentiment_stats,
        "recent_activity": [
            {"name": name, "sentiment": snt, "intent": intnt} 
            for name, snt, intnt in recent_customers
        ]
    }

# Membuat Endpoint GET /health-score/{sender_id} dengan Proteksi Token
@app.get("/health-score/{sender_id:path}")
def get_health_score(
    sender_id: str,
    db: Session = Depends(get_db),
    token: str = Depends(verify_api_key)
):
    past_interactions = db.query(MessageLog.sentiment).filter(MessageLog.sender_id == sender_id).all()
    
    total_score = 0
    for (snt,) in past_interactions:
        if snt == "positive":
            total_score += 100
        elif snt == "negative":
            total_score += 30
        else:
            total_score += 70
            
    if len(past_interactions) > 0:
        cumulative_score = int(total_score / len(past_interactions))
    else:
        cumulative_score = 70 # Default jika belum ada riwayat

    return {
        "sender_id": sender_id,
        "health_score": cumulative_score,
        "interactions_count": len(past_interactions)
    }

# Membuat Endpoint POST /feedback dengan Proteksi Token & Fitur Intensi
@app.post("/feedback")
def save_feedback(
    data: FeedbackRequest, 
    db: Session = Depends(get_db),
    token: str = Depends(verify_api_key)
):
    new_correction = ManualCorrection(
        message_text=data.message_text,
        original_sentiment=data.original_sentiment,
        corrected_sentiment=data.corrected_sentiment,
        original_intent=data.original_intent,
        corrected_intent=data.corrected_intent,
        admin_id=data.admin_id
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