from fastapi import FastAPI, Depends
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import List

# Import SQLAlchemy
from sqlalchemy import create_engine, Column, Integer, String, Text, func
from sqlalchemy.orm import declarative_base, sessionmaker, Session

# Konfigurasi Database SQLite
SQLALCHEMY_DATABASE_URL = "sqlite:///./emotext.db"
engine = create_engine(SQLALCHEMY_DATABASE_URL, connect_args={"check_same_thread": False})
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)
Base = declarative_base()

# Model Tabel Database
class MessageLog(Base):
    __tablename__ = "message_logs"
    
    id = Column(Integer, primary_key=True, index=True)
    sender_id = Column(String, index=True)
    sender_name = Column(String)
    message_text = Column(Text)
    sentiment = Column(String)
    intent = Column(String)

class ManualCorrection(Base):
    __tablename__ = "manual_corrections"
    
    id = Column(Integer, primary_key=True, index=True)
    message_text = Column(Text)
    original_sentiment = Column(String)
    corrected_sentiment = Column(String)
    admin_id = Column(String)

# Buat file emotext.db dan tabelnya secara otomatis
Base.metadata.create_all(bind=engine)

# Dependency untuk mendapatkan sesi database tiap kali ada request
def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()
# -----------------------------------

# Inisialisasi Aplikasi FastAPI
app = FastAPI(title="EmoText CRM API")

# Konfigurasi CORS agar WhatsApp Web bisa mengakses API ini
app.add_middleware(
    CORSMiddleware,
    allow_origins=["https://web.whatsapp.com"], # Hanya izinkan dari WA Web
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Membuat Struktur Data Input (Validasi Request)
# Ini berfungsi untuk memastikan JSON yang dikirim ekstensi formatnya benar
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
    original_sentiment: str
    corrected_sentiment: str
    admin_id: str

# Membuat Endpoint POST /analyze
@app.post("/analyze")
async def analyze_message(data: AnalyzeRequest, db: Session = Depends(get_db)):
    # 1. Deteksi Sentimen & Intensi Pesan Saat Ini (Rule-Based)
    last_message = data.context[-1].text.lower()
    
    sentiment = "neutral"
    intent = "general"
    suggestion = "Baik Kak, ada yang bisa kami bantu?"
    confidence = 0.85

    if data.message_type == "media" or "[media]" in last_message:
        intent = "media"
        suggestion = "Terima kasih atas lampirannya. Akan kami cek segera."
    elif any(word in last_message for word in ["rusak", "kecewa", "jelek", "kurang", "lambat"]):
        sentiment = "negative"
        intent = "complaint"
        suggestion = "Mohon maaf atas ketidaknyamanan ini. Boleh kirimkan foto detail kendalanya?"
        confidence = 0.95
    elif any(word in last_message for word in ["bagus", "terima kasih", "mantap", "puas", "keren"]):
        sentiment = "positive"
        intent = "appreciation"
        suggestion = "Terima kasih kembali! Senang bisa melayani Anda."
        confidence = 0.98
    elif any(word in last_message for word in ["pesan", "order", "beli", "mau", "checkout"]):
        intent = "order"
        suggestion = "Siap Kak! Untuk pemesanan, mohon informasikan alamat lengkapnya ya."
    elif any(word in last_message for word in ["stok", "harga", "tanya", "ready"]):
        intent = "inquiry"
        suggestion = "Halo Kak! Produk tersebut saat ini ready stock. Ingin pesan berapa banyak?"

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
            intent=intent
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
async def get_dashboard_stats(db: Session = Depends(get_db)):
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

# Membuat Endpoint GET /health-score
@app.get("/health-score")
async def get_health_score():
    return {"message": "Endpoint health-score berjalan dengan baik!"}

@app.post("/feedback")
async def save_feedback(data: FeedbackRequest, db: Session = Depends(get_db)):
    new_correction = ManualCorrection(
        message_text=data.message_text,
        original_sentiment=data.original_sentiment,
        corrected_sentiment=data.corrected_sentiment,
        admin_id=data.admin_id
    )
    db.add(new_correction)
    db.commit()
    db.refresh(new_correction)
    
    print("=========================================")
    print(f"KOREKSI DITERIMA!")
    print(f"Pesan: {data.message_text}")
    print(f"Lama: {data.original_sentiment} -> Baru: {data.corrected_sentiment}")
    print("=========================================\n")
    
    return {"status": "success", "correction_id": new_correction.id}