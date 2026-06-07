from sqlalchemy import create_engine
from sqlalchemy.orm import declarative_base, sessionmaker
import os
from dotenv import load_dotenv

# BUG-02 FIX: Load kredensial dari file .env, bukan hardcoded di source code.
# Pastikan file .env sudah dibuat dan TIDAK dicommit ke git.
load_dotenv()

SQLALCHEMY_DATABASE_URL = os.getenv("DATABASE_URL")

if not SQLALCHEMY_DATABASE_URL:
    raise RuntimeError(
        "DATABASE_URL tidak ditemukan! "
        "Buat file .env di folder backend/ dan isi: DATABASE_URL=postgresql://..."
    )

engine = create_engine(SQLALCHEMY_DATABASE_URL)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)
Base = declarative_base()