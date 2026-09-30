<?php
require __DIR__ . '/../layout/header.php';
$cfg = AiAssistant::settings();
?>
<div class="ai-hero p-4 mb-4"><div class="d-flex align-items-center gap-3"><i class="bi bi-robot" style="font-size:3rem"></i><div><h1 class="h3 mb-1">AI 客服小編</h1><p class="mb-0 opacity-75">只協助 ESG-SMP 的操作、流程、報表、公式與資料庫說明。</p></div></div></div>
<?php if (!$cfg['enabled'] || !AiAssistant::hasApiKey()): ?><div class="alert ai-disabled"><i class="bi bi-info-circle me-2"></i>AI 客服尚未完成設定，請通知系統管理員到「AI 客服設定」填入 API 金鑰並測試連線。</div><?php endif; ?>
<div class="card border-0 shadow-sm ai-chat-shell"><div id="aiMessages" class="ai-messages"><div class="ai-message bot">您好，我是 ESG-SMP AI 客服小編。\n我只能回答本系統的操作、權限、工作流、計算公式、報表與資料庫結構問題。\n\n您可以問我：如何新增 GHG 資料？為什麼報表不含草稿？FR／SR 怎麼計算？</div></div><div class="card-footer bg-white"><div id="aiStatus" class="ai-status text-muted mb-2">僅回答 ESG-SMP 系統問題 · 資安問題將立即斷線 180 秒 · 第 3 次範圍警告後會中止連線</div><form id="aiChatForm" class="input-group"><input type="hidden" id="aiCsrf" value="<?= e(csrf_token()) ?>"><input id="aiMessage" class="form-control" maxlength="4000" placeholder="輸入系統操作或資料庫問題…" autocomplete="off" required><button id="aiSend" class="btn btn-success" type="submit"><i class="bi bi-send me-1"></i>送出</button></form></div></div>
<script src="assets/js/ai.js"></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
