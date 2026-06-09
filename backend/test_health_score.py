import pytest
import pytest_asyncio
from httpx import AsyncClient, ASGITransport
from sqlalchemy.ext.asyncio import create_async_engine, AsyncSession
from sqlalchemy.orm import sessionmaker
import sys
import os

sys.path.append(os.path.dirname(os.path.abspath(__file__)))

from main import app, get_db
from database import Base
from models import Message
from sqlalchemy import text

# Gunakan SQLite in-memory untuk testing
SQLALCHEMY_DATABASE_URL = "sqlite+aiosqlite:///:memory:"
engine = create_async_engine(SQLALCHEMY_DATABASE_URL, connect_args={"check_same_thread": False})
TestingSessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine, class_=AsyncSession)

async def override_get_db():
    async with TestingSessionLocal() as db:
        yield db

def override_verify_api_key():
    return {"user_id": 1, "token": "EMOTEXT_TEST"}

@pytest_asyncio.fixture(autouse=True)
async def setup_db():
    async with engine.begin() as conn:
        await conn.run_sync(Base.metadata.create_all)
        await conn.execute(text('''
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY,
                name TEXT,
                email TEXT,
                password TEXT,
                api_token TEXT,
                subscription_status TEXT
            )
        '''))
        await conn.execute(text('''
            INSERT OR IGNORE INTO users (id, name, email, password, api_token, subscription_status)
            VALUES (1, 'Test User', 'test@example.com', 'hashed', 'EMOTEXT_TEST', 'active')
        '''))
    
    async with TestingSessionLocal() as db:
        messages = [
            Message(user_id=1, sender_id="+62811", message="Bagus", sentiment="positive", intent="other", confidence=0.9),
            Message(user_id=1, sender_id="+62811", message="Oke", sentiment="positive", intent="other", confidence=0.9),
            Message(user_id=1, sender_id="+62811", message="Keren", sentiment="positive", intent="other", confidence=0.9),
            Message(user_id=1, sender_id="+62811", message="Jelek", sentiment="negative", intent="complaint", confidence=0.9),
            Message(user_id=1, sender_id="+62811", message="Rusak", sentiment="negative", intent="complaint", confidence=0.9),
            Message(user_id=1, sender_id="+62811", message="Ya", sentiment="neutral", intent="other", confidence=0.9),
        ]
        db.add_all(messages)
        await db.commit()
    
    app.dependency_overrides[get_db] = override_get_db
    from main import verify_api_key
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
async def test_health_score_calculation(client: AsyncClient):
    headers = {"Authorization": "Bearer EMOTEXT_TEST"}
    response = await client.get("/health-score/+62811", headers=headers)
    
    assert response.status_code == 200
    data = response.json()
    
    assert data["interactions_count"] == 6
    assert data["health_score"] == 71
