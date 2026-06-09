import asyncio
import httpx
import time
import pytest

API_URL = "http://127.0.0.1:8000/analyze"
TOKEN = "test_token_123"
HEADERS = {"Authorization": f"Bearer {TOKEN}", "Content-Type": "application/json"}

@pytest.mark.anyio
async def test_oom_long_text():
    print("\n--- 1. UJI OOM (OUT OF MEMORY) DENGAN KALIMAT PANJANG ---")
    # Generate a massive 10,000 word text without any rule-based keywords like "kecewa"
    long_text = "Sebenarnya saya sangat bingung dengan warna produk ini. " * 500
    
    payload = {
        "sender_id": "test_oom_user",
        "sender_name": "Test OOM",
        "context": [{"text": long_text, "role": "user"}],
        "timestamp": "2026-06-08T00:00:00Z",
        "message_type": "text"
    }

    start_time = time.time()
    async with httpx.AsyncClient() as client:
        try:
            response = await client.post(API_URL, json=payload, headers=HEADERS, timeout=30.0)
            print(f"Status OOM Test: {response.status_code}")
            print(f"Response: {response.json()}")
        except Exception as e:
            print(f"Error pada OOM Test: {e}")
    print(f"Waktu eksekusi kalimat sangat panjang: {time.time() - start_time:.2f} detik\n")

async def send_message(idx, client):
    payload = {
        "sender_id": "test_race_user",
        "sender_name": "Test Race Condition",
        "context": [{"text": f"Pesan serentak {idx} - barang tidak sampai", "role": "user"}],
        "timestamp": "2026-06-08T00:00:00Z",
        "message_type": "text"
    }
    try:
        res = await client.post(API_URL, json=payload, headers=HEADERS)
        return f"Req {idx}: Status {res.status_code}"
    except Exception as e:
        return f"Req {idx}: Error {e}"

@pytest.mark.anyio
async def test_race_condition():
    print("--- 2. UJI RACE CONDITION (5 REQUEST BERSAMAAN) ---")
    async with httpx.AsyncClient() as client:
        tasks = [send_message(i, client) for i in range(1, 6)]
        results = await asyncio.gather(*tasks)
        for res in results:
            print(res)
