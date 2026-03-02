/**
 * Chat Widget Embeddable - NAS Hotspot Platform
 * Widget autonome pour portail captif MikroTik
 * Supporte: Chat texte, Appels WebRTC, Historique d'appels
 * Usage: <script src="chat-widget.js" data-widget-key="VOTRE_CLE"></script>
 */
(function() {
    'use strict';

    // Prevent double init
    if (window.__cwgtInitialized) return;
    window.__cwgtInitialized = true;

    var script = document.currentScript;
    if (!script) {
        var scripts = document.getElementsByTagName('script');
        script = scripts[scripts.length - 1];
    }

    var widgetKey = script.getAttribute('data-widget-key');
    if (!widgetKey) {
        console.error('[ChatWidget] Attribut data-widget-key manquant');
        return;
    }

    // Derive API base from script src
    var scriptSrc = script.src;
    var basePath = scriptSrc.substring(0, scriptSrc.lastIndexOf('/'));
    var apiBase = basePath + '/api.php?route=';

    // ========== STYLES ==========
    var CSS = '\
    #cwgt-container, #cwgt-container *, #cwgt-container *::before, #cwgt-container *::after { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }\
    #cwgt-bubble { position: fixed; bottom: 24px; right: 24px; z-index: 99999; width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 20px rgba(37, 99, 235, 0.4); transition: transform 0.2s, box-shadow 0.2s; }\
    #cwgt-bubble:hover { transform: scale(1.08); box-shadow: 0 6px 28px rgba(37, 99, 235, 0.5); }\
    #cwgt-bubble.cwgt-has-notif { animation: cwgt-bounce 0.6s ease; }\
    @keyframes cwgt-bounce { 0%,100% { transform: scale(1); } 30% { transform: scale(1.2); } 50% { transform: scale(0.95); } 70% { transform: scale(1.1); } }\
    #cwgt-bubble svg { width: 28px; height: 28px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }\
    #cwgt-badge { position: absolute; top: -4px; right: -4px; background: #ef4444; color: #fff; font-size: 11px; font-weight: 700; min-width: 20px; height: 20px; border-radius: 10px; display: flex; align-items: center; justify-content: center; padding: 0 5px; border: 2px solid #fff; animation: cwgt-pulse 1.5s ease-in-out infinite; }\
    @keyframes cwgt-pulse { 0%,100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.15); opacity: 0.85; } }\
    #cwgt-window { position: fixed; bottom: 96px; right: 24px; z-index: 99999; width: 360px; max-width: calc(100vw - 32px); height: 520px; max-height: calc(100vh - 120px); background: #fff; border-radius: 16px; box-shadow: 0 12px 48px rgba(0,0,0,0.15); display: none; flex-direction: column; overflow: hidden; animation: cwgt-slideUp 0.3s ease; }\
    @keyframes cwgt-slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }\
    #cwgt-header { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; }\
    #cwgt-header-left { display: flex; align-items: center; gap: 12px; }\
    #cwgt-header-avatar { width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; }\
    #cwgt-header-avatar svg { width: 22px; height: 22px; fill: none; stroke: #fff; stroke-width: 2; }\
    #cwgt-header-title { font-size: 16px; font-weight: 600; }\
    #cwgt-header-subtitle { font-size: 12px; opacity: 0.85; margin-top: 2px; }\
    #cwgt-close { background: rgba(255,255,255,0.15); border: none; color: #fff; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s; }\
    #cwgt-close:hover { background: rgba(255,255,255,0.3); }\
    #cwgt-close svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 2; }\
    #cwgt-login { padding: 24px 20px; flex: 1; display: flex; flex-direction: column; justify-content: center; }\
    #cwgt-login h3 { font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 4px; }\
    #cwgt-login p { font-size: 14px; color: #6b7280; margin-bottom: 20px; }\
    #cwgt-login label { font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px; display: block; }\
    #cwgt-login input { width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 14px; color: #1f2937; outline: none; transition: border-color 0.2s, box-shadow 0.2s; margin-bottom: 14px; }\
    #cwgt-login input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }\
    #cwgt-login input::placeholder { color: #9ca3af; }\
    #cwgt-login-btn { width: 100%; padding: 12px; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; transition: opacity 0.2s; }\
    #cwgt-login-btn:hover { opacity: 0.9; }\
    #cwgt-login-btn:disabled { opacity: 0.6; cursor: not-allowed; }\
    #cwgt-login-error { color: #ef4444; font-size: 13px; margin-bottom: 10px; display: none; }\
    #cwgt-chat { flex: 1; display: none; flex-direction: column; overflow: hidden; }\
    #cwgt-messages { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 8px; }\
    .cwgt-msg { max-width: 85%; padding: 10px 14px; border-radius: 16px; font-size: 14px; line-height: 1.4; word-wrap: break-word; }\
    .cwgt-msg-customer { align-self: flex-end; background: #2563eb; color: #fff; border-bottom-right-radius: 4px; }\
    .cwgt-msg-admin { align-self: flex-start; background: #f3f4f6; color: #1f2937; border-bottom-left-radius: 4px; }\
    .cwgt-msg-time { font-size: 11px; opacity: 0.7; margin-top: 4px; }\
    .cwgt-msg-admin .cwgt-msg-time { color: #9ca3af; }\
    .cwgt-msg-call { align-self: center; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 20px; padding: 6px 16px; display: flex; align-items: center; gap: 8px; font-size: 13px; color: #6b7280; }\
    .cwgt-msg-call svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2; flex-shrink: 0; }\
    .cwgt-msg-call .cwgt-call-missed { color: #ef4444; }\
    .cwgt-msg-call .cwgt-call-ok { color: #22c55e; }\
    #cwgt-empty { text-align: center; padding: 40px 20px; color: #9ca3af; }\
    #cwgt-empty svg { width: 48px; height: 48px; margin: 0 auto 12px; stroke: #d1d5db; fill: none; stroke-width: 1.5; }\
    #cwgt-empty p { font-size: 14px; }\
    #cwgt-input-area { padding: 12px 16px; border-top: 1px solid #e5e7eb; display: flex; gap: 8px; flex-shrink: 0; background: #fff; }\
    #cwgt-input { flex: 1; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 24px; font-size: 14px; color: #1f2937; outline: none; transition: border-color 0.2s; }\
    #cwgt-input:focus { border-color: #2563eb; }\
    #cwgt-input::placeholder { color: #9ca3af; }\
    #cwgt-send { width: 40px; height: 40px; border-radius: 50%; background: #2563eb; color: #fff; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s; flex-shrink: 0; }\
    #cwgt-send:hover { background: #1d4ed8; }\
    #cwgt-send:disabled { background: #93c5fd; cursor: not-allowed; }\
    #cwgt-send svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 2; }\
    #cwgt-typing { padding: 4px 16px 8px; font-size: 12px; color: #9ca3af; display: none; }\
    #cwgt-incoming-call { display: none; flex-shrink: 0; background: linear-gradient(135deg, #22c55e, #16a34a); padding: 10px 14px; color: #fff; }\
    #cwgt-incoming-call.cwgt-visible { display: block; }\
    #cwgt-incoming-call .cwgt-ic-info { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }\
    #cwgt-incoming-call .cwgt-ic-icon { width: 28px; height: 28px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; animation: cwgt-pulse 1s ease-in-out infinite; }\
    #cwgt-incoming-call .cwgt-ic-icon svg { width: 14px; height: 14px; fill: none; stroke: #fff; stroke-width: 2; }\
    #cwgt-incoming-call .cwgt-ic-text { font-size: 13px; font-weight: 600; }\
    #cwgt-incoming-call .cwgt-ic-sub { font-size: 11px; opacity: 0.85; }\
    #cwgt-incoming-call .cwgt-ic-btns { display: flex; gap: 6px; }\
    #cwgt-incoming-call .cwgt-ic-btns button { padding: 5px 14px; border: none; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px; transition: opacity 0.2s; }\
    #cwgt-incoming-call .cwgt-ic-btns button svg { width: 14px; height: 14px; }\
    #cwgt-incoming-call .cwgt-ic-btns button:hover { opacity: 0.9; }\
    .cwgt-btn-accept { background: #fff; color: #16a34a; }\
    .cwgt-btn-reject { background: rgba(255,255,255,0.2); color: #fff; }\
    #cwgt-active-call { display: none; flex-shrink: 0; background: #1f2937; padding: 8px 14px; color: #fff; }\
    #cwgt-active-call.cwgt-visible { display: flex !important; align-items: center; justify-content: space-between; }\
    #cwgt-active-call .cwgt-ac-info { display: flex; align-items: center; gap: 8px; }\
    #cwgt-active-call .cwgt-ac-dot { width: 8px; height: 8px; border-radius: 50%; background: #22c55e; animation: cwgt-pulse 1.5s ease-in-out infinite; }\
    #cwgt-active-call .cwgt-ac-label { font-size: 12px; opacity: 0.7; }\
    #cwgt-active-call .cwgt-ac-time { font-size: 14px; font-weight: 600; font-variant-numeric: tabular-nums; }\
    #cwgt-active-call .cwgt-ac-btns { display: flex; gap: 6px; }\
    #cwgt-active-call .cwgt-ac-btns button { width: 32px; height: 32px; border-radius: 50%; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: opacity 0.2s; }\
    #cwgt-active-call .cwgt-ac-btns button:hover { opacity: 0.85; }\
    #cwgt-active-call .cwgt-ac-btns button svg { width: 16px; height: 16px; fill: none; stroke: currentColor; stroke-width: 2; }\
    .cwgt-btn-mute { background: rgba(255,255,255,0.15); color: #fff; }\
    .cwgt-btn-mute.cwgt-muted { background: #ef4444; color: #fff; }\
    .cwgt-btn-hangup { background: #ef4444; color: #fff; }\
    @media (max-width: 420px) { #cwgt-window { width: calc(100vw - 16px); right: 8px; bottom: 80px; height: calc(100vh - 100px); } #cwgt-bubble { bottom: 16px; right: 16px; width: 52px; height: 52px; } }\
    ';

    // SVG icons
    var PHONE_SVG = '<svg viewBox="0 0 24 24" style="width:1em;height:1em;fill:none;stroke:currentColor;stroke-width:2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>';
    var MIC_SVG = '<svg viewBox="0 0 24 24"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>';
    var MIC_OFF_SVG = '<svg viewBox="0 0 24 24"><line x1="1" y1="1" x2="23" y2="23"/><path d="M9 9v3a3 3 0 0 0 5.12 2.12M15 9.34V4a3 3 0 0 0-5.94-.6"/><path d="M17 16.95A7 7 0 0 1 5 12v-2m14 0v2c0 .76-.13 1.49-.35 2.17"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>';

    // ========== WIDGET CLASS ==========
    function ChatWidget(config) {
        this.config = config;
        this.adminId = config.admin_id;
        this.appName = config.app_name || 'Support';
        this.translations = config.translations || {};
        this.lang = config.lang || 'fr';
        this.isOpen = false;
        this.phone = localStorage.getItem('cwgt_phone') || '';
        this.customerName = localStorage.getItem('cwgt_name') || '';
        this.conversationId = localStorage.getItem('cwgt_conv_' + this.adminId) || null;
        this.messages = [];
        this.lastMessageId = 0;
        this.pollTimer = null;
        this.unreadCount = 0;
        this.sending = false;

        // WebRTC state
        this.callState = 'idle'; // idle, incoming, connecting, connected
        this.peerConnection = null;
        this.localStream = null;
        this.isMuted = false;
        this.callDuration = 0;
        this.callDurationFormatted = '00:00';
        this.callTimer = null;
        this.rtcPollTimer = null;
        this.lastRtcMessageId = 0;
        this.pendingCandidates = [];
        this.incomingOffer = null;
        this._callHistorySent = false;
        this._messagesLoaded = false;

        this.injectStyles();
        this.createDOM();
        this.bindEvents();

        // If we have a stored conversation, try resuming
        if (this.conversationId && this.phone) {
            this.showChat();
            this.loadMessages();
            this.startPolling();
        }
    }

    ChatWidget.prototype.t = function(key, fallback) {
        return this.translations[key] || fallback || key;
    };

    ChatWidget.prototype.injectStyles = function() {
        var style = document.createElement('style');
        style.textContent = CSS;
        document.head.appendChild(style);
    };

    ChatWidget.prototype.createDOM = function() {
        var container = document.createElement('div');
        container.id = 'cwgt-container';

        container.innerHTML = '\
        <button id="cwgt-bubble" aria-label="' + this.t('open_chat', 'Open chat') + '">\
            <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>\
            <span id="cwgt-badge" style="display:none">0</span>\
        </button>\
        <div id="cwgt-window">\
            <div id="cwgt-header">\
                <div id="cwgt-header-left">\
                    <div id="cwgt-header-avatar">\
                        <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>\
                    </div>\
                    <div>\
                        <div id="cwgt-header-title">' + this.escapeHtml(this.appName) + '</div>\
                        <div id="cwgt-header-subtitle">' + this.t('live_chat', 'Live chat') + '</div>\
                    </div>\
                </div>\
                <button id="cwgt-close"><svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>\
            </div>\
            <div id="cwgt-incoming-call">\
                <div class="cwgt-ic-info">\
                    <div class="cwgt-ic-icon">' + PHONE_SVG + '</div>\
                    <div>\
                        <div class="cwgt-ic-text">' + this.t('incoming_call', 'Incoming call') + '</div>\
                        <div class="cwgt-ic-sub">' + this.t('support_calling', 'Support is calling you...') + '</div>\
                    </div>\
                </div>\
                <div class="cwgt-ic-btns">\
                    <button class="cwgt-btn-accept" id="cwgt-accept-call">' + PHONE_SVG + ' ' + this.t('accept', 'Accept') + '</button>\
                    <button class="cwgt-btn-reject" id="cwgt-reject-call">' + this.t('reject', 'Reject') + '</button>\
                </div>\
            </div>\
            <div id="cwgt-active-call">\
                <div class="cwgt-ac-info">\
                    <div class="cwgt-ac-dot"></div>\
                    <div>\
                        <div class="cwgt-ac-label">' + this.t('in_call', 'In call') + '</div>\
                        <span class="cwgt-ac-time" id="cwgt-call-duration">00:00</span>\
                    </div>\
                </div>\
                <div class="cwgt-ac-btns">\
                    <button class="cwgt-btn-mute" id="cwgt-mute-btn">' + MIC_SVG + '</button>\
                    <button class="cwgt-btn-hangup" id="cwgt-hangup-btn">' + PHONE_SVG + '</button>\
                </div>\
            </div>\
            <audio id="cwgt-remote-audio" autoplay></audio>\
            <div id="cwgt-login">\
                <h3>' + this.t('welcome', 'Welcome!') + '</h3>\
                <p>' + this.t('welcome_desc', 'Enter your information to start chatting.') + '</p>\
                <div id="cwgt-login-error"></div>\
                <label for="cwgt-phone-input">' + this.t('phone_label', 'Phone *') + '</label>\
                <input type="tel" id="cwgt-phone-input" placeholder="' + this.t('phone_placeholder', '+229 97 00 00 00') + '" value="' + this.escapeHtml(this.phone) + '">\
                <label for="cwgt-name-input">' + this.t('name_label', 'Name (optional)') + '</label>\
                <input type="text" id="cwgt-name-input" placeholder="' + this.t('name_placeholder', 'Your name') + '" value="' + this.escapeHtml(this.customerName) + '">\
                <button id="cwgt-login-btn">' + this.t('start_chat', 'Start chat') + '</button>\
            </div>\
            <div id="cwgt-chat">\
                <div id="cwgt-messages">\
                    <div id="cwgt-empty">\
                        <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>\
                        <p>' + this.t('send_message_hint', 'Send a message to start') + '</p>\
                    </div>\
                </div>\
                <div id="cwgt-input-area">\
                    <input type="text" id="cwgt-input" placeholder="' + this.t('write_message', 'Write a message...') + '">\
                    <button id="cwgt-send" disabled>\
                        <svg viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>\
                    </button>\
                </div>\
            </div>\
        </div>';

        document.body.appendChild(container);

        // Cache DOM refs
        this.els = {
            bubble: document.getElementById('cwgt-bubble'),
            badge: document.getElementById('cwgt-badge'),
            window: document.getElementById('cwgt-window'),
            close: document.getElementById('cwgt-close'),
            login: document.getElementById('cwgt-login'),
            loginBtn: document.getElementById('cwgt-login-btn'),
            loginError: document.getElementById('cwgt-login-error'),
            phoneInput: document.getElementById('cwgt-phone-input'),
            nameInput: document.getElementById('cwgt-name-input'),
            chat: document.getElementById('cwgt-chat'),
            messages: document.getElementById('cwgt-messages'),
            empty: document.getElementById('cwgt-empty'),
            inputArea: document.getElementById('cwgt-input-area'),
            input: document.getElementById('cwgt-input'),
            send: document.getElementById('cwgt-send'),
            incomingCall: document.getElementById('cwgt-incoming-call'),
            activeCall: document.getElementById('cwgt-active-call'),
            callDuration: document.getElementById('cwgt-call-duration'),
            acceptBtn: document.getElementById('cwgt-accept-call'),
            rejectBtn: document.getElementById('cwgt-reject-call'),
            muteBtn: document.getElementById('cwgt-mute-btn'),
            hangupBtn: document.getElementById('cwgt-hangup-btn'),
            remoteAudio: document.getElementById('cwgt-remote-audio')
        };
    };

    ChatWidget.prototype.bindEvents = function() {
        var self = this;

        this.els.bubble.addEventListener('click', function() { self.toggle(); });
        this.els.close.addEventListener('click', function() { self.close(); });
        this.els.loginBtn.addEventListener('click', function() { self.startChat(); });
        this.els.phoneInput.addEventListener('keydown', function(e) { if (e.key === 'Enter') self.startChat(); });
        this.els.nameInput.addEventListener('keydown', function(e) { if (e.key === 'Enter') self.startChat(); });
        this.els.send.addEventListener('click', function() { self.sendMessage(); });
        this.els.input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); self.sendMessage(); }
        });
        this.els.input.addEventListener('input', function() {
            self.els.send.disabled = !self.els.input.value.trim();
        });

        // Call buttons
        this.els.acceptBtn.addEventListener('click', function() { self.acceptCall(); });
        this.els.rejectBtn.addEventListener('click', function() { self.rejectCall(); });
        this.els.muteBtn.addEventListener('click', function() { self.toggleMute(); });
        this.els.hangupBtn.addEventListener('click', function() { self.endCall(); });
    };

    ChatWidget.prototype.toggle = function() {
        if (this.isOpen) this.close(); else this.open();
    };

    ChatWidget.prototype.open = function() {
        this.isOpen = true;
        this.els.window.style.display = 'flex';
        this.unreadCount = 0;
        this.els.badge.style.display = 'none';
        this.els.bubble.classList.remove('cwgt-has-notif');
        if (this.conversationId) {
            this.els.input.focus();
            this.scrollToBottom();
        } else {
            this.els.phoneInput.focus();
        }
    };

    ChatWidget.prototype.close = function() {
        this.isOpen = false;
        this.els.window.style.display = 'none';
    };

    ChatWidget.prototype.showChat = function() {
        this.els.login.style.display = 'none';
        this.els.chat.style.display = 'flex';
    };

    ChatWidget.prototype.showLoginError = function(msg) {
        this.els.loginError.textContent = msg;
        this.els.loginError.style.display = 'block';
    };

    ChatWidget.prototype.startChat = function() {
        var phone = this.els.phoneInput.value.trim();
        var name = this.els.nameInput.value.trim();

        if (!phone) { this.showLoginError(this.t('phone_required', 'Phone number is required')); return; }

        var cleanPhone = phone.replace(/[^0-9+]/g, '');
        if (cleanPhone.length < 8) { this.showLoginError(this.t('phone_invalid', 'Invalid phone number (min 8 digits)')); return; }

        this.els.loginError.style.display = 'none';
        this.els.loginBtn.disabled = true;
        this.els.loginBtn.textContent = this.t('connecting', 'Connecting...');

        this.phone = cleanPhone;
        this.customerName = name;
        localStorage.setItem('cwgt_phone', cleanPhone);
        localStorage.setItem('cwgt_name', name);

        var self = this;
        this.apiPost('/chat/conversations', {
            phone: cleanPhone,
            customer_name: name || null,
            admin_id: this.adminId
        }).then(function(data) {
            if (data.success && data.data && data.data.conversation) {
                self.conversationId = data.data.conversation.id;
                localStorage.setItem('cwgt_conv_' + self.adminId, self.conversationId);
                self.showChat();
                self.loadMessages();
                self.startPolling();
            } else {
                self.showLoginError(data.message || self.t('connection_error', 'Connection error'));
            }
        }).catch(function(err) {
            self.showLoginError(self.t('server_error', 'Server connection error'));
            console.error('[ChatWidget]', err);
        }).finally(function() {
            self.els.loginBtn.disabled = false;
            self.els.loginBtn.textContent = self.t('start_chat', 'Start chat');
        });
    };

    // ==========================================
    // Messages
    // ==========================================

    ChatWidget.prototype.loadMessages = function() {
        if (!this.conversationId) return;

        var self = this;
        this.apiGet('/chat/conversations/' + this.conversationId + '/messages').then(function(data) {
            if (data.success && data.data && data.data.messages) {
                self.messages = data.data.messages;
                self.renderMessages();
                if (self.messages.length > 0) {
                    var lastId = parseInt(self.messages[self.messages.length - 1].id);
                    self.lastMessageId = lastId;
                    // Sync RTC baseline to avoid reprocessing old signals
                    self.lastRtcMessageId = Math.max(self.lastRtcMessageId, lastId);
                }
                self._messagesLoaded = true;
            }
        }).catch(function(err) {
            console.error('[ChatWidget] loadMessages error:', err);
            self._messagesLoaded = true;
        });
    };

    ChatWidget.prototype.startPolling = function() {
        if (this.pollTimer) clearInterval(this.pollTimer);
        if (this.rtcPollTimer) clearInterval(this.rtcPollTimer);
        var self = this;
        // Text message polling every 3s
        this.pollTimer = setInterval(function() { self.poll(); }, 3000);
        // WebRTC signaling polling every 1s — delay start until messages are loaded
        // to avoid reprocessing old signals with lastRtcMessageId=0
        this.lastRtcMessageId = this.lastMessageId;
        function startRtcPoll() {
            if (self._messagesLoaded) {
                self.lastRtcMessageId = Math.max(self.lastRtcMessageId, self.lastMessageId);
                self.rtcPollTimer = setInterval(function() { self.pollRtc(); }, 1000);
            } else {
                setTimeout(startRtcPoll, 500);
            }
        }
        startRtcPoll();
    };

    ChatWidget.prototype.poll = function() {
        if (!this.phone) return;

        var self = this;
        var url = '/chat/messages/poll&phone=' + encodeURIComponent(this.phone)
            + '&after_id=' + this.lastMessageId
            + '&admin_id=' + this.adminId;

        this.apiGet(url).then(function(data) {
            if (!data.success || !data.data) return;
            var msgs = data.data.messages || [];
            if (msgs.length === 0) return;

            // Separate text and call history messages
            var displayMsgs = [];
            for (var i = 0; i < msgs.length; i++) {
                var mt = msgs[i].message_type || 'text';
                if (mt === 'text') {
                    displayMsgs.push(msgs[i]);
                }
                // webrtc messages are handled by pollRtc
            }

            // Always update lastMessageId
            self.lastMessageId = Math.max(self.lastMessageId, parseInt(msgs[msgs.length - 1].id));

            if (displayMsgs.length === 0) return;

            // Check for new admin messages
            var adminMsgs = [];
            for (var j = 0; j < displayMsgs.length; j++) {
                // Avoid duplicates
                var isDup = false;
                for (var k = 0; k < self.messages.length; k++) {
                    if (String(self.messages[k].id) === String(displayMsgs[j].id)) { isDup = true; break; }
                }
                if (!isDup) {
                    self.messages.push(displayMsgs[j]);
                    if (displayMsgs[j].sender_type === 'admin') {
                        // Check if it's a real text message (not call history)
                        try { var p = JSON.parse(displayMsgs[j].message); if (p._type === 'call') continue; } catch(e) {}
                        adminMsgs.push(displayMsgs[j]);
                    }
                }
            }
            self.renderMessages();

            // Notify for new admin messages
            if (adminMsgs.length > 0) {
                if (!self.isOpen) {
                    self.unreadCount += adminMsgs.length;
                    self.els.badge.textContent = self.unreadCount;
                    self.els.badge.style.display = 'flex';
                    self.els.bubble.classList.remove('cwgt-has-notif');
                    void self.els.bubble.offsetWidth;
                    self.els.bubble.classList.add('cwgt-has-notif');
                }
                self.playNotifSound();
            }

            // Update conversation_id if needed
            if (data.data.conversation_id && !self.conversationId) {
                self.conversationId = data.data.conversation_id;
                localStorage.setItem('cwgt_conv_' + self.adminId, self.conversationId);
            }
        }).catch(function(err) {
            console.error('[ChatWidget] poll error:', err);
        });
    };

    ChatWidget.prototype.sendMessage = function() {
        var text = this.els.input.value.trim();
        if (!text || !this.conversationId || this.sending) return;

        this.sending = true;
        this.els.input.value = '';
        this.els.send.disabled = true;

        var tempMsg = {
            id: 'temp_' + Date.now(),
            message: text,
            sender_type: 'customer',
            created_at: new Date().toISOString(),
            _pending: true
        };
        this.messages.push(tempMsg);
        this.renderMessages();

        var self = this;
        this.apiPost('/chat/conversations/' + this.conversationId + '/messages', {
            message: text,
            sender_type: 'customer',
            message_type: 'text'
        }).then(function(data) {
            if (data.success && data.data && data.data.message) {
                for (var i = self.messages.length - 1; i >= 0; i--) {
                    if (self.messages[i].id === tempMsg.id) {
                        self.messages[i] = data.data.message;
                        break;
                    }
                }
                self.lastMessageId = Math.max(self.lastMessageId, parseInt(data.data.message.id));
                self.renderMessages();
            } else {
                self.messages = self.messages.filter(function(m) { return m.id !== tempMsg.id; });
                self.renderMessages();
            }
        }).catch(function(err) {
            console.error('[ChatWidget] send error:', err);
            self.messages = self.messages.filter(function(m) { return m.id !== tempMsg.id; });
            self.renderMessages();
        }).finally(function() {
            self.sending = false;
            self.els.send.disabled = !self.els.input.value.trim();
        });
    };

    ChatWidget.prototype.renderMessages = function() {
        var container = this.els.messages;
        var html = '';
        var hasVisible = false;

        for (var i = 0; i < this.messages.length; i++) {
            var msg = this.messages[i];
            var mt = msg.message_type || 'text';

            // Skip webrtc signaling
            if (mt === 'webrtc') continue;

            // Check for call history
            var callData = null;
            try { callData = JSON.parse(msg.message); } catch(e) {}

            if (callData && callData._type === 'call') {
                // Render call history bubble
                var callText = '';
                var callClass = '';
                var dur = callData.duration || 0;
                var dm = String(Math.floor(dur / 60)).padStart(2, '0');
                var ds = String(dur % 60).padStart(2, '0');
                switch (callData.status) {
                    case 'completed': callText = this.t('voice_call', 'Voice call') + ' - ' + dm + ':' + ds; callClass = 'cwgt-call-ok'; break;
                    case 'rejected': callText = this.t('call_rejected', 'Call rejected'); callClass = 'cwgt-call-missed'; break;
                    case 'missed': callText = this.t('call_missed', 'Missed call'); callClass = 'cwgt-call-missed'; break;
                    case 'failed': callText = this.t('call_failed', 'Call failed'); callClass = 'cwgt-call-missed'; break;
                    default: callText = this.t('call_label', 'Call'); callClass = '';
                }
                html += '<div class="cwgt-msg-call">'
                    + '<svg class="' + callClass + '" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>'
                    + '<span class="' + callClass + '">' + callText + '</span>'
                    + '<span style="font-size:11px;color:#9ca3af;">' + this.formatTime(msg.created_at) + '</span>'
                    + '</div>';
                hasVisible = true;
                continue;
            }

            // Regular text message
            var isCustomer = msg.sender_type === 'customer';
            var cls = isCustomer ? 'cwgt-msg-customer' : 'cwgt-msg-admin';
            html += '<div class="cwgt-msg ' + cls + '">'
                + '<div>' + this.escapeHtml(msg.message) + '</div>'
                + '<div class="cwgt-msg-time">' + this.formatTime(msg.created_at) + (msg._pending ? ' ...' : '') + '</div>'
                + '</div>';
            hasVisible = true;
        }

        this.els.empty.style.display = hasVisible ? 'none' : 'block';

        var emptyEl = this.els.empty;
        container.innerHTML = '';
        container.appendChild(emptyEl);
        container.insertAdjacentHTML('beforeend', html);

        this.scrollToBottom();
    };

    // ==========================================
    // WebRTC Audio Call
    // ==========================================

    ChatWidget.prototype.getRtcConfig = function() {
        return {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' }
            ]
        };
    };

    ChatWidget.prototype.sendRtcSignal = function(action, payload) {
        if (!this.conversationId) return Promise.resolve();
        var data = JSON.stringify(Object.assign({ action: action }, payload || {}));
        return this.apiPost('/chat/conversations/' + this.conversationId + '/messages', {
            message: data,
            sender_type: 'customer',
            message_type: 'webrtc'
        });
    };

    ChatWidget.prototype.pollRtc = function() {
        if (!this.conversationId) return;
        var self = this;
        var url = '/chat/conversations/' + this.conversationId + '/messages&after_id=' + this.lastRtcMessageId + '&type=webrtc';
        this.apiGet(url).then(function(data) {
            var msgs = data.data?.messages || [];
            for (var i = 0; i < msgs.length; i++) {
                var msg = msgs[i];
                self.lastRtcMessageId = Math.max(self.lastRtcMessageId, parseInt(msg.id));
                // Ignore old signals (> 30s)
                var age = (Date.now() - new Date(msg.created_at).getTime()) / 1000;
                if (age > 30) continue;
                // Only process admin signals
                if (msg.sender_type === 'admin') {
                    try {
                        var signal = JSON.parse(msg.message);
                        self.handleRtcSignal(signal);
                    } catch(e) {}
                }
            }
        }).catch(function() {});
    };

    ChatWidget.prototype.handleRtcSignal = function(signal) {
        var self = this;
        switch (signal.action) {
            case 'call_offer':
                if (this.callState !== 'idle') break;
                this.incomingOffer = signal.sdp;
                this.callState = 'incoming';
                this.updateCallUI();
                // Auto-open widget window for incoming call
                if (!this.isOpen) this.open();
                this.playRingtone();
                break;

            case 'call_answer':
                // Not used on client side (admin answers, not us)
                break;

            case 'ice_candidate':
                if (this.peerConnection && this.peerConnection.remoteDescription) {
                    try { this.peerConnection.addIceCandidate(new RTCIceCandidate(signal.candidate)); } catch(e) {}
                } else {
                    this.pendingCandidates.push(signal.candidate);
                }
                break;

            case 'call_reject':
                this.stopRingtone();
                this.cleanupCall();
                break;

            case 'call_end':
                this.stopRingtone();
                // Ignore call_end during 'connecting' — we just accepted, give ICE time
                if (this.callState === 'connecting') {
                    console.log('[ChatWidget] Ignoring call_end during connecting phase');
                    break;
                }
                if (this.callState === 'connected') {
                    this.sendCallHistory('completed', this.callDuration);
                } else if (this.callState === 'incoming') {
                    this.sendCallHistory('missed', 0);
                }
                this.cleanupCall();
                break;
        }
    };

    ChatWidget.prototype.showCallError = function(msg) {
        // Show error as a system message in the chat
        var el = document.createElement('div');
        el.style.cssText = 'text-align:center;padding:8px 12px;margin:4px 0;background:#fef2f2;color:#dc2626;border-radius:12px;font-size:12px;border:1px solid #fecaca;';
        el.textContent = msg;
        if (this.els.messages) this.els.messages.appendChild(el);
        this.scrollToBottom();
    };

    ChatWidget.prototype.acceptCall = function() {
        if (this.callState !== 'incoming' || !this.incomingOffer) return;
        this.stopRingtone();
        this.callState = 'connecting';
        this._callHistorySent = false;
        this.updateCallUI();
        console.log('[ChatWidget] acceptCall: starting...');

        // Check if getUserMedia is available (requires HTTPS or localhost)
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            console.error('[ChatWidget] getUserMedia not available (HTTP context?)');
            this.showCallError(this.t('mic_requires_https', 'Microphone requires a secure connection (HTTPS)'));
            this.sendCallHistory('failed', 0);
            this.cleanupCall();
            return;
        }

        var self = this;
        var offerSdp = this.incomingOffer; // Store locally in case it gets cleared

        navigator.mediaDevices.getUserMedia({ audio: true, video: false }).then(function(stream) {
            console.log('[ChatWidget] acceptCall: got audio stream');
            // Check that we're still in connecting state (not cleaned up while waiting for permission)
            if (self.callState !== 'connecting') {
                console.log('[ChatWidget] acceptCall: state changed to', self.callState, ', aborting');
                stream.getTracks().forEach(function(t) { t.stop(); });
                return Promise.reject('__aborted__');
            }

            self.localStream = stream;
            self.peerConnection = new RTCPeerConnection(self.getRtcConfig());
            console.log('[ChatWidget] acceptCall: PeerConnection created');

            stream.getTracks().forEach(function(track) {
                self.peerConnection.addTrack(track, stream);
            });

            self.peerConnection.ontrack = function(event) {
                console.log('[ChatWidget] ontrack: got remote stream');
                if (self.els.remoteAudio) self.els.remoteAudio.srcObject = event.streams[0];
            };

            self.peerConnection.onicecandidate = function(event) {
                if (event.candidate) {
                    self.sendRtcSignal('ice_candidate', { candidate: event.candidate.toJSON() });
                }
            };

            self.peerConnection.onconnectionstatechange = function() {
                var state = self.peerConnection?.connectionState;
                console.log('[ChatWidget] connectionState:', state, 'callState:', self.callState);
                if (state === 'connected') {
                    if (self.callState === 'connecting') {
                        self.callState = 'connected';
                        self.incomingOffer = null;
                        self.startCallTimer();
                        self.updateCallUI();
                    }
                } else if (state === 'failed') {
                    self.showCallError(self.t('audio_connection_failed', 'Audio connection failed'));
                    self.sendCallHistory('failed', self.callDuration);
                    self.cleanupCall();
                } else if (state === 'closed' && self.callState === 'connected') {
                    self.sendCallHistory('completed', self.callDuration);
                    self.cleanupCall();
                }
            };

            self.peerConnection.oniceconnectionstatechange = function() {
                var state = self.peerConnection?.iceConnectionState;
                console.log('[ChatWidget] iceConnectionState:', state);
            };

            console.log('[ChatWidget] acceptCall: setting remote description...');
            return self.peerConnection.setRemoteDescription(
                new RTCSessionDescription({ type: 'offer', sdp: offerSdp })
            );
        }).then(function() {
            if (self.callState !== 'connecting') return Promise.reject('__aborted__');
            console.log('[ChatWidget] acceptCall: remote description set, applying ICE candidates...');

            self.pendingCandidates.forEach(function(c) {
                try { self.peerConnection.addIceCandidate(new RTCIceCandidate(c)); } catch(e) {}
            });
            self.pendingCandidates = [];

            console.log('[ChatWidget] acceptCall: creating answer...');
            return self.peerConnection.createAnswer();
        }).then(function(answer) {
            if (self.callState !== 'connecting') return Promise.reject('__aborted__');
            console.log('[ChatWidget] acceptCall: setting local description...');
            return self.peerConnection.setLocalDescription(answer).then(function() {
                console.log('[ChatWidget] acceptCall: sending answer to admin...');
                return self.sendRtcSignal('call_answer', { sdp: answer.sdp });
            });
        }).then(function() {
            if (self.callState === 'connecting') {
                console.log('[ChatWidget] acceptCall: answer sent, waiting for ICE...');
                // If connectionState is already 'connected', handle it now
                if (self.peerConnection && self.peerConnection.connectionState === 'connected') {
                    self.callState = 'connected';
                    self.incomingOffer = null;
                    self.startCallTimer();
                    self.updateCallUI();
                }
            }
        }).catch(function(err) {
            if (err === '__aborted__') return;
            console.error('[ChatWidget] acceptCall error:', err);
            if (self.callState !== 'connected' && self.callState !== 'idle') {
                self.showCallError((err.message || err.name || String(err)));
                self.sendCallHistory('failed', 0);
                self.cleanupCall();
            }
        });
    };

    ChatWidget.prototype.rejectCall = function() {
        this.stopRingtone();
        this.sendRtcSignal('call_reject');
        this.sendCallHistory('rejected', 0);
        this.cleanupCall();
    };

    ChatWidget.prototype.endCall = function() {
        if (this.callState !== 'idle') {
            var status = this.callState === 'connected' ? 'completed' : 'missed';
            this.sendRtcSignal('call_end');
            this.sendCallHistory(status, this.callDuration);
        }
        this.stopRingtone();
        this.cleanupCall();
    };

    ChatWidget.prototype.toggleMute = function() {
        if (!this.localStream) return;
        this.isMuted = !this.isMuted;
        this.localStream.getAudioTracks().forEach(function(track) {
            track.enabled = !this.isMuted;
        }.bind(this));
        this.els.muteBtn.innerHTML = this.isMuted ? MIC_OFF_SVG : MIC_SVG;
        this.els.muteBtn.classList.toggle('cwgt-muted', this.isMuted);
    };

    ChatWidget.prototype.startCallTimer = function() {
        this.callDuration = 0;
        this.callDurationFormatted = '00:00';
        if (this.callTimer) clearInterval(this.callTimer);
        var self = this;
        this.callTimer = setInterval(function() {
            self.callDuration++;
            var m = String(Math.floor(self.callDuration / 60)).padStart(2, '0');
            var s = String(self.callDuration % 60).padStart(2, '0');
            self.callDurationFormatted = m + ':' + s;
            if (self.els.callDuration) self.els.callDuration.textContent = self.callDurationFormatted;
        }, 1000);
    };

    ChatWidget.prototype.sendCallHistory = function(status, duration) {
        if (!this.conversationId || this._callHistorySent) return;
        this._callHistorySent = true;

        var callData = JSON.stringify({ _type: 'call', status: status, duration: duration || 0 });
        var self = this;
        this.apiPost('/chat/conversations/' + this.conversationId + '/messages', {
            message: callData,
            sender_type: 'customer',
            message_type: 'text'
        }).then(function(data) {
            if (data.success && data.data?.message) {
                self.messages.push(data.data.message);
                self.lastMessageId = Math.max(self.lastMessageId, parseInt(data.data.message.id));
                self.renderMessages();
            }
        }).catch(function() {});
    };

    ChatWidget.prototype.cleanupCall = function() {
        if (this.peerConnection) {
            this.peerConnection.close();
            this.peerConnection = null;
        }
        if (this.localStream) {
            this.localStream.getTracks().forEach(function(t) { t.stop(); });
            this.localStream = null;
        }
        if (this.els.remoteAudio) this.els.remoteAudio.srcObject = null;
        if (this.callTimer) { clearInterval(this.callTimer); this.callTimer = null; }
        this.callState = 'idle';
        this.isMuted = false;
        this.callDuration = 0;
        this.callDurationFormatted = '00:00';
        this.incomingOffer = null;
        this.pendingCandidates = [];
        this.els.muteBtn.innerHTML = MIC_SVG;
        this.els.muteBtn.classList.remove('cwgt-muted');
        this.updateCallUI();
    };

    ChatWidget.prototype.updateCallUI = function() {
        var subtitle = document.getElementById('cwgt-header-subtitle');
        // Incoming call banner
        if (this.callState === 'incoming') {
            this.els.incomingCall.classList.add('cwgt-visible');
            if (subtitle) subtitle.textContent = this.t('incoming_call', 'Incoming call') + '...';
        } else {
            this.els.incomingCall.classList.remove('cwgt-visible');
        }
        // Active call bar
        if (this.callState === 'connecting' || this.callState === 'connected') {
            this.els.activeCall.classList.add('cwgt-visible');
            this.els.callDuration.textContent = this.callState === 'connecting' ? this.t('call_connecting', 'Connecting...') : this.callDurationFormatted;
            if (subtitle) subtitle.textContent = this.callState === 'connecting' ? this.t('call_connecting', 'Connecting...') : this.t('call_in_progress', 'Call in progress');
        } else {
            this.els.activeCall.classList.remove('cwgt-visible');
        }
        // Reset subtitle when idle
        if (this.callState === 'idle') {
            if (subtitle) subtitle.textContent = this.t('live_chat', 'Live chat');
        }
    };

    // Simple ringtone using AudioContext
    ChatWidget.prototype.playRingtone = function() {
        this.stopRingtone();
        var self = this;
        this._ringtoneInterval = setInterval(function() {
            if (self.callState !== 'incoming') { self.stopRingtone(); return; }
            self.playNotifSound();
        }, 2000);
        this.playNotifSound(); // play immediately
    };

    ChatWidget.prototype.stopRingtone = function() {
        if (this._ringtoneInterval) {
            clearInterval(this._ringtoneInterval);
            this._ringtoneInterval = null;
        }
    };

    // ==========================================
    // Utilities
    // ==========================================

    ChatWidget.prototype.scrollToBottom = function() {
        var container = this.els.messages;
        setTimeout(function() { container.scrollTop = container.scrollHeight; }, 50);
    };

    ChatWidget.prototype.formatTime = function(dateStr) {
        if (!dateStr) return '';
        var date = new Date(dateStr);
        var now = new Date();
        var diff = now - date;

        if (diff < 60000) return this.t('now', 'Now');
        if (diff < 3600000) return Math.floor(diff / 60000) + 'min';
        if (diff < 86400000) {
            var h = date.getHours().toString().padStart(2, '0');
            var m = date.getMinutes().toString().padStart(2, '0');
            return h + ':' + m;
        }
        var locale = this.lang === 'fr' ? 'fr-FR' : 'en-US';
        return date.toLocaleDateString(locale, { day: '2-digit', month: '2-digit' });
    };

    ChatWidget.prototype.escapeHtml = function(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    };

    ChatWidget.prototype.playNotifSound = function() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.setValueAtTime(880, ctx.currentTime);
            osc.frequency.setValueAtTime(1047, ctx.currentTime + 0.1);
            gain.gain.setValueAtTime(0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.3);
        } catch(e) {}
    };

    // ========== API Helpers ==========
    ChatWidget.prototype.apiGet = function(route) {
        return fetch(apiBase + route, {
            method: 'GET',
            cache: 'no-store'
        }).then(function(r) { return r.json(); });
    };

    ChatWidget.prototype.apiPost = function(route, body) {
        return fetch(apiBase + route, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        }).then(function(r) { return r.json(); });
    };

    // ========== INIT ==========
    function init() {
        fetch(apiBase + '/chat/widget/config&key=' + encodeURIComponent(widgetKey), { cache: 'no-store' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success || !data.data) {
                    console.error('[ChatWidget] Cle widget invalide');
                    return;
                }
                if (!data.data.chat_enabled) {
                    console.warn('[ChatWidget] Module chat desactive');
                    return;
                }
                new ChatWidget(data.data);
            })
            .catch(function(err) {
                console.error('[ChatWidget] Erreur initialisation:', err);
            });
    }

    // Wait for DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
