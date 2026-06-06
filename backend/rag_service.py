import json
import os
import random

# 1. Tentukan Path ke file kb.json
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
KB_PATH = os.path.join(BASE_DIR, "kb.json")

# 2. Load Knowledge Base
try:
    with open(KB_PATH, "r", encoding="utf-8") as f:
        KB = json.load(f)
except FileNotFoundError:
    KB = []
    print("⚠️ Peringatan: File kb.json tidak ditemukan. Fitur RAG dinamis tidak akan maksimal.")

# 3. Fungsi Kecocokan Sentimen (Dari rag.ipynb)
def sentiment_alignment(user_sentiment, response_text):
    response_text = response_text.lower()
    if user_sentiment == "negative":
        keywords = ["maaf", "kendala", "cek", "menyesal", "tolong"]
    elif user_sentiment == "positive":
        keywords = ["terima kasih", "senang", "😊", "baik", "mantap"]
    else:
        keywords = ["informasi", "bantu", "jelas", "silakan"]

    return 1.0 if any(k in response_text for k in keywords) else 0.4

# 4. Fungsi Perhitungan Skor (Dari rag.ipynb)
def score_response(intent, sentiment, kb_intent, response):
    intent_score = 1.0 if intent == kb_intent else 0.0
    sentiment_score = sentiment_alignment(sentiment, response)
    quality_score = min(len(response) / 100, 1.0)
    
    final_score = (0.5 * intent_score) + (0.3 * sentiment_score) + (0.2 * quality_score)
    return final_score

# 5. Fungsi Utama untuk dipanggil di main.py
def get_smart_suggestion(intent: str, sentiment: str) -> str:
    if not KB:
        return "Baik Kak, ada yang bisa kami bantu?"

    candidates = []
    for item in KB:
        # Hanya ambil dari kategori intent yang cocok
        if item["intent"] == intent:
            for resp in item["responses"]:
                score = score_response(intent, sentiment, item["intent"], resp)
                candidates.append({"text": resp, "score": score})

    if not candidates:
        return "Baik Kak, ada yang bisa kami bantu?"

    # Urutkan berdasarkan skor tertinggi (Rank Responses)
    ranked = sorted(candidates, key=lambda x: x["score"], reverse=True)
    
    # Ambil 3 terbaik, lalu pilih secara acak salah satu
    # Hal ini membuat CS tidak terlihat seperti robot yang menjawab itu-itu saja
    top_3 = ranked[:3]
    selected_response = random.choice(top_3)
    
    return selected_response["text"]