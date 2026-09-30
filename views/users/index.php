<?php
// views/users/index.php
require __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-person-badge text-primary me-2"></i>系統使用者帳號與 RBAC 角色權限管理
        </h4>
        <div class="text-secondary small">
            管理五大系統角色：管理員、ESG負責人、廠區主管、填報員與外部查證員
        </div>
    </div>
    <button type="button" class="btn btn-success btn-sm shadow-sm" style="background-color: var(--esg-green); border: none;" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="bi bi-person-plus me-1"></i>建立新使用者
    </button>
</div>

<div class="card border-0 shadow-sm rounded-3 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>帳號 / 員編</th>
                    <th>中文姓名</th>
                    <th>所屬角色</th>
                    <th>所屬廠區/部門</th>
                    <th>電子信箱</th>
                    <th>最後登入時間</th>
                    <th>帳號狀態</th>
                    <th class="text-center" style="width: 80px;">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="text-muted small"><?= $u['id'] ?></td>
                        <td><code><?= e($u['username']) ?></code></td>
                        <td><strong class="text-dark"><?= e($u['full_name']) ?></strong></td>
                        <td>
                            <?php
                            $roleBadges = [
                                'admin' => 'bg-danger',
                                'esg_lead' => 'bg-warning text-dark',
                                'reviewer' => 'bg-primary',
                                'collector' => 'bg-success',
                                'auditor' => 'bg-secondary'
                            ];
                            $badgeClass = $roleBadges[$u['role_code']] ?? 'bg-info text-dark';
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= e($u['role_name']) ?></span>
                        </td>
                        <td>
                            <div class="small fw-semibold"><?= e($u['unit_name']) ?></div>
                            <div class="text-muted" style="font-size: 0.72rem;"><?= e($u['unit_code']) ?></div>
                        </td>
                        <td class="small"><?= e($u['email']) ?></td>
                        <td class="small text-muted font-monospace"><?= $u['last_login_at'] ?: '尚未登入' ?></td>
                        <td>
                            <?php if ($u['status'] == 1): ?>
                                <span class="badge bg-success-subtle text-success">正常啟用</span>
                            <?php elseif ($u['status'] == -1): ?>
                                <span class="badge bg-danger">密碼鎖定</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">停用</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                    data-bs-toggle="modal" data-bs-target="#editUserModal"
                                    data-id="<?= $u['id'] ?>"
                                    data-username="<?= e($u['username']) ?>"
                                    data-name="<?= e($u['full_name']) ?>"
                                    data-email="<?= e($u['email']) ?>"
                                    data-phone="<?= e($u['phone']) ?>"
                                    data-org="<?= $u['org_unit_id'] ?>"
                                    data-role="<?= $u['role_id'] ?>"
                                    data-status="<?= $u['status'] ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="index.php?route=users">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-plus text-success me-2"></i>建立新使用者帳號</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small text-secondary">登入帳號 <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" required placeholder="如：collector02">
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-secondary">中文姓名 <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" required placeholder="如：王大明">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-secondary">電子信箱 (審批通知) <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required placeholder="user@corp.com">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small text-secondary">指派角色 <span class="text-danger">*</span></label>
                            <select name="role_id" class="form-select" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>"><?= e($r['role_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-secondary">所屬組織廠區 <span class="text-danger">*</span></label>
                            <select name="org_unit_id" class="form-select" required>
                                <?php foreach ($orgs as $o): ?>
                                    <option value="<?= $o['id'] ?>"><?= e($o['unit_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-secondary">初始登入密碼（至少 10 碼，含英文與數字）</label>
                        <input type="password" name="password" class="form-control" minlength="10" required autocomplete="new-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-success" style="background-color: var(--esg-green); border: none;">建立帳號</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="index.php?route=users">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" id="editUserId">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>編輯使用者資料</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small text-secondary">帳號</label>
                            <input type="text" id="editUsername" class="form-control bg-light" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-secondary">姓名 <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" id="editName" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-secondary">信箱 <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="editEmail" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small text-secondary">指派角色 <span class="text-danger">*</span></label>
                            <select name="role_id" id="editRole" class="form-select" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>"><?= e($r['role_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-secondary">所屬組織 <span class="text-danger">*</span></label>
                            <select name="org_unit_id" id="editOrg" class="form-select" required>
                                <?php foreach ($orgs as $o): ?>
                                    <option value="<?= $o['id'] ?>"><?= e($o['unit_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small text-secondary">帳號狀態</label>
                            <select name="status" id="editStatus" class="form-select">
                                <option value="1">正常啟用 (Active)</option>
                                <option value="0">停用帳號 (Disabled)</option>
                                <option value="-1">鎖定狀態 (Locked)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-secondary">重設密碼 (若不修改請留空)</label>
                            <input type="password" name="password" class="form-control" minlength="10" placeholder="輸入新密碼" autocomplete="new-password">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">更新帳號</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editUserModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(e) {
            const btn = e.relatedTarget;
            document.getElementById('editUserId').value = btn.getAttribute('data-id');
            document.getElementById('editUsername').value = btn.getAttribute('data-username');
            document.getElementById('editName').value = btn.getAttribute('data-name');
            document.getElementById('editEmail').value = btn.getAttribute('data-email');
            document.getElementById('editOrg').value = btn.getAttribute('data-org');
            document.getElementById('editRole').value = btn.getAttribute('data-role');
            document.getElementById('editStatus').value = btn.getAttribute('data-status');
        });
    }
});
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
