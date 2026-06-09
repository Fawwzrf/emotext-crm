# 🚀 Emotext-CRM

Emotext-CRM adalah solusi cerdas untuk meningkatkan kualitas layanan pelanggan (Customer Service) di WhatsApp Web menggunakan AI (Artificial Intelligence). Sistem ini menganalisis sentimen pesan secara *real-time* (menggunakan model **IndoBERT yang dioptimasi dengan ONNX**) dan memberikan saran balasan otomatis berdasarkan SOP perusahaan Anda (menggunakan RAG - *Retrieval-Augmented Generation*).

Semua keajaiban ini berjalan tanpa memerlukan akses API resmi WhatsApp yang berbayar (Official WhatsApp Business API), karena sistem diinjeksikan secara mulus melalui **Ekstensi Google Chrome**!

---

## ✨ Fitur Utama
1. **Smart Sentiment Analysis**: Mendeteksi secara instan apakah pesan pelanggan bersentimen Positif, Netral, atau Negatif dari bahasa Indonesia, termasuk bahasa sehari-hari.
2. **SOP Intelligence (RAG)**: AI membaca dokumen pedoman SOP Anda dan langsung menyarankan draf teks balasan yang akurat di atas kotak ketik WhatsApp.
3. **Seamless Chrome Extension**: Menyatu langsung dengan antarmuka WhatsApp Web. Tidak perlu berpindah *tab* atau aplikasi.
4. **Real-time Analytics Dashboard**: Pantau performa tim CS Anda, pantau "Health Score" pelanggan, dan visualisasikan data analitik operasional secara komprehensif.
5. **Data Isolation & Security**: Arsitektur *multi-tenant* per pelanggan dengan lapisan keamanan *Hashing* SHA-256 untuk API Token ekstensi (SaaS Ready).
6. **High Performance**: Backend FastAPI asinkron (*asyncpg*) dipadukan dengan komputasi model AI berformat ONNX untuk latensi di bawah 100 milidetik.

---

## 🏗️ Arsitektur Sistem

Sistem ini terbagi menjadi 3 komponen repositori utama yang bekerja sama secara sinkron:

1. **`backend/` (FastAPI + Python)**: Bertindak sebagai otak AI utama. Menjalankan model *Machine Learning* PyTorch dan *Knowledge Base* (RAG).
2. **`dashboard/` (Laravel 11 + Tailwind + Alpine.js)**: Antarmuka Web terpusat untuk manajemen pengguna (SaaS), API Token *Security*, dan Dasbor analitik CRM.
3. **`extension/` (JavaScript + Chrome API)**: Ekstensi *browser* yang ditempelkan ke DOM WhatsApp Web untuk memantau pesan masuk dan menampilkan UI panel AI.

---

## ⚙️ Prasyarat (Prerequisites)

Sebelum mencoba menginstal di lokal, pastikan komputer Anda memiliki spesifikasi/aplikasi berikut:
- **PHP >= 8.2** & **Composer**
- **Python >= 3.10**
- **Node.js & npm**
- Akun **Supabase** (Atau *instance* PostgreSQL yang mendukung remote connection)
- **Google Chrome** (untuk menjalankan ekstensi)

---

## 🚀 Panduan Instalasi Lengkap

Ikuti langkah-langkah di bawah ini secara berurutan untuk menjalankan Emotext-CRM di komputer lokal (*localhost*) Anda.

