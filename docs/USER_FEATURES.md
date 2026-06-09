# 🚀 Fitur Pengguna Emotext-CRM

Dokumen ini berisi rangkuman seluruh fitur yang tersedia bagi pengguna (Admin / Customer Service) di sistem **Emotext-CRM**. Sistem dirancang menjadi dua ekosistem utama: Ekstensi WhatsApp Web dan Dasbor Analitik.

---

## 🟢 1. Ekstensi Chrome (WhatsApp Web)
Fitur ini beroperasi langsung di dalam layar WhatsApp Web, menyatu dengan antarmuka yang sudah familiar bagi tim Customer Service.

### 🏷️ Real-Time Badges (Klasifikasi Otomatis)
Setiap pesan masuk akan dianalisis oleh model AI (IndoBERT ONNX) dalam hitungan milidetik.
*   **Badge Sentimen**: Lencana berwarna pada balon pesan yang mendeteksi emosi pelanggan (Positif/Hijau, Negatif/Merah, Netral/Abu-abu).
*   **Badge Intensi**: AI mengklasifikasikan tujuan pesan, seperti *Order* (Pemesanan), *Complaint* (Keluhan), *Inquiry* (Tanya-tanya), atau *Other*.

### 💡 Click-to-Suggest (RAG AI)
Sistem tidak akan mengganggu antarmuka dengan *pop-up* yang tiba-tiba. Balon pesan yang memiliki saran balasan akan ditandai.
*   **Cara kerja**: Anda cukup **mengklik balon pesan WhatsApp** dari pelanggan. Sebuah panel hijau elegan akan muncul.
*   **Saran Balasan Cerdas**: Panel ini berisi kalimat balasan (*draft*) yang diracik AI berdasarkan pedoman SOP/Pengetahuan Perusahaan Anda (RAG).
*   **Satu Klik Sisipkan**: Klik panel saran tersebut, dan teksnya otomatis masuk ke kolom pengetikan WhatsApp Anda.

### 💖 Customer Health Score
Di daftar obrolan sebelah kiri (*sidebar* chat list), AI menampilkan lencana angka bulat di atas foto profil pelanggan.
*   **Warna Merah (< 50)**: Pelanggan berisiko tinggi / marah / komplain berturut-turut. Harus segera dilayani!
*   **Warna Kuning (50 - 79)**: Pelanggan dalam tahap waspada atau netral.
*   **Warna Hijau (≥ 80)**: Pelanggan loyal dengan sentimen yang sangat baik.

### 🔧 Manual Correction (Koreksi Admin)
Jika prediksi klasifikasi AI dirasa kurang pas, klik saja badge klasifikasi tersebut.
*   Pilih opsi sentimen/intensi yang benar dari *dropdown*.
*   Data ini dikirim ke *Memory Layer*. AI akan langsung mengingat koreksi tersebut untuk percakapan yang sama ke depannya.

---

## 📊 2. Dasbor Analitik (Web Portal)
Pusat kontrol bagi tim *Marketing* atau Manajer CRM untuk memantau performa layanan pelanggan secara keseluruhan.

### 🔔 Real-Time WebSockets & Auto Reload
*   **Live Toast Notifications**: Muncul notifikasi pop-up seketika (tanpa me-refresh halaman) setiap kali ada pelanggan yang mengirim pesan baru di WhatsApp.
*   **Audio Alert**: Jika pesan yang masuk terdeteksi sebagai **Keluhan (Negatif)**, dasbor akan membunyikan alarm *beep* agar admin segera merespons.
*   **Auto-Reload**: Setelah notifikasi muncul, tabel dan grafik dasbor akan diperbarui secara otomatis.

### 📈 Visualisasi Data Sentimen
*   **Sentiment Trend (Line Chart)**: Grafik garis yang memantau pergerakan jumlah pesan Positif, Negatif, dan Netral jam demi jam. Sangat berguna untuk melihat di jam berapa komplain memuncak.
*   **Distribution (Pie Chart)**: Grafik donat persentase keseluruhan sentimen hari ini.

### 📇 CRM Table & Customer Tracking
*   Menampilkan daftar seluruh nomor kontak yang berinteraksi.
*   Melihat skor metrik *Health Score* keseluruhan per pengguna.
*   **View Details**: Tombol untuk melihat histori pesan yang masuk, lengkap dengan persentase *Confidence* AI.

### 🛠️ Fitur Resolusi Pesan (Mark as Resolved)
*   Manajer/Admin dapat menyalin *draft* balasan AI langsung dari dasbor web.
*   Manajer dapat menekan tombol **"Tandai Selesai"** (`Resolved`). Fitur ini mengubah status pesan agar tim internal tahu bahwa komplain/masalah pelanggan terkait sudah ditangani.

---

## 🔒 3. Keamanan & Performa Infrastruktur (SaaS Ready)
Fitur tidak kasat mata namun krusial yang memastikan keamanan dan kelancaran bisnis Anda:

*   **Isolasi Data Penuh**: Akun Anda dan akun perusahaan lain (SaaS) dipisahkan secara total. Ekstensi Anda tidak akan bisa mengakses riwayat pesan perusahaan lain.
*   **API Token Security (SHA-256)**: Kunci koneksi ekstensi (Token API) telah dienkripsi secara asimetris sehingga terlindungi dari pencurian *hacker*.
*   **Infrastruktur Ultra Cepat**: Penggunaan basis data asinkron (*asyncpg*) dan model *Machine Learning* yang terkompresi (ONNX) memungkinkan AI membalas dalam waktu di bawah 100 milidetik dan memproses riwayat panjang percakapan secara kilat tanpa *error*.
