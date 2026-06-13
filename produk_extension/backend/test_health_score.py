import asyncio
from main import get_health_score
from database import SessionLocal

async def test_endpoint():
    async with SessionLocal() as db:
        auth = {"user_id": 1, "token": "test"}
        try:
            res = await get_health_score("test_sender", db, auth)
            print(res)
        except Exception as e:
            import traceback
            traceback.print_exc()

asyncio.run(test_endpoint())
