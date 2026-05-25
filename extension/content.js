/**
 * Emotext-CRM Extension - Content Script (Master PRD Compliant)
 */

// 1. Decoupled DOM Selectors for Maintenance Stability
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

// 2. Production URL mapping with Localhost fallback
const API_BASE_URL = 'http://127.0.0.1:8000'; // local developer fallback. Production target: https://api.emotext-crm.com

let COMPANY_API_KEY = null;
let TERMS_AGREED = false;

// 3. Initial Security & Privacy Gating Configuration
chrome.storage.local.get(['emotext_api_key', 'emotext_terms_agreed'], (result) => {
    TERMS_AGREED = result.emotext_terms_agreed || false;
    COMPANY_API_KEY = result.emotext_api_key || 'DUMMY_KEY';

    if (TERMS_AGREED) {
        console.log('[Emotext-CRM] Syarat & Ketentuan disetujui. Memulai monitoring WhatsApp CRM...');
        setTimeout(initObservers, 2000);
    } else {
        console.warn('[Emotext-CRM] Ekstensi dinonaktifkan karena Syarat & Ketentuan belum disetujui. Harap buka popup ekstensi.');
    }
});

// Helper: PII Anonymization / Masking (Kepatuhan UU PDP)
// Mengganti informasi sensitif seperti nomor telepon, email, dan akun finansial sebelum dikirim ke API
function anonymizeText(text) {
    if (!text || typeof text !== 'string') return text;
    
    // Masking nomor telepon (format Indo: +628 atau 08 diikuti angka)
    const phoneRegex = /(\+62|0)[0-9\s\-]{8,14}/g;
    
    // Masking alamat email
    const emailRegex = /[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g;

    // Masking nomor kartu kredit atau nomor rekening (10 hingga 16 digit berturut-turut)
    const cardRegex = /\b\d{4}[-\s]?\d{4}[-\s]?\d{4}[-\s]?\d{4}\b|\b\d{10,16}\b/g;

    let masked = text;
    masked = masked.replace(phoneRegex, '[NOMOR_TELEPON_TERMASKING]');
    masked = masked.replace(emailRegex, '[EMAIL_TERMASKING]');
    masked = masked.replace(cardRegex, '[AKUN_SENSITIF_TERMASKING]');

    return masked;
}

// Helper: Create Sentiment Badge & Intent Label
function createBadges(sentiment, intent, messageId, senderId) {
    const container = document.createElement('div');
    container.className = 'emotext-badges-container';
    
    const sentimentBadge = document.createElement('div');
    sentimentBadge.className = `emotext-snt-badge emotext-snt-${sentiment.toLowerCase()}`;
    
    // Feedback dropdown untuk Sentimen
    const dropdown = document.createElement('div');
    dropdown.className = 'emotext-snt-dropdown';
    ['Positive', 'Neutral', 'Negative'].forEach(opt => {
        const option = document.createElement('div');
        option.className = 'emotext-snt-option';
        option.innerText = opt;
        
        option.onclick = async (e) => {
            e.stopPropagation();
            const correctedValue = opt.toLowerCase();
            
            // 1. Update visual badge instantly
            sentimentBadge.className = `emotext-snt-badge emotext-snt-${correctedValue}`;
            console.log(`[Emotext-CRM] Mengirim koreksi: ${sentiment} -> ${correctedValue}`);

            // 2. Extract message bubble text & anonymize before sending
            const bubble = container.closest(SELECTORS.msgContainer);
            const messageText = bubble ? (bubble.querySelector(SELECTORS.textLtr)?.innerText || "Unknown") : "Unknown";
            const maskedMessageText = anonymizeText(messageText);

            // 3. Send feedback securely to backend
            try {
                await fetch(`${API_BASE_URL}/feedback`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${COMPANY_API_KEY}`
                    },
                    body: JSON.stringify({
                        message_text: maskedMessageText,
                        original_sentiment: sentiment,
                        corrected_sentiment: correctedValue,
                        admin_id: "admin_01" // CRM Admin ID
                    })
                });
                console.log('[Emotext-CRM] Feedback berhasil disimpan.');
            } catch (err) {
                console.error('[Emotext-CRM] Gagal mengirim feedback:', err);
            }
        };
        dropdown.appendChild(option);
    });
    sentimentBadge.appendChild(dropdown);
    
    const intentLabel = document.createElement('div');
    intentLabel.className = 'emotext-int-badge';
    if (intent.toLowerCase() === 'media') {
        intentLabel.classList.add('emotext-int-media');
    }
    intentLabel.innerText = intent;

    // Feedback dropdown untuk Intensi
    const intDropdown = document.createElement('div');
    intDropdown.className = 'emotext-int-dropdown';
    ['General', 'Complaint', 'Appreciation', 'Order', 'Inquiry', 'Media'].forEach(opt => {
        const option = document.createElement('div');
        option.className = 'emotext-snt-option'; // Menggunakan gaya opsi yang sama
        option.innerText = opt;
        
        option.onclick = async (e) => {
            e.stopPropagation();
            const correctedValue = opt.toLowerCase();
            
            // 1. Update visual badge text & class instantly
            intentLabel.childNodes[0].nodeValue = opt; // Update teks tanpa menghapus dropdown child
            if (correctedValue === 'media') {
                intentLabel.classList.add('emotext-int-media');
            } else {
                intentLabel.classList.remove('emotext-int-media');
            }
            console.log(`[Emotext-CRM] Mengirim koreksi intensi: ${intent} -> ${correctedValue}`);

            // 2. Extract message bubble text & anonymize before sending
            const bubble = container.closest(SELECTORS.msgContainer);
            const messageText = bubble ? (bubble.querySelector(SELECTORS.textLtr)?.innerText || "Unknown") : "Unknown";
            const maskedMessageText = anonymizeText(messageText);

            // 3. Send feedback securely to backend
            try {
                await fetch(`${API_BASE_URL}/feedback`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${COMPANY_API_KEY}`
                    },
                    body: JSON.stringify({
                        message_text: maskedMessageText,
                        original_intent: intent,
                        corrected_intent: correctedValue,
                        admin_id: "admin_01" // CRM Admin ID
                    })
                });
                console.log('[Emotext-CRM] Feedback intensi berhasil disimpan.');
            } catch (err) {
                console.error('[Emotext-CRM] Gagal mengirim feedback intensi:', err);
            }
        };
        intDropdown.appendChild(option);
    });
    intentLabel.appendChild(intDropdown);
    
    container.appendChild(sentimentBadge);
    container.appendChild(intentLabel);
    return container;
}

