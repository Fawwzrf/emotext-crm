<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Chrome Extension Remote Configuration
    |--------------------------------------------------------------------------
    |
    | File konfigurasi ini menjadi pusat kebenaran (source of truth) dari seluruh 
    | selektor DOM CSS yang digunakan oleh Ekstensi Chrome Emotext-CRM pada WhatsApp Web.
    | 
    | Jika pihak WhatsApp (Meta) mengubah antarmuka WhatsApp Web dan mengubah
    | nama-nama class/ID elemen, Anda tidak perlu lagi membongkar ulang ekstensi 
    | dan menunggu berhari-hari ulasan dari Chrome Web Store.
    | Cukup ubah nilai-nilai di file ini (atau pindahkan ke Database kelak),
    | maka seluruh ekstensi pelanggan di seluruh dunia akan mendapatkan update secara real-time.
    |
    */

    'selectors' => [
        'msgContainer' => '[data-testid="msg-container"]',
        'cellFrame' => '[data-testid="cell-frame-container"]',
        'textLtr' => '.copyable-text [dir="ltr"], span[dir="ltr"]',
        'conversationPanel' => '[data-testid="conversation-panel-messages"]',
        'chatList' => '[data-testid="chat-list"]',
        'lastMsgStatus' => '[data-testid="last-msg-status"]',
        'avatarContainer' => '[data-testid="avatar-container"]',
        'footer' => '[data-testid="footer"], footer',
        'inputEditable' => 'div[contenteditable="true"]',
        'conversationHeader' => '[data-testid="conversation-header"]',
        'conversationInfoChatTitle' => '[data-testid="conversation-info-header-chat-title"]',
        'conversationHeaderSpan' => '[data-testid="conversation-header"] span[dir="auto"]',
        'audioDownload' => '[data-testid="audio-download"]',
        'pttStatusIcon' => '[data-testid="ptt-status-icon"]',
        'mediaUrlLink' => '[data-testid="media-url-link"]'
    ],
];
