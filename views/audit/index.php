<?php
// views/audit/index.php
require __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-clock-history text-secondary me-2"></i>全生命週期稽核軌跡日誌 (Audit Trail)
        </h4>
        <div class="text-secondary small">
            符合 ISO 14064-1 & GRI 確信查證規範，100% 記錄所有登入、填報、修改、審批與匯出操作
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm p-3 mb-4 rounded-3">
    <form method="GET" action="index.php" class="row g-2 align-items-center">
        <input type="hidden" name="route" value="audit">
        
        <div class="col-md-3 col-6">
            <label class="form-label small text-secondary mb-1">動作類型</label>
            <select name="action_type" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">全部動作</option>
                <?php foreach ($actions as $act): ?>
                    <option value="<?= $act ?>" <?= $action === $act ? 'selected' : '' ?>><?= $act ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3 col-6">
            <label class="form-label small text-secondary mb-1">目標資料表</label>
            <select name="table" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">全部資料表</option>
                <?php foreach ($tables as $tbl): ?>
                    <option value="<?= $tbl ?>" <?= $table === $tbl ? 'selected' : '' ?>><?= $tbl ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-4 col-8">
            <label class="form-label small text-secondary mb-1">關鍵字 (姓名/帳號/IP)</label>
            <input type="text" name="keyword" class="form-control form-control-sm" placeholder="搜尋人員或 IP" value="<?= e($keyword) ?>">
        </div>

        <div class="col-md-2 col-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary btn-sm w-100" style="margin-top: 24px;">
                <i class="bi bi-search me-1"></i>搜尋
            </button>
        </div>
    </form>
</div>

<!-- Table Card -->
<div class="card border-0 shadow-sm rounded-3 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 170px;">記錄時間</th>
                    <th>操作人員</th>
                    <th>角色</th>
                    <th>動作類型</th>
                    <th>受影響資料表</th>
                    <th>主鍵 ID</th>
                    <th>來源 IP</th>
                    <th class="text-center" style="width: 100px;">異動資料</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">目前暫無符合條件之稽核軌跡日誌</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td class="small text-muted font-monospace"><?= $l['created_at'] ?></td>
                            <td>
                                <strong class="text-dark small"><?= e($l['full_name'] ?: '系統內部') ?></strong>
                                <div class="text-muted" style="font-size: 0.72rem;"><?= e($l['username']) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border"><?= e($l['role_name'] ?: 'System') ?></span>
                            </td>
                            <td>
                                <?php
                                $badgeClass = match($l['action_type']) {
                                    'LOGIN' => 'bg-secondary',
                                    'CREATE' => 'bg-success',
                                    'UPDATE' => 'bg-warning text-dark',
                                    'APPROVE' => 'bg-primary',
                                    'REJECT' => 'bg-danger',
                                    'DELETE' => 'bg-danger',
                                    'LOCK_ANNUAL_DATA' => 'bg-dark',
                                    default => 'bg-info text-dark'
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $l['action_type'] ?></span>
                            </td>
                            <td><code><?= e($l['target_table']) ?></code></td>
                            <td><?= $l['record_id'] ?: '-' ?></td>
                            <td class="small text-muted font-monospace"><?= e($l['ip_address']) ?></td>
                            <td class="text-center">
                                <?php if (!empty($l['old_values']) || !empty($l['new_values'])): ?>
                                    <button type="button" class="btn btn-outline-info btn-sm view-diff-btn" 
                                            data-bs-toggle="modal" data-bs-target="#diffModal"
                                            data-action="<?= $l['action_type'] ?>"
                                            data-old='<?= htmlspecialchars($l['old_values'] ?: '{}', ENT_QUOTES, 'UTF-8') ?>'
                                            data-new='<?= htmlspecialchars($l['new_values'] ?: '{}', ENT_QUOTES, 'UTF-8') ?>'>
                                        <i class="bi bi-code-slash"></i> 比對
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Diff Modal -->
<div class="modal fade" id="diffModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-file-diff text-info me-2"></i>資料異動對照 (JSON Diff)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-danger small"><i class="bi bi-dash-circle me-1"></i>異動前原始數據 (Old Values)</label>
                        <pre class="bg-light p-3 rounded border small" style="max-height: 350px; overflow-y: auto;" id="diffOldPre"></pre>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-success small"><i class="bi bi-plus-circle me-1"></i>異動後更新數據 (New Values)</label>
                        <pre class="bg-light p-3 rounded border small" style="max-height: 350px; overflow-y: auto;" id="diffNewPre"></pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const diffModal = document.getElementById('diffModal');
    if (diffModal) {
        diffModal.addEventListener('show.bs.modal', function(e) {
            const btn = e.relatedTarget;
            const oldStr = btn.getAttribute('data-old');
            const newStr = btn.getAttribute('data-new');

            try {
                const oldObj = JSON.parse(oldStr);
                document.getElementById('diffOldPre').textContent = JSON.stringify(oldObj, null, 2);
            } catch (err) {
                document.getElementById('diffOldPre').textContent = oldStr || '(無)';
            }

            try {
                const newObj = JSON.parse(newStr);
                document.getElementById('diffNewPre').textContent = JSON.stringify(newObj, null, 2);
            } catch (err) {
                document.getElementById('diffNewPre').textContent = newStr || '(無)';
            }
        });
    }
});
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
