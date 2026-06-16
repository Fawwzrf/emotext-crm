import os
import sys
import asyncio

sys.path.append(os.path.join(os.path.dirname(__file__), "..", "..", "backend"))
from rag_service import _load_local_rag_index, stream_smart_suggestion, _faiss_indices

async def main():
    print("Memuat RAG index dari database...")
    await _load_local_rag_index()
    
    intent = "pertanyaan"
    sentiment = "netral"
    message = "Kalau kasir saya mau pakai dompet digital kayak GoPay atau OVO, sistemnya udah support belum?"
    
    print("\n--- TEST USER 4 ---")
    print(f"Apakah Index 4 ada? {4 in _faiss_indices or '4' in _faiss_indices}")
    gen_4 = stream_smart_suggestion(intent, sentiment, message, user_id=4)
    res_4 = ""
    for chunk in gen_4:
        res_4 += chunk
    print("\n[Balasan User 4]:")
    print(res_4)

if __name__ == "__main__":
    asyncio.run(main())
