/**
 * Emotext-CRM Extension - Content Script v3.0 (Clean Rewrite)
 * 
 * Ditulis ulang dari versi asli yang sudah bekerja.
 * Hanya 3 perbaikan ditambahkan dari versi asli:
 *   1. Skip date-separator & system messages
 *   2. Skip pesan keluar (via delivery icon check)
 *   3. injectSuggestion menggunakan try/catch aman
 */

// =========================================================
// 1. DOM SELECTORS
// =========================================================
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

// =========================================================
// 2. CONFIGURATION
// =========================================================
const API_BASE_URL = 'http://127.0.0.1:8000';
let COMPANY_API_KEY = null;
let TERMS_AGREED = false;

// =========================================================
// 3. INITIALIZATION
// =========================================================
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

// =========================================================
// 4. PII ANONYMIZATION
// =========================================================
function anonymizeText(text) {
    if (!text || typeof text !== 'string') return text;
    let masked = text;
    masked = masked.replace(/(\+62|0)[0-9\s\-]{8,14}/g, '[PHONE]');
    masked = masked.replace(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g, '[EMAIL]');
    masked = masked.replace(/\b\d{4}[-\s]?\d{4}[-\s]?\d{4}[-\s]?\d{4}\b|\b\d{10,16}\b/g, '[SENSITIVE]');
    return masked;
}

// =========================================================
// 5. INCOMING MESSAGE DETECTION (BARU)
//    - Outgoing messages punya centang delivery icon
//    - Incoming messages TIDAK punya
// =========================================================
function isOutgoing(msgContainer) {
    // Check 1: classic class
    if (msgContainer.classList.contains('message-out')) return true;
    if (msgContainer.closest('.message-out')) return true;

    // Check 2: data-id prefix (The absolute truth in WA Web)
    // Cari elemen yang punya atribut data-id yang diawali false_ (keluar) atau true_ (masuk)
    const rowWithId = msgContainer.closest('[data-id^="false_"], [data-id^="true_"]');
    if (rowWithId) {
        const dataId = rowWithId.getAttribute('data-id');
        if (dataId.startsWith('false_')) return true;
        if (dataId.startsWith('true_')) return false;
    }

    // Check 3: Delivery icons (hanya ada di pesan keluar)
    const deliverySelector = 
        '[data-testid="msg-dblcheck"], [data-testid="msg-check"], ' +
        '[data-testid="msg-dblcheck-ack"], [data-icon="msg-dblcheck"], ' +
        '[data-icon="msg-check"], [data-icon="msg-dblcheck-ack"]';
    if (msgContainer.querySelector(deliverySelector)) return true;
    const parentRow = msgContainer.closest('[data-id]') || msgContainer.parentElement;
    if (parentRow && parentRow.querySelector(deliverySelector)) return true;
    
    // Check 4: Fallback Visual Position yang BENAR (Relatif terhadap row, BUKAN #main)
    const row = msgContainer.closest('[role="row"]');
    if (row) {
        const rowRect = row.getBoundingClientRect();
        const msgRect = msgContainer.getBoundingClientRect();
        const msgCenter = msgRect.left + (msgRect.width / 2);
        const rowCenter = rowRect.left + (rowRect.width / 2);
        if (msgCenter > rowCenter + 20) return true;
    }

    return false; // Default: anggap incoming agar diproses
}

// =========================================================
// 6. SYSTEM / DATE SEPARATOR CHECK (BARU)
// =========================================================
function isSystemOrDate(msgContainer) {
    // System messages & date separators: TIDAK punya .copyable-text DAN TIDAK punya media
    // Media messages juga tidak punya .copyable-text tapi harus tetap diproses
    if (msgContainer.querySelector('.copyable-text')) return false; // Ada teks = pesan biasa
    if (detectMedia(msgContainer)) return false; // Ada media = pesan media
    return true; // Tidak ada teks dan tidak ada media = system/date
}

