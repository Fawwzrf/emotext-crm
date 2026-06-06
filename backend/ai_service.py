import os
import torch
import torch.nn as nn
import torch.nn.functional as F
from transformers import AutoTokenizer, AutoModel

from database import SessionLocal
from models import ManualCorrection

# 1. Path Konfigurasi
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
TOKENIZER_PATH = os.path.join(BASE_DIR, "../ml-engine/tokenizer_indobert")
MODEL_PATH = os.path.join(BASE_DIR, "../ml-engine/indobert_model")
PT_FILE_PATH = os.path.join(BASE_DIR, "../ml-engine/multitask_indobert.pt")

SENTIMENT_LABELS = ["positive", "neutral", "negative"] 
INTENT_LABELS = ["inquiry", "order", "complaint", "other"]

# 2. Definisikan Arsitektur Multi-Task Standar
class MultiTaskIndoBERT(nn.Module):
    def __init__(self, model_path):
        super(MultiTaskIndoBERT, self).__init__()
        self.bert = AutoModel.from_pretrained(model_path)
        self.dropout = nn.Dropout(0.3)
        self.sentiment_classifier = nn.Linear(self.bert.config.hidden_size, len(SENTIMENT_LABELS))
        self.intent_classifier = nn.Linear(self.bert.config.hidden_size, len(INTENT_LABELS))

    def forward(self, input_ids, attention_mask):
        outputs = self.bert(input_ids=input_ids, attention_mask=attention_mask)
        pooled_output = outputs.pooler_output
        pooled_output = self.dropout(pooled_output)
        
        sentiment_logits = self.sentiment_classifier(pooled_output)
        intent_logits = self.intent_classifier(pooled_output)
        return sentiment_logits, intent_logits

# 3. Inisialisasi Model
print("⏳ Sedang memuat tokenizer dan model IndoBERT (Mohon tunggu)...")
use_ai = False

try:
    tokenizer = AutoTokenizer.from_pretrained(TOKENIZER_PATH)
    model = MultiTaskIndoBERT(MODEL_PATH)
    
    if os.path.exists(PT_FILE_PATH):
        model.load_state_dict(torch.load(PT_FILE_PATH, map_location=torch.device('cpu')))
        print(f"✅ Bobot model kustom ({PT_FILE_PATH}) berhasil dimuat!")
    else:
        print("⚠️ File multitask_indobert.pt tidak ditemukan.")
        
    model.eval()
    use_ai = True
    print("✅ Sistem AI Siap Digunakan!")
except Exception as e:
    print(f"⚠️ Peringatan: Gagal memuat AI ({e}).")

# --- FUNGSI MEMORY LAYER (KOREKSI MANUAL) ---
def get_correction_from_db(text: str):
    """Mengecek apakah pesan ini pernah dikoreksi admin di database"""
    db = SessionLocal()
    try:
        # Mencari koreksi berdasarkan teks pesan
        correction = db.query(ManualCorrection).filter(ManualCorrection.message_text == text).first()
        if correction:
            return {
                "sentiment": correction.corrected_sentiment or correction.original_sentiment,
                "intent": correction.corrected_intent or correction.original_intent,
                "confidence": 1.0 # Admin selalu benar
            }
    except Exception as e:
        print(f"⚠️ Error mengakses DB untuk koreksi: {e}")
    finally:
        db.close()
    return None

# 4. Fungsi Prediksi Utama
def predict_sentiment_and_intent(text: str):
    # A. CEK MEMORI: Apakah ada koreksi admin?
    memory_result = get_correction_from_db(text)
    if memory_result:
        print(f"✅ [MEMORY LAYER] Menggunakan data koreksi admin untuk: '{text}'")
        return memory_result

    # B. JIKA TIDAK ADA DI MEMORI, GUNAKAN AI
    try:
        if use_ai:
            inputs = tokenizer(text, return_tensors="pt", truncation=True, padding=True, max_length=128)
            
            with torch.no_grad():
                sentiment_logits, intent_logits = model(
                    input_ids=inputs['input_ids'], 
                    attention_mask=inputs['attention_mask']
                )
                
            sentiment_probs = F.softmax(sentiment_logits, dim=1)
            intent_probs = F.softmax(intent_logits, dim=1)
            
            pred_sentiment_idx = torch.argmax(sentiment_probs, dim=1).item()
            pred_intent_idx = torch.argmax(intent_probs, dim=1).item()
            
            sentiment_conf = torch.max(sentiment_probs, dim=1).values.item()
            intent_conf = torch.max(intent_probs, dim=1).values.item()
            real_confidence = round((sentiment_conf + intent_conf) / 2, 2)
            
            return {
                "sentiment": SENTIMENT_LABELS[pred_sentiment_idx],
                "intent": INTENT_LABELS[pred_intent_idx],
                "confidence": real_confidence
            }
            
    except Exception as e:
        print(f"❌ Error saat prediksi AI: {e}")
        
    return {"sentiment": "neutral", "intent": "other", "confidence": 0.5}