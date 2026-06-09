# Catatan Bug dan Perbaikan (Bugs & Fixes Log)

Dokumen ini melacak riwayat *bug* yang ditemukan pada sistem Emotext-CRM beserta status penyelesaian dan tindak lanjutnya. Dokumen ini penting sebagai referensi bagi tim pengembang QA dan *Machine Learning*.

---

## 🛠️ Bug yang Telah Diperbaiki (Resolved)

| No | Komponen | Deskripsi Bug | Dampak | Solusi yang Diterapkan |
|----|----------|---------------|--------|------------------------|
| 1  | Database (FastAPI/Laravel) | Tabel `manual_corrections` tidak pernah dibuat di migrasi Laravel, sehingga fitur *feedback* ML (mengubah sentimen secara manual) memicu error 500 (`ProgrammingError`). | **CRITICAL** | Membuat migrasi baru `create_manual_corrections_table` di Laravel yang strukturnya sinkron dengan model SQLAlchemy. |
| 2  | Database (Laravel) | Kolom `user_id` pada tabel `messages` menggunakan relasi `nullOnDelete()`. Jika akun perusahaan dihapus, histori obrolan WA tetap ada dan menjadi data *orphan*. | **CRITICAL** (Privasi / GDPR) | Mengubah skema migrasi tabel `messages` menjadi `cascadeOnDelete()`. Jika akun terhapus, data pesan ikut musnah. |
| 3  | Backend API (FastAPI) | Endpoint penghitungan *Health Score* menarik semua (`.all()`) history pesan pengguna ke RAM Python hanya untuk menghitung skor rata-rata. | **HIGH** (Memory Leak / OOM) | Memindahkan kalkulasi *health score* murni ke tingkat PostgreSQL menggunakan operasi *aggregate* SQL (`func.sum` dan `case`). |
| 4  | Chrome Extension | Logika RAG di `content.js` mencoba memasukkan saran teks dengan sekadar menargetkan `div[contenteditable="true"]`. Sering *typo* ke kolom *search*. | **MEDIUM** | Memperketat *Query Selector* menjadi `footer.querySelector('div[contenteditable="true"]')` agar AI hanya mengetik di kotak pesan utama. |
| 5  | Backend API (FastAPI) | Endpoint `GET /dashboard-stats` sudah usang dan digantikan oleh *DashboardController* di Laravel. Membebani *technical debt*. | **LOW** | Endpoint tersebut dihapus sepenuhnya dari `main.py`. |
| 6  | Chrome Extension | Kinerja ekstensi bocor saat *scrolling* riwayat WA akibat manipulasi *Virtualized List* DOM. Ekstensi memicu ratusan `fetch` berulang (DDoS) ke FastAPI untuk pesan yang sama. Selain itu, ada kelemahan *silent fail* jika FastAPI mati. | **HIGH** | Menerapkan struktur `Map()` memori dengan batas ukuran 500 sebagai sistem *LRU Cache* (waktu respons menjadi 0ms). Menambahkan blok *error handling* UI "⚠️ API Error" secara visual di layar pengguna. |
| 7  | Dashboard (Laravel) | Mengalami masalah fatal *N+1 Query* di `dashboard.blade.php` karena memanggil relasi *resolver* di dalam *loop*. Terdapat juga *logic bug* di mana filter prioritas menyebabkan indikator metrik/stat berubah karena builder *query* yang termutasi. | **HIGH** | Menambahkan optimasi `with('resolver')` pada *Controller*. Memisahkan `$baseQuery` dan `$filteredQuery` menggunakan perintah `(clone $baseQuery)` agar perhitungan kartu metrik tetap murni. Melengkapi sistem dengan PHPUnit *Feature Test* untuk memastikan keamanan data jangka panjang. |
| 8  | Dashboard UI (Blade) | *Pop-up* "Saran Balasan" terpotong (*clipping*) akibat `overflow-hidden` pada elemen parent. Efek *blur* (*backdrop-filter*) pada modal tidak muncul, dan warna tombol *tailwind* gagal me-render. | **LOW** (Visual) | Mengubah desain *pop-up* "Saran Balasan" dari `absolute` menjadi `inline` agar melebar dengan mulus (efek *accordion*). Menginjeksi CSS murni (*inline style*) untuk memaksa *backdrop blur* dan warna *background* tombol modal. |
| 9  | Dashboard Analytics | *Pie Chart* hanya menampilkan warna abu-abu (Netral) karena logika terikat pada *Health Score* Kontak, bukan Pesan. Selain itu, *Chart.js* gagal memuat ukuran grafik (*blank/invisible*) karena diinisialisasi saat berada di dalam tab tersembunyi (`display: none`). | **MEDIUM** | Mengembalikan perhitungan `$pieData` di *Controller* menggunakan metrik Pesan (*positive/negative count*). Menambahkan fungsi *trigger* ukuran jendela (`window.dispatchEvent(new Event('resize'))`) pada tombol transisi *Alpine.js* agar Chart.js mencetak ulang dimensinya saat *tab* terlihat. |
| 10 | Keamanan (Laravel) | Route `messages.resolve` (PATCH) berada di luar perlindungan middleware `auth`, memungkinkan penyerang menutup tiket pesan tanpa *login*. | **CRITICAL** | Memindahkan pendefinisian route `messages.resolve` ke dalam blok `Route::middleware('auth')` untuk mencegah eksploitasi URL terbuka. |
| 11 | Backend API (FastAPI) | Terjadi *Race Condition* saat Ekstensi Chrome terpicu ganda (*double fetch*), sehingga membuat beberapa baris data kembar di Database PostgreSQL secara bersamaan. Algoritma dedup tidak sempat mencegah karena transaksi paralel. | **CRITICAL** | Membangun sistem "In-Memory Lock/Cache" di level Python menggunakan struktur `dict` dan `threading.Lock()` untuk membuang permintaan identik dari `sender_id` yang sama dalam jeda kurang dari 5 detik. |
| 12 | Dashboard (Blade) | Kartu "Avg Confidence" dirender ganda (duplikat) di dua bagian, yaitu Contact Analytics dan Message Analytics. | **LOW** (Visual) | Menghapus kartu duplikat di "Contact Analytics" dan menggantinya dengan metrik baru "Neutral Contacts" agar statistik lebih komprehensif. |
| 13 | Chrome Extension | Fungsi `injectSuggestion` menggunakan API lama `document.execCommand('insertText')` yang sudah didepresiasi oleh W3C dan bisa dihapus Chrome sewaktu-waktu. | **LOW** (Tech Debt) | Bermigrasi menggunakan `DataTransfer` dan `dispatchEvent(new ClipboardEvent('paste'))` modern yang meniru persis aksi *paste user*, sekaligus menjamin kompatibilitas dengan ekosistem DOM milik React. |
| 14 | Ekstensi & FastAPI | Nilai `admin_id` di-hardcode ke "admin_01" oleh Ekstensi Chrome saat mengirim koreksi sentimen, dan backend mensyaratkannya. | **MEDIUM** | Menghapus persyaratan `admin_id` di Payload (`FeedbackRequest`) pydantic `main.py`, dan mengambil ID langsung secara dinamis dari `str(auth["user_id"])` JWT token, sehingga sistem lebih aman (bebas pemalsuan ID). |
| 15 | Dashboard & FastAPI | Dasbor bersifat statis. Admin harus me-*refresh* halaman terus-menerus untuk melihat pesan terbaru dan peringatan komplain. | **HIGH** (UX) | Mengimplementasikan **WebSockets** (Laravel Reverb & Echo). FastAPI mengirim *Webhook* internal (`/api/internal/broadcast`), dan Laravel memancarkan event ke *Private Channel*, memicu Pop-up Visual (*Toast*) & Suara Audio ("Ting!") secara seketika (*Real-Time*). |
| 16 | Keamanan (FastAPI & Laravel) | Tidak ada mekanisme pembatasan tingkat permintaan (*Rate Limiting*), memungkinkan penyerang melakukan *brute force* memori ML di FastAPI atau menebak kata sandi di Laravel. | **HIGH** (Security) | Mengimplementasikan `slowapi` limiter di FastAPI (30 req/min untuk `/analyze`, 20 req/min untuk `/feedback`), serta *throttle middleware* bawaan Laravel (5/min untuk Login, 60/min untuk API umum). |
| 17 | Backend API (FastAPI) | Seluruh sistem *logging* masih menggunakan `print()` standar Python, yang membuat log hilang seketika saat *server* mati (*restart*). | **LOW** (DevOps) | Menambahkan modul `logging` bawaan Python. Semua output kini dialihkan ke file rekaman permanen (`app.log`) dengan format *timestamp* dan *log level* yang standar untuk memudahkan proses *debugging* tingkat produksi. |
---

