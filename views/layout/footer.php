        </main>
        <!-- Footer -->
        <footer class="bg-white border-top py-3 px-4 text-center text-secondary small mt-auto no-print">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <span>© <?= date('Y') ?> 企業 ESG 永續管理資訊系統 (ESG-SMP) - 遵循 ISO 14064-1 & GRI Standards 2021</span>
                <span>技術架構：PHP 8 + MySQL 8 + Bootstrap 5 + Chart.js</span>
            </div>
        </footer>
    </div>
</div>

<?php if (Auth::check() && ($currentRoute ?? '') !== 'ai_assistant' && ($currentRoute ?? '') !== 'ai_settings'): ?>
<!-- Global floating AI helpdesk widget -->
<button id="aiWidgetToggle" class="ai-widget-toggle" type="button" aria-label="開啟 AI 客服" aria-expanded="false">
    <i class="bi bi-robot"></i><span class="ai-widget-pulse"></span>
</button>
<section id="aiWidget" class="ai-widget-panel" aria-label="AI 客服小編" hidden>
    <div class="ai-widget-header">
        <div><strong><i class="bi bi-robot me-1"></i>AI 客服小編</strong><small>僅回答 ESG-SMP 系統問題</small></div>
        <button id="aiWidgetClose" type="button" class="btn btn-sm text-white" aria-label="關閉 AI 客服"><i class="bi bi-x-lg"></i></button>
    </div>
    <div id="aiWidgetMessages" class="ai-widget-messages"><div class="ai-widget-message bot">您好！我可以協助您查詢系統操作、權限、流程、報表、計算公式與資料庫說明。</div></div>
    <div class="ai-widget-footer">
        <div id="aiWidgetStatus" class="ai-widget-status">資安問題立即斷線 180 秒；範圍警告達 3 次後中止連線</div>
        <form id="aiWidgetForm" class="input-group"><input type="hidden" id="aiWidgetCsrf" value="<?= e(csrf_token()) ?>"><input id="aiWidgetInput" class="form-control" maxlength="4000" placeholder="輸入系統問題…" autocomplete="off" required><button id="aiWidgetSend" class="btn btn-success" type="submit" aria-label="送出"><i class="bi bi-send"></i></button></form>
    </div>
</section>
<script src="assets/js/ai-widget.js"></script>
<?php endif; ?>

<!-- Bootstrap 5.3.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar Toggle for Mobile
const sidebarToggle = document.getElementById('sidebarToggle');
if (sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('d-none');
    });
}
</script>
</body>
</html>
