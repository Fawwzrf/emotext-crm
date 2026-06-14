import os
import torch
import torch.nn as nn
import torch.nn.functional as F
import numpy as np
from transformers import AutoTokenizer, AutoModel
import onnxruntime as ort

from database import SessionLocal
from models import ManualCorrection
from sqlalchemy.future import select

# 1. Path Konfigurasi
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
TOKENIZER_PATH = os.path.join(BASE_DIR, "../models/indobert_onnx/tokenizer")
MODEL_PATH = os.path.join(BASE_DIR, "../models/indobert_base")
PT_FILE_PATH = os.path.join(BASE_DIR, "../models/indobert_base/multitask_indobert.pt")
ONNX_FILE_PATH = os.path.join(BASE_DIR, "../models/indobert_onnx/multitask_indobert.onnx")

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
print("Sedang memuat tokenizer dan model (Mohon tunggu)...")
use_ai = False
use_onnx = False

try:
    tokenizer = AutoTokenizer.from_pretrained(TOKENIZER_PATH)
    
    # Prioritaskan ONNX Runtime jika file ada
    if os.path.exists(ONNX_FILE_PATH):
        print(f"Memuat model ONNX dari {ONNX_FILE_PATH}...")
        ort_session = ort.InferenceSession(ONNX_FILE_PATH)
        use_onnx = True
        use_ai = True
        print("Sistem AI (ONNX) Siap Digunakan!")
    else:
        # Fallback ke PyTorch
        model = MultiTaskIndoBERT(MODEL_PATH)
        if os.path.exists(PT_FILE_PATH):
            model.load_state_dict(torch.load(PT_FILE_PATH, map_location=torch.device('cpu')))
            print(f"Bobot model kustom ({PT_FILE_PATH}) berhasil dimuat!")
        else:
            print("File multitask_indobert.pt tidak ditemukan.")
            
        model.eval()
        use_ai = True
        print("Sistem AI (PyTorch) Siap Digunakan!")
except Exception as e:
    print(f"Peringatan: Gagal memuat AI ({e}).")

# --- FUNGSI MEMORY LAYER (KOREKSI MANUAL) ---
async def get_correction_from_db(text: str, db=None):
    """Mengecek apakah pesan ini pernah dikoreksi admin di database"""
    if db is None:
        return None
    try:
        result = await db.execute(select(ManualCorrection).filter(ManualCorrection.message_text == text))
        correction = result.scalars().first()
        if correction:
            return {
                "sentiment": correction.corrected_sentiment or correction.original_sentiment,
                "intent": correction.corrected_intent or correction.original_intent,
                "confidence": 1.0 # Admin selalu benar
            }
    except Exception as e:
        print(f"Error mengakses DB untuk koreksi: {e}")
    return None

def softmax(x):
    e_x = np.exp(x - np.max(x, axis=1, keepdims=True))
    return e_x / np.sum(e_x, axis=1, keepdims=True)

# 4. Fungsi Prediksi Utama
async def predict_sentiment_and_intent(text: str, db=None):
    # A. CEK MEMORI: Apakah ada koreksi admin?
    memory_result = await get_correction_from_db(text, db)
    if memory_result:
        print(f"[MEMORY LAYER] Menggunakan data koreksi admin untuk: '{text}'")
        return memory_result

    # B. JIKA TIDAK ADA DI MEMORI, GUNAKAN AI
    try:
        if use_ai:
            inputs = tokenizer(text, return_tensors="pt", truncation=True, padding=True, max_length=128)
            
            if use_onnx:
                # Inferensi via ONNX
                ort_inputs = {
                    "input_ids": inputs["input_ids"].numpy(),
                    "attention_mask": inputs["attention_mask"].numpy()
                }
                ort_outs = ort_session.run(None, ort_inputs)
                sentiment_logits, intent_logits = ort_outs[0], ort_outs[1]
                
                sentiment_probs = softmax(sentiment_logits)
                intent_probs = softmax(intent_logits)
                
                pred_sentiment_idx = np.argmax(sentiment_probs, axis=1)[0]
                pred_intent_idx = np.argmax(intent_probs, axis=1)[0]
                
                sentiment_conf = np.max(sentiment_probs, axis=1)[0]
                intent_conf = np.max(intent_probs, axis=1)[0]
            else:
                # Inferensi via PyTorch
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
                
            real_confidence = round(float((sentiment_conf + intent_conf) / 2), 2)
            
            return {
                "sentiment": SENTIMENT_LABELS[pred_sentiment_idx],
                "intent": INTENT_LABELS[pred_intent_idx],
                "confidence": real_confidence
            }
            
    except Exception as e:
        print(f"Error saat prediksi AI: {e}")
        
    return {"sentiment": "neutral", "intent": "other", "confidence": 0.5}