# Catatan Pengembangan (Bugs & Features Log)

Dokumen ini melacak riwayat pengembangan sistem Emotext-CRM beserta status penyelesaian dan tindak lanjutnya. Dokumen ini dibagi menjadi 5 kategori utama sesuai dengan tingkat prioritas dan skalabilitas.

---

## 🛠️ 1. Bug Resolved (Telah Diperbaiki)

| No | Komponen | Deskripsi Bug | Dampak | Solusi yang Diterapkan |
|----|----------|---------------|--------|------------------------|
| 1  | Database (FastAPI/Laravel) | Tabel `manual_corrections` tidak pernah dibuat di migrasi Laravel, memicu error 500 (`ProgrammingError`). | **CRITICAL** | Membuat migrasi baru `create_manual_corrections_table` di Laravel yang strukturnya sinkron dengan model SQLAlchemy. |
| 2  | Database (Laravel) | Kolom `user_id` pada tabel `messages` menggunakan relasi `nullOnDelete()`. Data menjadi *orphan* jika perusahaan dihapus. | **CRITICAL** | Mengubah skema migrasi tabel `messages` menjadi `cascadeOnDelete()`. |
| 3  | Backend API (FastAPI) | Endpoint *Health Score* menarik semua (`.all()`) history pesan ke RAM Python (Memory Leak). | **HIGH** | Memindahkan kalkulasi *health score* murni ke tingkat PostgreSQL menggunakan operasi *aggregate* SQL. |
| 4  | Chrome Extension | Logika RAG salah mengetik di kotak pencarian karena hanya menargetkan `div[contenteditable="true"]`. | **MEDIUM** | Memperketat *Query Selector* menjadi `footer.querySelector('div[contenteditable="true"]')`. |
| 5  | Backend API (FastAPI) | Endpoint `GET /dashboard-stats` usang dan membebani *technical debt*. | **LOW** | Endpoint dihapus sepenuhnya dari `main.py`. |
| 6  | Chrome Extension | *Memory leak* saat *scrolling* riwayat WA dan memicu *DDoS* ke FastAPI. | **HIGH** | Menerapkan struktur `Map()` memori batas 500 (LRU Cache) dan menambahkan blok *error handling* UI. |
| 7  | Dashboard (Laravel) | *N+1 Query* di `dashboard.blade.php` karena memanggil relasi *resolver* di dalam *loop*. | **HIGH** | Menambahkan optimasi `with('resolver')` pada *Controller* dan memisahkan builder *query* cloning. |
| 8  | Dashboard UI (Blade) | *Pop-up* "Saran Balasan" terpotong (*clipping*) akibat `overflow-hidden`. | **LOW** | Mengubah desain *pop-up* dari `absolute` menjadi `inline` (efek *accordion*) dan menginjeksi CSS *backdrop blur*. |
| 9  | Dashboard Analytics | *Pie Chart* hanya menampilkan warna abu-abu dan grafik tidak menyesuaikan dimensi (*blank*). | **MEDIUM** | Mengembalikan perhitungan metrik Pesan dan menambahkan *event trigger* `resize` pada *Alpine.js*. |
| 10 | Keamanan (Laravel) | Route `messages.resolve` (PATCH) berada di luar perlindungan middleware `auth`. | **CRITICAL** | Memindahkan route `messages.resolve` ke dalam grup `Route::middleware('auth')`. |
| 11 | Backend API (FastAPI) | Terjadi *Race Condition* saat Ekstensi ganda (*double fetch*), membuat data duplikat. | **CRITICAL** | Membangun sistem "In-Memory Lock/Cache" dengan `threading.Lock()` dalam jeda 5 detik. |
| 12 | Dashboard (Blade) | Kartu "Avg Confidence" dirender ganda di dua bagian Analytics. | **LOW** | Menghapus duplikat dan mengganti metrik dengan "Neutral Contacts". |
| 13 | Chrome Extension | Fungsi `injectSuggestion` menggunakan API lama `document.execCommand('insertText')` (didepresiasi). | **LOW** | Bermigrasi menggunakan `DataTransfer` dan `ClipboardEvent('paste')` modern. |
| 14 | Ekstensi & FastAPI | Nilai `admin_id` di-hardcode ke "admin_01". | **MEDIUM** | Mengambil ID secara dinamis dari `str(auth["user_id"])` JWT token, menghapus persyaratan `admin_id` di Payload. |
| 15 | Ekstensi & Dashboard | Selektor CSS WA Web bersifat *hardcoded* di `content.js`, rawan mati jika WA Web di-update. | **MEDIUM** | Membangun sistem **Remote Config**. Ekstensi mengunduh selektor secara dinamis dari `config/extension.php` Laravel. |
| 16 | Database | **Token API Tersimpan Polos:** Token API ekstensi saat ini tersimpan di `users` secara *plain text*. | **HIGH** | Menerapkan enkripsi Hashing Token (SHA-256) untuk keamanan skala *Enterprise*. |
| 17 | Database | **ManualCorrection Tanpa Timestamps:** Model SQLAlchemy `ManualCorrection` tidak memiliki `created_at` / `updated_at`. | **LOW** | Menambahkan dan menyinkronkan kolom timestamps ke dalam skema basis data. |
| 18 | Backend API (FastAPI) | **QueuePool Exhaustion (Timeout 500)** karena *background task* RAG saling mengunci koneksi DB saat menunggu giliran antrean Llama.cpp. | **CRITICAL** | Mengubah arsitektur RAG menjadi *On-Demand* murni (tanpa *background task*) & menjalankan inferensi LLM *sebelum* membuka koneksi Database. |
| 19 | Ekstensi | **Badge Kesehatan (Kuning) Hilang:** Indikator *health bar* sulit terlihat karena tertutup atribut `overflow` dan kurang tebal. | **MEDIUM** | Menebalkan bar (6px) ditambah efek *glow*, serta menyuntikkan *Sidebar Badge* melingkar langsung ke avatar foto profil pengguna. |
| 20 | Backend API (FastAPI) | **Dead Code pgvector & KnowledgeBase:** Model `KnowledgeBase` dan dependency `pgvector` tetap ada meski sistem sudah beralih ke RAG offline FAISS. Menyebabkan test SQLite crash karena tipe `JSONB`. | **HIGH** | Menghapus `KnowledgeBase`, `pgvector`, dan semua referensinya. Sistem RAG kini 100% berbasis file lokal + FAISS. |
| 21 | Backend AI (FastAPI) | **PyTorch Fallback Dead Code:** Blok `MultiTaskIndoBERT` PyTorch di `ai_service.py` tidak pernah dieksekusi karena file ONNX selalu tersedia, namun tetap dimuat dan memboroskan import. | **MEDIUM** | Menghapus seluruh blok PyTorch dan menyederhanakan ke ONNX Runtime murni. Mengurangi ukuran dependensi signifikan (tidak perlu `torch`). |