// FR-08: Context Window Scraping
function getContextMessages(currentNode, limit = 3) {
    const context = [];
    let prev = currentNode.previousElementSibling;
    while (prev && context.length < limit) {
        const textNode = prev.querySelector(SELECTORS.textLtr);
        if (textNode) {
            const isIncoming = prev.classList.contains('message-in') || prev.closest('.message-in');
            context.unshift({
                text: textNode.innerText,
                role: isIncoming ? 'user' : 'admin'
            });
        }
        prev = prev.previousElementSibling;
    }
    return context;
}

// FR-09: Media Detection
function detectMedia(msgContainer) {
    const mediaIndicators = [
        'img', 'video', 
        SELECTORS.audioDownload,
        SELECTORS.pttStatusIcon,
        SELECTORS.mediaUrlLink
    ];
    for (let selector of mediaIndicators) {
        if (msgContainer.querySelector(selector)) return true;
    }
    return false;
}

// API: Send text context to server
async function analyzeMessageAPI(text, senderId, senderName, context = []) {
    try {
        // Anonymize data for safety before transmitting over API
        const maskedText = anonymizeText(text);
        const maskedSenderName = anonymizeText(senderName);
        const maskedContext = context.map(item => ({
            text: anonymizeText(item.text),
            role: item.role
        }));

        const fullContext = [...maskedContext, { text: maskedText, role: 'user' }];

        const response = await fetch(`${API_BASE_URL}/analyze`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${COMPANY_API_KEY}`
            },
            body: JSON.stringify({
                sender_id: senderId,
                sender_name: maskedSenderName,
                context: fullContext,
                timestamp: new Date().toISOString(),
                message_type: text === "[MEDIA]" ? "media" : "text"
            })
        });

        if (!response.ok) {
            throw new Error(`Server returned status: ${response.status}`);
        }

        const result = await response.json();
        return result;

    } catch (error) {
        console.error('[Emotext-CRM] API Analysis Error:', error);
        return { 
            sentiment: 'neutral', 
            intent: 'offline', 
            health_score: 50, 
            suggestion: null 
        };
    }
}

// FR-03: Sidebar Priority Icon
function processSidebarChat(chatCell) {
    if (chatCell.dataset.emotextSidebarProcessed) return;
    
    const lastMsgText = chatCell.querySelector(SELECTORS.lastMsgStatus)?.parentElement?.innerText || "";
    if (lastMsgText.toLowerCase().includes('rusak') || lastMsgText.toLowerCase().includes('lapor')) {
        let avatarContainer = chatCell.querySelector(SELECTORS.avatarContainer) || chatCell.children[0];
        if (avatarContainer) {
            avatarContainer.style.position = 'relative';
            const dot = document.createElement('div');
            dot.className = 'emotext-priority-dot';
            avatarContainer.appendChild(dot);
        }
    }
    chatCell.dataset.emotextSidebarProcessed = "true";
}

// Suggestion UI Injector
function injectSuggestion(suggestionText) {
    const footer = document.querySelector(SELECTORS.footer);
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
        const inputDiv = document.querySelector(SELECTORS.inputEditable);
        if (inputDiv) {
            inputDiv.focus();
            document.execCommand('insertText', false, suggestionText);
            inputDiv.dispatchEvent(new Event('input', { bubbles: true }));
        }
        container.innerHTML = '';
    };
    container.appendChild(pill);
}

// Loyalty Health Bar UI
function updateHealthBar(score) {
    const header = document.querySelector(SELECTORS.conversationHeader);
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
        healthBar.style.background = score < 40 ? '#FF4B4B' : (score < 70 ? '#FFC107' : '#25D366');
    }
}

// Message node processor
async function processMessageNode(msgContainer) {
    if (!msgContainer || msgContainer.dataset.emotextProcessed) return;

    const textNode = msgContainer.querySelector(SELECTORS.textLtr);
    const isMedia = detectMedia(msgContainer);
    
    if (!textNode && !isMedia) return;

    const text = textNode ? textNode.innerText : "[MEDIA]";
    const isIncoming = msgContainer.classList.contains('message-in') || msgContainer.closest('.message-in');

    if (!isIncoming) {
        msgContainer.dataset.emotextProcessed = "true";
        return;
    }

    msgContainer.dataset.emotextProcessed = "true";
    
    // FR-08: Gather context
    const context = getContextMessages(msgContainer, 3);
    
    // Ambil nama kontak dari header WhatsApp Web
    const headerTitle = document.querySelector(SELECTORS.conversationInfoChatTitle) || document.querySelector(SELECTORS.conversationHeaderSpan);
    const contactName = headerTitle ? headerTitle.innerText : "Unknown";
    
    // Buat ID unik berdasarkan nama
    const uniqueId = contactName.replace(/\s+/g, '_').toLowerCase();
    
    // analysis via API
    const analysis = await analyzeMessageAPI(text, uniqueId, contactName, context);
    
    const bubble = msgContainer.querySelector('.copyable-text')?.parentElement || msgContainer;
    bubble.style.position = 'relative'; 
    bubble.style.overflow = 'visible'; 
    bubble.appendChild(createBadges(analysis.sentiment, analysis.intent, 'id', 'user'));

    updateHealthBar(analysis.health_score);
    if (analysis.suggestion) injectSuggestion(analysis.suggestion);
}

// Mutation Observers Initialization
function initObservers() {
    const mainObserver = new MutationObserver((mutations) => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType !== 1) return;
                
                // Messages
                const msgs = node.querySelectorAll(SELECTORS.msgContainer);
                msgs.forEach(m => processMessageNode(m));
                if (node.dataset?.testid === 'msg-container') processMessageNode(node);

                // Sidebar Chats
                const cells = node.querySelectorAll(SELECTORS.cellFrame);
                cells.forEach(c => processSidebarChat(c));
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

    setInterval(checkPanel, 2000);
}
