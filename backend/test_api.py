import pytest
from fastapi.testclient import TestClient
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker
from sqlalchemy.pool import StaticPool
import uuid
import json

from main import app, verify_api_key, get_db
from database import Base
from models import Message
from sqlalchemy import text

# Setup In-Memory SQLite untuk testing
SQLALCHEMY_DATABASE_URL = "sqlite:///:memory:"
engine = create_engine(SQLALCHEMY_DATABASE_URL, connect_args={"check_same_thread": False}, poolclass=StaticPool)
TestingSessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)

def override_get_db():
    try:
        db = TestingSessionLocal()
        yield db
    finally:
        db.close()

def override_verify_api_key():
    return {"user_id": 9999, "token": "test_token_123"}

client = TestClient(app)

@pytest.fixture(autouse=True)
def setup_db_for_test():
    Base.metadata.create_all(bind=engine)
    db = TestingSessionLocal()
    db.execute(text('''
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY,
            name TEXT,
            email TEXT,
            password TEXT,
            company_name TEXT,
            api_token TEXT,
            subscription_status TEXT
        )
    '''))
    db.execute(text("INSERT OR IGNORE INTO users (id, name, email, password, company_name, subscription_status, api_token) VALUES (9999, 'Test User', 'test@test.com', 'pwd', 'Test Corp', 'active', 'test_token_123')"))
    db.commit()
    db.close()
    
    app.dependency_overrides[get_db] = override_get_db
    app.dependency_overrides[verify_api_key] = override_verify_api_key
    yield
    app.dependency_overrides.clear()
    Base.metadata.drop_all(bind=engine)

# 1. Tes System: Cek handling payload error 422
def test_analyze_invalid_payload():
    """
    Skenario: Mengirim JSON kosong ke endpoint analisis.
    Ekspektasi: Mengembalikan HTTP 422 Unprocessable Entity (Validasi Pydantic)
    dan bukan crash 500 Internal Server Error.
    """
    response = client.post("/analyze", json={})
    
    assert response.status_code == 422, f"Expected 422, got {response.status_code}"
    data = response.json()
    assert "detail" in data
    # Pydantic otomatis memberi tahu field apa saja yang hilang
    assert isinstance(data["detail"], list)

# 2. Tes Logic: Mocking IndoBERT & Cek Simpan Data ke Database
def test_analyze_and_store(mocker):
    """
    Skenario: Mengirim data pesan yang benar. Fungsi IndoBERT di-mocking agar tidak loading model berat.
    Ekspektasi: 
    1. Response berhasil dengan data yang dimock.
    2. Data benar-benar masuk dan tersimpan ke tabel 'messages' di Supabase.
    """
    # Lakukan mocking fungsi IndoBERT
    mock_predict = mocker.patch("main.predict_sentiment_and_intent")
    mock_predict.return_value = {
        "sentiment": "positive",
        "intent": "inquiry",
        "confidence": 0.99
    }

    # Buat sender_id unik agar tidak dianggap duplikat (reload check)
    test_sender_id = f"test_{uuid.uuid4().hex[:8]}"
    test_message = "Halo, saya mau nanya soal produk ini dong!"

    payload = {
        "sender_id": test_sender_id,
        "sender_name": "QA Tester",
        "context": [{"text": test_message, "role": "user"}],
        "timestamp": "2026-06-08T00:00:00Z",
        "message_type": "text"
    }

    # Hit endpoint API
    response = client.post("/analyze", json=payload)
    
    # Pastikan status 200 OK
    assert response.status_code == 200, f"Expected 200, got {response.status_code}. Response: {response.text}"
    
    data = response.json()
    assert data["sentiment"] == "positive"
    assert data["intent"] == "inquiry"
    
    # Verifikasi bahwa data BENAR-BENAR TERSIMPAN di SQLite in-memory
    db = TestingSessionLocal()
    try:
        saved_msg = db.query(Message).filter(Message.sender_id == test_sender_id).first()
        assert saved_msg is not None, "Data tidak ditemukan di database!"
        assert saved_msg.message == test_message
        assert saved_msg.sentiment == "positive"
        assert saved_msg.intent == "inquiry"
        assert saved_msg.user_id == 9999
    finally:
        db.close()
