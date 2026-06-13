"""
Skrip satu kali untuk membuat akun admin pertama di database.
Jalankan: python seed_admin.py
"""
import hashlib
from database import SessionLocal, engine, Base
from models import Admin

def hash_password(password: str) -> str:
    return hashlib.sha256(password.encode()).hexdigest()

def seed_admin():
    # Buat semua tabel jika belum ada (termasuk tabel 'admins' yang baru)
    Base.metadata.create_all(bind=engine)

    db = SessionLocal()
    try:
        # Cek apakah admin sudah ada
        existing = db.query(Admin).filter(Admin.email == "admin@emotext.com").first()
        if existing:
            print("[INFO] Admin 'admin@emotext.com' sudah ada di database. Tidak ada yang diubah.")
            return

        # Buat admin baru
        new_admin = Admin(
            email="admin@emotext.com",
            hashed_password=hash_password("GantiPassword123!")
        )
        db.add(new_admin)
        db.commit()
        print("[OK] Admin berhasil dibuat!")
        print("     Email   : admin@emotext.com")
        print("     Password: GantiPassword123!")
        print("")
        print("[PENTING] Segera ubah password ini setelah login pertama!")

    except Exception as e:
        db.rollback()
        print(f"[ERROR] Gagal membuat admin: {e}")
    finally:
        db.close()

if __name__ == "__main__":
    seed_admin()

