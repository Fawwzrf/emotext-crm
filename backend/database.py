from sqlalchemy.ext.asyncio import create_async_engine, AsyncSession
from sqlalchemy.orm import declarative_base, sessionmaker
import os
from dotenv import load_dotenv

load_dotenv()

SQLALCHEMY_DATABASE_URL = os.getenv("DATABASE_URL")

if not SQLALCHEMY_DATABASE_URL:
    raise RuntimeError(
        "DATABASE_URL tidak ditemukan! "
        "Buat file .env di folder backend/ dan isi: DATABASE_URL=postgresql+asyncpg://..."
    )

# Konversi postgresql:// menjadi postgresql+asyncpg:// secara otomatis jika belum
if SQLALCHEMY_DATABASE_URL.startswith("postgresql://"):
    SQLALCHEMY_DATABASE_URL = SQLALCHEMY_DATABASE_URL.replace("postgresql://", "postgresql+asyncpg://", 1)

engine = create_async_engine(SQLALCHEMY_DATABASE_URL, future=True, echo=False)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine, class_=AsyncSession)
Base = declarative_base()