<?php
$users = $pdo->query("SELECT * FROM users ORDER BY id ASC")->fetchAll();

// Detailed Role Templates (Recommended defaults)
$role_defaults = [
    'Admin' => ['menu', 'reservations', 'gallery', 'tables', 'staff', 'jobs', 'applications', 'company', 'promos', 'blogs', 'users', 'chatbot', 'recycle_bin'],
    'Manager' => ['menu', 'reservations', 'gallery', 'tables', 'staff', 'promos', 'blogs', 'chatbot'],
    'Supervisor' => ['menu', 'reservations', 'tables', 'chatbot'],
    'Waiter' => ['menu', 'reservations']
];

$permission_groups = [
    'Restaurant Core' => [
        'menu' => ['label' => 'Menu Mgmt', 'icon' => 'fa-utensils'],
        'reservations' => ['label' => 'Reservations', 'icon' => 'fa-calendar-check'],
        'gallery' => ['label' => 'Gallery Mgmt', 'icon' => 'fa-images'],
        'tables' => ['label' => 'Tables Mgmt', 'icon' => 'fa-table-list'],
    ],
    'Human Resources' => [
        'staff' => ['label' => 'Staff Directory', 'icon' => 'fa-users-gear'],
        'jobs' => ['label' => 'Job Postings', 'icon' => 'fa-briefcase'],
        'applications' => ['label' => 'Job Applications', 'icon' => 'fa-file-signature'],
    ],
    'Website Content' => [
        'company' => ['label' => 'Website Content', 'icon' => 'fa-pen-to-square'],
        'promos' => ['label' => 'Promo Mgmt', 'icon' => 'fa-gift'],
        'blogs' => ['label' => 'Blog Mgmt', 'icon' => 'fa-newspaper'],
    ],
    'System & Comm' => [
        'users' => ['label' => 'User Control', 'icon' => 'fa-user-shield'],
        'chatbot' => ['label' => 'Real-time Chat', 'icon' => 'fa-comments'],
        'recycle_bin' => ['label' => 'Recycle Bin', 'icon' => 'fa-trash-arrow-up'],
    ]
];
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="font-size: 28px; font-weight: 800; color: #1e293b;">User & Access Control</h2>
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <!-- Bulk Actions -->
        <div id="users_bulk_actions" style="display: none; align-items: center; gap: 10px;">
            <span id="users_selected_count" style="font-size: 13px; font-weight: 700; color: #2563eb;">0 selected</span>
            <form method="POST" id="users_bulk_form" style="display: flex; gap: 5px;">
                <input type="hidden" name="users_bulk_ids" id="users_bulk_ids_input">
                <button type="submit" name="bulk_delete_admin_users" class="btn"
                    onclick="return confirm('🚨 WARN: This will wipe all selected admin accounts. Proceed?')"
                    style="background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; padding: 7px 16px; font-size: 12px; border-radius: 8px; font-weight: 700; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(239,68,68,0.3);">
                    <i class="fa-solid fa-trash-can"></i> Delete Selected
                </button>
            </form>
        </div>
        <button onclick="toggleModal('addUserModal')" class="btn" style="background: #10b981; color: white; padding: 10px 18px; border-radius: 10px; font-weight: 600; display: flex; align-items: center; gap: 8px; border: none; cursor: pointer;">
            <i class="fa-solid fa-user-plus"></i> Add New Admin
        </button>
    </div>
</div>

<!-- Requests Center -->
<?php 
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        email VARCHAR(100),
        reset_token VARCHAR(64),
        status ENUM('Pending', 'Completed') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {}

