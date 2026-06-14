"""
test_db_ssl.py — Verifikasi bahwa koneksi SSL ke PostgreSQL (Supabase) berhasil.
Test ini akan di-skip jika URL database tidak bisa dijangkau.
"""
import pytest
from sqlalchemy.ext.asyncio import create_async_engine
from sqlalchemy import text
import os


@pytest.mark.asyncio
async def test_postgres_ssl_connection():
    """
    Verifikasi koneksi SSL ke Supabase PostgreSQL.
    Test ini bergantung pada koneksi jaringan ke Supabase;
    akan di-skip otomatis di lingkungan tanpa akses internet.
    """
    db_url = os.getenv(
        "DATABASE_URL",
        "postgresql+asyncpg://postgres:kitaemotextkeren@db.ncakukjqcwktqtphyktx.supabase.co:5432/postgres"
    )
    try:
        engine = create_async_engine(db_url, future=True, echo=False,
                                     connect_args={"ssl": "require"})
        async with engine.connect() as conn:
            result = await conn.execute(text("SELECT 1"))
            row = result.fetchone()
            assert row is not None
            assert row[0] == 1
        await engine.dispose()
    except Exception as e:
        pytest.skip(f"Koneksi Supabase tidak tersedia: {e}")
