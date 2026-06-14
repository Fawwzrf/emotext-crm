# 🚀 Emotext-CRM (WA-CRM Intelligence)
**Emotext-CRM** adalah platform *Customer Relationship Management* (CRM) inovatif yang mengintegrasikan kecerdasan buatan (AI) secara langsung ke dalam antarmuka WhatsApp Web. Dirancang khusus untuk tim *Customer Service* guna menganalisis sentimen, mendeteksi intensi, dan memberikan balasan otomatis berdasarkan SOP perusahaan (*Offline RAG*).

Sistem ini beroperasi dengan model **SaaS (Software as a Service)**, memisahkan ekstensi klien yang ringan dari mesin AI (*Backend*) yang kuat.

---

## 🏗️ Arsitektur Sistem (Client-Server)

Untuk melindungi kerahasiaan (*Intellectual Property*) model AI dan menjaga performa perangkat pelanggan, Emotext-CRM dipisah menjadi dua komponen utama:

1. **Front-End (Chrome Extension):**
   Aplikasi ringan berukuran <5 MB yang diunduh dan dipasang oleh pelanggan (*user*) di Google Chrome. Bertugas membaca pesan WhatsApp Web, mengirimkannya ke server, dan merender antarmuka *dashboard* CRM di layar pengguna.
2. **Back-End (Cloud AI Engine):**
   Server FastAPI yang men-hosting model NLP (IndoBERT ONNX) berukuran besar dan *Knowledge Base* (FAISS Llama.cpp). Bertugas melakukan komputasi kecerdasan buatan kelas berat dan mengembalikan hasilnya ke *Extension*.

---

## 🌐 Panduan Deployment Backend (Bagi Developer)
*Agar ekstensi berfungsi, Backend ini wajib di-deploy (di-hosting) di server *cloud*.*

Karena model AI (*IndoBERT* dan *FAISS*) membutuhkan RAM yang cukup besar, platform *serverless* biasa (seperti Vercel atau Netlify) **tidak bisa digunakan**. Kami merekomendasikan **Hugging Face Spaces** sebagai solusi *hosting* gratis terbaik (16GB RAM, 2 vCPU).

### Langkah-langkah Deploy ke Hugging Face Spaces (Gratis):
1. Buat akun di [Hugging Face](https://huggingface.co/).
2. Buat **Space** baru, berikan nama (misal: `emotext-backend`), lalu pilih **Docker** sebagai SDK.
3. Unggah seluruh isi folder `produk_extension/backend/` beserta folder `models/` ke dalam Space tersebut.
4. Buat file `Dockerfile` di *root* direktori Space Anda dengan konfigurasi standar FastAPI Uvicorn:
   ```dockerfile
   FROM python:3.10
   WORKDIR /app
   COPY ./requirements.txt /app/requirements.txt
   RUN pip install --no-cache-dir -r /app/requirements.txt
   COPY . /app
   CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "7860"]
   ```
5. Hugging Face akan otomatis membangun kontainer dan men-*deploy* API Anda. Anda akan mendapatkan URL publik (contoh: `https://username-emotext-backend.hf.space`).
6. Ubah URL API di dalam *source code* Ekstensi Chrome Anda agar menunjuk ke URL baru tersebut, lalu kompres (*ZIP*) folder ekstensi menjadi `Emotext-Extension.zip` untuk didistribusikan ke pelanggan.

---

## 💻 Panduan Instalasi (Bagi Pelanggan/User)

Pelanggan dapat menikmati layanan Emotext-CRM dengan langkah instalasi yang sangat mudah. Panduan visual lengkap tersedia di halaman pendaftaran *Website* resmi.

**Cara Memasang Emotext-Extension:**
1. Unduh file `Emotext-Extension.zip` dari halaman *Dashboard Website* setelah Anda berlangganan.
2. Ekstrak (Unzip) file tersebut ke sebuah folder di laptop Anda.
3. Buka browser **Google Chrome** dan ketik `chrome://extensions/` di kolom URL.
4. Aktifkan **Developer mode** (Mode Pengembang) di pojok kanan atas layar.
5. Klik tombol **Load unpacked** (Muat yang tidak dikemas) di pojok kiri atas.
6. Pilih folder hasil ekstraksi `Emotext-Extension` tadi. Ekstensi berhasil dipasang!

---

## 🔑 Panduan Pengaktifan & Login (User)

Sistem ini diamankan dengan kredensial berlangganan untuk mencegah akses tidak sah.

1. Buka [WhatsApp Web (web.whatsapp.com)](https://web.whatsapp.com) di Google Chrome Anda.
2. Saat pertama kali dibuka, layar *pop-up* Emotext-CRM akan muncul meminta otentikasi.
3. Masukkan **Alamat Email** dan **Password** yang Anda gunakan saat membeli paket langganan di *website* kami.
4. Setelah berhasil *Login*, sistem akan aktif secara permanen dan otomatis menganalisis setiap pesan WhatsApp yang masuk!

---

## 🛠️ Tech Stack
- **AI / NLP Engine:** IndoBERT (ONNX Runtime), Llama.cpp (GGUF), FAISS (Offline RAG)
- **Backend API:** FastAPI (Python), SQLite/PostgreSQL
- **Frontend / Extension:** React.js, Vanilla CSS, Manifest V3
- **Infrastructure:** Docker, Uvicorn, Hugging Face Spaces

*Emotext-CRM v1.0 - Stable Release. Developed by Fawwaz.*