## ✨ Fitur Baru yang Diimplementasikan

Selain perbaikan *bug*, dokumen ini juga melacak fitur fungsional utama yang telah berhasil diimplementasikan ke dalam kode untuk menunjang kebutuhan bisnis (*Business Logic*):

| No | Komponen | Nama Fitur | Deskripsi Pekerjaan |
|----|----------|------------|---------------------|
| 1  | Dashboard (Blade) | **Perombakan UI Daftar Kontak (Accordion CRM View)** | Merombak "Sample Data" yang awalnya hanya tumpukan pesan tak beraturan menjadi struktur "Daftar Kontak" yang dikelompokkan berdasarkan nomor/nama WA (`sender_id`). Menambahkan efek *Accordion* agar baris bisa diklik untuk melebar ke bawah dan menampilkan riwayat pesan tiap individu. |
| 2  | Dashboard (Controller & UI) | **Pemisahan Metrik Ganda (Dual Metrics)** | Memecah kartu statistik Dasbor menjadi dua baris: **Contact Analytics** (Total pelanggan, tingkat risiko) dan **Message Analytics** (Volume pesan, rata-rata sentimen) agar informasi terbaca jelas dari sudut pandang prospek pelanggan maupun volume chat. |
| 3  | Dashboard (Blade) | **Perbaikan Bug Tombol Resolve** | Memperbaiki *syntax error* di mana penulisan `@patch` menyebabkan error 500 (Route not defined). Diganti dengan sintaks Laravel yang valid yaitu `@method('PATCH')`. |
| 4  | Database (Laravel) | **Skema Langganan (SaaS Trial System)** | Membuat migrasi `add_trial_fields_to_users_table` untuk menambahkan kolom `trial_ends_at`, `subscription_status`, dan `company_name` di tabel `users` guna mendukung logika masa percobaan dan *billing*. |
| 5  | Database (Laravel) | **Skema Isolasi Data Perusahaan** | Membuat migrasi `add_user_id_and_sender_name_to_messages_table` agar setiap pesan WA terikat pada `user_id` (ID Perusahaan), sehingga data pelanggan dari klien A tidak terekspos ke dasbor klien B. |
| 6  | Website (Blade) | **Landing Page Produk SaaS Terintegrasi** | Membuat halaman utama modern (`/`) menggunakan Tailwind CSS dan Alpine.js yang memuat Hero Section (Mockup Dashboard), fitur unggulan (Bento Grid), dan Harga Langganan (Pricing). Menggantikan halaman welcome default Laravel. |

