/**
 * Emotext-CRM Extension - Content Script (Master PRD Compliant)
 */

const DUMMY_MODE = true; 
let COMPANY_API_KEY = null;

console.log('[Emotext-CRM] Extension Logic Active.');

// 1. Initial configuration
chrome.storage.local.get(['emotext_api_key'], (result) => {
    COMPANY_API_KEY = result.emotext_api_key || 'DUMMY_KEY';
});

// Helper: Create Sentiment Badge & Intent Label
function createBadges(sentiment, intent, messageId, senderId) {
    const container = document.createElement('div');
    container.className = 'emotext-badges-container';
    
    const sentimentBadge = document.createElement('div');
    sentimentBadge.className = `emotext-snt-badge emotext-snt-${sentiment.toLowerCase()}`;
    
    // Feedback dropdown
    const dropdown = document.createElement('div');
    dropdown.className = 'emotext-snt-dropdown';
    ['Positive', 'Neutral', 'Negative'].forEach(opt => {
        const option = document.createElement('div');
        option.className = 'emotext-snt-option';
        option.innerText = opt;
        option.onclick = (e) => {
            e.stopPropagation();
            console.log('[Emotext-CRM] Feedback logic:', opt);
            sentimentBadge.className = `emotext-snt-badge emotext-snt-${opt.toLowerCase()}`;
            dropdown.style.display = 'none';
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
    
    container.appendChild(sentimentBadge);
    container.appendChild(intentLabel);
    return container;
}

// FR-08: Context Window Scraping
function getContextMessages(currentNode, limit = 3) {
    const context = [];
    let prev = currentNode.previousElementSibling;
    while (prev && context.length < limit) {
        const textNode = prev.querySelector('.copyable-text [dir="ltr"]') || prev.querySelector('span[dir="ltr"]');
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
        '[data-testid="audio-download"]',
        '[data-testid="ptt-status-icon"]',
        '[data-testid="media-url-link"]'
    ];
    for (let selector of mediaIndicators) {
        if (msgContainer.querySelector(selector)) return true;
    }
    return false;
}

// Helper: Mock API
async function mockAnalyzeAPI(text, senderId, context = []) {
    return new Promise(resolve => {
        setTimeout(() => {
            let sentiment = 'neutral';
            let intent = 'inquiry';
            let health_score = 70;
            let suggestion = "Baik Kak, ada yang bisa kami bantu?";
            
            const lowerText = text.toLowerCase();
            if (lowerText === "[media]") {
                intent = "media";
            } else if (lowerText.includes('rusak') || lowerText.includes('kecewa')) {
                sentiment = 'negative'; intent = 'complaint'; health_score = 30;
                suggestion = "Mohon maaf atas kendalanya. Boleh kirimkan detail pesanan Anda?";
            } else if (lowerText.includes('bagus') || lowerText.includes('terima kasih')) {
                sentiment = 'positive'; intent = 'appreciation'; health_score = 90;
                suggestion = "Terima kasih kembali! Senang bisa melayani Anda.";
            }
            
            resolve({ sentiment, intent, health_score, suggestion });
        }, 200);
    });
}

// FR-03: Sidebar Priority Icon
function processSidebarChat(chatCell) {
    if (chatCell.dataset.emotextSidebarProcessed) return;
    
    // Check if last message is a complaint (Simplified for demo)
    // In real app, we check the metadata/intent assigned to this chat
    const lastMsgText = chatCell.querySelector('[data-testid="last-msg-status"]')?.parentElement?.innerText || "";
    if (lastMsgText.toLowerCase().includes('rusak') || lastMsgText.toLowerCase().includes('lapor')) {
        let avatarContainer = chatCell.querySelector('[data-testid="avatar-container"]') || chatCell.children[0];
        if (avatarContainer) {
            avatarContainer.style.position = 'relative';
            const dot = document.createElement('div');
            dot.className = 'emotext-priority-dot';
            avatarContainer.appendChild(dot);
        }
    }
    chatCell.dataset.emotextSidebarProcessed = "true";
}

// Main Processors
function injectSuggestion(suggestionText) {
    const footer = document.querySelector('[data-testid="footer"]') || document.querySelector('footer');
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
        healthBar.style.background = score < 40 ? '#FF4B4B' : (score < 70 ? '#FFC107' : '#25D366');
    }
}

async function processMessageNode(msgContainer) {
    if (!msgContainer || msgContainer.dataset.emotextProcessed) return;

    const textNode = msgContainer.querySelector('.copyable-text [dir="ltr"]') || msgContainer.querySelector('span[dir="ltr"]');
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
    
    const analysis = await mockAnalyzeAPI(text, 'user', context);
    
    const bubble = msgContainer.querySelector('.copyable-text')?.parentElement || msgContainer;
    bubble.style.position = 'relative'; 
    bubble.appendChild(createBadges(analysis.sentiment, analysis.intent, 'id', 'user'));

    updateHealthBar(analysis.health_score);
    if (analysis.suggestion) injectSuggestion(analysis.suggestion);
}

// Observers
function initObservers() {
    const mainObserver = new MutationObserver((mutations) => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType !== 1) return;
                
                // Messages
                const msgs = node.querySelectorAll('[data-testid="msg-container"]');
                msgs.forEach(m => processMessageNode(m));
                if (node.dataset?.testid === 'msg-container') processMessageNode(node);

                // Sidebar Chats
                const cells = node.querySelectorAll('[data-testid="cell-frame-container"]');
                cells.forEach(c => processSidebarChat(c));
            });
        });
    });

    const checkPanel = () => {
        const panel = document.querySelector('[data-testid="conversation-panel-messages"]');
        if (panel && !panel.dataset.emotextObserved) {
            panel.dataset.emotextObserved = "true";
            mainObserver.observe(panel, { childList: true, subtree: true });
            panel.querySelectorAll('[data-testid="msg-container"]').forEach(m => processMessageNode(m));
        }
        
        const sidebar = document.querySelector('[data-testid="chat-list"]');
        if (sidebar && !sidebar.dataset.emotextObserved) {
            sidebar.dataset.emotextObserved = "true";
            mainObserver.observe(sidebar, { childList: true, subtree: true });
            sidebar.querySelectorAll('[data-testid="cell-frame-container"]').forEach(c => processSidebarChat(c));
        }
    };

    setInterval(checkPanel, 2000);
}

setTimeout(initObservers, 2000);
