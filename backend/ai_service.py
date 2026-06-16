"""
ai_service.py — ONNX Runtime inference untuk IndoBERT multi-task.
Melakukan klasifikasi Sentimen (Positif/Negatif/Netral) dan Intensi
(Inquiry/Order/Complaint/Other) menggunakan model ONNX yang sudah dicompile.
PyTorch tidak diperlukan di runtime — hanya ONNX Runtime.
"""
import os
import numpy as np
from transformers import AutoTokenizer
import onnxruntime as ort
from huggingface_hub import snapshot_download

from database import SessionLocal
from models import ManualCorrection
from sqlalchemy.future import select

# ─── Path Konfigurasi ────────────────────────────────────────────────────────
BASE_DIR       = os.path.dirname(os.path.abspath(__file__))

if os.environ.get("SPACE_ID"):
    MODEL_ROOT = os.path.join(BASE_DIR, "models")
    DOWNLOAD_ROOT = BASE_DIR
else:
    MODEL_ROOT = os.path.join(BASE_DIR, "..", "models")
    DOWNLOAD_ROOT = os.path.join(BASE_DIR, "..")

TOKENIZER_PATH = os.path.join(MODEL_ROOT, "indobert_onnx", "tokenizer")
ONNX_FILE_PATH = os.path.join(MODEL_ROOT, "indobert_onnx", "multitask_indobert.onnx")

SENTIMENT_LABELS = ["positive", "neutral", "negative"]
INTENT_LABELS    = ["inquiry", "order", "complaint", "other"]

# ─── Inisialisasi Model (ONNX Runtime only) ──────────────────────────────────
print("Sedang memuat tokenizer dan model ONNX IndoBERT (Mohon tunggu)...")
use_onnx = False
tokenizer = None
ort_session = None

try:
    # Download model if not exists (Bypass HF Space 1GB Limit)
    if not os.path.exists(ONNX_FILE_PATH):
        print("Model ONNX tidak ditemukan lokal. Mengunduh dari repositori fawwzrf/emotext-models...")
        os.makedirs(os.path.dirname(ONNX_FILE_PATH), exist_ok=True)
        snapshot_download(
            repo_id="fawwzrf/emotext-models",
            allow_patterns=["models/indobert_onnx/*"],
            local_dir=DOWNLOAD_ROOT
        )
        print("Unduhan model ONNX selesai!")

    tokenizer = AutoTokenizer.from_pretrained(TOKENIZER_PATH)
    print(f"Memuat model ONNX dari {ONNX_FILE_PATH}...")
    ort_session = ort.InferenceSession(ONNX_FILE_PATH)
    use_onnx = True
    print("[OK] Sistem AI (ONNX Runtime) Siap Digunakan!")
except Exception as e:
    print(f"[WARN] Peringatan: Gagal memuat model ONNX ({e}). Sistem akan fallback ke rule-based.")


# ─── Memory Layer: Koreksi Manual dari Admin ─────────────────────────────────
async def get_correction_from_db(text: str, db=None):
    """Mengecek apakah pesan ini pernah dikoreksi admin di database."""
    if db is None:
        return None
    try:
        result = await db.execute(
            select(ManualCorrection).filter(ManualCorrection.message_text == text)
        )
        correction = result.scalars().first()
        if correction:
            return {
                "sentiment": correction.corrected_sentiment or correction.original_sentiment,
                "intent":    correction.corrected_intent or correction.original_intent,
                "confidence": 1.0  # Admin selalu benar
            }
    except Exception as e:
        print(f"Error mengakses DB untuk koreksi: {e}")
    return None


def _softmax(x):
    e_x = np.exp(x - np.max(x, axis=1, keepdims=True))
    return e_x / np.sum(e_x, axis=1, keepdims=True)


# ─── Fungsi Prediksi Utama ───────────────────────────────────────────────────
async def predict_sentiment_and_intent(text: str, db=None):
    # A. CEK MEMORI: Apakah ada koreksi admin?
    memory_result = await get_correction_from_db(text, db)
    if memory_result:
        print(f"[MEMORY LAYER] Menggunakan data koreksi admin untuk: '{text}'")
        return memory_result

    # B. ONNX INFERENCE
    if use_onnx and tokenizer and ort_session:
        try:
            inputs = tokenizer(
                text, return_tensors="np",
                truncation=True, padding='max_length', max_length=128
            )
            ort_inputs = {
                "input_ids":      inputs["input_ids"],
                "attention_mask": inputs["attention_mask"]
            }
            ort_outs = ort_session.run(None, ort_inputs)
            sentiment_logits, intent_logits = ort_outs[0], ort_outs[1]

            sentiment_probs = _softmax(sentiment_logits)
            intent_probs    = _softmax(intent_logits)

            pred_sentiment_idx = int(np.argmax(sentiment_probs, axis=1)[0])
            pred_intent_idx    = int(np.argmax(intent_probs, axis=1)[0])
            sentiment_conf     = float(np.max(sentiment_probs, axis=1)[0])
            intent_conf        = float(np.max(intent_probs, axis=1)[0])
            real_confidence    = round((sentiment_conf + intent_conf) / 2, 2)

            return {
                "sentiment":  SENTIMENT_LABELS[pred_sentiment_idx],
                "intent":     INTENT_LABELS[pred_intent_idx],
                "confidence": real_confidence
            }
        except Exception as e:
            print(f"Error saat prediksi ONNX: {e}")

    # C. FALLBACK: jika model tidak tersedia
    return {"sentiment": "neutral", "intent": "other", "confidence": 0.5}