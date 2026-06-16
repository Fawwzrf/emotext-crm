/**
 * FILE KONFIGURASI ENVIRONMENT (PENGGANTI .env)
 * ---------------------------------------------------------
 * Karena Chrome Extension murni tidak mendukung file .env,
 * gunakan file ini untuk mengatur URL yang menghubungkan
 * ekstensi Anda dengan server AI (FastAPI) dan Dashboard (Laravel).
 */

const ENV = {
    // 1. URL untuk Backend AI (FastAPI / Model Hugging Face)
    // Jika menjalankan AI di localhost (komputer sendiri): http://127.0.0.1:8000
    // Jika AI di-hosting di Hugging Face Cloud: https://<username-anda>-emotext-backend.hf.space
    API_BASE_URL: 'http://127.0.0.1:8000',

    // 2. URL untuk Dashboard Website (Laravel)
    // Biasanya menggunakan port 8001 untuk backend lokal: http://127.0.0.1:8001
    // Jika dihosting (misal VPS/Vercel): https://admin.domainanda.com
    LARAVEL_BASE_URL: 'http://127.0.0.1:8001'
};

// Mengekspos variabel agar bisa dibaca oleh content.js
window.ENV = ENV;
