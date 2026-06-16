# 🚀 Emotext-CRM — Fitur Pengguna (v1.0 Release)

> Dokumen ini adalah panduan resmi fitur yang tersedia bagi pengguna (Admin / Customer Service) di sistem **Emotext-CRM**. Sistem terdiri dari dua ekosistem utama yang bekerja bersama: **Ekstensi WhatsApp Web** dan **Dasbor Analitik Web**.

---

## 🟢 1. Ekstensi Chrome (WhatsApp Web)
Berjalan langsung di dalam layar WhatsApp Web — tanpa perlu berpindah tab.

### 🏷️ Real-Time Badges — Klasifikasi Pesan Otomatis
Setiap pesan masuk dari pelanggan dianalisis secara otomatis oleh AI (IndoBERT ONNX) dalam **< 200 milidetik** — bahkan tanpa koneksi internet setelah dimuat.

- **Badge Sentimen** 🟢🔴⚫ — Terdeteksi di balon pesan: Positif (hijau), Negatif (merah), Netral (abu-abu).
- **Badge Intensi** — AI mengklasifikasikan *tujuan* pesan: **Order** (Pemesanan), **Complaint** (Keluhan), **Inquiry** (Pertanyaan), atau **Other**.
- **Confidence Score** — Persentase keyakinan AI ditampilkan di dalam badge.

### 💡 Click-to-Suggest — Saran Balasan AI (RAG Offline)
Panel saran muncul **hanya saat Anda mengklik** balon pesan — tidak ada notifikasi atau pop-up yang mengganggu.

- **Cara kerja**: Klik balon pesan WhatsApp dari pelanggan → panel hijau muncul di atas kolom ketik.
- **Streaming AI** ✨ — Teks saran muncul *kata per kata* secara langsung (seperti ChatGPT), bukan sekadar loading bar.
- **Berbasis SOP Perusahaan** — AI membaca dokumen SOP/FAQ Anda (`doc/`) dan memberikan balasan yang kontekstual, bukan sekadar template.
- **Satu Klik Sisipkan** — Klik panel saran → teks otomatis masuk ke kolom ketik WhatsApp.
- **Cache Cerdas** — Saran yang pernah di-generate disimpan di DB. Klik kedua kalinya langsung instan.

### 💖 Customer Health Score — Indikator Loyalitas
Badge angka bulat muncul di atas foto profil setiap kontak di sidebar daftar chat.

| Warna | Rentang | Artinya |
|---|---|---|
| 🔴 Merah | < 50 | Pelanggan **berisiko tinggi** — komplain berturut-turut. Prioritaskan! |
| 🟡 Kuning | 50 – 79 | Pelanggan **netral** — perlu perhatian. |
| 🟢 Hijau | ≥ 80 | Pelanggan **loyal** — sentimen sangat baik. |

- Bar visual juga ditampilkan di bagian atas jendela obrolan dengan efek warna yang menyala.

### 🔧 Memory Layer — Koreksi Manual Admin
Jika prediksi AI dirasa kurang tepat:
1. Klik badge klasifikasi di balon pesan.
2. Pilih sentimen/intensi yang benar dari dropdown.
3. AI **langsung mengingat** koreksi ini. Pesan serupa berikutnya akan otomatis diklasifikasikan dengan benar.

---

## 📊 2. Dasbor Analitik (Unified Web Portal)
Pusat kontrol bagi *Management* atau tim *Customer Success* untuk memantau performa layanan secara holistik. Dasbor telah dirombak dengan arsitektur **Single Page Application (SPA)** untuk transisi antartab yang kilat dan mulus.

### 🗂️ Navigasi Berbasis Tab (Terpadu)
- **Satu Layar Untuk Semua**: Fitur pemantauan, analisis, dan pengaturan AI kini tergabung dalam satu layar utama tanpa perlu berpindah-pindah URL, memaksimalkan efisiensi.
- **Tab Analytics**: Menampilkan ringkasan metrik (Grafik dan Statistik).
- **Tab CRM / Inbox**: Menampilkan daftar pesan komplain dan riwayat percakapan.
- **Tab SOP & RAG**: Modul khusus untuk mengunggah dan mengatur pedoman perusahaan sebagai dasar otak AI.

### 🔔 Real-Time WebSockets & Notifikasi Langsung
- **Toast Notification** — Pop-up seketika setiap ada pesan baru masuk tanpa perlu refresh halaman.
- **Audio Alert** 🔊 — Alarm berbunyi otomatis jika pesan yang masuk terdeteksi sebagai **Keluhan (Negatif)**.
- **Auto-Reload Data** — Tabel dan grafik diperbarui otomatis setelah notifikasi masuk.

