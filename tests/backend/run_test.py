import requests
import time

BASE_URL = "http://127.0.0.1:8000"
INTERNAL_API_KEY = "emotext_secret_internal_key_2026"

def test_rag():
    print("1. Syncing KB...")
    res = requests.post(f"{BASE_URL}/sync-kb", headers={"x-internal-api-key": INTERNAL_API_KEY})
    print("Sync KB response:", res.json())
    
    # Wait for background sync to finish
    time.sleep(5)
    
    # Payload for User 991 (Toko Budi)
    payload_991 = {
        "sender_id": "08123456789",
        "sender_name": "Pelanggan 1",
        "context": [{"text": "Barang saya rusak, saya mau retur. Gimana aturannya?", "role": "user"}],
        "timestamp": "123456789",
        "message_type": "text"
    }
    
    # Payload for User 992 (Toko Andi)
    payload_992 = {
        "sender_id": "08987654321",
        "sender_name": "Pelanggan 2",
        "context": [{"text": "Barang saya rusak, saya mau retur. Gimana aturannya?", "role": "user"}],
        "timestamp": "123456789",
        "message_type": "text"
    }
    
    print("\n2. Mengirim pesan ke Toko Budi (User 991)...")
    res1 = requests.post(f"{BASE_URL}/analyze", json=payload_991, headers={"Authorization": "Bearer token_budi"})
    data1 = res1.json()
    print("Response Toko Budi:", data1)
    
    print("\n3. Mengirim pesan ke Toko Andi (User 992)...")
    res2 = requests.post(f"{BASE_URL}/analyze", json=payload_992, headers={"Authorization": "Bearer token_andi"})
    data2 = res2.json()
    print("Response Toko Andi:", data2)
    
    print("\n(Menunggu RAG Llama-CPP menghasilkan balasan...)")
    time.sleep(5) 
    
    print("\n4. Mengambil balasan Toko Budi (User 991)...")
    msg_id_1 = data1["message_id"]
    r1 = requests.get(f"{BASE_URL}/suggestion/stream/{msg_id_1}", headers={"Authorization": "Bearer token_budi"})
    print("Suggestion Toko Budi:")
    print(r1.text)
    
    print("\n5. Mengambil balasan Toko Andi (User 992)...")
    msg_id_2 = data2["message_id"]
    r2 = requests.get(f"{BASE_URL}/suggestion/stream/{msg_id_2}", headers={"Authorization": "Bearer token_andi"})
    print("Suggestion Toko Andi:")
    print(r2.text)

if __name__ == "__main__":
    test_rag()
