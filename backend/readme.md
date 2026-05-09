
## Jalankan Server Backend
Setelah kode disimpan, kembali ke terminal (di dalam folder backend) dan jalankan perintah ini:

```Bash
uvicorn main:app --reload
```

---
## Lain lain
Pastikan Library Sudah Terinstal
Buka terminal Anda, pastikan Anda berada di dalam folder backend dan virtual environment (venv) sudah aktif. Instal library yang dibutuhkan dengan perintah ini:

```Bash
pip install fastapi uvicorn pydantic
```

---

## Aktifin VE
Aktifkan Virtual Environment
Karena posisi terminal Anda saat ini ada di E:\coding\CRM\emotext-crm\backend>, dan folder .venv Anda ada di satu tingkat di atasnya (emotext-crm), ketik perintah berikut lalu tekan Enter:

```PowerShell
..\.venv\Scripts\activate
```

---

Install SQLAlchemy
Setelah tulisan (.venv) muncul, itu menandakan Anda sudah berada di dalam lingkungan proyek Anda. Sekarang Anda bisa menginstal library-nya dengan aman:

```PowerShell
pip install sqlalchemy
```

---
## Buat buka emotext.db
SQLite Viewer (oleh Florian Klampfer) – Sangat simpel, cukup klik file database langsung terbuka seperti Excel.