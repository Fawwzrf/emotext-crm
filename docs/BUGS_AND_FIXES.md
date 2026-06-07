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

Sebagai sistem yang sudah stabil (*Production-Grade* dengan *100% Test Coverage*), berikut adalah 4 rekomendasi utama untuk fase penyempurnaan (V2.0) guna menunjang skala bisnis:

### 1. Ekstraksi Histori Chat Awal (Initial Sync)
- **Kondisi Saat Ini:** Ekstensi hanya mulai menghitung skor CRM ketika ada pesan *baru* yang masuk saat ekstensi aktif (atau saat _chat_ baru saja dirender di layar). Database *fresh* akan selalu memunculkan angka netral 70.
- **Rekomendasi:** Tambahkan tombol **"Sync History"** di antarmuka popup ekstensi. Saat ditekan, ekstensi akan melakukan *auto-scroll* ke atas untuk mengambil 50 pesan terakhir dari UI WA, lalu mengirimkannya secara massal ke endpoint baru `/api/v1/analyze-batch`. Ini berguna agar pengguna baru bisa langsung melihat *Health Score* asli pelanggannya sejak hari pertama berlangganan.

### 2. Koneksi Real-Time (WebSockets) untuk Dashboard
- **Kondisi Saat Ini:** Admin atau Manajer yang memantau Dasbor Laravel harus me-refresh halaman (atau mengandalkan Livewire polling) untuk melihat apakah ada komplain masuk yang butuh prioritas.
- **Rekomendasi:** Integrasikan **Laravel Reverb / Pusher WebSockets**. Ketika FastAPI mendeteksi pesan masuk bersentimen *"Negative"*, FastAPI akan menembak *webhook* ke Laravel, lalu Laravel memancarkan *event* ke *browser* admin. Notifikasi peringatan (Toast) dapat muncul secara *real-time* di layar manajer tanpa *refresh*.

### 3. Ketahanan DOM Selektor Ekstensi (Remote Config)
- **Kondisi Saat Ini:** Selektor CSS (*classes*, `data-testid`) di dalam `content.js` bersifat *hardcoded*. Jika pihak Meta/WhatsApp mengubah antarmuka WhatsApp Web secara tiba-tiba, ekstensi ini bisa mati total (*broken*).
- **Rekomendasi:** Buat endpoint API publik di Laravel (`/api/extension/config`). Ekstensi akan selalu *fetch* konfigurasi selektor CSS terbaru setiap kali Chrome dibuka. Jika Meta memperbarui WA Web, Anda hanya perlu mengubah *string* selektor di *database* server, dan ekstensi di seluruh komputer klien akan langsung sembuh tanpa perlu pembaruan ekstensi via Google Chrome Store.

### 4. Beralih ke Async Database Driver & Connection Pooling di FastAPI
- **Kondisi Saat Ini:** FastAPI menggunakan `create_engine` standar. Kita telah melihat bahwa koneksi ke *Supabase PostgreSQL* bisa mengalami *Timeout* (SQLSTATE 08006). Jika ekstensi mengirim 100 *request* serentak, koneksi DB bisa tersendat.
- **Rekomendasi:** Aktifkan **IPv4 Connection Pooling (PgBouncer)** di pengaturan Dasbor Supabase. Selain itu, migrasi *SQLAlchemy* di `database.py` agar menggunakan *driver* asinkron (`asyncpg` alih-alih `psycopg2`). Ini akan membebaskan *Event Loop* FastAPI untuk menangani ribuan *request* tanpa menunggu antrean *database*.
