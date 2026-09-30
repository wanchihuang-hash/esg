(function () {
    'use strict';
    const form = document.getElementById('aiChatForm');
    const input = document.getElementById('aiMessage');
    const messages = document.getElementById('aiMessages');
    const status = document.getElementById('aiStatus');
    const send = document.getElementById('aiSend');
    const csrf = document.getElementById('aiCsrf');
    let cooldownTimer = null;
    function startCooldown(seconds) { if(cooldownTimer) clearInterval(cooldownTimer); let remaining=Math.max(1,Number(seconds)||180); input.disabled=true; send.disabled=true; const tick=function(){ status.textContent=remaining>0?('資安安全冷卻中，'+remaining+' 秒後可重新連線。'):'冷卻已結束，您可以重新提問。'; if(remaining<=0){clearInterval(cooldownTimer); cooldownTimer=null; input.disabled=false; send.disabled=false; input.focus();} remaining--; }; tick(); cooldownTimer=setInterval(tick,1000); }
    function add(text, kind) { const el=document.createElement('div'); el.className='ai-message '+kind; el.textContent=text; messages.appendChild(el); messages.scrollTop=messages.scrollHeight; }
    if (!form) return;
    form.addEventListener('submit', async function (e) {
        e.preventDefault(); const text=input.value.trim(); if(!text) return;
        add(text,'user'); input.value=''; input.disabled=true; send.disabled=true; status.textContent='AI 正在查詢系統知識庫…';
        try {
            const body=new URLSearchParams({csrf_token:csrf.value,message:text});
            const res=await fetch('index.php?route=ai_chat',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','Accept':'application/json'},body});
            const data=await res.json(); add(data.message || 'AI 沒有回覆。', data.warning ? 'warning' : (data.ok ? 'bot' : 'warning'));
            if(data.security_disconnect){ startCooldown(data.cooldown_seconds); }
            else if(data.disconnected){ input.disabled=true; status.textContent='連線已中止；請重新登入或聯絡管理員。'; }
            else { input.disabled=false; send.disabled=false; status.textContent=data.warning ? ('範圍警告 '+(data.warnings||0)+'/3') : '僅回答 ESG-SMP 系統問題'; input.focus(); }
        } catch(err) { add('客服服務暫時無法連線，請稍後再試。','warning'); input.disabled=false; send.disabled=false; status.textContent='連線錯誤'; }
    });
})();
