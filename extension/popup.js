document.addEventListener('DOMContentLoaded', () => {
    const apiKeyInput = document.getElementById('apiKey');
    const saveBtn = document.getElementById('saveBtn');
    const status = document.getElementById('status');

    // Load existing API Key
    chrome.storage.local.get(['emotext_api_key'], (result) => {
        if (result.emotext_api_key) {
            apiKeyInput.value = result.emotext_api_key;
        }
    });

    saveBtn.addEventListener('click', () => {
        const apiKey = apiKeyInput.value.trim();
        if (!apiKey) {
            showStatus('Please enter an API Key', 'error');
            return;
        }

        chrome.storage.local.set({ 'emotext_api_key': apiKey }, () => {
            showStatus('Settings saved successfully!', 'success');
            setTimeout(() => {
                window.close();
            }, 1000);
        });
    });

    function showStatus(msg, type) {
        status.innerText = msg;
        status.className = type;
    }
});
