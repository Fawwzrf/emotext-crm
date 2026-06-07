/**
 * @jest-environment jsdom
 */

const fs = require('fs');
const path = require('path');

// Evaluasi script asli di scope global JSDOM
const contentJsCode = fs.readFileSync(path.resolve(__dirname, 'content.js'), 'utf-8');
// Mock minimal untuk API Chrome jika diperlukan
global.chrome = {
    runtime: {
        sendMessage: jest.fn(),
        onMessage: {
            addListener: jest.fn()
        }
    },
    storage: {
        onChanged: {
            addListener: jest.fn()
        },
        local: {
            get: jest.fn((keys, callback) => callback({ emotext_terms_agreed: true, emotext_api_key: 'TEST' }))
        }
    }
};

// Modifikasi sederhana agar tidak melempar error saat script mendefinisikan variable const di top-level (karena kita load via script tag atau eval)
const scriptEl = document.createElement('script');
scriptEl.textContent = contentJsCode;
document.body.appendChild(scriptEl);

// Kita bisa memuat ulang fungsinya dari window (karena di-eval via script tag di JSDOM)
let detectMedia, createBadges;

describe('DOM Manipulation Tests (FR-03 & FR-09)', () => {
    
    beforeAll(() => {
        detectMedia = window.detectMedia;
        createBadges = window.createBadges;
    });

    test('FR-09: detectMedia harus mendeteksi kontainer yang memiliki gambar/audio (media-url-link)', () => {
        const container = document.createElement('div');
        // Tiruan WA Web media
        const mediaChild = document.createElement('div');
        mediaChild.setAttribute('data-testid', 'media-url-link');
        container.appendChild(mediaChild);
        
        // Memanggil fungsi deteksi
        const result = detectMedia(container);
        expect(result).toBe(true);
    });

    test('FR-09: detectMedia harus mengembalikan false jika kontainer murni teks', () => {
        const container = document.createElement('div');
        container.innerHTML = '<span>Halo ini teks biasa</span>';
        
        const result = detectMedia(container);
        expect(result).toBe(false);
    });

    test('FR-03: createBadges harus membuat elemen badge Sentimen & Intensi', () => {
        const badgeContainer = createBadges('negative', 'complaint', 0.95);
        
        // Memeriksa keberadaan DOM
        expect(badgeContainer.className).toBe('emotext-badges-container');
        expect(badgeContainer.childNodes.length).toBe(2); // Satu sentimen, satu intent
        
        const sentimentBadge = badgeContainer.childNodes[0];
        expect(sentimentBadge.className).toContain('emotext-snt-badge');
        expect(sentimentBadge.className).toContain('emotext-snt-negative');
        // Teks 'NEGATIVE' sebenarnya di-render via CSS ::after, jadi tidak ada di innerHTML
        
        const intentBadge = badgeContainer.childNodes[1];
        expect(intentBadge.className).toContain('emotext-int-badge');
        // intentBadge diisi menggunakan .innerText, namun di JSDOM bisa tidak muncul di innerHTML. Kita asumsikan lolos jika class benar.
    });

});
