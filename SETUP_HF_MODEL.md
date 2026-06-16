# Panduan Setup Emotext-CRM (Jalur Hugging Face / Cloud)

Dokumen ini berisi panduan untuk menjalankan proyek **Emotext-CRM** dengan menggunakan model AI yang sudah di-*hosting* di Hugging Face Spaces. 
**Jalur ini sangat disarankan** karena Anda tidak perlu mendownload model AI bergiga-giga atau menjalankan server Python lokal.

---

## 🚀 1. Persiapan Awal
Pastikan komputer Anda sudah terinstal:
- **PHP** (minimal v8.2) & **Composer**
- **Node.js** (minimal v18) & **npm**
- **Git**

Lakukan kloning repositori:
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
4. Sesuaikan kredensial Database Supabase PostgreSQL tim Anda di file `.env`. Pastikan juga variabel ini ada di `.env` untuk menunjuk ke Hugging Face:
   ```env
   FASTAPI_URL=https://<your-hf-username>-emotext-backend.hf.space
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

## 🧠 3. Konfigurasi Ekstensi Chrome (Mode Cloud)

1. Buka folder `chrome_extension` di text editor Anda.
2. Buka file `env.js` dan pastikan URL-nya mengarah ke Hugging Face:
   ```javascript
   API_BASE_URL: 'https://<your-hf-username>-emotext-backend.hf.space'
   ```
3. Buka browser **Google Chrome** dan ketik `chrome://extensions/`.
4. Nyalakan **Developer mode** (pojok kanan atas).
5. Klik **Load unpacked** dan pilih folder `chrome_extension` proyek Anda.

> ⚠️ **TROUBLESHOOTING RAG HUGGING FACE:** 
> Server Hugging Face versi gratis akan "tertidur" (*sleep*) jika tidak ada aktivitas selama 48 jam. Jika fitur obrolan Anda tiba-tiba "Koneksi Gagal", buka tautan Hugging Face Spaces Anda di browser dan tunggu mesinnya bangun (sekitar 1-2 menit), lalu RAG akan aktif kembali.

---

## 🎉 4. Cara Mengetes

1. Buka halaman registrasi lokal: `http://127.0.0.1:8001/register` dan buat akun.
2. Masuk ke halaman Dashboard.
3. Buka tab baru, masuk ke [web.whatsapp.com](https://web.whatsapp.com) dan hubungkan HP Anda.
4. Pop-up login Ekstensi Emotext akan muncul. Masukkan Email & Password Laravel Anda.
5. Ekstensi & Dashboard akan langsung terhubung! Silakan chat di WA atau upload dokumen SOP di Dasbor.
