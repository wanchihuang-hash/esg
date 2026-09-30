<?php
// views/ghg/index.php
require __DIR__ . '/../layout/header.php';
$role = Auth::role();
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-cloud-haze2 text-success me-2"></i>溫室氣體活動數據盤查清冊
        </h4>
        <div class="text-secondary small">
            依循 ISO 14064-1:2018 與 GHG Protocol 標準，管理範疇一、二、三原始活動數據與憑證
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?route=report_export&year=<?= $year ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-download me-1"></i>匯出 CSV
        </a>
        <?php if (Auth::can('edit_data')): ?>
            <a href="index.php?route=ghg_create" class="btn btn-success btn-sm shadow-sm" style="background-color: var(--esg-green); border: none;">
                <i class="bi bi-plus-circle me-1"></i>新增活動數據
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm p-3 mb-4 rounded-3">
    <form method="GET" action="index.php" class="row g-2 align-items-center">
        <input type="hidden" name="route" value="ghg">
        
        <div class="col-md-2 col-6">
            <label class="form-label small text-secondary mb-1">盤查年度</label>
            <select name="year" class="form-select form-select-sm">
                <option value="">全部年度</option>
                <option value="2024" <?= $year == 2024 ? 'selected' : '' ?>>2024</option>
                <option value="2023" <?= $year == 2023 ? 'selected' : '' ?>>2023 (基準年)</option>
            </select>
        </div>

        <div class="col-md-2 col-6">
            <label class="form-label small text-secondary mb-1">範疇分類</label>
            <select name="scope" class="form-select form-select-sm">
                <option value="">全部範疇</option>
                <option value="Scope1" <?= $scope === 'Scope1' ? 'selected' : '' ?>>範疇一 (直接)</option>
                <option value="Scope2" <?= $scope === 'Scope2' ? 'selected' : '' ?>>範疇二 (外購電力)</option>
                <option value="Scope3" <?= $scope === 'Scope3' ? 'selected' : '' ?>>範疇三 (其他間接)</option>
            </select>
        </div>

        <div class="col-md-3 col-6">
            <label class="form-label small text-secondary mb-1">組織廠區</label>
            <select name="org_unit_id" class="form-select form-select-sm">
                <option value="">全部廠區</option>
                <?php foreach ($orgs as $o): ?>
                    <option value="<?= $o['id'] ?>" <?= $orgUnitId == $o['id'] ? 'selected' : '' ?>><?= e($o['unit_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2 col-6">
            <label class="form-label small text-secondary mb-1">審批狀態</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">全部狀態</option>
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>草稿 (Draft)</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>待審核 (Pending)</option>
                <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>已核准 (Approved)</option>
                <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>退回 (Rejected)</option>
                <option value="locked" <?= $status === 'locked' ? 'selected' : '' ?>>鎖檔封存 (Locked)</option>
            </select>
        </div>

        <div class="col-md-2 col-8">
            <label class="form-label small text-secondary mb-1">關鍵字搜尋</label>
            <input type="text" name="keyword" class="form-control form-control-sm" placeholder="排放源 / 發票號" value="<?= e($keyword) ?>">
        </div>

        <div class="col-md-1 col-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary btn-sm w-100" style="margin-top: 24px;">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </form>
</div>

<!-- Summary Ribbon -->
<div class="d-flex justify-content-between align-items-center mb-2 px-1">
    <div class="text-secondary small">
        共找到 <strong><?= count($emissions) ?></strong> 筆活動數據
    </div>
    <div>
        <span class="text-muted small me-2">篩選碳排合計：</span>
        <span class="fs-5 fw-bold text-success"><?= number_format($totalTco2e, 4) ?> <span class="fs-6 fw-normal text-muted">tCO₂e</span></span>
    </div>
</div>

<!-- Table Card -->
<div class="card border-0 shadow-sm rounded-3 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>廠區據點</th>
                    <th>年月</th>
                    <th>範疇</th>
                    <th>具體排放源</th>
                    <th>活動數據量</th>
                    <th>引用係數</th>
                    <th>排放量 (tCO₂e)</th>
                    <th>發票/憑單</th>
                    <th>審批狀態</th>
                    <th style="width: 140px;" class="text-center">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($emissions)): ?>
                    <tr>
                        <td colspan="11" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-1"></i>無符合條件之碳盤查活動數據
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($emissions as $idx => $em): ?>
                        <?php
                        $ownsEditableRecord = Auth::hasRole('collector')
                            && (int)$em['created_by'] === Auth::id()
                            && in_array($em['workflow_status'], ['draft', 'rejected'], true);
                        $canEditRecord = (Auth::hasRole(['admin', 'esg_lead'])
                            && in_array($em['workflow_status'], ['draft', 'rejected'], true)) || $ownsEditableRecord;
                        ?>
                        <tr>
                            <td class="text-muted small"><?= $idx + 1 ?></td>
                            <td>
                                <div class="fw-semibold text-dark small"><?= e($em['unit_name']) ?></div>
                                <div class="text-muted" style="font-size: 0.72rem;"><?= e($em['unit_code']) ?></div>
                            </td>
                            <td class="text-nowrap small fw-bold">
                                <?= $em['period_year'] ?>/<?= str_pad($em['period_month'], 2, '0', STR_PAD_LEFT) ?>
                            </td>
                            <td>
                                <?php if ($em['scope_type'] === 'Scope1'): ?>
                                    <span class="badge badge-scope1">範疇一</span>
                                <?php elseif ($em['scope_type'] === 'Scope2'): ?>
                                    <span class="badge badge-scope2">範疇二</span>
                                <?php else: ?>
                                    <span class="badge badge-scope3">範疇三</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small fw-semibold text-dark"><?= e($em['emission_source']) ?></div>
                                <div class="text-muted" style="font-size: 0.72rem;">填報人: <?= e($em['creator_name']) ?></div>
                            </td>
                            <td class="text-nowrap small">
                                <strong><?= number_format($em['activity_amount'], 2) ?></strong>
                                <span class="text-muted"><?= e($em['activity_unit']) ?></span>
                            </td>
                            <td>
                                <div class="small text-truncate" style="max-width: 160px;" title="<?= e($em['factor_name']) ?>">
                                    <?= e($em['factor_name']) ?>
                                </div>
                                <div class="text-muted" style="font-size: 0.72rem;">
                                    <?= (float)$em['co2e_factor'] ?> kg/<?= e($em['factor_unit']) ?>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <span class="fw-bold text-dark"><?= number_format($em['calculated_tco2e'], 4) ?></span>
                            </td>
                            <td class="small">
                                <?php if (!empty($em['invoice_no'])): ?>
                                    <div><i class="bi bi-receipt text-secondary me-1"></i><?= e($em['invoice_no']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($em['proof_file_path'])): ?>
                                    <a href="<?= e($em['proof_file_path']) ?>" target="_blank" class="badge bg-light text-primary border text-decoration-none">
                                        <i class="bi bi-paperclip me-1"></i>佐證單據
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $statusClass = 'status-' . $em['workflow_status'];
                                $statusNames = [
                                    'draft' => '草稿',
                                    'pending' => '待審核',
                                    'approved' => '已核准',
                                    'rejected' => '退回',
                                    'locked' => '已鎖檔'
                                ];
                                ?>
                                <span class="badge <?= $statusClass ?> status-badge">
                                    <?= $statusNames[$em['workflow_status']] ?? $em['workflow_status'] ?>
                                </span>
                                <?php if (!empty($em['reviewer_comment'])): ?>
                                    <i class="bi bi-info-circle text-muted ms-1" title="<?= e($em['reviewer_comment']) ?>"></i>
                                <?php endif; ?>
                            </td>
                            <td class="text-center text-nowrap">
                                <!-- Action Buttons depending on role and status -->
                                <div class="btn-group btn-group-sm">
                                    <!-- Edit -->
                                    <?php if ($canEditRecord): ?>
                                        <a href="index.php?route=ghg_edit&id=<?= $em['id'] ?>" class="btn btn-outline-secondary" title="編輯">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    <?php endif; ?>

                                    <!-- Submit (if draft) -->
                                    <?php if ($canEditRecord && in_array($em['workflow_status'], ['draft', 'rejected'], true)): ?>
                                        <form method="POST" action="index.php?route=ghg_workflow" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="id" value="<?= $em['id'] ?>">
                                            <input type="hidden" name="action" value="submit">
                                            <button type="submit" class="btn btn-outline-warning" title="送審" onclick="return confirm('確定送審此項數據？')">
                                                <i class="bi bi-send"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- Approve / Reject Modal Trigger (if reviewer or lead or admin) -->
                                    <?php if (Auth::can('approve_data') && $em['workflow_status'] === 'pending'): ?>
                                        <button type="button" class="btn btn-outline-success" title="審核" 
                                                data-bs-toggle="modal" data-bs-target="#approvalModal" 
                                                data-id="<?= $em['id'] ?>" 
                                                data-source="<?= e($em['emission_source']) ?>" 
                                                data-amount="<?= $em['calculated_tco2e'] ?> tCO2e">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    <?php endif; ?>

                                    <!-- Delete (if draft or admin) -->
                                    <?php if ((Auth::hasRole('admin') && in_array($em['workflow_status'], ['draft', 'rejected'], true)) || $ownsEditableRecord): ?>
                                        <form method="POST" action="index.php?route=ghg_delete" class="d-inline" onsubmit="return confirm('確定刪除這筆數據？')">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="id" value="<?= $em['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="刪除">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Workflow Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="index.php?route=ghg_workflow">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" id="modalEmissionId" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-check2-circle text-success me-2"></i>線上審核溫室氣體活動數據</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small">審查項目</label>
                        <div id="modalItemDescription" class="fw-semibold text-dark"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-semibold">審核意見 / 退回原因說明</label>
                        <textarea name="comment" class="form-control" rows="3" placeholder="例如：活動數據與電費單相符，核准通過 / 佐證發票有缺漏，請補正後重新送審"></textarea>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="submit" name="action" value="reject" class="btn btn-danger" onclick="return confirm('確定退回此筆申請？')">
                        <i class="bi bi-x-circle me-1"></i>退回補正 (Reject)
                    </button>
                    <button type="submit" name="action" value="approve" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i>核准通過 (Approve)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const approvalModal = document.getElementById('approvalModal');
    if (approvalModal) {
        approvalModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const source = button.getAttribute('data-source');
            const amount = button.getAttribute('data-amount');
            
            document.getElementById('modalEmissionId').value = id;
            document.getElementById('modalItemDescription').textContent = `${source} (${amount})`;
        });
    }
});
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