// =========================================================
// 7. CREATE BADGES UI
// =========================================================
function createBadges(sentiment, intent) {
    const container = document.createElement('div');
    container.className = 'emotext-badges-container';

    // Sentiment dot
    const sentimentBadge = document.createElement('div');
    sentimentBadge.className = `emotext-snt-badge emotext-snt-${sentiment.toLowerCase()}`;

    // Sentiment feedback dropdown
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

    // Intent label
    const intentLabel = document.createElement('div');
    intentLabel.className = 'emotext-int-badge';
    if (intent.toLowerCase() === 'media') intentLabel.classList.add('emotext-int-media');
    intentLabel.innerText = intent;

    // Intent feedback dropdown
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

// =========================================================
// 8. CONTEXT WINDOW (3 pesan sebelumnya)
// =========================================================
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

// =========================================================
// 9. MEDIA DETECTION
// =========================================================
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

// =========================================================
// 10. API CALL
// =========================================================
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
        return { sentiment: 'neutral', intent: 'other', health_score: 50, suggestion: null };
    }
}

// =========================================================
// 11. SIDEBAR HEALTH BADGE
// =========================================================
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

// =========================================================
// 12. SUGGESTION PILL
// =========================================================
function injectSuggestion(suggestionText) {
    try {
        const footer = document.querySelector(SELECTORS.footer);
        if (!footer) return;
        let container = footer.parentElement?.querySelector('.emotext-suggest-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'emotext-suggest-container';
            // Aman: coba insertBefore, fallback ke appendChild
            try {
                footer.parentElement.insertBefore(container, footer);
            } catch (e) {
                footer.parentElement.appendChild(container);
            }
        }
        container.innerHTML = '';
        const pill = document.createElement('div');
        pill.className = 'emotext-suggest-pill';
        pill.innerText = suggestionText;
        pill.onclick = () => {
            const inputDiv = document.querySelector(SELECTORS.inputEditable);
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

// =========================================================
// 13. HEALTH BAR DI HEADER
// =========================================================
function updateHealthBar(score) {
    const header = document.querySelector(SELECTORS.conversationHeader) || document.querySelector('header');
    if (!header) return;
    
    let healthContainer = header.querySelector('.emotext-health-container');
    if (!healthContainer) {
        healthContainer = document.createElement('div');
        healthContainer.className = 'emotext-health-container';
        // Styling inline agar kebal terhadap perubahan DOM WA
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

// =========================================================
// 14. PROCESS MESSAGE NODE (INTI)
// =========================================================
async function processMessageNode(msgContainer) {
    if (!msgContainer || msgContainer.dataset.emotextProcessed) return;

    // FILTER 1: Skip system messages & date separators
    // (mereka tidak punya .copyable-text)
    if (isSystemOrDate(msgContainer)) {
        msgContainer.dataset.emotextProcessed = "true";
        return;
    }

    // FILTER 2: Skip pesan keluar (punya delivery check icon)
    if (isOutgoing(msgContainer)) {
        msgContainer.dataset.emotextProcessed = "true";
        return;
    }

    msgContainer.dataset.emotextProcessed = "true";

    const textNode = msgContainer.querySelector(SELECTORS.textLtr);
    const isMedia = detectMedia(msgContainer);
    if (!textNode && !isMedia) return;

    const text = textNode ? textNode.innerText : "[MEDIA]";
    // Debug log removed to clean up console

    const context = getContextMessages(msgContainer, 3);

    const headerTitle = document.querySelector(SELECTORS.conversationInfoChatTitle) ||
                        document.querySelector(SELECTORS.conversationHeaderSpan);
    const contactName = headerTitle ? headerTitle.innerText : "Unknown";
    const uniqueId = contactName.replace(/\s+/g, '_').toLowerCase();

    const analysis = await analyzeMessageAPI(text, uniqueId, contactName, context);

    // Inject badge
    const bubble = msgContainer.querySelector('.copyable-text')?.parentElement || msgContainer;
    bubble.style.position = 'relative';
    bubble.style.overflow = 'visible';
    bubble.appendChild(createBadges(analysis.sentiment, analysis.intent));

    updateHealthBar(analysis.health_score);
    if (analysis.suggestion) injectSuggestion(analysis.suggestion);
}

// =========================================================
// 15. MUTATION OBSERVERS
// =========================================================
function initObservers() {
    const mainObserver = new MutationObserver((mutations) => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType !== 1) return;

                // Messages
                const msgs = node.querySelectorAll(SELECTORS.msgContainer);
                msgs.forEach(m => processMessageNode(m));
                if (node.matches?.(SELECTORS.msgContainer)) processMessageNode(node);

                // Sidebar
                const cells = node.querySelectorAll(SELECTORS.cellFrame);
                cells.forEach(c => processSidebarChat(c));
                if (node.matches?.(SELECTORS.cellFrame)) processSidebarChat(node);
            });
        });
    });

    const checkPanel = () => {
        // Conversation panel
        const panel = document.querySelector(SELECTORS.conversationPanel);
        if (panel && !panel.dataset.emotextObserved) {
            panel.dataset.emotextObserved = "true";
            mainObserver.observe(panel, { childList: true, subtree: true });
            panel.querySelectorAll(SELECTORS.msgContainer).forEach(m => processMessageNode(m));
        }

        // Sidebar
        const sidebar = document.querySelector(SELECTORS.chatList);
        if (sidebar && !sidebar.dataset.emotextObserved) {
            sidebar.dataset.emotextObserved = "true";
            mainObserver.observe(sidebar, { childList: true, subtree: true });
            sidebar.querySelectorAll(SELECTORS.cellFrame).forEach(c => processSidebarChat(c));
        }
    };

    // Scan awal
    checkPanel();

    // Re-check setiap 1 detik (menangkap lazy-loaded elements)
    setInterval(checkPanel, 1000);
}

