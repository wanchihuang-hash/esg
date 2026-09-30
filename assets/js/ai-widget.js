(function () {
    'use strict';
    const toggle = document.getElementById('aiWidgetToggle');
    const panel = document.getElementById('aiWidget');
    const close = document.getElementById('aiWidgetClose');
    const form = document.getElementById('aiWidgetForm');
    const input = document.getElementById('aiWidgetInput');
    const messages = document.getElementById('aiWidgetMessages');
    const status = document.getElementById('aiWidgetStatus');
    const send = document.getElementById('aiWidgetSend');
    const csrf = document.getElementById('aiWidgetCsrf');
    let cooldownTimer = null;
    if (!toggle || !panel || !form) return;
    function add(text, type) { const el=document.createElement('div'); el.className='ai-widget-message '+type; el.textContent=text; messages.appendChild(el); messages.scrollTop=messages.scrollHeight; }
    function open() { panel.hidden=false; toggle.setAttribute('aria-expanded','true'); input.focus(); }
    function shut() { panel.hidden=true; toggle.setAttribute('aria-expanded','false'); toggle.focus(); }
    function startCooldown(seconds) {
        if (cooldownTimer) clearInterval(cooldownTimer);
        let remaining = Math.max(1, Number(seconds) || 180);
        input.disabled=true; send.disabled=true;
        const tick = function () {
            status.textContent = remaining > 0 ? ('資安安全冷卻中，'+remaining+' 秒後可重新連線。') : '冷卻已結束，您可以重新提問。';
            if (remaining <= 0) { clearInterval(cooldownTimer); cooldownTimer=null; input.disabled=false; send.disabled=false; input.focus(); }
            remaining--;
        };
        tick(); cooldownTimer=setInterval(tick,1000);
    }
    toggle.addEventListener('click', function () { panel.hidden ? open() : shut(); });
    close.addEventListener('click', shut);
    form.addEventListener('submit', async function (event) {
        event.preventDefault(); const text=input.value.trim(); if(!text) return;
        add(text,'user'); input.value=''; input.disabled=true; send.disabled=true; status.textContent='AI 正在查詢系統知識庫…';
        try {
            const body=new URLSearchParams({csrf_token:csrf.value,message:text});
            const response=await fetch('index.php?route=ai_chat',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','Accept':'application/json'},body});
            const data=await response.json(); add(data.message || 'AI 沒有回覆。', data.warning ? 'warning' : (data.ok ? 'bot' : 'warning'));
            if (data.security_disconnect) { startCooldown(data.cooldown_seconds); }
            else if (data.disconnected) { input.disabled=true; status.textContent='連線已中止，請重新登入或聯絡管理員。'; }
            else { input.disabled=false; send.disabled=false; status.textContent=data.warning ? ('範圍警告 '+(data.warnings||0)+'/3') : '僅回答 ESG-SMP 系統問題'; input.focus(); }
        } catch (error) { add('客服服務暫時無法連線，請稍後再試。','warning'); input.disabled=false; send.disabled=false; status.textContent='連線錯誤'; }
    });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && !panel.hidden) shut(); });
})();
