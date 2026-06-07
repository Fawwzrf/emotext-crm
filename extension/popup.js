document.addEventListener('DOMContentLoaded', () => {
    const stepTerms     = document.getElementById('step-terms');
    const stepLogin     = document.getElementById('step-login');
    const stepDashboard = document.getElementById('step-dashboard');

    const agreeCheck = document.getElementById('agreeCheck');
    const nextBtn    = document.getElementById('nextBtn');

    const emailInput       = document.getElementById('emailInput');
    const passwordInput    = document.getElementById('passwordInput');
    const togglePasswordBtn = document.getElementById('togglePassword');
    const loginBtn         = document.getElementById('loginBtn');

    const displayEmail  = document.getElementById('displayEmail');
    const displayCompany = document.getElementById('displayCompany');
    const userAvatar    = document.getElementById('userAvatar');
    const logoutBtn     = document.getElementById('logoutBtn');
    const trialBadge    = document.getElementById('trialBadge');
    const openDashBtn   = document.getElementById('openDashboardBtn');

    const status = document.getElementById('status');

    // URL Laravel (auth) dan FastAPI (ML)
    const LARAVEL_URL = 'http://127.0.0.1:8001';

    // ── 1. Cek state yang sudah ada ──────────────────────────────────────────
    chrome.storage.local.get(
        ['emotext_terms_agreed', 'emotext_api_key', 'emotext_user_email', 'emotext_company', 'emotext_trial_days'],
        (result) => {
            if (result.emotext_terms_agreed) {
                if (result.emotext_api_key && result.emotext_user_email) {
                    setupDashboard(result.emotext_user_email, result.emotext_company, result.emotext_trial_days);
                    showStep(stepDashboard);
                } else {
                    showStep(stepLogin);
                }
            } else {
                showStep(stepTerms);
            }
        }
    );

    // ── 2. Terms Handlers ────────────────────────────────────────────────────
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

    // ── 3. Password Toggle ───────────────────────────────────────────────────
    togglePasswordBtn.addEventListener('click', () => {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        togglePasswordBtn.style.color = type === 'text' ? '#10b981' : '';
    });

    // ── 4. Login — memanggil Laravel /api/extension/login ───────────────────
    loginBtn.addEventListener('click', async () => {
        const email    = emailInput.value.trim();
        const password = passwordInput.value;

        if (!email || !password) {
            showStatus('Email dan Password wajib diisi!', 'error');
            return;
        }

        showStatus('Memverifikasi akun...', 'info');
        loginBtn.disabled = true;
        const originalBtnText = loginBtn.innerText;
        loginBtn.innerHTML = '<svg class="spinner" viewBox="0 0 50 50"><circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle></svg> Memverifikasi...';

        try {
            const response = await fetch(`${LARAVEL_URL}/api/extension/login`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
            });

            loginBtn.disabled = false;
            loginBtn.innerText = originalBtnText;

            if (!response.ok) {
                const err = await response.json().catch(() => ({}));
                showStatus(err.message || 'Email atau Password salah!', 'error');
                return;
            }

            const data = await response.json();

            // Simpan semua info yang dibutuhkan
            chrome.storage.local.set({
                'emotext_api_key':    data.api_token,
                'emotext_user_email': data.email,
                'emotext_company':    data.company_name,
                'emotext_trial_days': data.trial_days_left,
                'emotext_sub_status': data.subscription_status,
                'emotext_is_active':  data.is_active,
            }, () => {
                showStatus('Login berhasil!', 'success');
                setTimeout(() => {
                    clearStatus();
                    setupDashboard(data.email, data.company_name, data.trial_days_left, data.subscription_status);
                    showStep(stepDashboard);
                }, 800);
            });

        } catch (err) {
            loginBtn.disabled = false;
            loginBtn.innerText = originalBtnText;
            if (err.message === 'Failed to fetch') {
                showStatus('Gagal terhubung ke server. Pastikan backend Laravel berjalan di port 8001.', 'error');
            } else {
                showStatus('Terjadi kesalahan. Coba lagi.', 'error');
            }
        }
    });

    // ── 5. Logout ────────────────────────────────────────────────────────────
    logoutBtn.addEventListener('click', () => {
        chrome.storage.local.remove(
            ['emotext_api_key', 'emotext_user_email', 'emotext_company', 'emotext_trial_days', 'emotext_sub_status', 'emotext_is_active'],
            () => {
                emailInput.value    = '';
                passwordInput.value = '';
                showStep(stepLogin);
                showStatus('Berhasil logout.', 'info');
                setTimeout(() => clearStatus(), 2000);
            }
        );
    });

    // ── 6. Buka Dashboard ────────────────────────────────────────────────────
    if (openDashBtn) {
        openDashBtn.addEventListener('click', () => {
            chrome.tabs.create({ url: `${LARAVEL_URL}/dashboard` });
        });
    }

    // ── Helper Functions ─────────────────────────────────────────────────────
    function setupDashboard(email, company, trialDays, subStatus) {
        displayEmail.innerText = email;
        userAvatar.innerText   = email.charAt(0).toUpperCase();
        if (displayCompany) displayCompany.innerText = company || '';

        if (trialBadge) {
            if (subStatus === 'active') {
                trialBadge.innerText = 'Langganan Aktif';
                trialBadge.className = 'trial-badge active';
            } else if (trialDays > 0) {
                trialBadge.innerText = `Trial: ${trialDays} hari tersisa`;
                trialBadge.className = trialDays <= 2 ? 'trial-badge warning' : 'trial-badge trial';
            } else {
                trialBadge.innerText = 'Trial Berakhir — Upgrade!';
                trialBadge.className = 'trial-badge expired';
            }
        }
    }

    function showStep(stepElement) {
        document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
        stepElement.classList.add('active');
    }

    function showStatus(msg, type) {
        status.innerText  = msg;
        status.className  = type;
    }

    function clearStatus() {
        status.innerText = '';
        status.className = '';
    }
});