### Tahap 1: Persiapan Database (Supabase)
Sistem ini menggunakan satu *database* PostgreSQL yang diakses secara bersamaan oleh Laravel dan FastAPI.
1. Buat proyek baru di [Supabase](https://supabase.com/).
2. Masuk ke **Settings > Database** dan catat kredensial *Connection String* Anda.

### Tahap 2: Menjalankan Dashboard (Laravel)
Jalankan aplikasi *terminal* baru dan arahkan ke folder `dashboard`:
```bash
cd dashboard
```
1. Salin file konfigurasi *environment*:
   ```bash
   cp .env.example .env
   ```
2. Buka file `.env` dan isi kredensial *database* Supabase Anda:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=aws-0-....pooler.supabase.com
   DB_PORT=6543
   DB_DATABASE=postgres
   DB_USERNAME=postgres.xxxx
   DB_PASSWORD=password_anda_disini
   ```
3. Instal semua dependensi PHP dan Javascript:
   ```bash
   composer install
   npm install
   php artisan key:generate
   ```
4. Jalankan migrasi untuk merakit struktur tabel di *database*:
   ```bash
   php artisan migrate
   ```
5. Lakukan *build* aset CSS/JS dan hidupkan *server* Laravel (Biarkan terminal ini terus terbuka):
   ```bash
   npm run build
   php artisan serve --port=8001
   ```
6. Buka terminal baru di folder `dashboard`, lalu jalankan server WebSocket (Reverb) agar fitur Real-Time aktif:
   ```bash
   php artisan reverb:start
   ```
> 💡 *Situs Landing Page & Dashboard sekarang bisa diakses di `http://127.0.0.1:8001`*

### Tahap 3: Menjalankan AI Backend (FastAPI)
Buka terminal baru dan arahkan ke folder `backend`:
```bash
cd backend
```
1. Buat *Virtual Environment* Python agar instalasi pustaka lebih bersih:
   ```bash
   python -m venv venv
   
   # Untuk pengguna Windows:
   venv\Scripts\activate
   
   # Untuk pengguna Mac/Linux:
   source venv/bin/activate
   ```
2. Instal semua pustaka AI (*library*):
   ```bash
   pip install -r requirements.txt
   ```
3. Salin konfigurasi *environment*:
   ```bash
   cp .env.example .env
   ```
4. Isi file `.env` dengan kredensial Supabase (Pastikan format koneksi SQLAlchemy benar):
   ```env
   DATABASE_URL=postgresql+asyncpg://postgres.xxxx:password_anda@aws-0-....pooler.supabase.com:6543/postgres
   ```
5. Hidupkan *server* AI (Biarkan terminal ini terbuka):
   ```bash
   uvicorn main:app --reload
   ```
> 💡 *Mesin AI FastAPI sekarang siaga di `http://127.0.0.1:8000`*

### Tahap 4: Memasang Ekstensi Chrome
1. Buka *browser* Google Chrome.
2. Ketik `chrome://extensions/` di *address bar* Anda.
3. Aktifkan sakelar **"Developer mode"** di pojok kanan atas.
4. Klik tombol **"Load unpacked"** di kiri atas layar.
5. Cari lalu pilih *folder* bernama `extension/` dari *source code* Emotext-CRM ini.
6. Ekstensi Emotext-CRM kini akan bertengger di menu ekstensi *browser* Anda.

---

## 🎮 Cara Menguji Coba (*User Flow*)

Setelah Laravel, FastAPI, dan Ekstensi sukses berjalan bersamaan:

1. **Buat Akun Perusahaan:**
   Buka `http://127.0.0.1:8001` di *browser*. Tekan **"Install Ekstensi Gratis"** (atau menu Register) untuk membuat akun SaaS bisnis baru Anda.
2. **Buat Kunci API (API Token):**
   Setelah Anda diarahkan masuk ke dalam *Dashboard* Laravel, klik ikon Nama Profil Anda di pojok kanan atas. Pilih menu **API Tokens**. Ketikkan nama token bebas (contoh: "Ext_Laptop"), tekan *Create*, dan **salin (copy)** *Token* berformat teks panjang tersebut.
3. **Login Ekstensi di WhatsApp:**
   Buka situs [WhatsApp Web](https://web.whatsapp.com/). Klik logo Ekstensi Emotext-CRM (ikon *puzzle*) di pojok kanan atas Google Chrome. Jendela *login* akan muncul. **Paste (Tempel)** API Token Anda ke sana.
4. **Mulai Simulasi Chat:**
   - Coba masuk ke salah satu percakapan (*chat room*) di WhatsApp Web.
   - Panel AI otomatis akan muncul tepat di bawah kotak pengetikan pesan.
   - Saksikan bagaimana sistem secara otomatis merangkum sentimen (marah/senang) dari pesan lawan bicara Anda!
   - Lihat perubahan grafik *real-time* Anda di layar Dashboard Analytics `http://127.0.0.1:8001/dashboard`.

---

## 📖 Referensi Dokumentasi
Untuk memahami teknis di balik layar, optimalisasi memori, dan cara kerja sinkronisasi *Cache*, Anda dapat membaca direktori dokumen:
- [`docs/MASTER_PRD.md`](docs/MASTER_PRD.md) : Spesifikasi sistem.
- [`docs/BUGS_AND_FIXES.md`](docs/BUGS_AND_FIXES.md) : Jurnal historis perbaikan masalah dan saran pengembangan arsitektur level *Enterprise*.

---
**Emotext-CRM** – *Supercharge Your WhatsApp Customer Service.*
