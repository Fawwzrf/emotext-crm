document.addEventListener('DOMContentLoaded', () => {
    const stepTerms = document.getElementById('step-terms');
    const stepLogin = document.getElementById('step-login');
    const stepDashboard = document.getElementById('step-dashboard');
    
    const agreeCheck = document.getElementById('agreeCheck');
    const nextBtn = document.getElementById('nextBtn');
    
    const emailInput = document.getElementById('emailInput');
    const passwordInput = document.getElementById('passwordInput');
    const togglePasswordBtn = document.getElementById('togglePassword');
    const loginBtn = document.getElementById('loginBtn');
    
    const displayEmail = document.getElementById('displayEmail');
    const userAvatar = document.getElementById('userAvatar');
    const logoutBtn = document.getElementById('logoutBtn');
    
    const status = document.getElementById('status');

    // Default API Host Configuration for validation (dynamic fallback)
    const API_HOST = "http://127.0.0.1:8000";

    // 1. Check existing state
    chrome.storage.local.get(['emotext_terms_agreed', 'emotext_api_key', 'emotext_user_email'], (result) => {
        if (result.emotext_terms_agreed) {
            if (result.emotext_api_key && result.emotext_user_email) {
                // User is already logged in
                setupDashboard(result.emotext_user_email);
                showStep(stepDashboard);
            } else {
                // Agreed to terms but not logged in
                showStep(stepLogin);
            }
        } else {
            // New user, show terms
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
                showStep(stepLogin);
                showStatus('Ketentuan disetujui. Silakan login.', 'info');
                setTimeout(() => clearStatus(), 2000);
            });
        }
    });

    // 3. Password Toggle Logic
    togglePasswordBtn.addEventListener('click', () => {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        // Update icon based on state (optional visual cue, currently relying on color transition)
        if (type === 'text') {
            togglePasswordBtn.style.color = '#10b981'; // Primary color when active
        } else {
            togglePasswordBtn.style.color = ''; // Revert to default
        }
    });

    // 4. Login Logic
    loginBtn.addEventListener('click', async () => {
        const email = emailInput.value.trim();
        const password = passwordInput.value;
        
        if (!email || !password) {
            showStatus('Email dan Password wajib diisi!', 'error');
            return;
        }

        showStatus('Memverifikasi akun...', 'info');
        loginBtn.disabled = true;
        const originalBtnText = loginBtn.innerText;
        loginBtn.innerHTML = '<svg class="spinner" viewBox="0 0 50 50"><circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle></svg> Memverifikasi...';
        
        // TODO (Backend Team): Implement real fetch to /login endpoint here
        /*
        try {
            const response = await fetch(`${API_HOST}/login`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
            });
            if (!response.ok) throw new Error("Invalid credentials");
            const data = await response.json();
            const token = data.token; // Save this to emotext_api_key
        } catch(err) {
            loginBtn.disabled = false;
            loginBtn.innerText = originalBtnText;
            if (err.message === "Failed to fetch") {
                showStatus('Gagal terhubung ke server CRM. Periksa koneksi internet Anda.', 'error');
            } else {
                showStatus('Email atau Password salah!', 'error');
            }
            return;
        }
        */

        // MOCK VALIDATION FOR FRONTEND TESTING
        setTimeout(() => {
            loginBtn.disabled = false;
            loginBtn.innerText = originalBtnText;

            // Simulasi Network Error
            if (email === 'error@gmail.com') {
                showStatus('Gagal terhubung ke server CRM. Periksa koneksi internet Anda.', 'error');
                return;
            }

            if (email === 'admin@gmail.com' && password === 'admin123') {
                const dummyToken = "EMOTEXT_MOCK_TOKEN_" + Date.now();
                
                // Simpan email dan token ke storage lokal
                chrome.storage.local.set({ 
                    'emotext_api_key': dummyToken,
                    'emotext_user_email': email
                }, () => {
                    showStatus('Login berhasil!', 'success');
                    setTimeout(() => {
                        clearStatus();
                        setupDashboard(email);
                        showStep(stepDashboard);
                    }, 800);
                });
            } else {
                showStatus('Email atau Password salah/tidak terdaftar!', 'error');
            }
        }, 1000); // Simulate network delay
    });

    // 5. Logout Logic
    logoutBtn.addEventListener('click', () => {
        // Hapus kredensial dari storage (tetap simpan terms_agreed agar tidak perlu acc ulang)
        chrome.storage.local.remove(['emotext_api_key', 'emotext_user_email'], () => {
            emailInput.value = '';
            passwordInput.value = '';
            showStep(stepLogin);
            showStatus('Berhasil logout.', 'info');
            setTimeout(() => clearStatus(), 2000);
        });
    });

    // Helper Functions
    function setupDashboard(email) {
        displayEmail.innerText = email;
        userAvatar.innerText = email.charAt(0).toUpperCase();
    }

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