---

## ✨ 2. Fitur Resolved (Telah Diimplementasikan)

| No | Komponen | Nama Fitur | Deskripsi Pekerjaan |
|----|----------|------------|---------------------|
| 1  | Dashboard (Blade) | **Perombakan UI Daftar Kontak (Accordion CRM View)** | Merombak "Sample Data" menjadi "Daftar Kontak" yang dikelompokkan berdasarkan nomor/nama WA (`sender_id`) dengan efek *Accordion*. |
| 2  | Dashboard (Controller) | **Pemisahan Metrik Ganda (Dual Metrics)** | Memecah kartu statistik Dasbor menjadi **Contact Analytics** dan **Message Analytics**. |
| 3  | Database (Laravel) | **Skema Langganan (SaaS Trial System)** | Membuat migrasi kolom `trial_ends_at`, `subscription_status`, dan `company_name` di tabel `users`. |
| 4  | Database (Laravel) | **Skema Isolasi Data Perusahaan** | Membuat migrasi `user_id` di tabel `messages` agar setiap data terikat pada entitas Perusahaan yang berbeda. |
| 5  | Website (Blade) | **Landing Page Produk SaaS Terintegrasi** | Membuat halaman utama modern (`/`) menggunakan Tailwind CSS memuat Hero Section, Bento Grid, dan Pricing. |
| 6  | Backend API (FastAPI) | **Persistent Logging & DevOps** | Migrasi dari `print()` ke modul `logging` Python untuk merekam ke `app.log` dengan format standar. |
| 7  | Keamanan (API) | **Rate Limiting (SlowAPI & Throttle)** | Mencegah eksploitasi dan serangan *brute-force* pada *endpoint* AI FastAPI dan otentikasi Laravel. |
| 8  | Dashboard (Laravel) | **Koneksi Real-Time (WebSockets)** | Menggunakan Laravel Reverb & Echo untuk memunculkan *Pop-up Toast* dan audio seketika jika ada keluhan masuk tanpa perlu *refresh*. |
| 9  | Backend API (FastAPI) | **Async Database Driver & Connection Pooling** | Migrasi fungsi SQLAlchemy ke arsitektur *async* dengan `asyncpg` untuk optimasi dan penanganan ribuan *request* bersamaan. |
| 10 | Backend API (FastAPI) | **Sentralisasi Logika Smart Suggestion / RAG** | AI merekam saran langsung ke DB untuk Dasbor, sehingga sistem saran tidak terduplikasi. |
| 11 | NLP Model (FastAPI) | **Optimasi Latensi IndoBERT (PyTorch ke ONNX)** | Melakukan kompilasi graf dan format bobot dari model PyTorch menjadi format `ONNX`, memangkas respon CPU dari ~2 detik ke ~100 ms. |
| 12 | NLP Model (FastAPI) | **Offline RAG Murni (Llama.cpp + FAISS)** | Menghapus integrasi cloud, membaca basis pengetahuan murni dari folder lokal `doc/` dan menjalankan generasi RAG via GGUF secara luring demi kerahasiaan data tingkat militer. |
| 13 | Ekstensi & API | **Real-Time Streaming Animation (SSE)** | Mengganti *loading* balasan RAG yang stagnan dengan mekanisme *Server-Sent Events (SSE)* agar agen melihat AI mengetik (Token-by-Token) secara seketika (*instant-feedback*). |
| 14 | Seluruh Sistem | **Restrukturisasi Monorepo (Professional Grade)** | Memisahkan kode produksi dari tes (`tests/`), memusatkan semua model AI ke `models/`, menghapus *dead code* pgvector dan PyTorch, membersihkan `requirements.txt` dari 6 dependensi yang tidak terpakai di runtime. |
| 15 | NLP Model | **Keterbatasan Pemahaman IndoBERT pada Sarkasme/Slang:** Model NLP kesulitan membaca sarkasme dan singkatan ekstrem. | **[FIXED]** Telah dilakukan *Transfer Learning / Fine-Tuning* ulang (v2.0) dengan 4.015 baris data sintetis. Akurasi meningkat drastis. Sisa *edge-cases* akan ditangani lewat *Manual Correction*. |

