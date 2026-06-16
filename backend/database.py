from sqlalchemy.ext.asyncio import create_async_engine, AsyncSession
from sqlalchemy.orm import declarative_base, sessionmaker
import os
import ssl
from dotenv import load_dotenv

load_dotenv()

SQLALCHEMY_DATABASE_URL = os.getenv("DATABASE_URL")

if not SQLALCHEMY_DATABASE_URL:
    raise RuntimeError(
        "DATABASE_URL tidak ditemukan! "
        "Set environment variable DATABASE_URL di Hugging Face Space Settings > Repository secrets."
    )

# Konversi postgresql:// menjadi postgresql+asyncpg:// secara otomatis jika belum
if SQLALCHEMY_DATABASE_URL.startswith("postgresql://"):
    SQLALCHEMY_DATABASE_URL = SQLALCHEMY_DATABASE_URL.replace("postgresql://", "postgresql+asyncpg://", 1)

# Hapus parameter sslmode karena asyncpg tidak mendukungnya di format string
if "?sslmode=" in SQLALCHEMY_DATABASE_URL:
    SQLALCHEMY_DATABASE_URL = SQLALCHEMY_DATABASE_URL.split("?sslmode=")[0]
if "&sslmode=" in SQLALCHEMY_DATABASE_URL:
    SQLALCHEMY_DATABASE_URL = SQLALCHEMY_DATABASE_URL.split("&sslmode=")[0]

connect_args = {}
# Wajibkan SSL jika menggunakan Supabase dari cloud (asyncpg menggunakan ssl.SSLContext, bukan string)
if "supabase" in SQLALCHEMY_DATABASE_URL or os.getenv("SPACE_ID"):
    ssl_ctx = ssl.create_default_context()
    ssl_ctx.check_hostname = False
    ssl_ctx.verify_mode = ssl.CERT_NONE
    connect_args["ssl"] = ssl_ctx

engine = create_async_engine(
    SQLALCHEMY_DATABASE_URL,
    future=True,
    echo=False,
    connect_args=connect_args,
    pool_pre_ping=True,       # cek koneksi sebelum dipakai
    pool_recycle=300,         # recycle koneksi tiap 5 menit (Supabase pooler timeout)
)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine, class_=AsyncSession)
Base = declarative_base()
