# Panduan Setup Lokal Emotext-CRM (Bagi Tim Developer)

Dokumen ini berisi panduan langkah demi langkah untuk melakukan *pull* dan menjalankan proyek **Emotext-CRM** secara lokal di komputer anggota tim lain.

Proyek ini terbagi menjadi 3 bagian utama:
1. **Website (Laravel)** — Dashboard & API Authentication.
2. **Backend AI (FastAPI)** — Mesin AI, NLP (IndoBERT), dan RAG (Llama.cpp).
3. **Ekstensi Chrome** — *Frontend* yang di-*inject* ke WhatsApp Web.

---

## 🚀 1. Persiapan Awal (Prerequisites)
Pastikan komputer tim Anda sudah terinstal:
- **PHP** (minimal v8.2) & **Composer**
- **Node.js** (minimal v18) & **npm**
- **Python** (minimal v3.10)
- **Git**

Lakukan kloning repositori (jika menggunakan Git):
```bash
git clone <url-repository-anda>
cd Emotext-CRM
```

---

## 🐘 2. Setup Website (Laravel Dashboard)

1. Buka terminal dan masuk ke folder `website`:
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
4. Sesuaikan file `.env`. Karena proyek ini sudah diset menggunakan Supabase PostgreSQL, pastikan baris `DB_CONNECTION` diisi dengan kredensial PostgreSQL Supabase milik tim. Pastikan juga variabel ini ada di `.env`:
   ```env
   FASTAPI_URL=https://<your-hf-username>-emotext-backend.hf.space
   INTERNAL_API_KEY=<your_secure_internal_key>
   ```
   *(Catatan: `FASTAPI_URL` ku menunjuk ke cloud Hugging Face sehingga Anda tidak perlu repot menjalankan AI secara lokal).*
5. Generate *App Key* untuk mengenkripsi *session* lokal Anda:
   ```bash
   php artisan key:generate
   ```
   > ⚠️ **PENTING:** Jika tim Anda menggunakan **Database Supabase yang sama/bersama**, Anda **TIDAK PERLU** menjalankan `php artisan migrate --seed` karena tabel dan data contoh sudah dibuat oleh anggota tim pertama. Anda bisa melewati langkah migrasi. Namun jika Anda menggunakan *database* SQLite lokal masing-masing, jalankan:
   > `php artisan migrate --seed`
6. Jalankan server Laravel:
   ```bash
   php artisan serve --port=8001
   ```
7. Di terminal terpisah, jalankan *asset bundler* Vite:
   ```bash
   npm run dev
   ```
   *Dashboard sekarang dapat diakses di http://127.0.0.1:8001*

---

## 🐍 3. Setup Backend AI (Opsional)

**Kabar Baik:** Anda sebenarnya **tidak perlu** menjalankan *Backend* AI ini di komputer Anda karena *Backend* Emotext sudah di-*hosting* secara *live* di Hugging Face Spaces.

Namun, jika Anda **ingin menggunakan model AI secara lokal sepenuhnya** (misalnya karena koneksi internet lambat, Hugging Face sedang *down/sleep*, atau Anda AI *Engineer* yang merubah *source code*), ikuti langkah ini:

1. Buka terminal baru dan masuk ke folder `backend`:
   ```bash
   cd backend
   ```
2. Buat *Virtual Environment* Python:
   ```bash
   python -m venv venv
   ```
3. Aktifkan *Virtual Environment*:
   - Di Windows: `venv\Scripts\activate`
   - Di Mac/Linux: `source venv/bin/activate`
4. Instal dependensi AI:
   ```bash
   pip install -r requirements.txt
   ```
5. Buat file `.env` di dalam folder `backend`:
   ```bash
   cp .env.example .env
   ```
   *Isi file `.env` dengan kredensial Supabase PostgreSQL (jika pakai DB) dan `INTERNAL_API_KEY=<your_secure_internal_key>`.*
6. Jalankan server FastAPI:
   ```bash
   uvicorn main:app --reload --port 8000
   ```
   *(Catatan: Saat pertama kali dijalankan, sistem mungkin akan men-download file model IndoBERT dan Llama.cpp secara otomatis).*

---

## 🧩 4. Setup Ekstensi Chrome

1. Buka browser **Google Chrome**.
2. Ketik `chrome://extensions/` di bilah URL (Address bar).
3. Nyalakan **Developer mode** (Mode Pengembang) di pojok kanan atas.
4. Klik tombol **Load unpacked** di pojok kiri atas.
5. Arahkan dan pilih folder `ekstensi` yang ada di dalam proyek Anda (pastikan folder tersebut berisi file `manifest.json`).
6. Ekstensi ini menggunakan file konfigurasi khusus. Buka file `env.js` pada folder `chrome_extension` dan sesuaikan variabel URL jika perlu:
   - Jika memakai AI *Cloud* (Hugging Face): `API_BASE_URL: 'https://<your-hf-username>-emotext-backend.hf.space'`
   - Jika memakai AI *Lokal*: `API_BASE_URL: 'http://127.0.0.1:8000'`
7. Buka file `manifest.json` pada folder `chrome_extension`, pastikan di dalam `host_permissions` URL yang sama juga terdaftar (baik URL Hugging Face maupun URL Localhost).
   
> ⚠️ **TROUBLESHOOTING RAG HUGGING FACE:** Jika tim Anda mencoba RAG di Hugging Face dan muncul "Koneksi Gagal", kemungkinan besar karena 2 hal: 
> 1. Tim Anda hanya mengatur `.env` di Laravel, tapi **lupa mengubah `API_BASE_URL`** di dalam file ekstensi `env.js` dan izin domain di `manifest.json`. 
> 2. *Server Hugging Face Free Tier* tertidur (*sleep*). Jika ruang kerja (*space*) tertidur, *request* pertama akan gagal (Time-out). Anda perlu menekan/membangunkan Space-nya terlebih dahulu, lalu tunggu 2 menit sebelum RAG aktif lagi.

---

## 🎉 5. Cara Mengetes (Testing Flow)

1. Buka halaman registrasi lokal di browser: `http://127.0.0.1:8001/register` dan buat satu akun perusahaan (contoh: *Admin*).
2. Masuk ke halaman Dashboard.
3. Buka tab baru, masuk ke [web.whatsapp.com](https://web.whatsapp.com) dan hubungkan dengan HP Anda.
4. Pop-up login Ekstensi Emotext akan muncul. Masukkan Email & Password yang baru Anda daftarkan di Laravel tadi.
5. Selesai! Ekstensi dan Dashboard akan langsung terhubung (*sync*). Silakan coba membalas obrolan WA atau upload dokumen SOP di Dasbor!