// =========================================================
// 16. WA UI STATUS INDICATOR
// =========================================================
function injectStatusIndicator(isActive) {
    let indicator = document.getElementById('emotext-status-indicator');
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.id = 'emotext-status-indicator';
        indicator.style.position = 'fixed';
        indicator.style.top = '12px';
        indicator.style.left = '80px'; // Sebelah foto profil kiri
        indicator.style.zIndex = '9999';
        indicator.style.display = 'flex';
        indicator.style.alignItems = 'center';
        indicator.style.gap = '6px';
        indicator.style.padding = '4px 8px';
        indicator.style.background = 'rgba(15, 23, 42, 0.8)';
        indicator.style.borderRadius = '12px';
        indicator.style.border = '1px solid #334155';
        indicator.style.backdropFilter = 'blur(4px)';
        indicator.style.color = '#f8fafc';
        indicator.style.fontSize = '11px';
        indicator.style.fontFamily = 'system-ui, sans-serif';
        indicator.style.fontWeight = '600';
        indicator.style.pointerEvents = 'none'; // Jangan ganggu klik WA
        
        const dot = document.createElement('div');
        dot.id = 'emotext-status-dot';
        dot.style.width = '8px';
        dot.style.height = '8px';
        dot.style.borderRadius = '50%';
        
        const text = document.createElement('span');
        text.id = 'emotext-status-text';
        
        indicator.appendChild(dot);
        indicator.appendChild(text);
        document.body.appendChild(indicator);
    }
    
    const dot = document.getElementById('emotext-status-dot');
    const text = document.getElementById('emotext-status-text');
    
    if (isActive) {
        dot.style.background = '#10b981'; // Green
        dot.style.boxShadow = '0 0 6px rgba(16, 185, 129, 0.5)';
        text.innerText = 'Emotext: ON';
    } else {
        dot.style.background = '#ef4444'; // Red
        dot.style.boxShadow = '0 0 6px rgba(239, 68, 68, 0.5)';
        text.innerText = 'Emotext: OFF (Login Req)';
    }
}

