/**
 * chat_bubble.js
 * Place in: public/js/chat_bubble.js
 *
 * Usage: include this script in your challenge/index.html.twig
 * Requires: challenge row has data-id attribute
 * Routes needed:
 *   GET  /challenge-chat/{id}/messages
 *   POST /challenge-chat/{id}/send
 *   POST /challenge-chat/{id}/schedule-meeting
 *   POST /challenge-chat/meeting/{meetingId}/cancel
 */

(function () {
  'use strict';

  // ─── CSS injection ───────────────────────────────────────────────────────────
  const CSS = `
    @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;700&family=Syne:wght@400;600;700;800&display=swap');

    :root {
      --cb-bg:        #050710;
      --cb-surface:   #0c0f1e;
      --cb-card:      #111428;
      --cb-border:    rgba(80, 200, 255, 0.12);
      --cb-accent:    #00e5ff;
      --cb-accent2:   #7c4dff;
      --cb-accent3:   #ff4081;
      --cb-success:   #00e676;
      --cb-warn:      #ffab40;
      --cb-text:      #e8f0fe;
      --cb-muted:     #546e8a;
      --cb-ai:        rgba(0, 229, 255, 0.07);
      --cb-ai-border: rgba(0, 229, 255, 0.2);
      --cb-glow:      0 0 30px rgba(0, 229, 255, 0.15);
      --cb-radius:    18px;
    }

    /* ── Overlay ── */
    #cb-overlay {
      position: fixed; inset: 0; z-index: 999999;
      background: rgba(2, 4, 18, 0.88);
      backdrop-filter: blur(20px) saturate(1.5);
      display: flex; align-items: center; justify-content: center;
      opacity: 0; pointer-events: none;
      transition: opacity 0.4s cubic-bezier(.4,0,.2,1);
    }
    #cb-overlay.visible {
      opacity: 1; pointer-events: all;
    }

    /* ── Main panel ── */
    #cb-panel {
      width: min(96vw, 1100px);
      height: min(90vh, 820px);
      display: grid;
      grid-template-columns: 320px 1fr;
      grid-template-rows: 1fr;
      background: var(--cb-bg);
      border: 1px solid var(--cb-border);
      border-radius: 24px;
      overflow: hidden;
      box-shadow: 0 0 0 1px rgba(0,229,255,0.06), 0 40px 120px rgba(0,0,0,0.8), var(--cb-glow);
      transform: scale(0.94) translateY(20px);
      transition: transform 0.4s cubic-bezier(.34,1.56,.64,1);
      font-family: 'Syne', sans-serif;
    }
    #cb-overlay.visible #cb-panel {
      transform: scale(1) translateY(0);
    }

    /* ── Sidebar ── */
    #cb-sidebar {
      background: var(--cb-surface);
      border-right: 1px solid var(--cb-border);
      display: flex; flex-direction: column;
      overflow: hidden;
    }

    .cb-sidebar-header {
      padding: 22px 20px 16px;
      border-bottom: 1px solid var(--cb-border);
      background: linear-gradient(160deg, rgba(0,229,255,.06), rgba(124,77,255,.04));
    }
    .cb-challenge-badge {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 10px; font-weight: 700; letter-spacing: .12em;
      text-transform: uppercase; color: var(--cb-accent);
      background: rgba(0,229,255,.08);
      border: 1px solid var(--cb-ai-border);
      padding: 4px 10px; border-radius: 20px; margin-bottom: 10px;
    }
    .cb-challenge-badge::before {
      content: ''; width: 6px; height: 6px; border-radius: 50%;
      background: var(--cb-accent);
      box-shadow: 0 0 8px var(--cb-accent);
      animation: cb-pulse 2s infinite;
    }
    @keyframes cb-pulse {
      0%,100%{opacity:1;transform:scale(1)}
      50%{opacity:.5;transform:scale(1.4)}
    }
    .cb-challenge-title {
      color: var(--cb-text); font-size: 15px; font-weight: 700;
      line-height: 1.3; margin-bottom: 6px;
    }
    .cb-challenge-meta {
      color: var(--cb-muted); font-size: 11px;
      font-family: 'JetBrains Mono', monospace;
    }

    /* Tasks panel in sidebar */
    .cb-tasks-label {
      padding: 14px 20px 8px;
      font-size: 10px; font-weight: 700; letter-spacing: .12em;
      text-transform: uppercase; color: var(--cb-muted);
    }
    #cb-tasks-list {
      flex: 1; overflow-y: auto; padding: 0 10px 10px;
    }
    #cb-tasks-list::-webkit-scrollbar { width: 3px; }
    #cb-tasks-list::-webkit-scrollbar-thumb { background: rgba(0,229,255,.2); border-radius: 3px; }

    .cb-task-item {
      display: flex; align-items: flex-start; gap: 10px;
      padding: 10px 12px; border-radius: 10px; margin-bottom: 4px;
      cursor: pointer; transition: background .2s;
      border: 1px solid transparent;
    }
    .cb-task-item:hover { background: rgba(255,255,255,.03); border-color: var(--cb-border); }
    .cb-task-check {
      width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px;
      border: 1.5px solid var(--cb-muted); border-radius: 5px;
      display: flex; align-items: center; justify-content: center;
      font-size: 10px; transition: all .2s;
    }
    .cb-task-check.done {
      background: var(--cb-accent); border-color: var(--cb-accent);
      box-shadow: 0 0 10px rgba(0,229,255,.4);
      color: #000;
    }
    .cb-task-info { flex: 1; min-width: 0; }
    .cb-task-name {
      font-size: 12px; font-weight: 600; color: var(--cb-text);
      line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .cb-task-name.done { text-decoration: line-through; color: var(--cb-muted); }
    .cb-task-pts {
      font-size: 10px; color: var(--cb-warn);
      font-family: 'JetBrains Mono', monospace; margin-top: 2px;
    }
    .cb-progress-bar {
      height: 3px; background: rgba(255,255,255,.05);
      border-radius: 3px; margin: 12px 20px 0;
    }
    .cb-progress-fill {
      height: 100%; border-radius: 3px;
      background: linear-gradient(90deg, var(--cb-accent2), var(--cb-accent));
      transition: width .6s ease;
    }
    .cb-progress-text {
      padding: 6px 20px 12px;
      font-size: 10px; color: var(--cb-muted);
      font-family: 'JetBrains Mono', monospace;
    }

    /* Meetings panel in sidebar */
    .cb-meetings-section {
      border-top: 1px solid var(--cb-border);
      padding: 10px;
    }
    .cb-meetings-label {
      padding: 8px 10px 6px;
      font-size: 10px; font-weight: 700; letter-spacing: .12em;
      text-transform: uppercase; color: var(--cb-muted);
    }
    .cb-meeting-card {
      background: rgba(0,229,255,.04);
      border: 1px solid var(--cb-ai-border);
      border-radius: 10px; padding: 10px 12px; margin-bottom: 6px;
      position: relative; overflow: hidden;
    }
    .cb-meeting-card::before {
      content: ''; position: absolute; top: 0; left: 0;
      width: 100%; height: 2px;
      background: linear-gradient(90deg, var(--cb-accent), var(--cb-accent2));
    }
    .cb-meeting-type {
      font-size: 10px; font-weight: 700; color: var(--cb-accent);
      letter-spacing: .06em; margin-bottom: 4px;
    }
    .cb-meeting-date {
      font-size: 11px; color: var(--cb-text);
      font-family: 'JetBrains Mono', monospace; margin-bottom: 6px;
    }
    .cb-meeting-link {
      display: inline-flex; align-items: center; gap: 5px;
      font-size: 10px; font-weight: 600; color: var(--cb-success);
      text-decoration: none;
      background: rgba(0,230,118,.08); border: 1px solid rgba(0,230,118,.2);
      border-radius: 6px; padding: 3px 10px; transition: all .2s;
    }
    .cb-meeting-link:hover { background: rgba(0,230,118,.15); color: var(--cb-success); }

    /* ── Chat area ── */
    #cb-chat-area {
      display: flex; flex-direction: column;
      background: var(--cb-bg); min-width: 0;
    }

    /* Chat header */
    #cb-chat-header {
      padding: 18px 24px;
      border-bottom: 1px solid var(--cb-border);
      display: flex; align-items: center; justify-content: space-between;
      background: linear-gradient(90deg, rgba(124,77,255,.05), rgba(0,229,255,.03));
      flex-shrink: 0;
    }
    .cb-chat-title {
      display: flex; align-items: center; gap: 12px;
    }
    .cb-chat-title .cb-icon {
      width: 38px; height: 38px; border-radius: 11px;
      background: linear-gradient(135deg, var(--cb-accent2), var(--cb-accent));
      display: flex; align-items: center; justify-content: center;
      font-size: 18px;
      box-shadow: 0 4px 20px rgba(0,229,255,.25);
    }
    .cb-chat-title h3 {
      margin: 0; font-size: 15px; font-weight: 700; color: var(--cb-text);
    }
    .cb-chat-title p {
      margin: 2px 0 0; font-size: 11px; color: var(--cb-muted);
      font-family: 'JetBrains Mono', monospace;
    }
    .cb-header-actions { display: flex; gap: 8px; align-items: center; }

    /* Schedule meeting button */
    #cb-schedule-btn {
      display: flex; align-items: center; gap: 6px;
      padding: 8px 14px; border-radius: 10px;
      background: linear-gradient(135deg, rgba(124,77,255,.2), rgba(0,229,255,.1));
      border: 1px solid rgba(0,229,255,.25);
      color: var(--cb-accent); font-size: 12px; font-weight: 700;
      cursor: pointer; transition: all .2s; font-family: 'Syne', sans-serif;
      white-space: nowrap;
    }
    #cb-schedule-btn:hover {
      background: linear-gradient(135deg, rgba(124,77,255,.3), rgba(0,229,255,.2));
      box-shadow: 0 4px 20px rgba(0,229,255,.2);
      transform: translateY(-1px);
    }
    #cb-close-btn {
      width: 34px; height: 34px; border-radius: 9px;
      background: rgba(255,64,129,.08); border: 1px solid rgba(255,64,129,.2);
      color: var(--cb-accent3); font-size: 14px; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      transition: all .2s;
    }
    #cb-close-btn:hover { background: rgba(255,64,129,.15); transform: rotate(90deg); }

    /* Messages list */
    #cb-messages {
      flex: 1; overflow-y: auto; padding: 20px 24px;
      display: flex; flex-direction: column; gap: 14px;
    }
    #cb-messages::-webkit-scrollbar { width: 4px; }
    #cb-messages::-webkit-scrollbar-thumb { background: rgba(0,229,255,.15); border-radius: 4px; }

    /* AI system message */
    .cb-msg-system {
      display: flex; align-items: flex-start; gap: 10px;
      padding: 12px 16px; border-radius: 12px;
      background: var(--cb-ai); border: 1px solid var(--cb-ai-border);
      position: relative; overflow: hidden;
    }
    .cb-msg-system::before {
      content: ''; position: absolute; top: 0; left: 0;
      width: 100%; height: 1px;
      background: linear-gradient(90deg, transparent, var(--cb-accent), transparent);
    }
    .cb-msg-system .sys-icon {
      width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0;
      background: linear-gradient(135deg, var(--cb-accent2), var(--cb-accent));
      display: flex; align-items: center; justify-content: center;
      font-size: 13px;
    }
    .cb-msg-system .sys-text {
      flex: 1; font-size: 12px; color: rgba(0,229,255,.9);
      font-family: 'JetBrains Mono', monospace; line-height: 1.6;
    }
    .cb-msg-system .sys-text a {
      color: var(--cb-success); font-weight: 700;
    }

    /* Regular messages */
    .cb-msg-row { display: flex; gap: 10px; }
    .cb-msg-row.me { flex-direction: row-reverse; }

    .cb-avatar {
      width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      font-size: 12px; font-weight: 800; color: #fff;
      background: linear-gradient(135deg, var(--cb-accent2), var(--cb-accent3));
    }
    .cb-avatar.coach { background: linear-gradient(135deg, #ff6b35, var(--cb-warn)); }
    .cb-avatar.me    { background: linear-gradient(135deg, var(--cb-accent), var(--cb-accent2)); }

    .cb-bubble-wrap { display: flex; flex-direction: column; gap: 4px; max-width: 70%; }
    .cb-msg-row.me .cb-bubble-wrap { align-items: flex-end; }

    .cb-bubble-meta {
      font-size: 10px; color: var(--cb-muted);
      font-family: 'JetBrains Mono', monospace;
      padding: 0 4px;
    }
    .cb-bubble {
      padding: 10px 14px; border-radius: 14px;
      font-size: 13px; line-height: 1.6; color: var(--cb-text);
      background: var(--cb-card); border: 1px solid var(--cb-border);
      word-break: break-word; position: relative;
    }
    .cb-msg-row.me .cb-bubble {
      background: linear-gradient(135deg, rgba(124,77,255,.2), rgba(0,229,255,.08));
      border-color: rgba(0,229,255,.2);
      color: var(--cb-text);
    }
    .cb-bubble-time {
      font-size: 9px; color: var(--cb-muted);
      font-family: 'JetBrains Mono', monospace; margin-top: 2px;
    }
    .cb-msg-row.me .cb-bubble-time { text-align: right; }

    /* Empty state */
    .cb-empty {
      flex: 1; display: flex; flex-direction: column;
      align-items: center; justify-content: center; gap: 12px;
      color: var(--cb-muted); text-align: center;
    }
    .cb-empty-icon {
      font-size: 48px; filter: drop-shadow(0 0 20px rgba(0,229,255,.3));
      animation: cb-float 3s ease-in-out infinite;
    }
    @keyframes cb-float {
      0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)}
    }
    .cb-empty h4 { margin: 0; font-size: 14px; color: rgba(255,255,255,.4); font-weight: 600; }
    .cb-empty p  { margin: 0; font-size: 12px; font-family: 'JetBrains Mono', monospace; }

    /* Input area */
    #cb-input-area {
      padding: 16px 24px; border-top: 1px solid var(--cb-border);
      background: rgba(5,7,16,.8);
      flex-shrink: 0;
    }
    .cb-input-row {
      display: flex; gap: 10px; align-items: flex-end;
    }
    #cb-input {
      flex: 1; background: var(--cb-card);
      border: 1px solid var(--cb-border);
      border-radius: 14px; padding: 12px 16px;
      color: var(--cb-text); font-size: 13px; outline: none;
      font-family: 'Syne', sans-serif;
      resize: none; min-height: 44px; max-height: 120px;
      transition: border-color .2s, box-shadow .2s;
      line-height: 1.5;
    }
    #cb-input:focus {
      border-color: rgba(0,229,255,.4);
      box-shadow: 0 0 0 3px rgba(0,229,255,.08);
    }
    #cb-input::placeholder { color: var(--cb-muted); }
    #cb-send-btn {
      width: 44px; height: 44px; flex-shrink: 0;
      border-radius: 13px; border: none; cursor: pointer;
      background: linear-gradient(135deg, var(--cb-accent2), var(--cb-accent));
      color: #fff; font-size: 18px;
      display: flex; align-items: center; justify-content: center;
      transition: all .2s; box-shadow: 0 4px 20px rgba(0,229,255,.25);
    }
    #cb-send-btn:hover { transform: translateY(-2px) scale(1.05); box-shadow: 0 8px 30px rgba(0,229,255,.4); }
    #cb-send-btn:active { transform: scale(.95); }
    .cb-input-hint {
      font-size: 10px; color: var(--cb-muted);
      font-family: 'JetBrains Mono', monospace;
      margin-top: 8px; padding-left: 4px;
    }

    /* ── Schedule Meeting Modal ── */
    #cb-schedule-modal {
      position: absolute; inset: 0; z-index: 10;
      background: rgba(5,7,16,.95);
      display: none; align-items: center; justify-content: center;
      border-radius: 24px;
    }
    #cb-schedule-modal.open { display: flex; }
    .cb-sched-box {
      width: min(90%, 500px);
      background: var(--cb-surface);
      border: 1px solid var(--cb-ai-border);
      border-radius: 20px; padding: 30px;
      position: relative; overflow: hidden;
      box-shadow: 0 0 60px rgba(0,229,255,.1);
    }
    .cb-sched-box::before {
      content: ''; position: absolute; top: 0; left: 0; right: 0;
      height: 2px;
      background: linear-gradient(90deg, var(--cb-accent2), var(--cb-accent));
    }
    .cb-sched-header {
      display: flex; align-items: center; gap: 14px; margin-bottom: 24px;
    }
    .cb-sched-icon {
      width: 48px; height: 48px; border-radius: 14px; flex-shrink: 0;
      background: linear-gradient(135deg, var(--cb-accent2), var(--cb-accent));
      display: flex; align-items: center; justify-content: center;
      font-size: 22px;
      box-shadow: 0 0 30px rgba(0,229,255,.3);
    }
    .cb-sched-header h3 { margin: 0; font-size: 18px; font-weight: 800; color: var(--cb-text); }
    .cb-sched-header p { margin: 3px 0 0; font-size: 12px; color: var(--cb-muted); font-family: 'JetBrains Mono', monospace; }

    .cb-sched-field { margin-bottom: 16px; }
    .cb-sched-label {
      display: block; font-size: 10px; font-weight: 700; letter-spacing: .1em;
      text-transform: uppercase; color: var(--cb-accent);
      margin-bottom: 7px;
    }
    .cb-sched-input, .cb-sched-select, .cb-sched-textarea {
      width: 100%; background: var(--cb-card);
      border: 1px solid var(--cb-border); border-radius: 10px;
      padding: 10px 14px; color: var(--cb-text); font-size: 13px;
      outline: none; font-family: 'Syne', sans-serif;
      transition: border-color .2s;
      box-sizing: border-box;
    }
    .cb-sched-input:focus, .cb-sched-select:focus, .cb-sched-textarea:focus {
      border-color: rgba(0,229,255,.4);
      box-shadow: 0 0 0 3px rgba(0,229,255,.08);
    }
    .cb-sched-textarea { resize: vertical; min-height: 80px; }
    .cb-sched-select option { background: #0c0f1e; }

    /* AI suggestion box */
    #cb-ai-suggestion {
      background: var(--cb-ai); border: 1px solid var(--cb-ai-border);
      border-radius: 10px; padding: 12px 14px; margin-bottom: 16px;
      min-height: 60px; display: none;
    }
    #cb-ai-suggestion .ai-label {
      font-size: 9px; font-weight: 700; letter-spacing: .12em;
      text-transform: uppercase; color: var(--cb-accent);
      margin-bottom: 6px; display: flex; align-items: center; gap: 5px;
    }
    #cb-ai-suggestion .ai-text {
      font-size: 12px; color: rgba(200,230,255,.8);
      font-family: 'JetBrains Mono', monospace; line-height: 1.6;
    }
    /* Typing cursor effect */
    .cb-typing-cursor::after {
      content: '|'; animation: cb-blink .7s infinite; color: var(--cb-accent);
    }
    @keyframes cb-blink { 0%,100%{opacity:1} 50%{opacity:0} }

    .cb-sched-actions { display: flex; gap: 10px; margin-top: 4px; }
    .cb-sched-confirm {
      flex: 1; padding: 12px; border-radius: 10px;
      background: linear-gradient(135deg, var(--cb-accent2), var(--cb-accent));
      border: none; color: #fff; font-size: 13px; font-weight: 700;
      cursor: pointer; font-family: 'Syne', sans-serif;
      transition: all .2s; box-shadow: 0 4px 20px rgba(0,229,255,.25);
    }
    .cb-sched-confirm:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(0,229,255,.4); }
    .cb-sched-confirm:disabled { opacity: .5; cursor: not-allowed; transform: none; }
    .cb-sched-cancel {
      padding: 12px 20px; border-radius: 10px;
      background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1);
      color: var(--cb-muted); font-size: 13px; font-weight: 600;
      cursor: pointer; font-family: 'Syne', sans-serif; transition: all .2s;
    }
    .cb-sched-cancel:hover { background: rgba(255,255,255,.08); color: var(--cb-text); }

    /* Typing indicator */
    .cb-typing-indicator {
      display: flex; gap: 4px; align-items: center; padding: 6px 10px;
    }
    .cb-typing-dot {
      width: 6px; height: 6px; border-radius: 50%;
      background: var(--cb-accent); opacity: .4;
      animation: cb-type-bounce .8s infinite;
    }
    .cb-typing-dot:nth-child(2) { animation-delay: .15s; }
    .cb-typing-dot:nth-child(3) { animation-delay: .3s; }
    @keyframes cb-type-bounce {
      0%,60%,100%{transform:translateY(0);opacity:.4}
      30%{transform:translateY(-6px);opacity:1}
    }

    /* Loading skeleton */
    .cb-loading {
      display: flex; flex-direction: column; gap: 12px; padding: 20px 24px;
    }
    .cb-skeleton {
      height: 52px; border-radius: 12px;
      background: linear-gradient(90deg, var(--cb-card) 25%, rgba(255,255,255,.04) 50%, var(--cb-card) 75%);
      background-size: 200% 100%;
      animation: cb-shimmer 1.5s infinite;
    }
    @keyframes cb-shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

    /* Responsive */
    @media (max-width: 700px) {
      #cb-panel { grid-template-columns: 1fr; }
      #cb-sidebar { display: none; }
    }
  `;

  // Inject CSS
  const style = document.createElement('style');
  style.textContent = CSS;
  document.head.appendChild(style);

  // ─── Build DOM ───────────────────────────────────────────────────────────────
  const overlay = document.createElement('div');
  overlay.id = 'cb-overlay';
  overlay.innerHTML = `
    <div id="cb-panel">

      <!-- SIDEBAR -->
      <div id="cb-sidebar">
        <div class="cb-sidebar-header">
          <div class="cb-challenge-badge">⚡ LIVE SESSION</div>
          <div class="cb-challenge-title" id="cb-challenge-title">—</div>
          <div class="cb-challenge-meta" id="cb-challenge-meta">Chargement...</div>
        </div>
        <div class="cb-tasks-label">📋 Tâches du Challenge</div>
        <div id="cb-tasks-list"></div>
        <div class="cb-progress-bar"><div class="cb-progress-fill" id="cb-prog-fill" style="width:0%"></div></div>
        <div class="cb-progress-text" id="cb-prog-text">0 / 0 tâches complétées</div>
        <div class="cb-meetings-section">
          <div class="cb-meetings-label">🗓️ Réunions IA</div>
          <div id="cb-meetings-list"></div>
        </div>
      </div>

      <!-- CHAT AREA -->
      <div id="cb-chat-area">
        <div id="cb-chat-header">
          <div class="cb-chat-title">
            <div class="cb-icon">💬</div>
            <div>
              <h3>Discussion du Challenge</h3>
              <p id="cb-online-count">— participants</p>
            </div>
          </div>
          <div class="cb-header-actions">
            <button id="cb-schedule-btn" title="Planifier une réunion IA">
              🤖 Planifier une Réunion
            </button>
            <button id="cb-close-btn" title="Fermer">✕</button>
          </div>
        </div>

        <div id="cb-messages">
          <div class="cb-loading">
            <div class="cb-skeleton"></div>
            <div class="cb-skeleton" style="height:40px;width:60%;margin-left:auto"></div>
            <div class="cb-skeleton" style="height:64px;"></div>
          </div>
        </div>

        <div id="cb-input-area">
          <div class="cb-input-row">
            <textarea id="cb-input" placeholder="Écrivez un message… (Entrée pour envoyer)" rows="1"></textarea>
            <button id="cb-send-btn" title="Envoyer">➤</button>
          </div>
          <div class="cb-input-hint">↵ Entrée pour envoyer · Shift+↵ pour saut de ligne</div>
        </div>

        <!-- Schedule meeting modal (absolute inside panel) -->
        <div id="cb-schedule-modal">
          <div class="cb-sched-box">
            <div class="cb-sched-header">
              <div class="cb-sched-icon">🤖</div>
              <div>
                <h3>IA Orchestrateur</h3>
                <p>Planifier une réunion intelligente</p>
              </div>
            </div>

            <div class="cb-sched-field">
              <label class="cb-sched-label">Type de réunion</label>
              <select id="cb-meet-type" class="cb-sched-select">
                <option value="GROUP_SYNC">🔄 Sync Groupe — Revue d'avancement</option>
                <option value="FLASH_SYNC">⚡ Flash Sync — Stand-up rapide (15 min)</option>
                <option value="DEBUG">🐛 Session Déblocage — Résoudre les blocages</option>
                <option value="RETROSPECTIVE">🔍 Rétrospective — Amélioration continue</option>
              </select>
            </div>

            <div class="cb-sched-field">
              <label class="cb-sched-label">Date & Heure de la Réunion</label>
              <input type="datetime-local" id="cb-meet-date" class="cb-sched-input">
            </div>

            <div class="cb-sched-field">
              <label class="cb-sched-label">Raison / Contexte</label>
              <textarea id="cb-meet-reason" class="cb-sched-textarea"
                placeholder="Décrivez pourquoi cette réunion est nécessaire..."></textarea>
            </div>

            <div id="cb-ai-suggestion">
              <div class="ai-label">
                <span>⚡</span> Suggestion IA
              </div>
              <div class="ai-text cb-typing-cursor" id="cb-suggestion-text"></div>
            </div>

            <div class="cb-sched-actions">
              <button class="cb-sched-cancel" id="cb-sched-cancel">Annuler</button>
              <button class="cb-sched-confirm" id="cb-sched-confirm">🚀 Créer la Réunion</button>
            </div>
          </div>
        </div>
      </div>

    </div>
  `;
  document.body.appendChild(overlay);

  // ─── State ────────────────────────────────────────────────────────────────────
  let currentChallengeId   = null;
  let currentChallengeTitle = '';
  let pollInterval         = null;
  let lastMessageCount     = 0;
  let isAdmin              = false; // will be set from data attribute

  // ─── Element refs ─────────────────────────────────────────────────────────────
  const $overlay      = document.getElementById('cb-overlay');
  const $closeBtn     = document.getElementById('cb-close-btn');
  const $input        = document.getElementById('cb-input');
  const $sendBtn      = document.getElementById('cb-send-btn');
  const $messages     = document.getElementById('cb-messages');
  const $tasksList    = document.getElementById('cb-tasks-list');
  const $meetingsList = document.getElementById('cb-meetings-list');
  const $scheduleBtn  = document.getElementById('cb-schedule-btn');
  const $schedModal   = document.getElementById('cb-schedule-modal');
  const $schedCancel  = document.getElementById('cb-sched-cancel');
  const $schedConfirm = document.getElementById('cb-sched-confirm');
  const $meetType     = document.getElementById('cb-meet-type');
  const $meetDate     = document.getElementById('cb-meet-date');
  const $meetReason   = document.getElementById('cb-meet-reason');
  const $aiSuggestion = document.getElementById('cb-ai-suggestion');
  const $suggText     = document.getElementById('cb-suggestion-text');

  // Set default datetime to now + 1h
  const defaultDate = new Date(Date.now() + 3600000);
  defaultDate.setSeconds(0, 0);
  $meetDate.value = defaultDate.toISOString().slice(0, 16);

  // ─── Open / close ─────────────────────────────────────────────────────────────
  function open(challengeId, challengeTitle, adminFlag, catNom) {
    currentChallengeId    = challengeId;
    currentChallengeTitle = challengeTitle;
    isAdmin               = adminFlag;

    document.getElementById('cb-challenge-title').textContent = challengeTitle;
    document.getElementById('cb-challenge-meta').textContent  = catNom || 'Challenge';
    document.getElementById('cb-online-count').textContent    = 'Chargement…';

    // Hide schedule button if not admin
    $scheduleBtn.style.display = isAdmin ? 'flex' : 'none';

    showLoading();
    $overlay.classList.add('visible');
    document.body.style.overflow = 'hidden';

    loadData();
    pollInterval = setInterval(loadData, 5000);
    setTimeout(() => $input.focus(), 400);
  }

  function close() {
    $overlay.classList.remove('visible');
    document.body.style.overflow = '';
    if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
    currentChallengeId = null;
    $schedModal.classList.remove('open');
  }

  $closeBtn.addEventListener('click', close);
  $overlay.addEventListener('click', e => { if (e.target === $overlay) close(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });

  // ─── Load messages + meetings ─────────────────────────────────────────────────
  function loadData() {
    if (!currentChallengeId) return;

    fetch(`/challenge-chat/${currentChallengeId}/messages`)
      .then(r => r.json())
      .then(data => {
        renderMessages(data.messages || []);
        renderMeetings(data.meetings || []);
      })
      .catch(() => {});

    // Load tasks from challenge tasks endpoint
    fetch(`/challenge/${currentChallengeId}/tasks-json`)
      .then(r => r.ok ? r.json() : null)
      .then(data => { if (data) renderTasks(data.tasks || []); })
      .catch(() => {});
  }

  // ─── Render messages ──────────────────────────────────────────────────────────
  function renderMessages(messages) {
    const wasAtBottom = $messages.scrollHeight - $messages.scrollTop - $messages.clientHeight < 60;

    if (messages.length === 0) {
      $messages.innerHTML = `
        <div class="cb-empty">
          <div class="cb-empty-icon">🛸</div>
          <h4>Aucun message encore</h4>
          <p>Soyez le premier à démarrer la discussion !</p>
        </div>`;
      document.getElementById('cb-online-count').textContent = '0 message';
      return;
    }

    if (messages.length === lastMessageCount) return;
    lastMessageCount = messages.length;

    // Group by date
    const groups = {};
    messages.forEach(m => {
      if (!groups[m.date]) groups[m.date] = [];
      groups[m.date].push(m);
    });

    let html = '';
    Object.entries(groups).forEach(([date, msgs]) => {
      html += `<div style="text-align:center;margin:8px 0;">
        <span style="font-size:10px;color:var(--cb-muted);font-family:'JetBrains Mono',monospace;
          background:var(--cb-card);border:1px solid var(--cb-border);padding:3px 12px;border-radius:20px;">
          ${date}
        </span>
      </div>`;
      msgs.forEach(m => {
        html += renderMessage(m);
      });
    });

    $messages.innerHTML = html;
    document.getElementById('cb-online-count').textContent =
      `${messages.length} message${messages.length > 1 ? 's' : ''}`;

    if (wasAtBottom || messages.length === 1) {
      $messages.scrollTop = $messages.scrollHeight;
    }
  }

  function renderMessage(m) {
    // Detect AI system messages
    if (m.message.startsWith('🤖 **[IA ORCHESTRATEUR]**') || m.message.startsWith('🤖 [IA ORCHESTRATEUR]')) {
      const txt = m.message
        .replace('🤖 **[IA ORCHESTRATEUR]**', '')
        .replace('🤖 [IA ORCHESTRATEUR]', '')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank">$1</a>');
      return `
        <div class="cb-msg-system">
          <div class="sys-icon">🤖</div>
          <div class="sys-text">${txt}</div>
        </div>`;
    }

    const meClass    = m.isMe ? ' me' : '';
    const avatarClass = m.userRole === 'coach' ? ' coach' : (m.isMe ? ' me' : '');
    const roleTag    = m.userRole !== 'user'
      ? `<span style="font-size:9px;background:rgba(0,229,255,.1);color:var(--cb-accent);border:1px solid var(--cb-ai-border);padding:1px 6px;border-radius:6px;margin-left:5px;">${m.userRole.toUpperCase()}</span>`
      : '';

    return `
      <div class="cb-msg-row${meClass}">
        <div class="cb-avatar${avatarClass}">${m.initials}</div>
        <div class="cb-bubble-wrap">
          <div class="cb-bubble-meta">
            ${m.isMe ? 'Vous' : m.userName}${roleTag}
          </div>
          <div class="cb-bubble">${escapeHtml(m.message)}</div>
          <div class="cb-bubble-time">${m.createdAt}</div>
        </div>
      </div>`;
  }

  // ─── Render tasks ─────────────────────────────────────────────────────────────
  function renderTasks(tasks) {
    if (tasks.length === 0) {
      $tasksList.innerHTML = `<div style="padding:20px;text-align:center;color:var(--cb-muted);font-size:12px;">
        Aucune tâche générée.<br><span style="font-size:10px;font-family:'JetBrains Mono',monospace;">Utilisez le bouton 🤖 Tâches IA</span>
      </div>`;
      return;
    }

    const done  = tasks.filter(t => t.done).length;
    const pct   = Math.round((done / tasks.length) * 100);

    $tasksList.innerHTML = tasks.map(t => `
      <div class="cb-task-item">
        <div class="cb-task-check${t.done ? ' done' : ''}">
          ${t.done ? '✓' : ''}
        </div>
        <div class="cb-task-info">
          <div class="cb-task-name${t.done ? ' done' : ''}" title="${escapeHtml(t.title)}">${escapeHtml(t.title)}</div>
          <div class="cb-task-pts">+${t.points} pts · ${t.estimatedMinutes} min</div>
        </div>
      </div>
    `).join('');

    document.getElementById('cb-prog-fill').style.width = pct + '%';
    document.getElementById('cb-prog-text').textContent = `${done} / ${tasks.length} tâches · ${pct}%`;
  }

  // ─── Render meetings ──────────────────────────────────────────────────────────
  function renderMeetings(meetings) {
    if (meetings.length === 0) {
      $meetingsList.innerHTML = `<div style="padding:8px 10px;font-size:11px;color:var(--cb-muted);font-family:'JetBrains Mono',monospace;">
        Aucune réunion planifiée
      </div>`;
      return;
    }

    $meetingsList.innerHTML = meetings.map(m => {
      const typeLabels = {
        GROUP_SYNC: '🔄 Sync Groupe',
        FLASH_SYNC: '⚡ Flash Sync',
        DEBUG: '🐛 Déblocage',
        RETROSPECTIVE: '🔍 Rétrospective',
      };
      return `
        <div class="cb-meeting-card">
          <div class="cb-meeting-type">${typeLabels[m.type] || m.type}</div>
          <div class="cb-meeting-date">
            ${m.scheduledFor ? '📅 ' + new Date(m.scheduledFor).toLocaleString('fr-FR', {day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'}) : m.createdAt}
          </div>
          <a class="cb-meeting-link" href="${m.meetingUrl}" target="_blank">
            🎥 Rejoindre
          </a>
        </div>`;
    }).join('');
  }

  // ─── Send message ─────────────────────────────────────────────────────────────
  function sendMessage() {
    const text = $input.value.trim();
    if (!text || !currentChallengeId) return;

    $input.value = '';
    $input.style.height = 'auto';
    $sendBtn.disabled = true;

    // Optimistic render
    const optimistic = document.createElement('div');
    optimistic.innerHTML = `
      <div class="cb-msg-row me" id="cb-optimistic">
        <div class="cb-avatar me">…</div>
        <div class="cb-bubble-wrap" style="align-items:flex-end">
          <div class="cb-bubble">${escapeHtml(text)}</div>
          <div class="cb-typing-indicator">
            <div class="cb-typing-dot"></div>
            <div class="cb-typing-dot"></div>
            <div class="cb-typing-dot"></div>
          </div>
        </div>
      </div>`;
    $messages.appendChild(optimistic.firstElementChild);
    $messages.scrollTop = $messages.scrollHeight;

    fetch(`/challenge-chat/${currentChallengeId}/send`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ message: text }),
    })
      .then(r => r.json())
      .then(data => {
        $sendBtn.disabled = false;
        const opt = document.getElementById('cb-optimistic');
        if (opt) opt.remove();
        if (data.success) {
          lastMessageCount = 0; // force re-render
          loadData();
        }
      })
      .catch(() => {
        $sendBtn.disabled = false;
        const opt = document.getElementById('cb-optimistic');
        if (opt) opt.remove();
      });
  }

  $sendBtn.addEventListener('click', sendMessage);
  $input.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
  });

  // Auto-resize textarea
  $input.addEventListener('input', () => {
    $input.style.height = 'auto';
    $input.style.height = Math.min($input.scrollHeight, 120) + 'px';
  });

  // ─── Schedule meeting ─────────────────────────────────────────────────────────
  const aiSuggestions = {
    GROUP_SYNC:     "Sync Groupe recommandé : commencez par un tour de table (5 min), travaillez les tâches bloquées en sous-groupes. Présentez vos avancées.",
    FLASH_SYNC:     "Flash de 15 minutes : 1) Où en sommes-nous ? 2) Qu'est-ce qui bloque ? 3) Actions pour 48h. Restez focus, pas de digressions.",
    DEBUG:          "Session de déblocage : listez les obstacles en amont, assignez un responsable par problème. Définissez un plan d'action en 3 points précis.",
    RETROSPECTIVE:  "Rétrospective Start/Stop/Continue : what went well, what didn't, what to improve. Durée recommandée : 45 minutes.",
  };

  $meetType.addEventListener('change', showAiSuggestion);

  function showAiSuggestion() {
    const text = aiSuggestions[$meetType.value] || '';
    if (!text) { $aiSuggestion.style.display = 'none'; return; }

    $aiSuggestion.style.display = 'block';
    $suggText.textContent = '';
    $suggText.classList.add('cb-typing-cursor');

    let i = 0;
    const typeText = () => {
      if (i < text.length) {
        $suggText.textContent += text[i++];
        setTimeout(typeText, 18);
      } else {
        $suggText.classList.remove('cb-typing-cursor');
      }
    };
    typeText();
  }

  $scheduleBtn.addEventListener('click', () => {
    $schedModal.classList.add('open');
    showAiSuggestion();
  });

  $schedCancel.addEventListener('click', () => {
    $schedModal.classList.remove('open');
  });

  $schedConfirm.addEventListener('click', () => {
    const date   = $meetDate.value;
    const type   = $meetType.value;
    const reason = $meetReason.value.trim() || 'Réunion planifiée par l\'administrateur';

    if (!date) { alert('Veuillez choisir une date.'); return; }

    $schedConfirm.disabled     = true;
    $schedConfirm.textContent  = '⏳ Création en cours…';

    fetch(`/challenge-chat/${currentChallengeId}/schedule-meeting`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ scheduled_for: date, meeting_type: type, reason }),
    })
      .then(r => r.json())
      .then(data => {
        $schedConfirm.disabled    = false;
        $schedConfirm.textContent = '🚀 Créer la Réunion';

        if (data.success) {
          $schedModal.classList.remove('open');
          $meetReason.value = '';
          lastMessageCount  = 0;
          loadData();

          // Show success flash
          const flash = document.createElement('div');
          flash.style.cssText = `
            position:fixed;top:20px;right:20px;z-index:9999999;
            padding:14px 22px;border-radius:14px;
            background:linear-gradient(135deg,rgba(0,230,118,.9),rgba(0,229,255,.8));
            color:#000;font-weight:700;font-size:13px;
            box-shadow:0 8px 30px rgba(0,229,255,.4);
            animation:cb-flash-in .3s ease;
          `;
          flash.textContent = '🎉 Réunion créée ! Lien Google Meet généré.';
          document.body.appendChild(flash);
          setTimeout(() => flash.remove(), 4000);
        } else {
          alert(data.error || 'Erreur lors de la création de la réunion.');
        }
      })
      .catch(() => {
        $schedConfirm.disabled    = false;
        $schedConfirm.textContent = '🚀 Créer la Réunion';
        alert('Erreur réseau, réessayez.');
      });
  });

  // ─── Loading state ────────────────────────────────────────────────────────────
  function showLoading() {
    $messages.innerHTML = `
      <div class="cb-loading">
        <div class="cb-skeleton"></div>
        <div class="cb-skeleton" style="height:40px;width:58%;margin-left:auto"></div>
        <div class="cb-skeleton" style="height:64px;"></div>
        <div class="cb-skeleton" style="height:44px;width:70%;"></div>
      </div>`;
    $tasksList.innerHTML   = '<div class="cb-skeleton" style="height:44px;margin:10px"></div>'.repeat(4);
    $meetingsList.innerHTML = '';
    lastMessageCount = 0;
  }

  // ─── Helpers ──────────────────────────────────────────────────────────────────
  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  // ─── Hook into challenge double-click ─────────────────────────────────────────
  // This function is called from the modified dblclick handler in index.html.twig
  window.openChallengeChat = function (challengeId, challengeTitle, adminFlag, catNom) {
    open(challengeId, challengeTitle, !!adminFlag, catNom || '');
  };

  // Add flash-in keyframe
  const kf = document.createElement('style');
  kf.textContent = `@keyframes cb-flash-in{from{transform:translateX(60px);opacity:0}to{transform:translateX(0);opacity:1}}`;
  document.head.appendChild(kf);

})();