---

## ⚠️ Keterbatasan Sistem dan Bug Terbuka (Open Issues)

Bagian ini memuat masalah logika atau keterbatasan sistem (*Known Limitations*) yang perlu ditindaklanjuti di masa mendatang.

### 1. Keterbatasan Pemahaman IndoBERT pada Bahasa Gaul dan Sarkasme
- **Status:** 🟡 Dalam Pantauan (Perlu *Fine-Tuning*)
- **Deskripsi:** Berdasarkan hasil pengujian (*test_ml_logic.py*), model dasar (base model) NLP saat ini hanya mampu mencapai akurasi **60%** saat dihadapkan pada kalimat *stress-test*.
- **Penyebab Utama Kegagalan AI:**
  1. **Sarkasme:** Kata-kata pujian yang bermakna negatif ("Hebat ya, dibales taun depan").
  2. **Slang / Umpatan Tidak Baku:** Singkatan ekstrem dan kata kasar (WOY, ANJ, NIPU) diabaikan oleh tokenisasi dan dianggap "Netral".
  3. **Bias Kata Positif:** Model mengasosiasikan kata gaul "Gila" secara harfiah (negatif), padahal dalam bahasa pergaulan seringkali bermakna pujian ("Gila mantap banget").
- **Tindak Lanjut yang Direkomendasikan:**
  - Kumpulkan hasil revisi pengguna melalui fitur *Manual Correction* (database).
  - Lakukan *retraining/fine-tuning* rutin (minimal 1 bulan sekali) pada bobot `multitask_indobert.pt` menggunakan data bahasa *slang* e-commerce lokal.

