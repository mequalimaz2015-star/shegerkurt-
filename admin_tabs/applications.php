<div class="card">
<?php
// Auto-create missing job tables (safe on every request)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS job_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NOT NULL,
        applicant_name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        phone VARCHAR(50),
        gpa DECIMAL(4,2) DEFAULT 0,
        photo_url VARCHAR(255),
        resume_url VARCHAR(255),
        exam_score DECIMAL(5,2) DEFAULT NULL,
        status ENUM('Pending','Reviewed','Accepted','Rejected') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS job_questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NOT NULL,
        question TEXT NOT NULL,
        option_a VARCHAR(255) NOT NULL,
        option_b VARCHAR(255) NOT NULL,
        option_c VARCHAR(255) NOT NULL,
        option_d VARCHAR(255) NOT NULL,
        correct_answer CHAR(1) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) { /* ignore if already exist */ }
?>

    <div class="card-header"
        style="display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap;">
        <span class="card-title">Job Applications</span>

        <div style="display: flex; align-items: center; gap: 20px;">
            <!-- GPA Filter -->
            <form method="GET"
                style="display: flex; gap: 10px; align-items: center; background: #f8fafc; padding: 5px 15px; border-radius: 10px; border: 1px solid #e2e8f0;">
                <input type="hidden" name="tab" value="applications">
                <i class="fa-solid fa-graduation-cap" style="color: #2563eb; font-size: 13px;"></i>
                <label style="font-size: 13px; font-weight: 600; color: #64748b;">Min GPA:</label>
                <input type="number" step="0.1" name="min_gpa" value="<?= htmlspecialchars($_GET['min_gpa'] ?? '') ?>"
                    placeholder="3.0"
                    style="width: 70px; padding: 5px; border-radius: 6px; border: 1px solid #cbd5e1; outline: none; font-size: 13px;">
                <button type="submit" class="btn"
                    style="background: #2563eb; color: white; padding: 5px 12px; font-size: 12px; border-radius: 6px; font-weight: 600; border: none; cursor: pointer;">Filter</button>
                <?php if (!empty($_GET['min_gpa'])): ?>
                    <a href="?tab=applications"
                        style="font-size: 12px; color: #ef4444; text-decoration: none; font-weight: 600;">Clear</a>
                <?php endif; ?>
            </form>

            <div id="bulk_actions" style="display: none; align-items: center; gap: 10px;">
                <span id="selected_count" style="font-size: 13px; font-weight: 700; color: #2563eb;">0 selected</span>
                <form method="POST" id="bulk_form" style="display: flex; gap: 5px;">
                    <input type="hidden" name="bulk_ids" id="bulk_ids_input">
                    <button type="submit" name="bulk_status" value="Reviewed" class="btn"
                        style="background: #0ea5e9; color: #fff; padding: 5px 12px; font-size: 12px; border-radius: 8px;">Review
                        All</button>
                    <button type="submit" name="bulk_status" value="Rejected" class="btn"
                        style="background: #64748b; color: #fff; padding: 5px 12px; font-size: 12px; border-radius: 8px;">Reject
                        All</button>
                    <button type="submit" name="bulk_delete" class="btn"
                        onclick="return confirm('Are you sure you want to delete all selected applications?')"
                        style="background: #ef4444; color: #fff; padding: 5px 12px; font-size: 12px; border-radius: 8px;">Delete
                        All</button>
                </form>
            </div>
        </div>
    </div>
    <table>
        <tr>
            <th style="width: 30px;"><input type="checkbox" id="select_all_apps" onchange="toggleSelectAll(this)"></th>
            <th>Photo</th>
            <th>Applicant Details</th>
            <th>Job Title</th>
            <th>GPA</th>
            <th>Status</th>
            <th>CV / Resume</th>
            <th>Action</th>
        </tr>
        <?php
        $min_gpa = isset($_GET['min_gpa']) && is_numeric($_GET['min_gpa']) ? floatval($_GET['min_gpa']) : 0;
        $sql = "SELECT a.*, j.title FROM job_applications a JOIN jobs j ON a.job_id = j.id";
        if ($min_gpa > 0) {
            $sql .= " WHERE a.gpa >= :min_gpa";
        }
        $sql .= " ORDER BY a.id DESC";
        $stmt = $pdo->prepare($sql);
        if ($min_gpa > 0) {
            $stmt->execute(['min_gpa' => $min_gpa]);
        } else {
            $stmt->execute();
        }
        $apps = $stmt->fetchAll();
        foreach ($apps as $app): ?>
            <tr>
                <td><input type="checkbox" class="app-checkbox" value="<?= $app['id'] ?>" onchange="updateBulkUI()"></td>
                <td style="width: 60px;">
                    <?php if ($app['photo_url']): ?>
                        <img src="<?= htmlspecialchars($app['photo_url']) ?>"
                            style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0;">
                    <?php else: ?>
                        <div
                            style="width: 50px; height: 50px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                            <i class="fa-solid fa-user"></i>
                        </div>
                    <?php endif; ?>
                </td>
                <td>
                    <strong style="font-size: 15px;"><?= htmlspecialchars($app['applicant_name']) ?></strong><br>
                    <a href="tel:<?= $app['phone'] ?>" style="color: #64748b; text-decoration: none; font-size: 13px;">
                        <i class="fa-solid fa-phone" style="font-size: 11px;"></i> <?= htmlspecialchars($app['phone']) ?>
                    </a><br>
                    <small style="color: #94a3b8;"><?= htmlspecialchars($app['email']) ?></small>
                </td>
                <td>
                    <span style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($app['title']) ?></span><br>
                    <small style="color: #94a3b8;"><?= explode(' ', $app['created_at'])[0] ?></small>
                </td>
                <td style="text-align: center;">
                    <div
                        style="font-weight: 800; color: #1e293b; font-size: 16px; background: #f8fafc; padding: 5px; border-radius: 8px; border: 1px solid #e2e8f0; display: inline-block; min-width: 50px;">
                        <?= number_format($app['gpa'], 2) ?>
                    </div>
                </td>
                <td><span class="badge <?= strtolower($app['status']) ?>">
                        <?= $app['status'] ?>
                    </span></td>
                <td>
                    <?php if ($app['resume_url']): ?>
                        <a href="<?= htmlspecialchars($app['resume_url']) ?>" target="_blank"
                            style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: #eff6ff; color: #2563eb; border-radius: 6px; font-weight: 600; text-decoration: none; font-size: 12px;">
                            <i class="fa-solid fa-file-pdf"></i> View CV
                        </a>
                    <?php else: ?>
                        <span style="color: #94a3b8; font-size: 12px;">No CV uploaded</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                            <form method="POST">
                                <input type="hidden" name="id" value="<?= $app['id'] ?>">
                                <input type="hidden" name="update_application_status" value="1">
                                <select name="status" onchange="this.form.submit()"
                                    style="width: auto; padding: 4px; font-size:11px; border-radius: 6px; border: 1px solid #cbd5e1;">
                                    <option value="Pending" <?= $app['status'] == 'Pending' ? 'selected' : '' ?>>Pending
                                    </option>
                                    <option value="Reviewed" <?= $app['status'] == 'Reviewed' ? 'selected' : '' ?>>Reviewed
                                    </option>
                                    <option value="Accepted" <?= $app['status'] == 'Accepted' ? 'selected' : '' ?>>Accepted
                                    </option>
                                    <option value="Rejected" <?= $app['status'] == 'Rejected' ? 'selected' : '' ?>>Rejected
                                    </option>
                                </select>
                            </form>
                            <span
                                style="font-size: 8px; font-weight: 700; color: #64748b; text-transform: uppercase;">Update</span>
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                            <button type="button" class="btn-icon btn-delete"
                                onclick="modernDelete('delete_application', '<?= $app['id'] ?>', '<?= htmlspecialchars($app['applicant_name'], ENT_QUOTES) ?>', 'Application')"
                                title="Remove Application"
                                style="background: #fee2e2; color: #ef4444; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer;">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                            <span
                                style="font-size: 8px; font-weight: 700; color: #64748b; text-transform: uppercase;">Delete</span>
                        </div>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<script>
    function toggleSelectAll(source) {
        const checkboxes = document.querySelectorAll('.app-checkbox');
        checkboxes.forEach(cb => cb.checked = source.checked);
        updateBulkUI();
    }
    function updateBulkUI() {
        const checkboxes = document.querySelectorAll('.app-checkbox:checked');
        const bulkDiv = document.getElementById('bulk_actions');
        const selectedCount = document.getElementById('selected_count');
        const bulkIdsInput = document.getElementById('bulk_ids_input');

        if (checkboxes.length > 0) {
            bulkDiv.style.display = 'flex';
            selectedCount.innerText = checkboxes.length + ' selected';
            const ids = Array.from(checkboxes).map(cb => cb.value);
            bulkIdsInput.value = ids.join(',');
        } else {
            bulkDiv.style.display = 'none';
            document.getElementById('select_all_apps').checked = false;
        }
    }
</script>