---

## 🚨 3. Bug Ditemukan (Open Issues)

*Belum ada bug terbuka saat ini. Sistem dalam status **Stable / Release Ready**.*

---

## 💡 4. Fitur Minor (Peningkatan Skalabilitas)

*(Semua target optimasi skalabilitas dan hutang teknis telah ditangani dengan baik dan dipindahkan ke seksi Resolusi).*

---

## 🚀 5. Fitur Major (Pengembangan V2.0)

Fitur-fitur ekspansi masif yang mengubah nilai jual (*selling point*) aplikasi di mata pelanggan korporat.

1. **Dashboard Analytics Lanjutan 📊**
   - **Tujuan:** Menambahkan analitik prediktif dan laporan konversi.
2. **Multi-Channel Integration 🌐**
   - **Tujuan:** Mendukung platform selain WhatsApp, seperti Instagram DM atau Telegram.
3. **Ekstraksi Histori Chat Awal (Initial Sync) 🔄**
   - **Tujuan:** Menambahkan fungsionalitas di Ekstensi untuk me-*load* 50 obrolan terakhir pelanggan (tarik mundur) ke backend AI `/api/v1/analyze-batch`, sehingga metrik *Health Score* pelanggan bisa langsung terbaca sejak hari pertama mendaftar.
4. **Visualisasi Komparasi Tanggal & Word Cloud di Analytics 📊**
   - **Tujuan:** Menambahkan kemampuan filter tanggal lanjutan (*Date Picker*: Hari Ini, Minggu Lalu, Bulan Lalu) serta melatih *Backend* NLP untuk mengekstrak Subjek Komplain (misal: pengiriman, pelayanan, produk rusak) menjadi grafik kata *Word Cloud*.
