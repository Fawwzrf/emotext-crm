import sys
import os

sys.path.insert(0, os.path.abspath(r"d:\Emotext-CRM\produk_extension\backend"))

import asyncio
from ai_service import predict_sentiment_and_intent

# Kumpulan Kalimat Uji (Edge Cases dari BUGS_AND_FIXES.md)
test_sentences = [
    # 1. Sarkasme Ekstrem
    "Bagus banget pelayanannya, pesan pagi ini nyampenya tahun depan!",
    "Hebat lu min, nipu orang gampang banget.",
    
    # 2. Singkatan & Slang (Marah)
    "Woy kpn d krm dah brg w, jgn nipu lu y!",
    "bgst bgt kurirnya paket dilempar",
    "P",
    
    # 3. Gaptek / Typo (Tanya/Order)
    "Ass. Pagi min, iyh cara ordernyah gmn yah???",
    "kakk aku mau pesen 2 bji bs gak??",
    
    # 4. Bahasa Campuran / Pujian Aneh
    "gila kece parah sepatunya euy, mau CO lg ah bray",
    "makasih min, paket udah landing dgn selamat",
    "wah mantul bgt dah, the best seller"
]

print("[MENGUJI KETAJAMAN MODEL INDOBERT BARU (V2.0)]")
print("="*60)

async def run_tests():
    for text in test_sentences:
        result = await predict_sentiment_and_intent(text)
        intent = result['intent']
        sentiment = result['sentiment']
        
        # Format Warna
        intent_color = "\033[96m" if intent == "inquiry" else "\033[92m" if intent == "order" else "\033[91m" if intent == "complaint" else "\033[93m"
        sentiment_color = "\033[92m" if sentiment == "positive" else "\033[91m" if sentiment == "negative" else "\033[93m"
        reset_color = "\033[0m"
        
        print(f"[Pesan] '{text}'")
        print(f"[Prediksi] Intent=[{intent_color}{intent.upper()}{reset_color}], Sentiment=[{sentiment_color}{sentiment.upper()}{reset_color}]\n")

    print("="*60)
    print("Selesai.")

if __name__ == "__main__":
    asyncio.run(run_tests())
