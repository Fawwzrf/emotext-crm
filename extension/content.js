/**
 * Emotext-CRM Extension - Content Script v3.2 (Click-to-Suggest RAG)
 */

const SELECTORS = {
    msgContainer: '[data-testid="msg-container"]',
    cellFrame: '[data-testid="cell-frame-container"]',
    textLtr: '.copyable-text [dir="ltr"], span[dir="ltr"]',
    conversationPanel: '[data-testid="conversation-panel-messages"]',
    chatList: '[data-testid="chat-list"]',
    lastMsgStatus: '[data-testid="last-msg-status"]',
    avatarContainer: '[data-testid="avatar-container"]',
    footer: '[data-testid="footer"], footer',
    inputEditable: 'div[contenteditable="true"]',
    conversationHeader: '[data-testid="conversation-header"]',
    conversationInfoChatTitle: '[data-testid="conversation-info-header-chat-title"]',
    conversationHeaderSpan: '[data-testid="conversation-header"] span[dir="auto"]',
    audioDownload: '[data-testid="audio-download"]',
    pttStatusIcon: '[data-testid="ptt-status-icon"]',
    mediaUrlLink: '[data-testid="media-url-link"]'
};

const API_BASE_URL = 'http://127.0.0.1:8000';
let COMPANY_API_KEY = null;
let TERMS_AGREED = false;

chrome.storage.onChanged.addListener((changes, namespace) => {
    if (namespace === 'local' && changes.emotext_api_key) {
        window.location.reload();
    }
});

chrome.storage.local.get(['emotext_terms_agreed', 'emotext_api_key'], (result) => {
    TERMS_AGREED = result.emotext_terms_agreed || false;
    COMPANY_API_KEY = result.emotext_api_key || null;
    
    injectStatusIndicator(COMPANY_API_KEY !== null);

    if (!TERMS_AGREED || !COMPANY_API_KEY) {
        console.warn('[Emotext-CRM] ⚠️ Extension disabled. Buka popup untuk login.');
        return;
    }

    console.log('[Emotext-CRM] 🚀 Monitoring dimulai...');
    initObservers();
});

function anonymizeText(text) {
    if (!text || typeof text !== 'string') return text;
    let masked = text;
    masked = masked.replace(/(\+62|0)[0-9\s\-]{8,14}/g, '[PHONE]');
    masked = masked.replace(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g, '[EMAIL]');
    masked = masked.replace(/\b\d{4}[-\s]?\d{4}[-\s]?\d{4}[-\s]?\d{4}\b|\b\d{10,16}\b/g, '[SENSITIVE]');
    return masked;
}

function isOutgoing(msgContainer) {
    if (msgContainer.classList.contains('message-out')) return true;
    if (msgContainer.closest('.message-out')) return true;

    const rowWithId = msgContainer.closest('[data-id^="false_"], [data-id^="true_"]');
    if (rowWithId) {
        const dataId = rowWithId.getAttribute('data-id');
        if (dataId.startsWith('false_')) return false; 
        if (dataId.startsWith('true_')) return true;   
    }

    const deliverySelector = 
        '[data-testid="msg-dblcheck"], [data-testid="msg-check"], ' +
        '[data-testid="msg-dblcheck-ack"], [data-icon="msg-dblcheck"], ' +
        '[data-icon="msg-check"], [data-icon="msg-dblcheck-ack"]';
    if (msgContainer.querySelector(deliverySelector)) return true;
    const parentRow = msgContainer.closest('[data-id]') || msgContainer.parentElement;
    if (parentRow && parentRow.querySelector(deliverySelector)) return true;
    
    const row = msgContainer.closest('[role="row"]');
    if (row) {
        const rowRect = row.getBoundingClientRect();
        const msgRect = msgContainer.getBoundingClientRect();
        const msgCenter = msgRect.left + (msgRect.width / 2);
        const rowCenter = rowRect.left + (rowRect.width / 2);
        if (msgCenter > rowCenter + 20) return true;
    }
    return false; 
}

function isSystemOrDate(msgContainer) {
    if (msgContainer.querySelector('.copyable-text')) return false; 
    if (detectMedia(msgContainer)) return false; 
    return true; 
}

