import pytest
import pytest_asyncio
from httpx import AsyncClient, ASGITransport
from sqlalchemy.ext.asyncio import create_async_engine, AsyncSession
from sqlalchemy.orm import sessionmaker
import uuid

from main import app, verify_api_key, get_db
from database import Base
from models import Message
from sqlalchemy import text, select

# Setup In-Memory SQLite untuk testing
SQLALCHEMY_DATABASE_URL = "sqlite+aiosqlite:///:memory:"
engine = create_async_engine(SQLALCHEMY_DATABASE_URL, connect_args={"check_same_thread": False})
TestingSessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine, class_=AsyncSession)

async def override_get_db():
    async with TestingSessionLocal() as db:
        yield db

def override_verify_api_key():
    return {"user_id": 9999, "token": "test_token_123"}

@pytest_asyncio.fixture(autouse=True)
async def setup_db_for_test():
    async with engine.begin() as conn:
        await conn.run_sync(Base.metadata.create_all)
        await conn.execute(text('''
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
        await conn.execute(text("INSERT OR IGNORE INTO users (id, name, email, password, company_name, subscription_status, api_token) VALUES (9999, 'Test User', 'test@test.com', 'pwd', 'Test Corp', 'active', 'test_token_123')"))
    
    app.dependency_overrides[get_db] = override_get_db
    app.dependency_overrides[verify_api_key] = override_verify_api_key
    yield
    app.dependency_overrides.clear()
    async with engine.begin() as conn:
        await conn.run_sync(Base.metadata.drop_all)

@pytest_asyncio.fixture
async def client():
    async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as ac:
        yield ac

@pytest.mark.asyncio
async def test_analyze_invalid_payload(client: AsyncClient):
    response = await client.post("/analyze", json={})
    assert response.status_code == 422
    data = response.json()
    assert "detail" in data
    assert isinstance(data["detail"], list)

@pytest.mark.asyncio
async def test_analyze_and_store(client: AsyncClient, mocker):
    mock_predict = mocker.patch("main.predict_sentiment_and_intent")
    mock_predict.return_value = {
        "sentiment": "positive",
        "intent": "inquiry",
        "confidence": 0.99
    }

    test_sender_id = f"test_{uuid.uuid4().hex[:8]}"
    test_message = "Halo, saya mau nanya soal produk ini dong!"

    payload = {
        "sender_id": test_sender_id,
        "sender_name": "QA Tester",
        "context": [{"text": test_message, "role": "user"}],
        "timestamp": "2026-06-08T00:00:00Z",
        "message_type": "text"
    }

    response = await client.post("/analyze", json=payload)
    
    assert response.status_code == 200
    
    data = response.json()
    assert data["sentiment"] == "positive"
    assert data["intent"] == "inquiry"
    
    async with TestingSessionLocal() as db:
        result = await db.execute(select(Message).filter(Message.sender_id == test_sender_id))
        saved_msg = result.scalars().first()
        assert saved_msg is not None
        assert saved_msg.message == test_message
        assert saved_msg.sentiment == "positive"
        assert saved_msg.intent == "inquiry"
        assert saved_msg.user_id == 9999
