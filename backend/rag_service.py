"""
rag_service.py — Offline RAG dengan GGUF & FAISS
Membaca file dari direktori lokal `doc` untuk RAG, tanpa koneksi database.
Menggunakan llama-cpp-python (GGUF) agar ringan di laptop spec rendah.
"""
import os
import glob
import threading
import asyncio
import numpy as np
from sentence_transformers import SentenceTransformer
import faiss
from huggingface_hub import snapshot_download

# Lazy import llama_cpp agar server tidak crash saat startup jika library gagal load
_Llama = None
def _get_llama_class():
    global _Llama
    if _Llama is None:
        try:
            from llama_cpp import Llama
            _Llama = Llama
        except Exception as e:
            print(f"[WARN] llama_cpp tidak bisa dimuat: {e}. Fitur RAG/LLM tidak tersedia.")
    return _Llama


BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DOC_DIR = os.path.join(BASE_DIR, "doc")

if os.environ.get("SPACE_ID"):
    MODEL_ROOT = os.path.join(BASE_DIR, "models")
    DOWNLOAD_ROOT = BASE_DIR
else:
    MODEL_ROOT = os.path.join(BASE_DIR, "..", "models")
    DOWNLOAD_ROOT = os.path.join(BASE_DIR, "..")

MODEL_PATH = os.path.join(MODEL_ROOT, "qwen_gguf", "qwen2.5-1.5b-instruct-q4_k_m.gguf")

# ─── State lazy-load ──────────────────────────────────────────────────────────
_embedding_model = None
_llm = None
_faiss_indices = {} # user_id -> faiss.IndexFlatL2
_user_doc_chunks = {} # user_id -> list of strings

_embedding_lock = threading.Lock()
_llm_lock = threading.Lock()
_rag_lock = threading.Lock()

_embedding_loaded = False
_llm_loaded = False
_rag_loaded = False

_generation_lock = threading.Lock()


def chunk_text(text: str, chunk_size: int = 150) -> list:
    words = text.split()
    chunks, current_chunk, current_length = [], [], 0
    for word in words:
        current_chunk.append(word)
        current_length += 1
        if current_length >= chunk_size:
            chunks.append(" ".join(current_chunk))
            current_chunk, current_length = [], 0
    if current_chunk:
        chunks.append(" ".join(current_chunk))
    return chunks


def _load_embedding_model():
    global _embedding_model, _embedding_loaded
    with _embedding_lock:
        if _embedding_loaded:
            return
        try:
            print("[RAG] Memuat SentenceTransformer...")
            _embedding_model = SentenceTransformer('paraphrase-multilingual-MiniLM-L12-v2')
            print("[RAG] SentenceTransformer siap.")
        except Exception as e:
            print(f"[RAG WARNING] Gagal memuat SentenceTransformer: {e}")
            _embedding_model = None
        finally:
            _embedding_loaded = True


def _load_local_rag_index():
    """Membaca semua file .txt di folder doc/, chunking, dan membuat FAISS index."""
    global _faiss_indices, _user_doc_chunks, _rag_loaded
    
    if not _embedding_loaded:
        _load_embedding_model()
        
    with _rag_lock:
        if _rag_loaded:
            return
            
        print(f"[RAG] Menyinkronkan dokumen dari Database Supabase...")
        
        try:
            import asyncio
            from database import SessionLocal
            from sqlalchemy import select
            from models import KnowledgeBase
            import json
            
            async def _fetch_all():
                async with SessionLocal() as db:
                    res = await db.execute(select(KnowledgeBase))
                    return res.scalars().all()
                    
            loop = asyncio.new_event_loop()
            asyncio.set_event_loop(loop)
            try:
                kb_docs = loop.run_until_complete(_fetch_all())
            finally:
                loop.close()
                asyncio.set_event_loop(None)
        
            _faiss_indices = {}
            _user_doc_chunks = {}

            if not kb_docs:
                print("[RAG] Tidak ada dokumen di database.")
                _rag_loaded = True
                return

            # Kelompokkan dokumen berdasarkan user_id
            from collections import defaultdict
            user_docs = defaultdict(list)
            for kb in kb_docs:
                user_docs[kb.user_id].append(kb)

            # Build index per user
            for uid, docs in user_docs.items():
                chunks_for_user = []
                for kb in docs:
                    if not kb.content:
                        continue
                    # Split content into sentences/chunks (simple split by newline for now, or paragraphs)
                    # We skip writing to physical files (DOC_DIR) for better security & multi-tenant isolation.
                    lines = kb.content.split('\n')
                    current_chunk = ""
                    for line in lines:
                        line = line.strip()
                        if not line:
                            if current_chunk:
                                chunks_for_user.append(current_chunk)
                                current_chunk = ""
                            continue
                        current_chunk += line + " "
                    if current_chunk:
                        chunks_for_user.append(current_chunk)
                
                _user_doc_chunks[uid] = chunks_for_user

                if chunks_for_user:
                    emb_matrix = _embedding_model.encode(chunks_for_user, show_progress_bar=False)
                    emb_matrix = np.array(emb_matrix).astype("float32")
                    d = emb_matrix.shape[1]
                    idx = faiss.IndexFlatL2(d)
                    idx.add(emb_matrix)
                    _faiss_indices[uid] = idx
                    print(f"[RAG] FAISS Index siap untuk user_id={uid} ({len(chunks_for_user)} chunks).")
                else:
                    print(f"[RAG] Tidak ada chunk valid untuk user_id={uid}.")

            _rag_loaded = True
        except Exception as e:
            print(f"[RAG ERROR] Gagal menyinkronkan dari Database: {e}")
            _rag_loaded = True