$pending_resets = $pdo->query("SELECT * FROM password_resets WHERE status = 'Pending'")->fetchAll();
$pending_signups = array_filter($users, fn($u) => $u['status'] === 'Pending');
?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 25px;">
    <?php if(count($pending_signups) > 0): ?>
    <div style="background: #fff; border-radius: 15px; padding: 20px; border: 1.5px solid #fecaca; box-shadow: 0 5px 15px rgba(239,68,68,0.05);">
        <h4 style="color: #991b1b; font-size: 14px; display: flex; align-items: center; gap: 8px; margin-bottom: 15px;">
            <i class="fa-solid fa-user-clock"></i> Pending Account Approvals (<?= count($pending_signups) ?>)
        </h4>
        <?php foreach(array_slice($pending_signups, 0, 3) as $signup): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px dashed #fee2e2;">
            <span style="font-size: 13px; font-weight: 600;"><?= htmlspecialchars($signup['full_name']) ?></span>
            <form method="POST">
                <input type="hidden" name="user_id" value="<?= $signup['id'] ?>">
                <button type="submit" name="approve_user" style="background: #22c55e; color: #fff; border: none; padding: 4px 10px; border-radius: 6px; font-size: 11px; cursor: pointer;">Approve</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if(count($pending_resets) > 0): ?>
    <div style="background: #fff; border-radius: 15px; padding: 20px; border: 1.5px solid #fed7aa; box-shadow: 0 5px 15px rgba(249,115,22,0.05);">
        <h4 style="color: #9a3412; font-size: 14px; display: flex; align-items: center; gap: 8px; margin-bottom: 15px;">
            <i class="fa-solid fa-key"></i> Password Reset Requests (<?= count($pending_resets) ?>)
        </h4>
        <?php foreach($pending_resets as $reset): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px dashed #ffedd5;">
            <span style="font-size: 13px; font-weight: 600;"><?= htmlspecialchars($reset['email']) ?></span>
            <form method="POST">
                <input type="hidden" name="reset_id" value="<?= $reset['id'] ?>">
                <button type="submit" name="complete_reset" style="background: #f59e0b; color: #fff; border: none; padding: 4px 10px; border-radius: 6px; font-size: 11px; cursor: pointer;">Action Needed</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div class="card" style="padding: 0; overflow: visible;">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: separate; border-spacing: 0;">
            <thead style="background: #f8fafc;">
                <tr>
                    <th style="padding: 20px; text-align: left; width: 40px;">
                        <input type="checkbox" id="select_all_users" onchange="toggleSelectAllUsers(this)" style="cursor:pointer; width: 16px; height: 16px; accent-color: #2563eb;">
                    </th>
                    <th style="padding: 20px; text-align: left;">Personnel Details</th>
                    <th style="padding: 20px; text-align: left; width: 120px;">Acc. Status</th>
                    <th style="padding: 20px; text-align: left; width: 180px;">Designated Role</th>
                    <th style="padding: 20px; text-align: left;">Tab-Level Access Permissions</th>
                    <th style="padding: 20px; text-align: right; width: 160px;">Operations</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user):
                    $user_perms = json_decode($user['permissions'] ?? '[]', true);
                    $is_self = $user['id'] == ($_SESSION['admin_id'] ?? 0);
                    $is_root = $user['id'] == 1;
                    $status = $user['status'] ?? 'Active';
                    $status_class = $status === 'Active' ? 'success' : ($status === 'Pending' ? 'warning' : 'danger');
                    ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;" class="user-row" id="row_<?= $user['id'] ?>">
                            <td style="padding: 20px;">
                                <?php if (!$is_root && !$is_self): ?>
                                    <input type="checkbox" class="user-checkbox" value="<?= $user['id'] ?>" onchange="updateUsersBulkUI()" style="cursor:pointer; width: 16px; height: 16px; accent-color: #2563eb;">
                                <?php else: ?>
                                    <i class="fa-solid fa-lock" style="color: #cbd5e1; font-size: 14px;" title="System Account Protected"></i>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 20px;">
                                <div style="display:flex; align-items:center; gap:15px;">
                                    <div style="width:50px; height:50px; border-radius:12px; background:#e2e8f0; display:flex; align-items:center; justify-content:center; overflow:hidden; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                                        <img src="<?= !empty($user['profile_pic']) ? $user['profile_pic'] : 'uploads/admin/admin_1774563258.jpg' ?>" style="width:100%; height:100%; object-fit:cover;">
                                    </div>
                                    <div>
                                        <strong style="color: #1e293b; font-size: 15px;"><?= htmlspecialchars($user['full_name']) ?></strong>
                                        <?php if ($is_self): ?><span class="badge-blue">Current User</span><?php endif; ?>
                                        <br>
                                        <span style="color: #64748b; font-size: 12px;"><?= htmlspecialchars($user['email']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 20px;">
                                <span class="badge-status <?= $status_class ?>"><?= $status ?></span>
                            </td>
                            <form method="POST">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <td style="padding: 20px;">
                                    <div style="position: relative;">
                                        <select name="role" class="detailed-select" onchange="applyRoleTemplate(this, <?= $user['id'] ?>)" <?= ($is_root) ? 'disabled' : '' ?>>
                                            <option value="Admin" <?= $user['role'] === 'Admin' ? 'selected' : '' ?>>Administrator</option>
                                            <option value="Manager" <?= $user['role'] === 'Manager' ? 'selected' : '' ?>>System Manager</option>
                                            <option value="Supervisor" <?= $user['role'] === 'Supervisor' ? 'selected' : '' ?>>Team Supervisor</option>
                                            <option value="Waiter" <?= $user['role'] === 'Waiter' ? 'selected' : '' ?>>Staff Waiter</option>
                                        </select>
                                        <i class="fa-solid fa-chevron-down" style="position:absolute; right:15px; top:15px; font-size:10px; color:#94a3b8; pointer-events:none;"></i>
                                    </div>
                                </td>
                                <td style="padding: 20px;">
                                    <?php if ($user['role'] === 'Admin'): ?>
                                            <div class="full-access-banner">
                                                <i class="fa-solid fa-lock-open"></i> UNRESTRICTED SYSTEM ACCESS GRANTED
                                            </div>
                                    <?php else: ?>
                                            <div style="display: flex; flex-wrap: wrap; gap: 25px;">
                                                <?php $group_idx = 0;
                                                foreach ($permission_groups as $group_name => $group_tabs):
                                                    $group_idx++; ?>
                                                        <div style="flex: 1; min-width: 160px;">
                                                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                                                                <span style="font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;"><?= $group_name ?></span>
                                                                <label style="font-size: 10px; color: #3b82f6; cursor: pointer; font-weight: 700;">
                                                                    <input type="checkbox" onclick="toggleGroup(this, 'grp_<?= $user['id'] ?>_<?= $group_idx ?>')" style="transform:scale(0.8); margin-right:2px; vertical-align:middle;"> ALL
                                                                </label>
                                                            </div>
                                                            <div style="display: flex; flex-direction: column; gap: 6px;" class="grp_<?= $user['id'] ?>_<?= $group_idx ?>">
                                                                <?php foreach ($group_tabs as $key => $tab): ?>
                                                                        <div class="perm-chip-row">
                                                                            <input type="checkbox" id="p_<?= $user['id'] ?>_<?= $key ?>" name="perms[]" value="<?= $key ?>" class="perm-check-hidden" data-role-keys="<?= $key ?>" <?= in_array($key, $user_perms) ? 'checked' : '' ?>>
                                                                            <label for="p_<?= $user['id'] ?>_<?= $key ?>" class="perm-custom-chip">
                                                                                <i class="fa-solid <?= $tab['icon'] ?>"></i>
                                                                                <span><?= $tab['label'] ?></span>
                                                                                <div class="selection-box"></div>
                                                                            </label>
                                                                        </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                <?php endforeach; ?>
                                            </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 20px; text-align:right;">
                                    <div style="display:flex; justify-content:flex-end; gap:12px;">
                                        <?php if(!$is_root && !$is_self): ?>
                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 5px;">
                                            <?php if($status === 'Pending'): ?>
                                            <button type="submit" name="approve_user" class="btn-action success" title="Approve User">
                                                <i class="fa-solid fa-user-check"></i>
                                            </button>
                                            <span style="font-size: 9px; font-weight: 700; color: #64748b; text-transform: uppercase;">Accept</span>
                                            <?php else: ?>
                                            <button type="submit" name="disable_user" class="btn-action warning" title="Disable/Enable User">
                                                <i class="fa-solid <?= $status === 'Disabled' ? 'fa-user-check' : 'fa-user-slash' ?>"></i>
                                            </button>
                                            <span style="font-size: 9px; font-weight: 700; color: #64748b; text-transform: uppercase;"><?= $status === 'Disabled' ? 'Enable' : 'Disable' ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>

                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 5px;">
                                            <button type="submit" name="update_user_permissions" class="btn-action primary" title="Save Permissions">
                                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                            </button>
                                            <span style="font-size: 9px; font-weight: 700; color: #64748b; text-transform: uppercase;">Update</span>
                                        </div>
                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 5px;">
                                            <button type="button" onclick="promptPassword(<?= $user['id'] ?>)" class="btn-action warning" title="Reset Credentials">
                                                <i class="fa-solid fa-shield-keyhole"></i>
                                            </button>
                                            <span style="font-size: 9px; font-weight: 700; color: #64748b; text-transform: uppercase;">Reset</span>
                                        </div>
                                        <?php if (!$is_root && !$is_self): ?>
                                            <div style="display: flex; flex-direction: column; align-items: center; gap: 5px;">
                                                <button type="submit" name="delete_admin_user" class="btn-action danger" onclick="return confirm('🚨 WARN: This will wipe this admin account. Proceed?')" title="Delete Account">
                                                    <i class="fa-solid fa-trash-xmark"></i>
                                                </button>
                                                <span style="font-size: 9px; font-weight: 700; color: #64748b; text-transform: uppercase;">Delete</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </form>
                        </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const roleTemplates = <?= json_encode($role_defaults) ?>;

function applyRoleTemplate(select, userId) {
    const role = select.value;
    if (role === 'Admin') {
        if(confirm("Setting to Admin will grant Full Unrestricted Access. Page will refresh to update UI.")) {
            select.closest('form').submit();
        }
        return;
    }
    
    const allowed = roleTemplates[role] || [];
    const checkboxes = document.querySelectorAll(`#row_${userId} .perm-check-hidden`);
    
    checkboxes.forEach(cb => {
        cb.checked = allowed.includes(cb.value);
    });
}

function toggleGroup(master, groupClass) {
    const container = document.querySelector('.' + groupClass);
    const checks = container.querySelectorAll('input[type="checkbox"]');
    checks.forEach(c => c.checked = master.checked);
}

    } else if (newPass) alert("Error: Use at least 6 characters.");
}

