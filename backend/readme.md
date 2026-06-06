
## Jalankan Server Backend
Setelah kode disimpan, kembali ke terminal (di dalam folder backend) dan jalankan perintah ini:

```Bash
uvicorn main:app --reload
```

---

Buka browser dan akses http://127.0.0.1:8000/docs.

---

## Aktifin VE
Aktifkan Virtual Environment
Karena posisi terminal Anda saat ini ada di E:\coding\CRM\emotext-crm\backend>, dan folder .venv Anda ada di satu tingkat di atasnya (emotext-crm), ketik perintah berikut lalu tekan Enter:

```PowerShell
..\.venv\Scripts\activate
```

## Install requirements
```PowerShell
pip install -r requirements.txt
```

---