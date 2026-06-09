import httpx
import asyncio
import traceback

async def test():
    async with httpx.AsyncClient() as c:
        try:
            r = await c.get('http://127.0.0.1:8000/health-score/test_sender', headers={'Authorization': 'Bearer test_token_123'})
            print(f"Status: {r.status_code}")
            print(f"Body: {r.text}")
        except Exception as e:
            traceback.print_exc()

asyncio.run(test())
