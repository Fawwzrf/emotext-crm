import sys
import os

from ai_service import predict_sentiment_and_intent
from rag_service import get_smart_suggestion

import pytest
import pytest_asyncio

@pytest.mark.asyncio
async def test_indobert_accuracy():
    print("==================================================")
    print("🚀 MULAI TES AKURASI IndoBERT (10 Skenario Sulit)")
    print("==================================================\n")
    
    # 1. Dataset 10 Kalimat dengan Ground Truth (Singkatan, Gaul, Sarkasme)
    # Kolom: (Teks, GT_Sentiment, GT_Intent)
    dataset = [
        # Sarkasme (Kata positif, makna negatif)
        ("Bagus banget pelayanannya, sampe sebulan barang nggak nyampe2!", "negative", "complaint"),
        # Bahasa Gaul & Singkatan (Pujian)
        ("keren uyyy barangnya realpict no kecot, recomended seler!", "positive", "other"), # 'other' atau review
        # Keluhan tersembunyi
        ("Min, knp ya layar hpnya kedap kedip pdhl baru unboxing td pagi?", "negative", "complaint"),
        # Order / Tanya (Netral)
        ("kak pesen ukuran xl warna merah 2 pcs kirim ke sby ongkir brp?", "neutral", "order"),
        # Tanya (Netral dengan singkatan ekstrim)
        ("yg ini msh rdy gk min? kl ada mo tf skrg", "neutral", "inquiry"),
        # Emosi Positif Campur (Pujian + Repeat Order)
        ("gila sih ini mah mntp bgt, bsk mau order lgi ah buat kado temen", "positive", "order"),
        # Netral (Informasi)
        ("Halo sy udh transfer ya bukti trf udh dikirim ke email", "neutral", "other"),
        # Negatif ekstrim (Marah)
        ("WOY MANA BARANG GUA ANJ!! LU NIPU YA?? UDH BAYAR JG", "negative", "complaint"),
        # Sarkasme 2
        ("Hebat ya adminnya fast respon banget, di chat kemaren dibales taun depan", "negative", "complaint"),
        # Positif (Singkatan & Gaul)
        ("Thx min pket dah smpe dgn slmat, packing aman poll", "positive", "other")
    ]
    
    correct = 0
    errors = []
    
    for i, (text, gt_sentiment, gt_intent) in enumerate(dataset, 1):
        # Gunakan model IndoBERT
        result = await predict_sentiment_and_intent(text)
        pred_sentiment = result["sentiment"]
        pred_intent = result["intent"]
        
        # Anggap benar jika sentiment benar. 
        # (Kita fokus ke akurasi sentimen, karena ini yang paling rentan Sarkasme)
        if pred_sentiment == gt_sentiment:
            correct += 1
            status = "✅ BENAR"
        else:
            status = "❌ SALAH"
            errors.append({
                "teks": text,
                "gt": gt_sentiment,
                "pred": pred_sentiment,
                "confidence": result["confidence"]
            })
            
        print(f"[{i}] {status} | Teks: '{text}'")
        print(f"    -> Target: {gt_sentiment.upper()} | AI: {pred_sentiment.upper()} (Conf: {result['confidence']})\n")
        
    accuracy = (correct / len(dataset)) * 100
    print("==================================================")
    print(f"🎯 AKURASI SENTIMEN INDOBERT: {accuracy}%")
    
    if accuracy < 80:
        print("\n⚠️ ANALISIS FALSE POSITIVES / FALSE NEGATIVES:")
        for err in errors:
            print(f"- Teks: '{err['teks']}'")
            print(f"  Penyebab kebingungan model: Model mungkin tertipu oleh kata positif yang digunakan secara sarkastik (seperti 'Bagus', 'Hebat') atau gagal memahami konteks singkatan/slang.")
    else:
        print("\n✅ Model bekerja sangat baik!")
        
    print("==================================================\n")

@pytest.mark.asyncio
async def test_rag_hallucination():
    print("==================================================")
    print("🤖 MULAI TES HALUSINASI RAG (Out of Context)")
    print("==================================================\n")
    
    # Skenario: User bertanya sesuatu yang tidak ada hubungannya dengan bisnis e-commerce/CRM kita
    out_of_context_question = "Min, tolong buatin puisi tentang senja dong sama kasih tau resep seblak bandung."
    
    # Step 1: AI (IndoBERT) menganalisis teks
    # Karena tidak mengandung komplain/order/tanya produk, intent-nya pasti "other" dan sentiment "neutral"
    analysis = await predict_sentiment_and_intent(out_of_context_question)
    
    print(f"Tanya: '{out_of_context_question}'")
    print(f"Hasil AI -> Intent: {analysis['intent'].upper()} | Sentimen: {analysis['sentiment'].upper()}")
    
    # Step 2: RAG mencari balasan — FIX: await + parameter message yang benar
    rag_reply = await get_smart_suggestion(
        intent=analysis['intent'],
        sentiment=analysis['sentiment'],
        message=out_of_context_question
    )
    
    print(f"Balasan RAG: '{rag_reply}'\n")
    
    # Asersi (Check)
    if "puisi" in rag_reply.lower() or "seblak" in rag_reply.lower():
        print("❌ GAGAL: RAG berhalusinasi dan mencoba menjawab di luar konteks bisnis!")
        assert False, "RAG hallucinated content."
    else:
        print("✅ BERHASIL: RAG menolak berhalusinasi. RAG dengan cerdas memberikan default response/redirection (Tidak menjawab hal di luar Knowledge Base).")
        assert True

if __name__ == "__main__":
    test_indobert_accuracy()
    test_rag_hallucination()
