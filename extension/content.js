/**
 * Emotext-CRM Extension - Content Script (Deep-Dive Diagnostic)
 */

const DUMMY_MODE = true; 

console.log('[Emotext-CRM] Deep-Dive Script Active.');

// Helper: Create Sentiment Badge & Intent Label
function createBadges(sentiment, intent, messageId, senderId) {
    const container = document.createElement('div');
    container.className = 'emotext-badges-container';
    const sentimentBadge = document.createElement('div');
    sentimentBadge.className = `emotext-snt-badge emotext-snt-${sentiment.toLowerCase()}`;
    const dropdown = document.createElement('div');
    dropdown.className = 'emotext-snt-dropdown';
    ['Positive', 'Neutral', 'Negative'].forEach(opt => {
        const option = document.createElement('div');
        option.className = 'emotext-snt-option';
        option.innerText = opt;
        option.onclick = (e) => {
            e.stopPropagation();
            sendFeedback(messageId, senderId, opt.toLowerCase());
            sentimentBadge.className = `emotext-snt-badge emotext-snt-${opt.toLowerCase()}`;
            dropdown.style.display = 'none';
        };
        dropdown.appendChild(option);
    });
    sentimentBadge.appendChild(dropdown);
    const intentLabel = document.createElement('div');
    intentLabel.className = 'emotext-int-badge';
    intentLabel.innerText = intent;
    container.appendChild(sentimentBadge);
    container.appendChild(intentLabel);
    return container;
}

// Helper: Mock API
async function mockAnalyzeAPI(text, senderId) {
    return new Promise(resolve => {
        setTimeout(() => {
            let sentiment = 'neutral';
            let intent = 'inquiry';
            let health_score = 70;
            let suggestion = "Baik Kak, ada yang bisa kami bantu?";
            const lowerText = text.toLowerCase();
            if (lowerText.includes('rusak') || lowerText.includes('kecewa') || lowerText.includes('jelek')) {
                sentiment = 'negative'; intent = 'complaint'; health_score = 30;
                suggestion = "Mohon maaf atas kendalanya. Boleh kirimkan detail pesanan Anda?";
            } else if (lowerText.includes('bagus') || lowerText.includes('keren') || lowerText.includes('terima kasih')) {
                sentiment = 'positive'; intent = 'appreciation'; health_score = 90;
                suggestion = "Terima kasih kembali! Senang bisa melayani Anda.";
            } else if (lowerText.includes('order') || lowerText.includes('pesan')) {
                intent = 'order'; suggestion = "Untuk pemesanan, silakan isi form berikut ya Kak.";
            }
            resolve({ sentiment, intent, health_score, suggestion });
        }, 200);
    });
}

function getChatFooter() {
    let footer = document.querySelector('[data-testid="footer"]') || document.querySelector('footer');
    if (footer) return footer;
    const input = document.querySelector('div[contenteditable="true"]');
    if (input) return input.closest('[role="contentinfo"]') || input.parentElement.parentElement;
    return null;
}

function injectSuggestion(suggestionText) {
    const footer = getChatFooter();
    if (!footer) return;
    let container = footer.parentElement.querySelector('.emotext-suggest-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'emotext-suggest-container';
        footer.parentElement.insertBefore(container, footer);
    }
    container.innerHTML = '';
    const pill = document.createElement('div');
    pill.className = 'emotext-suggest-pill';
    pill.innerText = suggestionText;
    pill.onclick = () => {
        const inputDiv = document.querySelector('div[contenteditable="true"]');
        if (inputDiv) {
            inputDiv.focus();
            document.execCommand('insertText', false, suggestionText);
            inputDiv.dispatchEvent(new Event('input', { bubbles: true }));
        }
        container.innerHTML = '';
    };
    container.appendChild(pill);
}

