import asyncio
from sqlalchemy.ext.asyncio import create_async_engine, AsyncSession
from sqlalchemy.orm import sessionmaker
from sqlalchemy import text

url = "postgresql+asyncpg://postgres:kitaemotextkeren@db.ncakukjqcwktqtphyktx.supabase.co:5432/postgres"

async def test():
    engine = create_async_engine(url, future=True, echo=False)
    async with engine.connect() as conn:
        res = await conn.execute(text("SELECT 1"))
        print(res.fetchone())

asyncio.run(test())
