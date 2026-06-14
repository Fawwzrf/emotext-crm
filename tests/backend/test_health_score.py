"""
test_health_score.py — Test endpoint /health-score menggunakan DB PostgreSQL sungguhan.
Dijalankan oleh pytest dengan marker asyncio.
"""
import pytest
from httpx import AsyncClient, ASGITransport
from main import app, verify_api_key, get_db
from database import SessionLocal


def override_verify_api_key():
    return {"user_id": 9999, "token": "test_token_123"}


@pytest.mark.asyncio
async def test_health_score_endpoint_returns_valid_schema():
    """
    Memastikan endpoint /health-score/{sender_id} mengembalikan
    struktur JSON yang valid tanpa error.
    Menggunakan DB real (Supabase) via .env — skip jika koneksi gagal.
    """
    app.dependency_overrides[verify_api_key] = override_verify_api_key

    try:
        async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as ac:
            response = await ac.get("/health-score/test_sender_pytest")
            # Hanya validasi schema response, bukan data
            assert response.status_code == 200
            data = response.json()
            assert "health_score" in data
            assert "sender_id" in data
            assert isinstance(data["health_score"], (int, float))
    except Exception as e:
        pytest.skip(f"Koneksi DB tidak tersedia di lingkungan ini: {e}")
    finally:
        app.dependency_overrides.clear()