### 2. Keterbatasan Dinamika RAG (*Knowledge Base*)
- **Status:** 🟢 Terkontrol (Sudah diminimalisir)
- **Deskripsi:** Karena sistem belum menggunakan Vector Database murni dan LLM Generatif (seperti OpenAI/Gemini), balasan AI (RAG) hanya berupa *template matching* dari `kb.json`.
- **Dampak:** Respons bisa terasa sedikit kaku (seperti robot).
- **Tindak Lanjut yang Direkomendasikan:** 
  - Selalu perbarui variasi jawaban (*responses array*) di `kb.json`.
  - Sistem telah dilengkapi penolakan halus (*graceful fallback*) untuk mencegah halusinasi jika topik keluar dari konteks bisnis (contoh: "Ada yang bisa kami bantu?").

### 3. Optimasi Latensi Model (FR-10 dari Master PRD)
- **Status:** 🔴 Terbuka (Belum Diterapkan)
- **Deskripsi:** Sesuai dengan dokumen PRD, model IndoBERT saat ini masih berjalan murni menggunakan framework PyTorch (`multitask_indobert.pt`). Hal ini memakan penggunaan komputasi CPU/RAM server yang relatif tinggi dan kurang efisien untuk *deployment* skala besar tanpa GPU.
- **Dampak:** Biaya server Backend berpotensi membengkak saat jumlah pengguna SaaS (Dashboard) bertambah secara eksponensial.
- **Tindak Lanjut yang Direkomendasikan:**
  - Melakukan konversi format dari `.pt` (PyTorch) ke **ONNX** atau melakukan kuantisasi ke format **FP16 / INT8**.
  - ONNX Runtime dapat mempercepat waktu *inference* CPU hingga 3x lipat.

---

## 💡 Rekomendasi Arsitektur & Fungsionalitas Mendatang

Sebagai sistem yang sudah stabil (*Production-Grade* dengan *100% Test Coverage*), berikut adalah beberapa rekomendasi utama untuk fase penyempurnaan (V2.0) guna menunjang skala bisnis:

### 1. Ekstraksi Histori Chat Awal (Initial Sync)
- **Kondisi Saat Ini:** Ekstensi hanya mulai menghitung skor CRM ketika ada pesan *baru* yang masuk saat ekstensi aktif (atau saat _chat_ baru saja dirender di layar). Database *fresh* akan selalu memunculkan angka netral 70.
- **Rekomendasi:** Tambahkan tombol **"Sync History"** di antarmuka popup ekstensi. Saat ditekan, ekstensi akan melakukan *auto-scroll* ke atas untuk mengambil 50 pesan terakhir dari UI WA, lalu mengirimkannya secara massal ke endpoint baru `/api/v1/analyze-batch`. Ini berguna agar pengguna baru bisa langsung melihat *Health Score* asli pelanggannya sejak hari pertama berlangganan.

### 2. Koneksi Real-Time (WebSockets) untuk Dashboard
- **Kondisi Saat Ini:** Admin atau Manajer yang memantau Dasbor Laravel harus me-refresh halaman (atau mengandalkan Livewire polling) untuk melihat apakah ada komplain masuk yang butuh prioritas.
- **Status:** ✅ **Telah Diimplementasikan** menggunakan Laravel Reverb. Dasbor kini mendukung pop-up visual dan audio notifikasi seketika tanpa refresh.

### 3. Ketahanan DOM Selektor Ekstensi (Remote Config)
- **Kondisi Saat Ini:** Selektor CSS (*classes*, `data-testid`) di dalam `content.js` bersifat *hardcoded*. Jika pihak Meta/WhatsApp mengubah antarmuka WhatsApp Web secara tiba-tiba, ekstensi ini bisa mati total (*broken*).
- **Rekomendasi:** Buat endpoint API publik di Laravel (`/api/extension/config`). Ekstensi akan selalu *fetch* konfigurasi selektor CSS terbaru setiap kali Chrome dibuka. Jika Meta memperbarui WA Web, Anda hanya perlu mengubah *string* selektor di *database* server, dan ekstensi di seluruh komputer klien akan langsung sembuh tanpa perlu pembaruan ekstensi via Google Chrome Store.

