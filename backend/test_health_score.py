import pytest
from fastapi.testclient import TestClient
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker
import sys
import os

sys.path.append(os.path.dirname(os.path.abspath(__file__)))

from main import app, get_db
from database import SessionLocal, engine, Base
from models import Message
from sqlalchemy import text
from sqlalchemy.pool import StaticPool

# Gunakan SQLite in-memory untuk testing
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
    return {"user_id": 1, "token": "EMOTEXT_TEST"}

# Dependency overrides akan di-apply di dalam fixture
client = TestClient(app)

@pytest.fixture(scope="module", autouse=True)
def setup_db():
    Base.metadata.create_all(bind=engine)
    db = TestingSessionLocal()
    
    # Create users table manually for testing (karena User model tidak ada di FastAPI)
    db.execute(text('''
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY,
            name TEXT,
            email TEXT,
            password TEXT,
            api_token TEXT,
            subscription_status TEXT
        )
    '''))
    
    # Masukkan dummy user
    db.execute(text('''
        INSERT OR IGNORE INTO users (id, name, email, password, api_token, subscription_status)
        VALUES (1, 'Test User', 'test@example.com', 'hashed', 'EMOTEXT_TEST', 'active')
    '''))
    db.commit()
    
    # Masukkan data pesan ke DB untuk menguji Health Score Engine
    # Skenario: 3 Pesan Positif (3x100), 2 Pesan Negatif (2x30), 1 Pesan Netral (1x70)
    # Total Score = 300 + 60 + 70 = 430
    # Total Pesan = 6
    # Cumulative Score = 430 / 6 = 71 (Good)
    
    messages = [
        Message(user_id=1, sender_id="+62811", message="Bagus", sentiment="positive", intent="other", confidence=0.9),
        Message(user_id=1, sender_id="+62811", message="Oke", sentiment="positive", intent="other", confidence=0.9),
        Message(user_id=1, sender_id="+62811", message="Keren", sentiment="positive", intent="other", confidence=0.9),
        Message(user_id=1, sender_id="+62811", message="Jelek", sentiment="negative", intent="complaint", confidence=0.9),
        Message(user_id=1, sender_id="+62811", message="Rusak", sentiment="negative", intent="complaint", confidence=0.9),
        Message(user_id=1, sender_id="+62811", message="Ya", sentiment="neutral", intent="other", confidence=0.9),
    ]
    
    db.add_all(messages)
    db.commit()
    
    app.dependency_overrides[get_db] = override_get_db
    from main import verify_api_key
    app.dependency_overrides[verify_api_key] = override_verify_api_key
    
    yield
    app.dependency_overrides.clear()
    Base.metadata.drop_all(bind=engine)

def test_health_score_calculation():
    headers = {"Authorization": "Bearer EMOTEXT_TEST"}
    response = client.get("/health-score/+62811", headers=headers)
    
    assert response.status_code == 200
    data = response.json()
    
    # Memeriksa kebenaran operasi matematiknya
    assert data["interactions_count"] == 6
    assert data["health_score"] == 71  # 430 / 6 = 71.6, cast to int -> 71