def _load_llm():
    global _llm, _llm_loaded
    with _llm_lock:
        if _llm_loaded:
            return
        try:
            if not os.path.exists(MODEL_PATH):
                print(f"[RAG] Model GGUF tidak ditemukan lokal. Mengunduh dari fawwzrf/emotext-models...")
                os.makedirs(os.path.dirname(MODEL_PATH), exist_ok=True)
                snapshot_download(
                    repo_id="fawwzrf/emotext-models",
                    allow_patterns=["models/qwen_gguf/*"],
                    local_dir=DOWNLOAD_ROOT
                )
                print("[RAG] Unduhan model GGUF selesai!")
                
            print(f"[RAG] Memuat LLM LlamaCPP ({MODEL_PATH})...")
            # Sesuaikan n_gpu_layers (0 = CPU only) jika tidak pakai CUDA
            LlamaClass = _get_llama_class()
            if LlamaClass is None:
                print("[RAG WARNING] llama_cpp tidak tersedia, melewati load LLM.")
                return
            _llm = LlamaClass(
                model_path=MODEL_PATH,
                n_ctx=1024, # Kurangi n_ctx dari 2048 ke 1024 agar hemat memori & lebih cepat
                n_threads=2, # FIX: Hardcode ke 2 core (HF Free Tier). Jangan pakai os.cpu_count() karena di Docker bisa me-return 64 core host yang bikin CPU bottleneck parah!
                n_batch=256, # Kurangi batch agar Time To First Token lebih responsif
                verbose=False
            )
            print("[RAG] LLM siap.")
        except Exception as e:
            print(f"[RAG WARNING] Gagal memuat GGUF LLM: {e}")
            _llm = None
        finally:
            _llm_loaded = True


def get_embedding(text: str) -> list:
    if not _embedding_loaded:
        _load_embedding_model()
    if not _embedding_model:
        return [0.0] * 384
    return _embedding_model.encode(text).tolist()


def force_reload_rag():
    """Fungsi dipanggil dari endpoint /sync-rag untuk memuat ulang data dari DB."""
    global _rag_loaded
    _rag_loaded = False
    _load_local_rag_index()