function createBadges(sentiment, intent, confidence) {
    const container = document.createElement('div');
    container.className = 'emotext-badges-container';

    const sentimentBadge = document.createElement('div');
    sentimentBadge.className = `emotext-snt-badge emotext-snt-${sentiment.toLowerCase()}`;
    const confPercent = confidence ? Math.round(confidence * 100) : 0;
    sentimentBadge.title = `AI Confidence: ${confPercent}%`;

    const dropdown = document.createElement('div');
    dropdown.className = 'emotext-snt-dropdown';
    ['Positive', 'Neutral', 'Negative'].forEach(opt => {
        const option = document.createElement('div');
        option.className = 'emotext-snt-option';
        option.innerText = opt;
        option.onclick = async (e) => {
            e.stopPropagation();
            const corrected = opt.toLowerCase();
            sentimentBadge.className = `emotext-snt-badge emotext-snt-${corrected}`;
            const bubble = container.closest(SELECTORS.msgContainer);
            const messageText = bubble ? (bubble.querySelector(SELECTORS.textLtr)?.innerText || "Unknown") : "Unknown";
            try {
                await fetch(`${API_BASE_URL}/feedback`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${COMPANY_API_KEY}` },
                    body: JSON.stringify({
                        message_text: anonymizeText(messageText),
                        original_sentiment: sentiment,
                        corrected_sentiment: corrected,
                        admin_id: "admin_01"
                    })
                });
            } catch (err) { /* silent */ }
        };
        dropdown.appendChild(option);
    });
    sentimentBadge.appendChild(dropdown);

    const intentLabel = document.createElement('div');
    intentLabel.className = 'emotext-int-badge';
    if (intent.toLowerCase() === 'media') intentLabel.classList.add('emotext-int-media');
    intentLabel.innerText = intent;
    intentLabel.title = `AI Confidence: ${confPercent}%`;

    const intDropdown = document.createElement('div');
    intDropdown.className = 'emotext-int-dropdown';
    ['Order', 'Inquiry', 'Media', 'Complaint', 'Other'].forEach(opt => {
        const option = document.createElement('div');
        option.className = 'emotext-snt-option';
        option.innerText = opt;
        option.onclick = async (e) => {
            e.stopPropagation();
            const corrected = opt.toLowerCase();
            intentLabel.childNodes[0].nodeValue = opt;
            intentLabel.classList.toggle('emotext-int-media', corrected === 'media');
            const bubble = container.closest(SELECTORS.msgContainer);
            const messageText = bubble ? (bubble.querySelector(SELECTORS.textLtr)?.innerText || "Unknown") : "Unknown";
            try {
                await fetch(`${API_BASE_URL}/feedback`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${COMPANY_API_KEY}` },
                    body: JSON.stringify({
                        message_text: anonymizeText(messageText),
                        original_intent: intent,
                        corrected_intent: corrected,
                        admin_id: "admin_01"
                    })
                });
            } catch (err) { /* silent */ }
        };
        intDropdown.appendChild(option);
    });
    intentLabel.appendChild(intDropdown);

    container.appendChild(sentimentBadge);
    container.appendChild(intentLabel);
    return container;
}

function getContextMessages(currentNode, limit = 3) {
    const context = [];
    let prev = currentNode.previousElementSibling;
    while (prev && context.length < limit) {
        const textNode = prev.querySelector(SELECTORS.textLtr);
        if (textNode) {
            const out = isOutgoing(prev);
            context.unshift({ text: textNode.innerText, role: out ? 'admin' : 'user' });
        }
        prev = prev.previousElementSibling;
    }
    return context;
}

function detectMedia(msgContainer) {
    const selectors = [
        'img[src*="blob"]', 'video', 'audio',
        '[data-testid="audio-download"]',
        '[data-testid="ptt-status-icon"]',
        '[data-testid="media-url-link"]',
        '[data-testid="image-thumb"]',
        '[data-testid="document-thumb"]',
        '[data-testid="doc-document"]',
        '[data-testid="sticker"]',
        '[data-testid="gif"]',
    ];
    for (const sel of selectors) {
        if (msgContainer.querySelector(sel)) return true;
    }
    return false;
}

