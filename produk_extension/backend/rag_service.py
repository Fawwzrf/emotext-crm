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
from llama_cpp import Llama
from sentence_transformers import SentenceTransformer
import faiss

BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DOC_DIR = os.path.join(BASE_DIR, "doc")
MODEL_PATH = os.path.join(BASE_DIR, "models", "qwen_gguf", "qwen2.5-1.5b-instruct-q4_k_m.gguf")

# ─── State lazy-load ──────────────────────────────────────────────────────────
_embedding_model = None
_llm = None
_faiss_index = None
_doc_chunks = []

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
    global _faiss_index, _doc_chunks, _rag_loaded
    
    if not _embedding_loaded:
        _load_embedding_model()
        
    with _rag_lock:
        if _rag_loaded:
            return
            
        print(f"[RAG] Membaca dokumen lokal dari: {DOC_DIR}")
        _doc_chunks = []
        embeddings = []
        
        if not os.path.exists(DOC_DIR):
            os.makedirs(DOC_DIR, exist_ok=True)
            
        for filepath in glob.glob(os.path.join(DOC_DIR, "*.txt")):
            try:
                with open(filepath, "r", encoding="utf-8") as f:
                    content = f.read()
                    chunks = chunk_text(content)
                    for chunk in chunks:
                        _doc_chunks.append(chunk)
            except Exception as e:
                print(f"[RAG WARNING] Gagal membaca {filepath}: {e}")
                
        if _doc_chunks and _embedding_model:
            print(f"[RAG] Membangun index FAISS untuk {len(_doc_chunks)} chunks...")
            emb_matrix = _embedding_model.encode(_doc_chunks)
            emb_matrix = np.array(emb_matrix).astype("float32")
            
            d = emb_matrix.shape[1]
            _faiss_index = faiss.IndexFlatL2(d)
            _faiss_index.add(emb_matrix)
            print("[RAG] FAISS Index siap.")
        else:
            print("[RAG] Tidak ada dokumen yang dimuat atau embedding gagal.")
            
        _rag_loaded = True


def _load_llm():
    global _llm, _llm_loaded
    with _llm_lock:
        if _llm_loaded:
            return
        try:
            if not os.path.exists(MODEL_PATH):
                raise FileNotFoundError(f"Model GGUF tidak ditemukan di {MODEL_PATH}")
                
            print(f"[RAG] Memuat LLM LlamaCPP ({MODEL_PATH})...")
            # Sesuaikan n_gpu_layers (0 = CPU only) jika tidak pakai CUDA 
            _llm = Llama(
                model_path=MODEL_PATH,
                n_ctx=2048,
                n_threads=max(1, os.cpu_count() - 1),
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
    if _faiss_index and _doc_chunks and _embedding_model:
        try:
            query_vector = await asyncio.to_thread(get_embedding, message)
            query_vector = np.array([query_vector]).astype("float32")
            distances, indices = _faiss_index.search(query_vector, k=2)
            
            context_text = "\n\nPanduan Terkait:\n"
            for idx in indices[0]:
                if idx >= 0 and idx < len(_doc_chunks):
                    context_text += f"- {_doc_chunks[idx]}\n"
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
                    max_tokens=150,
                    stop=["<|im_end|>"],
                    temperature=0.3
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
    if _faiss_index and _doc_chunks and _embedding_model:
        try:
            query_vector = get_embedding(message)
            query_vector = np.array([query_vector]).astype("float32")
            distances, indices = _faiss_index.search(query_vector, k=2)
            
            context_text = "\n\nPanduan Terkait:\n"
            for idx in indices[0]:
                if idx >= 0 and idx < len(_doc_chunks):
                    context_text += f"- {_doc_chunks[idx]}\n"
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
            stream = _llm(
                f"<|im_start|>system\nAnda adalah agen Customer Service profesional.<|im_end|>\n<|im_start|>user\n{prompt}<|im_end|>\n<|im_start|>assistant\n",
                max_tokens=150,
                stop=["<|im_end|>"],
                temperature=0.3,
                stream=True
            )
            for chunk in stream:
                text = chunk["choices"][0]["text"]
                if text:
                    yield text
    except Exception as e:
        print(f"[RAG STREAM] LLM Error: {e}")
        yield templates.get(intent.lower(), "Terima kasih telah menghubungi kami.")