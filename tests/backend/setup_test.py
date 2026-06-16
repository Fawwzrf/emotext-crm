import asyncio
import os
import sys
from dotenv import load_dotenv

sys.path.append(os.path.join(os.path.dirname(__file__), "..", "backend"))
from database import SessionLocal
from sqlalchemy import text

async def setup_test_data():
    async with SessionLocal() as db:
        # Create dummy users via raw SQL
        await db.execute(text("INSERT INTO users (id, name, email, password, api_token, subscription_status) VALUES (991, 'Toko Budi', 'budi@test.com', 'dummy', 'token_budi', 'active') ON CONFLICT DO NOTHING"))
        await db.execute(text("INSERT INTO users (id, name, email, password, api_token, subscription_status) VALUES (992, 'Toko Andi', 'andi@test.com', 'dummy', 'token_andi', 'active') ON CONFLICT DO NOTHING"))
        
        # Delete old KB
        await db.execute(text("DELETE FROM knowledge_bases WHERE user_id IN (991, 992)"))
        
        # Insert new KB using raw SQL to avoid JSONB casting errors
        await db.execute(text("INSERT INTO knowledge_bases (user_id, content, metadata, created_at, updated_at) VALUES (991, 'SOP Retur Toko Budi: Jika barang rusak, pelanggan wajib menyertakan video unboxing utuh. Biaya retur ditanggung oleh pembeli sepenuhnya.', '{}'::jsonb, now(), now())"))
        await db.execute(text("INSERT INTO knowledge_bases (user_id, content, metadata, created_at, updated_at) VALUES (992, 'SOP Retur Toko Andi: Jika barang rusak, kami akan mengganti 100% tanpa perlu video unboxing, gratis ongkos kirim retur dari kami.', '{}'::jsonb, now(), now())"))
        
        await db.commit()
        print("Data testing berhasil disiapkan di database!")

if __name__ == "__main__":
    asyncio.run(setup_test_data())
