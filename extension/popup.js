document.addEventListener('DOMContentLoaded', () => {
    const stepTerms = document.getElementById('step-terms');
    const stepConfig = document.getElementById('step-config');
    const agreeCheck = document.getElementById('agreeCheck');
    const nextBtn = document.getElementById('nextBtn');
    
    const apiKeyInput = document.getElementById('apiKey');
    const saveBtn = document.getElementById('saveBtn');
    const status = document.getElementById('status');

    // Default API Host Configuration for validation (dynamic fallback)
    const API_HOST = "http://127.0.0.1:8000";

    // 1. Check existing state
    chrome.storage.local.get(['emotext_terms_agreed', 'emotext_api_key'], (result) => {
        if (result.emotext_terms_agreed) {
            // Already agreed, go to configuration step
            showStep(stepConfig);
            if (result.emotext_api_key) {
                apiKeyInput.value = result.emotext_api_key;
            }
        } else {
            showStep(stepTerms);
        }
    });

    // 2. Terms Agreement Handlers
    agreeCheck.addEventListener('change', () => {
        nextBtn.disabled = !agreeCheck.checked;
    });

    nextBtn.addEventListener('click', () => {
        if (agreeCheck.checked) {
            chrome.storage.local.set({ 'emotext_terms_agreed': true }, () => {
                showStep(stepConfig);
                showStatus('Ketentuan disetujui. Silakan masukkan API Key Anda.', 'info');
                setTimeout(() => clearStatus(), 2000);
            });
        }
    });

    // 3. API Key Save Handler
    saveBtn.addEventListener('click', async () => {
        const apiKey = apiKeyInput.value.trim();
        if (!apiKey) {
            showStatus('Harap masukkan API Key perusahaan!', 'error');
            return;
        }

        showStatus('Menghubungkan ke server...', 'info');
        
        // Simpan langsung ke storage lokal
        chrome.storage.local.set({ 'emotext_api_key': apiKey }, () => {
            showStatus('Pengaturan berhasil disimpan!', 'success');
            setTimeout(() => {
                window.close();
            }, 1000);
        });
    });

    // Helper Functions
    function showStep(stepElement) {
        document.querySelectorAll('.step').forEach(step => {
            step.classList.remove('active');
        });
        stepElement.classList.add('active');
    }

    function showStatus(msg, type) {
        status.innerText = msg;
        status.className = type;
    }

    function clearStatus() {
        status.innerText = '';
        status.className = '';
    }
});