function toggleModal(id) {
    const m = document.getElementById(id);
    if(m) m.style.display = m.style.display === 'none' ? 'block' : 'none';
}

function promptPassword(uid) {
    const newPass = prompt("Enter secure new password for this personnel:");
    if (newPass && newPass.length >= 6) {
        const f = document.createElement('form');
        f.method = 'POST';
        f.innerHTML = `<input type="hidden" name="user_id" value="${uid}"><input type="hidden" name="new_password" value="${newPass}"><input type="hidden" name="reset_admin_password" value="1">`;
        document.body.appendChild(f);
        f.submit();
    } else if (newPass) alert("Error: Use at least 6 characters.");
}

function toggleSelectAllUsers(source) {
    document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = source.checked);
    updateUsersBulkUI();
}

function updateUsersBulkUI() {
    const checked = document.querySelectorAll('.user-checkbox:checked');
    const all = document.querySelectorAll('.user-checkbox');
    const bulk = document.getElementById('users_bulk_actions');
    const count = document.getElementById('users_selected_count');
    const ids = document.getElementById('users_bulk_ids_input');
    const selAll = document.getElementById('select_all_users');
    
    if (checked.length > 0) {
        bulk.style.display = 'flex';
        count.innerText = checked.length + ' selected';
        ids.value = Array.from(checked).map(cb => cb.value).join(',');
    } else {
        bulk.style.display = 'none';
    }
    
    if (all.length > 0 && checked.length === all.length) {
        selAll.checked = true;
        selAll.indeterminate = false;
    } else if (checked.length > 0) {
        selAll.checked = false;
        selAll.indeterminate = true;
    } else {
        selAll.checked = false;
        selAll.indeterminate = false;
    }
}
</script>