### 📈 Visualisasi & Analitik Sentimen
- **Sentiment Trend (Line Chart)** — Grafik garis yang memantau pergerakan Positif, Negatif, dan Netral secara periodik.
- **Sentiment Distribution (Pie Chart)** — Persentase keseluruhan sentimen positif/negatif/netral.
- **Intent Distribution (Doughnut Chart)** — Proporsi intensi pelanggan (seperti berapa % Keluhan, Pesanan, dsb).
- **Contact & Message Analytics** — Kartu metrik komprehensif untuk jumlah pesan dan rata-rata skor.
- **Date Filter Global** — Tombol filter (Hari Ini, 7 Hari, Bulan Ini, Semua) yang akan memuat ulang seluruh angka, grafik, dan tabel secara instan (menggunakan optimasi *Query Agregasi Massal* skala jutaan data).

### 📇 CRM Table & Customer Tracking
- **SLA Monitor (Top Urgent Complaints)** — Widget peringatan khusus untuk menyorot 5 pelanggan dengan keluhan paling kritis (*Health Score* < 50%). Widget ini **cerdas** dan akan otomatis *update* secara mandiri ketika keluhan diselesaikan.
- **Daftar Kontak (Accordion)** — Daftar seluruh kontak yang pernah berinteraksi. Sistem akan **mengingat** posisi chat mana yang sedang Anda buka (Persistent State), sehingga tidak menutup otomatis saat Anda melakukan *refresh* atau ganti filter.
- *Health Score* keseluruhan per kontak.
- **View Details** — Histori lengkap pesan per kontak, termasuk *confidence* AI.
- **Export Laporan (CSV)** — Unduh seluruh riwayat ke format Excel/CSV untuk pelaporan performa (*Performance Report*).

### 🛠️ Resolusi Pesan (Auto-Resolve)
- **Auto-Resolve Otomatis** — Saat agen mengetik dan membalas pelanggan dari WhatsApp Web, Ekstensi Chrome diam-diam mendeteksinya dan **otomatis mengubah** status komplain di Dashboard menjadi "Selesai" (Resolved) tanpa perlu diklik manual!
- **Tombol AJAX Instan** — Jika Anda menekan "✓ Tandai Selesai" secara manual, aksi terjadi di latar belakang (*Fetch API*). Tombol memunculkan animasi loading lalu menghilang seketika tanpa perlu me-*reload* keseluruhan halaman.

### 📚 Knowledge Base Manager (SOP Uploader)
- Berada di dalam Tab "SOP & RAG".
- Unggah file `.txt` atau `.pdf` pedoman perusahaan langsung dari Dasbor secara asinkron (*non-blocking*).
- AI RAG otomatis memuat ulang dokumen baru dan mulai menggunakannya sebagai referensi balasan untuk CS Anda.

---

## 🔒 3. Keamanan & Infrastruktur (SaaS-Ready)

| Fitur | Detail |
|---|---|
| **Isolasi Data Penuh** | Setiap akun perusahaan terisolasi total — data tidak bisa diakses oleh tenant lain. |
| **API Token SHA-256** | Token koneksi ekstensi dienkripsi dengan SHA-256; tidak tersimpan polos di database. |
| **Rate Limiting** | Endpoint AI dilindungi dari *brute-force* dan serangan DDoS via SlowAPI. |
| **Inference < 200ms** | IndoBERT ONNX mengklasifikasikan pesan dalam waktu di bawah 200ms di CPU biasa. |
| **RAG Offline Penuh** | Model Qwen GGUF berjalan sepenuhnya luring — data percakapan **tidak pernah keluar dari server Anda**. |
| **Duplikasi Cerdas** | Sistem in-memory lock mencegah pemrosesan ganda (*race condition*) jika ekstensi mengirim request berulang. |

---

## 🗺️ Arsitektur Sistem (Ringkas)

```
[WhatsApp Web]
    │
    ▼
[Chrome Extension — content.js]
    ├─ POST /analyze → [FastAPI Backend]
    │       ├─ Rule-based classify (< 1ms)
    │       ├─ IndoBERT ONNX classify (< 200ms)
    │       ├─ Simpan ke PostgreSQL (Supabase)
    │       └─ Response: Sentiment + Intent + Health Score + message_id
    │
    └─ GET /suggestion/stream/{id}  ← saat pesan diklik
            ├─ FAISS search (doc/ folder)
            ├─ Qwen GGUF generate (Llama.cpp, streaming)
            └─ Token-by-token ke UI via SSE

[Laravel Dashboard]
    ├─ Real-time via WebSocket (Laravel Reverb)
    ├─ GET /api/messages → tampilkan CRM table
    └─ POST /api/internal/broadcast ← dari FastAPI webhook
```
