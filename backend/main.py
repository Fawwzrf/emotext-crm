from fastapi import FastAPI, Depends
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import List

# Import SQLAlchemy
from sqlalchemy import create_engine, Column, Integer, String, Text
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

# Membuat Endpoint POST /analyze
@app.post("/analyze")
async def analyze_message(data: AnalyzeRequest, db: Session = Depends(get_db)):
    # Ambil teks pesan terakhir dari user dan ubah ke huruf kecil semua
    last_message = data.context[-1].text.lower()
    
    # Siapkan nilai default (jika tidak ada kata kunci yang cocok)
    sentiment = "neutral"
    intent = "general"
    health_score = 70
    health_status = "Good"
    suggestion = "Baik Kak, ada yang bisa kami bantu?"
    confidence = 0.85

    # Logika Rule-Based (Pengganti sementara model ML)
    if data.message_type == "media" or "[media]" in last_message:
        intent = "media"
        suggestion = "Terima kasih atas lampirannya. Akan kami cek segera."
        
    elif any(word in last_message for word in ["rusak", "kecewa", "jelek", "kurang", "lambat"]):
        sentiment = "negative"
        intent = "complaint"
        health_score = 30
        health_status = "At Risk"
        suggestion = "Mohon maaf atas ketidaknyamanan ini. Boleh kirimkan foto detail kendalanya?"
        confidence = 0.95
        
    elif any(word in last_message for word in ["bagus", "terima kasih", "mantap", "puas", "keren"]):
        sentiment = "positive"
        intent = "appreciation"
        health_score = 95
        health_status = "Loyal"
        suggestion = "Terima kasih kembali! Senang bisa melayani Anda."
        confidence = 0.98
        
    elif any(word in last_message for word in ["pesan", "order", "beli", "mau", "checkout"]):
        intent = "order"
        suggestion = "Siap Kak! Untuk pemesanan, mohon informasikan alamat lengkapnya ya."
        
    elif any(word in last_message for word in ["stok", "harga", "tanya", "ready"]):
        intent = "inquiry"
        suggestion = "Halo Kak! Produk tersebut saat ini ready stock. Ingin pesan berapa banyak?"

    # Simpan data ke Database
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
    # -------------------------------------

    # Cetak ke terminal agar mudah dipantau
    print("=========================================")
    print(f"Pesan dari {data.sender_name} berhasil disimpan ke Database! (ID: {new_log.id})")
    print(f"[USER]: {data.context[-1].text}")
    print("=========================================\n")
    
    # Kembalikan hasil yang sudah dinamis ke Ekstensi
    return {
        "sentiment": sentiment,
        "intent": intent,
        "confidence": confidence,
        "health_score": health_score,
        "health_status": health_status,
        "suggestion": suggestion
    }

# Membuat Endpoint GET /health-score
@app.get("/health-score")
async def get_health_score():
    return {"message": "Endpoint health-score berjalan dengan baik!"}