<style>
    /* Premium Detailed Styles */
    .detailed-select {
        width: 100%;
        padding: 12px 15px;
        background: #fff;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 700;
        color: #334155;
        appearance: none;
        cursor: pointer;
        transition: 0.2s;
    }
    .detailed-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
    .detailed-select:disabled { background: #f8fafc; cursor: not-allowed; opacity: 0.8; }

    .full-access-banner {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #166534;
        padding: 15px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 800;
        text-align: center;
        width: 100%;
        letter-spacing: 0.5px;
    }

    .perm-chip-row { position: relative; }
    .perm-check-hidden { position: absolute; opacity: 0; pointer-events: none; }
    
    .perm-custom-chip {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        background: #fff;
        border: 1.5px solid #f1f5f9;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }

    .selection-box {
        width: 18px;
        height: 18px;
        border-radius: 5px;
        border: 2px solid #cbd5e1;
        margin-left: auto;
        position: relative;
        transition: 0.2s;
        background: #fff;
    }

    .selection-box::after {
        content: '\f00c';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        font-size: 10px;
        color: #fff;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0);
        transition: 0.2s;
    }

    .perm-check-hidden:checked + .perm-custom-chip {
        background: #eff6ff;
        border-color: #3b82f6;
        color: #1d4ed8;
    }
    
    .perm-check-hidden:checked + .perm-custom-chip .selection-box {
        background: #3b82f6;
        border-color: #3b82f6;
    }
    
    .perm-check-hidden:checked + .perm-custom-chip .selection-box::after {
        transform: translate(-50%, -50%) scale(1);
    }

    .btn-action {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: 0.3s;
        font-size: 16px;
    }
    .btn-action.primary { background: #2563eb; color: #fff; }
    .btn-action.warning { background: #f59e0b; color: #fff; }
    .btn-action.danger { background: #ef4444; color: #fff; }
    .btn-action:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); filter: brightness(1.1); }

    .badge-blue { background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 6px; font-size: 10px; font-weight: 700; margin-left: 8px; vertical-align: middle; }
    
    .badge-status { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
    .badge-status.success { background: #dcfce7; color: #166534; }
    .badge-status.warning { background: #fef9c3; color: #854d0e; }
    .badge-status.danger { background: #fee2e2; color: #991b1b; }
    
    .btn-action.success { background: #22c55e; color: #fff; }
    
    .user-row:hover { background: #fafafa; }
</style>