function updateHealthBar(score) {
    const header = document.querySelector('[data-testid="conversation-header"]') || document.querySelector('header');
    if (!header) return;
    const contactInfo = header.querySelector('div[role="button"]'); 
    if (!contactInfo) return;
    let healthContainer = header.querySelector('.emotext-health-container');
    if (!healthContainer) {
        healthContainer = document.createElement('div');
        healthContainer.className = 'emotext-health-container';
        const healthBar = document.createElement('div');
        healthBar.className = 'emotext-health-bar';
        healthContainer.appendChild(healthBar);
        contactInfo.parentElement.appendChild(healthContainer);
    }
    const healthBar = healthContainer.querySelector('.emotext-health-bar');
    if (healthBar) {
        healthBar.style.width = `${score}%`;
        if (score < 40) healthBar.style.background = '#FF4B4B';
        else if (score < 70) healthBar.style.background = '#FFC107';
        else healthBar.style.background = '#25D366';
    }
}

async function processMessageNode(msgContainer) {
    if (!msgContainer || msgContainer.dataset.emotextProcessed) return;

    // Log that we found a potential message
    console.log('[Emotext-CRM] Checking message container...', msgContainer.className);

    // 1. Check for text content more aggressively
    // We try many potential text wrappers
    const textNode = msgContainer.querySelector('.copyable-text [dir="ltr"]') || 
                     msgContainer.querySelector('.selectable-text') ||
                     msgContainer.querySelector('[data-pre-plain-text] + div') ||
                     msgContainer.querySelector('span[dir="ltr"]');

    if (!textNode) {
        console.log('[Emotext-CRM] Skip: Could not find text node in container.');
        return;
    }

    const text = textNode.innerText;
    if (!text || text.length < 2) {
        console.log('[Emotext-CRM] Skip: Text too short or empty.');
        return;
    }

    // Identify if incoming (Customer)
    // Most WA versions use classes like 'message-in', but some use attributes
    const isIncoming = msgContainer.classList.contains('message-in') || 
                       msgContainer.innerHTML.includes('message-in') || // fuzzy check
                       msgContainer.closest('.message-in') !== null;

    if (!isIncoming) {
        console.log('[Emotext-CRM] Skip: Outgoing message (not customer).');
        // Mark as processed anyway so we don't spam logs
        msgContainer.dataset.emotextProcessed = "true";
        return;
    }

    msgContainer.dataset.emotextProcessed = "true";
    console.log(`[Emotext-CRM] Analyzing: "${text.substring(0, 30)}..."`);

    const analysis = await mockAnalyzeAPI(text, 'user');
    
    const metaContainer = msgContainer.querySelector('div[data-testid="msg-meta"]');
    if (metaContainer) {
        metaContainer.appendChild(createBadges(analysis.sentiment, analysis.intent, 'mid', 'user'));
    }

    updateHealthBar(analysis.health_score);
    if (analysis.suggestion) injectSuggestion(analysis.suggestion);
}

function initObserver() {
    console.log('[Emotext-CRM] Initializing Observer System...');
    
    const observer = new MutationObserver((mutations) => {
        mutations.forEach(mutation => {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1) {
                        const messages = node.querySelectorAll ? node.querySelectorAll('[data-testid="msg-container"]') : [];
                        if (node.getAttribute && node.getAttribute('data-testid') === 'msg-container') processMessageNode(node);
                        messages.forEach(m => processMessageNode(m));
                    }
                });
            }
        });
    });

    const checkPanel = () => {
        const panel = document.querySelector('[data-testid="conversation-panel-messages"]');
        if (panel && !panel.dataset.emotextObserved) {
            console.log('[Emotext-CRM] Success: Chat Panel Found!');
            panel.dataset.emotextObserved = "true";
            observer.observe(panel, { childList: true, subtree: true });
            
            // Process existing
            const existing = panel.querySelectorAll('[data-testid="msg-container"]');
            console.log(`[Emotext-CRM] Found ${existing.length} existing messages.`);
            existing.forEach(m => processMessageNode(m));
        }
    };

    setInterval(checkPanel, 2000);
    checkPanel();
}

boot = () => { setTimeout(initObserver, 1000); };
if (document.readyState === 'complete' || document.readyState === 'interactive') boot();
else window.addEventListener('load', boot);
