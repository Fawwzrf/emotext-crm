# Emotext-CRM (WA-CRM Intelligence)

![Version](https://img.shields.io/badge/version-1.0.0_Stable-blue.svg)
![React](https://img.shields.io/badge/Frontend-React.js-61DAFB?logo=react)
![FastAPI](https://img.shields.io/badge/Backend-FastAPI-009688?logo=fastapi)
![AI](https://img.shields.io/badge/AI_Engine-IndoBERT_%7C_Llama.cpp-FF9900?logo=huggingface)
![Database](https://img.shields.io/badge/Database-SQLite-003B57?logo=sqlite)

**Emotext-CRM** adalah platform *Customer Relationship Management* (CRM) inovatif berskala *Enterprise* yang mengintegrasikan kecerdasan buatan (AI) secara langsung ke dalam antarmuka WhatsApp Web. 

Dirancang khusus untuk tim *Customer Service*, sistem ini secara instan menganalisis sentimen, mendeteksi intensi pelanggan, dan memberikan balasan otomatis berlandaskan *Standard Operating Procedure* (SOP) perusahaan menggunakan arsitektur mutakhir **Offline Retrieval-Augmented Generation (RAG)**.

Sistem beroperasi dengan model bisnis **SaaS (Software as a Service)**, memisahkan ekstensi klien yang super ringan (< 5MB) dari mesin AI (*Backend*) raksasa yang aman di server cloud. Manajemen platform ini dilakukan melalui **Unified Web Portal** berarsitektur *Single Page Application* (SPA) untuk pengalaman *real-time* tanpa jeda *loading*.

---

## ✨ Fitur Unggulan (Core Features)
- **🧠 Localized NLP Engine (IndoBERT v2.0):** Dilatih khusus (Fine-Tuned) dengan >4.000 data sintesis bahasa jalanan, singkatan ekstrem, dan sarkasme. Akurasi klasifikasi Sentimen dan Intensi mencapai tingkat manusia.
- **📚 Offline RAG Knowledge Base:** Tidak menggunakan OpenAI. Menjalankan model Llama GGUF dan FAISS secara *offline* di *Backend* untuk membaca dokumen SOP perusahaan, menjamin **100% Data Privacy** (kerahasiaan data tingkat militer).
- **⚡ Real-Time Streaming (SSE):** Generasi jawaban AI ditampilkan secara *real-time* (token-by-token) kepada agen *Customer Service* di layar WhatsApp Web.
- **🛠️ Self-Learning Memory (Manual Correction):** Admin dapat memperbaiki klasifikasi yang salah. AI akan mengingat koreksi ini selamanya menggunakan lapisan *Memory Cache* SQLite.

---

## Arsitektur Sistem (Client-Server Architecture)

Untuk melindungi *Intellectual Property* model AI dan menjaga performa perangkat pelanggan (agar tidak membebani RAM laptop), Emotext-CRM memisahkan komputasi berat ke *Cloud*.

```mermaid
graph TD
    subgraph Client ["Client Side (User)"]
        WA["WhatsApp Web"]
        Ext["Emotext Chrome Extension"]
        WA <-->|"DOM Scraping & UI Inject"| Ext
    end

    subgraph Internet ["Cloud Network"]
        Auth["Email & Password Auth"]
        API["REST API / SSE Streams"]
    end

    subgraph Server ["Cloud AI Backend (Hugging Face Spaces)"]
        FastAPI["FastAPI Server"]
        
        subgraph NLP ["NLP Classification"]
            IndoBERT[("IndoBERT ONNX")]
        end
        
        subgraph RAG ["Response Generation"]
            FAISS[("FAISS Vector DB")]
            Llama[("Llama.cpp GGUF")]
        end
        
        DB[("SQLite/Postgres DB")]
    end

    Ext -->|"Login"| Auth
    Auth --> FastAPI
    Ext <-->|"Send Chat & Receive Analysis"| API
    API <--> FastAPI
    FastAPI --> IndoBERT
    FastAPI --> FAISS
    FAISS --> Llama
    Llama --> FastAPI
    FastAPI <--> DB
```

---

## Alur Pipeline Kecerdasan Buatan (AI Flow)

Proses yang terjadi di dalam *Backend* dalam satuan milidetik ketika ada pesan masuk:

```mermaid
sequenceDiagram
    participant User as Pelanggan (WhatsApp)
    participant CS as Agen (Chrome Ext)
    participant API as FastAPI Backend
    participant NLP as IndoBERT (ONNX)
    participant DB as SQLite Memory
    participant RAG as FAISS + Llama

    User->>CS: "Woy kpn brg dikirim, nipu lu ya!"
    CS->>API: POST /api/v1/analyze
    
    API->>DB: Cek Manual Correction?
    DB-->>API: Tidak Ditemukan
    
    API->>NLP: Prediksi Teks
    NLP-->>API: {Intent: COMPLAINT, Sentiment: NEGATIVE}
    
    API->>CS: Return Klasifikasi (Muncul Badge Merah di UI)
    
    CS->>API: Generate Balasan Cerdas (RAG)
    API->>RAG: Cari SOP Pengiriman & Generate Text
    RAG-->>API: "Mohon maaf atas keterlambatan... (Streaming)"
    API-->>CS: SSE Real-Time Typing...
```

---

## Panduan Instalasi & Deployment (Bagi Tim & Pengguna)

Kami menyediakan 2 (dua) opsi panduan instalasi yang sangat terperinci tergantung pada infrastruktur yang ingin Anda gunakan. Silakan baca dan ikuti salah satu panduan berikut:

1. **[Panduan Mode Cloud / Hugging Face](SETUP_HF_MODEL.md)** *(Sangat Disarankan)*
   Jalur *default* tanpa perlu men-*download* model raksasa. Menjalankan *Web Dashboard* secara lokal dengan menarik *Resource* AI dari Hugging Face Cloud.

2. **[Panduan Mode Lokal / Offline](SETUP_LOCAL_MODEL.md)** *(Advanced)*
   Jalur *offline* penuh untuk menjaga kerahasiaan data di *on-premise* Anda atau memanfaatkan akselerasi GPU lokal (NVIDIA). Membutuhkan unduhan model fisik (GGUF & ONNX) dan proses *running* Backend Python secara mandiri.

*(Bagi Pelanggan Akhir: Hubungi administrator sistem Anda untuk mendapatkan URL Dasbor dan Ekstensi terkompresi).*

---

## Panduan Pengaktifan & Login

Sistem diamankan dengan kredensial berlangganan untuk mencegah penggunaan pihak ketiga yang tidak sah.

1. Buka [WhatsApp Web (web.whatsapp.com)](https://web.whatsapp.com) di Google Chrome Anda.
2. Saat pertama kali dibuka, layar *pop-up* Emotext-CRM akan muncul meminta otentikasi.
3. Masukkan **Alamat Email** dan **Password** langganan Anda.
4. Setelah berhasil *Login*, sistem akan aktif secara permanen dan secara ajaib menyulap tampilan WhatsApp Web Anda menjadi dasbor CRM kelas atas!

---

*Emotext-CRM v1.0 - Stable Release. Developed by Fawwaz.*
