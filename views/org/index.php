<?php
// views/org/index.php
require __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-diagram-3 text-success me-2"></i>集團多層級組織與廠區架構管理
        </h4>
        <div class="text-secondary small">
            集團 (Group) ➔ 子公司 (Company) ➔ 廠區站點 (Plant) ➔ 部門處室 (Dept) 多層級邊界管理
        </div>
    </div>
    <?php if (Auth::hasRole('admin')): ?>
        <button type="button" class="btn btn-success btn-sm shadow-sm" style="background-color: var(--esg-green); border: none;" data-bs-toggle="modal" data-bs-target="#addOrgModal">
            <i class="bi bi-plus-circle me-1"></i>新增組織單位
        </button>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm rounded-3 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>組織代碼</th>
                    <th>組織 / 廠區名稱</th>
                    <th>組織層級</th>
                    <th>上級所屬單位</th>
                    <th>所在地 / 實體地址</th>
                    <th>關聯帳號數</th>
                    <th>盤查活動數</th>
                    <th>狀態</th>
                    <?php if (Auth::hasRole('admin')): ?>
                        <th class="text-center" style="width: 80px;">操作</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orgs as $o): ?>
                    <tr>
                        <td class="text-muted small"><?= $o['id'] ?></td>
                        <td><code><?= e($o['unit_code']) ?></code></td>
                        <td><strong class="text-dark"><?= e($o['unit_name']) ?></strong></td>
                        <td>
                            <?php
                            $typeMap = [
                                'group' => ['集團總部', 'bg-dark'],
                                'company' => ['子公司', 'bg-primary'],
                                'plant' => ['生產廠區', 'bg-success'],
                                'dept' => ['職能部門', 'bg-secondary']
                            ];
                            $badge = $typeMap[$o['unit_type']] ?? [$o['unit_type'], 'bg-light text-dark'];
                            ?>
                            <span class="badge <?= $badge[1] ?>"><?= $badge[0] ?></span>
                        </td>
                        <td><?= e($o['parent_name'] ?: '（根組織總部）') ?></td>
                        <td class="small text-secondary"><?= e($o['address'] ?: $o['country']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= $o['user_count'] ?> 人</span></td>
                        <td><span class="badge bg-light text-success border"><?= $o['emission_count'] ?> 筆</span></td>
                        <td>
                            <?= $o['is_active'] ? '<span class="badge bg-success-subtle text-success">正常運作</span>' : '<span class="badge bg-danger-subtle text-danger">已停用</span>' ?>
                        </td>
                        <?php if (Auth::hasRole('admin')): ?>
                            <td class="text-center">
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#editOrgModal"
                                        data-id="<?= $o['id'] ?>"
                                        data-code="<?= e($o['unit_code']) ?>"
                                        data-name="<?= e($o['unit_name']) ?>"
                                        data-type="<?= $o['unit_type'] ?>"
                                        data-parent="<?= $o['parent_id'] ?>"
                                        data-address="<?= e($o['address']) ?>"
                                        data-active="<?= $o['is_active'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Org Modal -->
<div class="modal fade" id="addOrgModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="index.php?route=org">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-success me-2"></i>新增組織單位</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small text-secondary">組織代碼 (唯一值) <span class="text-danger">*</span></label>
                        <input type="text" name="unit_code" class="form-control" required placeholder="如：PLANT-TC3">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-secondary">組織/廠區中文名稱 <span class="text-danger">*</span></label>
                        <input type="text" name="unit_name" class="form-control" required placeholder="如：台中先進製程廠">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small text-secondary">層級類型 <span class="text-danger">*</span></label>
                            <select name="unit_type" class="form-select" required>
                                <option value="plant">生產廠區 (Plant)</option>
                                <option value="dept">職能部門 (Dept)</option>
                                <option value="company">子公司 (Company)</option>
                                <option value="group">集團總部 (Group)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-secondary">上級組織單位</label>
                            <select name="parent_id" class="form-select">
                                <option value="">無 (設為最高層)</option>
                                <?php foreach ($orgs as $po): ?>
                                    <option value="<?= $po['id'] ?>"><?= e($po['unit_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-secondary">實體廠址 / 辦公地址</label>
                        <input type="text" name="address" class="form-control" placeholder="如：台中市大雅區科雅路...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-success" style="background-color: var(--esg-green); border: none;">新增組織</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Org Modal -->
<div class="modal fade" id="editOrgModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="index.php?route=org">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" id="editOrgId">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>編輯組織單位</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small text-secondary">組織代碼</label>
                        <input type="text" id="editOrgCode" class="form-control bg-light" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-secondary">組織/廠區中文名稱 <span class="text-danger">*</span></label>
                        <input type="text" name="unit_name" id="editOrgName" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small text-secondary">層級類型 <span class="text-danger">*</span></label>
                            <select name="unit_type" id="editOrgType" class="form-select" required>
                                <option value="plant">生產廠區 (Plant)</option>
                                <option value="dept">職能部門 (Dept)</option>
                                <option value="company">子公司 (Company)</option>
                                <option value="group">集團總部 (Group)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-secondary">上級組織單位</label>
                            <select name="parent_id" id="editOrgParent" class="form-select">
                                <option value="">無 (最高層)</option>
                                <?php foreach ($orgs as $po): ?>
                                    <option value="<?= $po['id'] ?>"><?= e($po['unit_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-secondary">實體地址</label>
                        <input type="text" name="address" id="editOrgAddress" class="form-control">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editOrgActive">
                        <label class="form-check-label small" for="editOrgActive">
                            啟用中 (勾選代表生效)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">更新組織</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editOrgModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(e) {
            const btn = e.relatedTarget;
            document.getElementById('editOrgId').value = btn.getAttribute('data-id');
            document.getElementById('editOrgCode').value = btn.getAttribute('data-code');
            document.getElementById('editOrgName').value = btn.getAttribute('data-name');
            document.getElementById('editOrgType').value = btn.getAttribute('data-type');
            document.getElementById('editOrgParent').value = btn.getAttribute('data-parent') || '';
            document.getElementById('editOrgAddress').value = btn.getAttribute('data-address');
            document.getElementById('editOrgActive').checked = (btn.getAttribute('data-active') === '1');
        });
    }
});
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