async function analyzeMessageAPI(text, senderId, senderName, context = []) {
    try {
        const maskedText = anonymizeText(text);
        const maskedName = anonymizeText(senderName);
        const maskedCtx = context.map(c => ({ text: anonymizeText(c.text), role: c.role }));
        const fullContext = [...maskedCtx, { text: maskedText, role: 'user' }];

        const response = await fetch(`${API_BASE_URL}/analyze`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${COMPANY_API_KEY}` },
            body: JSON.stringify({
                sender_id: senderId,
                sender_name: maskedName,
                context: fullContext,
                timestamp: new Date().toISOString(),
                message_type: text === "[MEDIA]" ? "media" : "text"
            })
        });
        if (!response.ok) throw new Error(`Status ${response.status}`);
        return await response.json();
    } catch (error) {
        console.warn('[Emotext-CRM] API error:', error.message);
        return { sentiment: 'neutral', intent: 'other', confidence: 0, health_score: 50, suggestion: null };
    }
}

async function processSidebarChat(chatCell) {
    const lastMsgEl = chatCell.querySelector(SELECTORS.lastMsgStatus)?.parentElement;
    const lastMsgText = lastMsgEl ? lastMsgEl.innerText : "";

    if (chatCell.dataset.emotextSidebarProcessed === "true" &&
        chatCell.dataset.emotextLastMsg === lastMsgText) return;

    chatCell.dataset.emotextSidebarProcessed = "true";
    chatCell.dataset.emotextLastMsg = lastMsgText;

    const titleEl = chatCell.querySelector('[data-testid="chat-title"]') || chatCell.querySelector('span[title]');
    const contactName = titleEl ? (titleEl.title || titleEl.innerText) : null;
    if (!contactName) return;

    const uniqueId = encodeURIComponent(contactName.replace(/\s+/g, '_').toLowerCase());

    try {
        const response = await fetch(`${API_BASE_URL}/health-score/${uniqueId}`, {
            headers: { 'Authorization': `Bearer ${COMPANY_API_KEY}` }
        });
        if (!response.ok) return;
        const data = await response.json();
        const score = data.health_score;

        let avatarContainer = chatCell.querySelector(SELECTORS.avatarContainer) || chatCell.children[0];
        if (!avatarContainer) return;

        avatarContainer.style.position = 'relative';
        const existing = avatarContainer.querySelector('.emotext-sidebar-health-badge');
        if (existing) existing.remove();

        const badge = document.createElement('div');
        badge.className = 'emotext-sidebar-health-badge';
        badge.innerText = score;

        if (score < 50) {
            badge.style.backgroundColor = '#FF4B4B';
            badge.style.color = '#ffffff';
            badge.style.boxShadow = '0 0 10px #FF4B4B';
            badge.title = `Urgensi Tinggi (${score})`;
        } else if (score < 80) {
            badge.style.backgroundColor = '#FFC107';
            badge.style.color = '#0f172a';
            badge.style.boxShadow = '0 0 6px #FFC107';
            badge.title = `Urgensi Sedang (${score})`;
        } else {
            badge.style.backgroundColor = '#25D366';
            badge.style.color = '#ffffff';
            badge.title = `Urgensi Rendah (${score})`;
        }

        avatarContainer.appendChild(badge);
    } catch (err) { /* silent */ }
}

function injectSuggestion(suggestionText) {
    try {
        const footer = document.querySelector('footer') || document.querySelector(SELECTORS.footer);
        
        if (!footer) return;
        
        footer.style.position = 'relative';

        let container = document.getElementById('emotext-rag-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'emotext-rag-container';
            container.style.cssText = `
                position: absolute; 
                bottom: 100%; 
                left: 0; 
                width: 100%; 
                padding: 10px 16px; 
                display: flex; 
                gap: 8px; 
                z-index: 9999; 
                overflow-x: auto; 
                box-sizing: border-box;
                pointer-events: none;
            `;
            footer.appendChild(container);
        }
        container.innerHTML = '';
        
        const pill = document.createElement('div');
        pill.style.cssText = `
            pointer-events: auto; 
            background: #ffffff; 
            border: 1.5px solid #10b981; 
            border-radius: 20px; 
            padding: 8px 16px; 
            font-size: 14px; 
            color: #10b981; 
            cursor: pointer; 
            white-space: nowrap; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.15); 
            font-weight: 600; 
            transition: all 0.2s ease;
        `;
        
        pill.onmouseenter = () => pill.style.background = '#f0fdf4';
        pill.onmouseleave = () => pill.style.background = '#ffffff';
        
        pill.innerHTML = `✨ ${suggestionText}`;
        pill.title = "Klik untuk memasukkan saran balasan AI (RAG)";
        
        pill.onclick = () => {
            const inputDiv = footer.querySelector('div[contenteditable="true"]') || document.querySelector(SELECTORS.inputEditable);
            if (inputDiv) {
                inputDiv.focus();
                document.execCommand('insertText', false, suggestionText);
                inputDiv.dispatchEvent(new Event('input', { bubbles: true }));
            }
            container.innerHTML = ''; 
        };
        
        container.appendChild(pill);
    } catch (err) {
        console.warn('[Emotext-CRM] Suggestion inject error:', err.message);
    }
}

function updateHealthBar(score) {
    const header = document.querySelector(SELECTORS.conversationHeader) || document.querySelector('header');
    if (!header) return;
    
    let healthContainer = header.querySelector('.emotext-health-container');
    if (!healthContainer) {
        healthContainer = document.createElement('div');
        healthContainer.className = 'emotext-health-container';
        healthContainer.style.width = '100%';
        healthContainer.style.height = '4px';
        healthContainer.style.position = 'absolute';
        healthContainer.style.bottom = '0';
        healthContainer.style.left = '0';
        healthContainer.style.zIndex = '1000';
        
        const healthBar = document.createElement('div');
        healthBar.className = 'emotext-health-bar';
        healthBar.style.height = '100%';
        healthBar.style.transition = 'width 0.3s ease, background 0.3s ease';
        
        healthContainer.appendChild(healthBar);
        header.style.position = 'relative';
        header.appendChild(healthContainer);
    }
    const healthBar = healthContainer.querySelector('.emotext-health-bar');
    if (healthBar) {
        healthBar.style.width = `${score}%`;
        healthBar.style.background = score < 50 ? '#FF4B4B' : (score < 80 ? '#FFC107' : '#25D366');
    }
}

async function processMessageNode(msgContainer) {
    if (!msgContainer || msgContainer.dataset.emotextProcessed) return;

    if (isSystemOrDate(msgContainer)) {
        msgContainer.dataset.emotextProcessed = "true";
        return;
    }

    if (isOutgoing(msgContainer)) {
        msgContainer.dataset.emotextProcessed = "true";
        return;
    }

    msgContainer.dataset.emotextProcessed = "true";

    const textNode = msgContainer.querySelector(SELECTORS.textLtr);
    const isMedia = detectMedia(msgContainer);
    if (!textNode && !isMedia) return;

    const text = textNode ? textNode.innerText : "[MEDIA]";
    const context = getContextMessages(msgContainer, 3);

    const headerTitle = document.querySelector(SELECTORS.conversationInfoChatTitle) ||
                        document.querySelector(SELECTORS.conversationHeaderSpan);
    const contactName = headerTitle ? headerTitle.innerText : "Unknown";
    const uniqueId = contactName.replace(/\s+/g, '_').toLowerCase();

    const analysis = await analyzeMessageAPI(text, uniqueId, contactName, context);

    const bubble = msgContainer.querySelector('.copyable-text')?.parentElement || msgContainer;
    bubble.style.position = 'relative';
    bubble.style.overflow = 'visible';
    
    // Pasang badge Sentimen & Intensi
    bubble.appendChild(createBadges(analysis.sentiment, analysis.intent, analysis.confidence));
    updateHealthBar(analysis.health_score);

    // ========================================================
    // PERUBAHAN: Munculkan RAG Suggestion HANYA saat pesan di-klik
    // ========================================================
    if (analysis.suggestion) {
        // Berikan penanda bahwa balon pesan ini bisa diklik
        bubble.title = "💡 Klik pesan ini untuk memunculkan saran balasan AI (RAG)";
        bubble.style.cursor = "pointer";

        bubble.addEventListener('click', (e) => {
            // Cegah agar klik tidak bentrok jika admin mengklik tombol/link/badge bawaan WA
            if (e.target.closest('a, button, .emotext-badges-container, [role="button"]')) return;
            
            // 1. Munculkan tombol RAG ke kotak input
            injectSuggestion(analysis.suggestion);
            
            // 2. Berikan animasi kilat (highlight) pada pesan yang sedang dipilih
            const originalTransition = bubble.style.transition;
            const originalBg = bubble.style.backgroundColor;
            
            bubble.style.transition = 'background-color 0.3s ease';
            bubble.style.backgroundColor = 'rgba(16, 185, 129, 0.2)'; // Warna hijau tipis
            
            setTimeout(() => {
                bubble.style.backgroundColor = originalBg;
                setTimeout(() => { bubble.style.transition = originalTransition; }, 300);
            }, 400);
        });
    }
}

function initObservers() {
    const mainObserver = new MutationObserver((mutations) => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType !== 1) return;

                const msgs = node.querySelectorAll(SELECTORS.msgContainer);
                msgs.forEach(m => processMessageNode(m));
                if (node.matches?.(SELECTORS.msgContainer)) processMessageNode(node);

                const cells = node.querySelectorAll(SELECTORS.cellFrame);
                cells.forEach(c => processSidebarChat(c));
                if (node.matches?.(SELECTORS.cellFrame)) processSidebarChat(node);
            });
        });
    });

    const checkPanel = () => {
        const panel = document.querySelector(SELECTORS.conversationPanel);
        if (panel && !panel.dataset.emotextObserved) {
            panel.dataset.emotextObserved = "true";
            mainObserver.observe(panel, { childList: true, subtree: true });
            panel.querySelectorAll(SELECTORS.msgContainer).forEach(m => processMessageNode(m));
        }

        const sidebar = document.querySelector(SELECTORS.chatList);
        if (sidebar && !sidebar.dataset.emotextObserved) {
            sidebar.dataset.emotextObserved = "true";
            mainObserver.observe(sidebar, { childList: true, subtree: true });
            sidebar.querySelectorAll(SELECTORS.cellFrame).forEach(c => processSidebarChat(c));
        }
    };

    checkPanel();
    setInterval(checkPanel, 1000);
}

function injectStatusIndicator(isActive) {
    let indicator = document.getElementById('emotext-status-indicator');
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.id = 'emotext-status-indicator';
        indicator.style.position = 'fixed';
        indicator.style.top = '260px'; 
        indicator.style.left = '12px'; 
        indicator.style.zIndex = '9999';
        indicator.style.width = '40px';
        indicator.style.height = '40px';
        indicator.style.borderRadius = '50%';
        indicator.style.background = 'linear-gradient(135deg, #10b981 0%, #3b82f6 100%)';
        indicator.style.display = 'flex';
        indicator.style.alignItems = 'center';
        indicator.style.justifyContent = 'center';
        indicator.style.color = 'white';
        indicator.style.fontWeight = 'bold';
        indicator.style.fontSize = '18px';
        indicator.style.fontFamily = 'system-ui, sans-serif';
        indicator.style.boxShadow = '0 4px 6px rgba(0,0,0,0.3)';
        indicator.style.cursor = 'help';
        indicator.innerText = 'E';
        
        const dot = document.createElement('div');
        dot.id = 'emotext-status-dot';
        dot.style.position = 'absolute';
        dot.style.top = '-2px';
        dot.style.right = '-2px';
        dot.style.width = '12px';
        dot.style.height = '12px';
        dot.style.borderRadius = '50%';
        dot.style.border = '2px solid #0f172a'; 
        indicator.appendChild(dot);
        document.body.appendChild(indicator);
    }
    
    const indicatorEl = document.getElementById('emotext-status-indicator');
    const dot = document.getElementById('emotext-status-dot');
    
    if (isActive) {
        dot.style.background = '#10b981'; 
        dot.style.boxShadow = '0 0 4px #10b981';
        indicatorEl.title = 'Emotext CRM: ON (Aktif)';
    } else {
        dot.style.background = '#ef4444'; 
        dot.style.boxShadow = '0 0 4px #ef4444';
        indicatorEl.title = 'Emotext CRM: OFF (Silakan Login)';
        indicatorEl.style.background = '#475569'; 
    }
}if (typeof module !== 'undefined') module.exports = { detectMedia, createBadges, updateHealthBar, injectStatusIndicator };
