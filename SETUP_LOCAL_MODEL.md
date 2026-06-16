# Panduan Setup Emotext-CRM (Jalur Model Lokal / Offline)

Dokumen ini berisi panduan untuk menjalankan proyek **Emotext-CRM** dengan mengunduh dan menjalankan mesin AI (Backend FastAPI, Model IndoBERT, Model RAG GGUF) secara langsung di komputer/laptop Anda sendiri. 

Jalur ini cocok jika Anda tidak ingin bergantung pada internet (Hugging Face) atau memiliki perangkat keras (GPU) yang mumpuni.

---

## 🚀 1. Persiapan Awal
Pastikan komputer Anda sudah terinstal:
- **PHP** (minimal v8.2) & **Composer**
- **Node.js** (minimal v18) & **npm**
- **Python** (minimal v3.10)
- **Git**

Lakukan kloning repositori:
```bash
git clone <url-repository-anda>
cd Emotext-CRM
```

---

## 💻 2. Menjalankan Backend AI Lokal & Import Model

Karena Anda menggunakan mode lokal, Anda wajib memiliki *file model* fisik di komputer Anda dan menjalankan *server* Python secara manual.

1. **Import Model AI dari Hugging Face / Rekan Tim:**
   Buat folder bernama `models` di direktori paling luar (sejajar dengan folder `backend`). Letakkan file model yang Anda dapatkan dari tim Anda ke dalam struktur ini secara presisi:
   ```text
   Emotext-CRM/
    └── models/
         ├── indobert_onnx/
         │    ├── model.onnx
         │    ├── config.json
         │    └── tokenizer.json
         └── qwen_gguf/
              └── qwen2.5-1.5b-instruct-q4_k_m.gguf
   ```
   *(Harap pastikan nama file model GGUF persis sama seperti di atas agar sistem bisa menemukannya).*

2. **Jalankan Backend Python:**
   Buka terminal baru, masuk ke folder `backend` dan jalankan:
   ```bash
   cd backend
   python -m venv venv
   
   # Aktifkan venv di Windows:
   venv\Scripts\activate
   # Di Mac/Linux: source venv/bin/activate
   
   # Instal dependensi:
   pip install -r requirements.txt
   
   # Konfigurasi env (Isi dengan kredensial Supabase & INTERNAL_API_KEY):
   cp .env.example .env
   
   # Jalankan Server FastAPI:
   uvicorn main:app --reload --port 8000
   ```
   *(Jangan tutup terminal ini selama Anda menggunakan AI lokal).*

---

## 🐘 3. Setup Website (Laravel Dashboard)

1. Buka terminal baru dan masuk ke folder `website`:
   ```bash
   cd website
   ```
2. Instal dependensi PHP dan Node.js:
   ```bash
   composer install
   npm install
   ```
3. Salin file `.env`:
   ```bash
   cp .env.example .env
   ```
4. Sesuaikan kredensial Database Supabase PostgreSQL tim Anda di file `.env`. Pastikan Anda mengarahkan lalu lintas API Laravel ke Python Lokal Anda:
   ```env
   FASTAPI_URL=http://127.0.0.1:8000
   INTERNAL_API_KEY=<your_secure_internal_key>
   ```
5. Generate App Key:
   ```bash
   php artisan key:generate
   ```
   > ⚠️ **PENTING:** Jika menggunakan Database Supabase yang sama dengan tim, Anda **TIDAK PERLU** `php artisan migrate --seed`. Jika menggunakan database kosong, silakan jalankan perintah tersebut.
6. Jalankan server Laravel:
   ```bash
   php artisan serve --port=8001
   ```
7. Di terminal baru, jalankan *asset bundler* Vite:
   ```bash
   npm run dev
   ```

---

## 🧠 4. Konfigurasi Ekstensi Chrome (Mode Lokal)

1. Buka folder `chrome_extension` di text editor Anda.
2. Buka file `env.js` dan ubah URL API menjadi lokal:
   ```javascript
   API_BASE_URL: 'http://127.0.0.1:8000'
   ```
3. Buka browser **Google Chrome** dan ketik `chrome://extensions/`.
4. Nyalakan **Developer mode** (pojok kanan atas).
5. Klik **Load unpacked** dan pilih folder `chrome_extension` proyek Anda.

---

## 🎉 5. Cara Mengetes

1. Buka halaman registrasi lokal: `http://127.0.0.1:8001/register` dan buat akun.
2. Masuk ke halaman Dashboard.
3. Buka tab baru, masuk ke [web.whatsapp.com](https://web.whatsapp.com) dan hubungkan HP Anda.
4. Pop-up login Ekstensi Emotext akan muncul. Masukkan Email & Password Laravel Anda.
5. Ekstensi & Dashboard akan langsung terhubung! Silakan chat di WA atau upload dokumen SOP di Dasbor.