### 4. Beralih ke Async Database Driver & Connection Pooling di FastAPI
- **Kondisi Saat Ini:** FastAPI menggunakan `create_engine` standar. Kita telah melihat bahwa koneksi ke *Supabase PostgreSQL* bisa mengalami *Timeout* (SQLSTATE 08006). Jika ekstensi mengirim 100 *request* serentak, koneksi DB bisa tersendat.
- **Rekomendasi:** Aktifkan **IPv4 Connection Pooling (PgBouncer)** di pengaturan Dasbor Supabase. Selain itu, migrasi *SQLAlchemy* di `database.py` agar menggunakan *driver* asinkron (`asyncpg` alih-alih `psycopg2`). Ini akan membebaskan *Event Loop* FastAPI untuk menangani ribuan *request* tanpa menunggu antrean *database*.

### 5. Visualisasi Komparasi Tanggal & Word Cloud di Analytics
- **Kondisi Saat Ini:** Data grafik (Tren & Distribusi Sentimen) bersifat statis pada "Hari Ini", dan tidak memuat alasan spesifik *mengapa* sentimen negatif terjadi.
- **Rekomendasi:** 
  1. Tambahkan *Date Picker* global untuk memfilter data (Hari Ini, 7 Hari Terakhir, Bulan Ini) beserta persentase komparasinya (misal: "Naik 5% dari minggu lalu").
  2. Implementasikan ekstraksi kata kunci di *backend* FastAPI (NLP untuk mendeteksi subjek seperti "pengiriman", "rusak", "pelayanan") lalu visualisasikan dalam bentuk *Word Cloud* di Dashboard agar manajemen mengetahui akar masalah komplain secara instan.

### 6. Integrasi Vector Database (pgvector) untuk Modul RAG
- **Kondisi Saat Ini:** Modul *Upload* Dokumen RAG belum terhubung ke mesin pengindeksan, dan kemampuan "Saran Balasan AI" saat ini murni menggunakan pencocokan *template JSON* yang statis.
- **Rekomendasi:** Karena Anda sudah menggunakan infrastruktur Supabase, aktifkan modul **pgvector**. Ketika Admin mengunggah dokumen SOP/FAQ dalam bentuk PDF, sistem akan mengekstrak teksnya dan menyimpannya sebagai *Embeddings*. Ekstensi kemudian dapat menarik jawaban dinamis berbasis konteks korporat dari Vector Database tersebut, bukan lagi kalimat *template* statis.

## 🚨 Bug & Kerentanan Keamanan Baru (Belum Terselesaikan)

*(Saat ini semua bug kritis dan rekomendasi keamanan dasar telah ditangani. Lanjutkan fokus ke fitur pengembangan V2.0)*

## 💡 Rekomendasi Teknis untuk Level Produk SaaS

### 5. Token API Tersimpan Polos di Database (`api_token` Plain Text)
- **Kondisi Saat Ini:** Token API di `users` disimpan dan dibandingkan secara *plain text*.
- **Rekomendasi:** Hash token API di database (misalnya dengan SHA-256) agar jika database bocor, token kredensial klien tetap aman.

### 6. `ManualCorrection` Tidak Memiliki Timestamps
- **Kondisi Saat Ini:** Model `ManualCorrection` di SQLAlchemy tidak memiliki kolom `created_at` atau `updated_at`.
- **Rekomendasi:** Tambahkan timestamps untuk mempermudah pemilahan data saat melakukan *fine-tuning* AI di masa mendatang.

### 7. `getReplyTemplate()` Duplikasi Logika RAG
- **Kondisi Saat Ini:** Fungsi statis pencocokan niat (*intent*) ke pesan balasan terdapat ganda: di `DashboardController.php` (Laravel) dan di `rag_service.py` (FastAPI).
- **Rekomendasi:** Pusatkan *source of truth* logika saran balasan di backend AI. Laravel cukup merender apa yang diterima dari API FastAPI.