async def get_smart_suggestion(intent: str, sentiment: str, message: str, db=None, user_id=None) -> str:
    """
    Generate balasan menggunakan llama.cpp dan pencarian lokal FAISS.
    Parameter `db` dan `user_id` dibiarkan untuk backward compatibility dengan main.py,
    tapi tidak lagi digunakan (RAG offline murni).
    """
    
    if not _rag_loaded:
        await asyncio.to_thread(_load_local_rag_index)
        
    if not _llm_loaded:
        await asyncio.to_thread(_load_llm)

    context_text = ""
    # Cari di FAISS
    if user_id in _faiss_indices and user_id in _user_doc_chunks and _embedding_model:
        user_faiss = _faiss_indices[user_id]
        user_chunks = _user_doc_chunks[user_id]
        try:
            query_vector = await asyncio.to_thread(get_embedding, message)
            query_vector = np.array([query_vector]).astype("float32")
            distances, indices = user_faiss.search(query_vector, k=2)
            
            context_text = "\n\nPanduan Terkait:\n"
            for idx in indices[0]:
                if idx >= 0 and idx < len(user_chunks):
                    context_text += f"- {user_chunks[idx]}\n"
        except Exception as e:
            print(f"[RAG] FAISS Error: {e}")

    templates = {
        "complaint": "Mohon maaf atas ketidaknyamanan yang Bapak/Ibu alami. Kami akan segera menindaklanjuti kendala ini.",
        "order":     "Terima kasih atas pesanan Bapak/Ibu. Pesanan sedang kami proses.",
        "inquiry":   "Terima kasih atas pertanyaannya. Kami akan segera memberikan informasi yang Bapak/Ibu butuhkan.",
    }

    if not _llm:
        return templates.get(intent.lower(), "Terima kasih telah menghubungi kami. Ada yang dapat kami bantu lebih lanjut?")

    prompt = f"""Kamu adalah agen Customer Service profesional. Tugas utamamu adalah merespons pertanyaan dan keluhan pelanggan dengan merujuk pada Panduan (SOP) Perusahaan.
JIKA Panduan Terkait menyediakan informasi (seperti estimasi waktu, syarat retur, dll), JAWAB BERDASARKAN PANDUAN TERSEBUT.{context_text}

Pesan Pelanggan: "{message}"

Instruksi: Berikan balasan informatif, langsung, dan sopan (maks 2-3 kalimat). Jangan sekadar meminta maaf; berikan jawaban konkret sesuai panduan di atas jika ada.
Balasan CS:"""

    def _generate():
        try:
            with _generation_lock:
                output = _llm(
                    f"<|im_start|>system\nAnda adalah agen Customer Service profesional.<|im_end|>\n<|im_start|>user\n{prompt}<|im_end|>\n<|im_start|>assistant\n",
                    max_tokens=100, # Kurangi max_tokens agar balasan selesai lebih cepat
                    stop=["<|im_end|>"],
                    temperature=0.2 # Temperature rendah untuk response cepat dan deterministik
                )
                return output["choices"][0]["text"].strip()
        except Exception as e:
            print(f"[RAG] LLM Error: {e}")
            return None

    result_text = await asyncio.to_thread(_generate)
    return result_text if result_text else templates.get(intent.lower(), "Terima kasih telah menghubungi kami.")


def stream_smart_suggestion(intent: str, sentiment: str, message: str, user_id=None):
    """
    Generator sinkron untuk men-stream token satu persatu dari Llama.cpp.
    Akan dikonsumsi oleh FastAPI StreamingResponse.
    """
    if not _rag_loaded:
        _load_local_rag_index()
        
    if not _llm_loaded:
        _load_llm()

    context_text = ""
    if user_id in _faiss_indices and user_id in _user_doc_chunks and _embedding_model:
        user_faiss = _faiss_indices[user_id]
        user_chunks = _user_doc_chunks[user_id]
        try:
            query_vector = get_embedding(message)
            query_vector = np.array([query_vector]).astype("float32")
            distances, indices = user_faiss.search(query_vector, k=2)
            
            context_text = "\n\nPanduan Terkait:\n"
            for idx in indices[0]:
                if idx >= 0 and idx < len(user_chunks):
                    context_text += f"- {user_chunks[idx]}\n"
        except Exception as e:
            print(f"[RAG STREAM] FAISS Error: {e}")

    templates = {
        "complaint": "Mohon maaf atas ketidaknyamanan yang Bapak/Ibu alami. Kami akan segera menindaklanjuti kendala ini.",
        "order":     "Terima kasih atas pesanan Bapak/Ibu. Pesanan sedang kami proses.",
        "inquiry":   "Terima kasih atas pertanyaannya. Kami akan segera memberikan informasi yang Bapak/Ibu butuhkan.",
    }

    if not _llm:
        yield templates.get(intent.lower(), "Terima kasih telah menghubungi kami. Ada yang dapat kami bantu lebih lanjut?")
        return

    prompt = f"""Kamu adalah agen Customer Service profesional. Tugas utamamu adalah merespons pertanyaan dan keluhan pelanggan dengan merujuk pada Panduan (SOP) Perusahaan.
JIKA Panduan Terkait menyediakan informasi (seperti estimasi waktu, syarat retur, dll), JAWAB BERDASARKAN PANDUAN TERSEBUT.{context_text}

Pesan Pelanggan: "{message}"

Instruksi: Berikan balasan informatif, langsung, dan sopan (maks 2-3 kalimat). Jangan sekadar meminta maaf; berikan jawaban konkret sesuai panduan di atas jika ada.
Balasan CS:"""

    try:
        with _generation_lock:
            for chunk in _llm(
                f"<|im_start|>system\nAnda adalah agen Customer Service profesional.<|im_end|>\n<|im_start|>user\n{prompt}<|im_end|>\n<|im_start|>assistant\n",
                max_tokens=150,
                stop=["<|im_end|>"],
                temperature=0.3,
                stream=True
            ):
                yield chunk["choices"][0]["text"]
    except Exception as e:
        print(f"[RAG STREAM] LLM Error: {e}")
        yield templates.get(intent.lower(), "Terima kasih telah menghubungi kami.")