import httpx
import asyncio

async def test():
    url = "http://127.0.0.1:8000/analyze"
    headers = {"Authorization": "Bearer test_token_123"}
    payload = {
        "sender_id": "test",
        "sender_name": "test",
        "context": [{"text": "hello", "role": "user"}],
        "timestamp": "2023-01-01T00:00:00Z",
        "message_type": "text"
    }
    
    async with httpx.AsyncClient() as client:
        tasks = []
        for i in range(15):
            p = dict(payload)
            p["context"] = [{"text": f"hello {i}", "role": "user"}]
            tasks.append(client.post(url, json=p, headers=headers))
        
        results = await asyncio.gather(*tasks)
        for i, r in enumerate(results):
            print(f"Req {i}: {r.status_code}")

asyncio.run(test())
