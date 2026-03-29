<?php
// Auto-create missing job tables (safe for production)
try {
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
} catch (PDOException $e) { /* tables already exist or DB doesn't support IF NOT EXISTS */ }

$job_id = $_GET['job_id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) {
    echo "<div class='card'><p>Job not found!</p></div>";
    exit;
}

$questions = $pdo->prepare("SELECT * FROM job_questions WHERE job_id = ? ORDER BY id ASC");
$questions->execute([$job_id]);
$questions_list = $questions->fetchAll();
?>

<style>
.exam-tab-btn { padding:10px 20px; border-radius:10px; border:none; cursor:pointer; font-weight:700; font-size:14px; transition:0.2s; }
.exam-tab-btn.active { background:var(--primary,#ff9d2d); color:#fff; }
.exam-tab-btn:not(.active) { background:#f1f5f9; color:#475569; }
.exam-tab-panel { display:none; }
.exam-tab-panel.active { display:block; }
.q-card { background:#fff; border:1px solid #e2e8f0; border-radius:15px; padding:20px; margin-bottom:15px; }
.q-card.editing { border-color:#ff9d2d; box-shadow: 0 0 0 3px rgba(255,157,45,0.15); }
.opt-pill { padding:10px 14px; border-radius:8px; background:#f8fafc; border:1.5px solid #e2e8f0; font-size:14px; margin-bottom:8px; display:flex; align-items:center; gap:8px; }
.opt-pill.correct { background:#d1fae5; border-color:#10b981; color:#065f46; font-weight:700; }
.bulk-textarea { width:100%; min-height:300px; padding:15px; border:1.5px solid #e2e8f0; border-radius:12px; font-family: 'Courier New', monospace; font-size:13px; resize:vertical; }
</style>

<!-- Header -->
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
    <div>
        <h2 style="font-size:22px; font-weight:800; color:#1e293b; margin:0;">
            <i class="fa-solid fa-clipboard-list" style="color:var(--primary,#ff9d2d);"></i> Pre-Screening Exam
        </h2>
        <p style="color:#64748b; font-size:14px; margin:5px 0 0;">
            Position: <strong style="color:#1e293b;"><?= htmlspecialchars($job['title']) ?></strong>
            &nbsp;·&nbsp; <span style="color:#10b981; font-weight:700;"><?= count($questions_list) ?> question<?= count($questions_list) != 1 ? 's' : '' ?></span>
        </p>
    </div>
    <a href="admin.php?tab=jobs" class="btn" style="background:#f1f5f9; color:#475569; border:none; padding:10px 18px; border-radius:10px; font-weight:700; text-decoration:none;">
        <i class="fa-solid fa-arrow-left"></i> Back to Jobs
    </a>
</div>

<?php if(isset($_SESSION['exam_msg'])): ?>
<div style="background:#d1fae5; color:#065f46; padding:12px 18px; border-radius:10px; margin-bottom:15px; font-weight:600; border:1px solid #a7f3d0;">
    <i class="fa-solid fa-circle-check"></i> <?= $_SESSION['exam_msg']; unset($_SESSION['exam_msg']); ?>
</div>
<?php endif; ?>

<!-- Tab Switcher -->
<div style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap;">
    <button class="exam-tab-btn active" onclick="switchTab('tab-questions', this)">
        <i class="fa-solid fa-list-ol"></i> Questions (<?= count($questions_list) ?>)
    </button>
    <button class="exam-tab-btn" onclick="switchTab('tab-add', this)">
        <i class="fa-solid fa-plus"></i> Add Question
    </button>
    <button class="exam-tab-btn" onclick="switchTab('tab-bulk', this)">
        <i class="fa-solid fa-layer-group"></i> Bulk Import (Text)
    </button>
    <button class="exam-tab-btn" onclick="switchTab('tab-upload', this)">
        <i class="fa-solid fa-file-arrow-up"></i> Import from Image/PDF
    </button>
</div>

<!-- ===== TAB 1: QUESTIONS LIST ===== -->
<div id="tab-questions" class="exam-tab-panel active">
    <?php if(empty($questions_list)): ?>
        <div class="card" style="text-align:center; padding:50px;">
            <i class="fa-solid fa-clipboard" style="font-size:48px; color:#cbd5e1; margin-bottom:15px;"></i>
            <h3 style="color:#94a3b8;">No Questions Yet</h3>
            <p style="color:#94a3b8;">Use the tabs above to add questions.</p>
        </div>
    <?php else: ?>
        <div id="questions-container">
        <?php foreach($questions_list as $i => $q): ?>
            <div class="q-card" id="qcard-<?= $q['id'] ?>">
                <!-- VIEW MODE -->
                <div id="view-<?= $q['id'] ?>">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
                        <h4 style="margin:0; font-size:15px; font-weight:700; color:#1e293b; flex:1; padding-right:15px;">
                            <span style="color:var(--primary,#ff9d2d); font-size:13px;">Q<?= $i+1 ?>.</span> <?= htmlspecialchars($q['question']) ?>
                        </h4>
                        <div style="display:flex; gap:8px; flex-shrink:0;">
                            <button onclick="editQuestion(<?= $q['id'] ?>)" style="background:#e0f2fe; color:#0369a1; border:none; padding:6px 12px; border-radius:8px; cursor:pointer; font-weight:700; font-size:12px;">
                                <i class="fa-solid fa-pen"></i> Edit
                            </button>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="delete_job_question" value="1">
                                <input type="hidden" name="id" value="<?= $q['id'] ?>">
                                <input type="hidden" name="job_id" value="<?= $job_id ?>">
                                <button type="submit" onclick="return confirm('Delete this question?')" style="background:#fee2e2; color:#ef4444; border:none; padding:6px 12px; border-radius:8px; cursor:pointer; font-weight:700; font-size:12px;">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                        <?php foreach(['A','B','C','D'] as $letter): 
                            $opt = 'option_' . strtolower($letter);
                            $isCorrect = $q['correct_answer'] == $letter;
                        ?>
                            <div class="opt-pill <?= $isCorrect ? 'correct' : '' ?>">
                                <span style="min-width:22px; height:22px; background:<?= $isCorrect ? '#10b981' : '#e2e8f0' ?>; color:<?= $isCorrect ? '#fff' : '#64748b' ?>; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:11px; font-weight:800;"><?= $letter ?></span>
                                <?= htmlspecialchars($q[$opt]) ?>
                                <?php if($isCorrect): ?><i class="fa-solid fa-check-circle" style="margin-left:auto;"></i><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- EDIT MODE -->
                <div id="edit-<?= $q['id'] ?>" style="display:none;">
                    <form method="POST" action="admin.php?tab=manage_exam&job_id=<?= $job_id ?>">
                        <input type="hidden" name="update_job_question" value="1">
                        <input type="hidden" name="id" value="<?= $q['id'] ?>">
                        <input type="hidden" name="job_id" value="<?= $job_id ?>">
                        
                        <div style="margin-bottom:12px;">
                            <label style="font-size:11px; font-weight:700; color:#64748b; display:block; margin-bottom:5px;">QUESTION TEXT</label>
                            <textarea name="question" rows="2" required style="width:100%; padding:10px; border:1.5px solid #e2e8f0; border-radius:8px; font-family:inherit; resize:none;"><?= htmlspecialchars($q['question']) ?></textarea>
                        </div>
                        
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:12px;">
                            <?php foreach(['A','B','C','D'] as $letter): 
                                $opt = 'option_' . strtolower($letter);
                            ?>
                                <div>
                                    <label style="font-size:11px; font-weight:700; color:#64748b; display:block; margin-bottom:4px;">OPTION <?= $letter ?></label>
                                    <input type="text" name="option_<?= strtolower($letter) ?>" required value="<?= htmlspecialchars($q[$opt]) ?>" style="width:100%; padding:9px; border:1.5px solid #e2e8f0; border-radius:8px;">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div style="display:flex; align-items:center; gap:15px;">
                            <div style="flex:1;">
                                <label style="font-size:11px; font-weight:700; color:#64748b; display:block; margin-bottom:5px;">✅ CORRECT ANSWER</label>
                                <select name="correct_answer" required style="width:100%; padding:10px; border:1.5px solid #10b981; border-radius:8px; background:#f0fdf4; font-weight:700;">
                                    <?php foreach(['A','B','C','D'] as $l): ?>
                                        <option value="<?=$l?>" <?= $q['correct_answer']==$l ? 'selected' : '' ?>>Option <?=$l?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div style="display:flex; gap:8px; margin-top:20px;">
                                <button type="submit" style="background:#10b981; color:#fff; border:none; padding:10px 18px; border-radius:8px; cursor:pointer; font-weight:700;">
                                    <i class="fa-solid fa-floppy-disk"></i> Save
                                </button>
                                <button type="button" onclick="cancelEdit(<?= $q['id'] ?>)" style="background:#f1f5f9; color:#64748b; border:none; padding:10px 18px; border-radius:8px; cursor:pointer; font-weight:700;">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ===== TAB 2: ADD SINGLE QUESTION ===== -->
<div id="tab-add" class="exam-tab-panel">
    <div class="card">
        <h3 style="margin:0 0 20px; font-weight:800; color:#1e293b;"><i class="fa-solid fa-circle-plus" style="color:var(--primary,#ff9d2d);"></i> Add New Question</h3>
        <form method="POST" action="admin.php?tab=manage_exam&job_id=<?= $job_id ?>">
            <input type="hidden" name="add_job_question" value="1">
            <input type="hidden" name="job_id" value="<?= $job_id ?>">
            
            <div style="margin-bottom:15px;">
                <label style="font-size:12px; font-weight:700; color:#64748b; display:block; margin-bottom:6px;">QUESTION TEXT *</label>
                <textarea name="question" required rows="3" style="width:100%; padding:12px; border:1.5px solid #e2e8f0; border-radius:10px; font-family:inherit; resize:none;" placeholder="Type the exam question here..."></textarea>
            </div>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                <?php foreach(['A','B','C','D'] as $l): ?>
                    <div>
                        <label style="font-size:12px; font-weight:700; color:#64748b; display:block; margin-bottom:5px;">OPTION <?= $l ?> *</label>
                        <input type="text" name="option_<?= strtolower($l) ?>" required style="width:100%; padding:10px; border:1.5px solid #e2e8f0; border-radius:8px;" placeholder="Option <?= $l ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="margin-bottom:20px;">
                <label style="font-size:12px; font-weight:700; color:#10b981; display:block; margin-bottom:6px;">✅ CORRECT ANSWER *</label>
                <select name="correct_answer" required style="width:100%; padding:12px; border:2px solid #10b981; border-radius:10px; background:#f0fdf4; font-weight:700; color:#065f46;">
                    <option value="A">Option A</option>
                    <option value="B">Option B</option>
                    <option value="C">Option C</option>
                    <option value="D">Option D</option>
                </select>
            </div>
            
            <button type="submit" style="background:var(--primary,#ff9d2d); color:#fff; border:none; padding:12px 30px; border-radius:10px; font-weight:700; cursor:pointer; font-size:15px;">
                <i class="fa-solid fa-plus"></i> Add Question
            </button>
        </form>
    </div>
</div>

<!-- ===== TAB 3: BULK TEXT IMPORT ===== -->
<div id="tab-bulk" class="exam-tab-panel">
    <div class="card">
        <h3 style="margin:0 0 5px; font-weight:800; color:#1e293b;"><i class="fa-solid fa-layer-group" style="color:var(--primary,#ff9d2d);"></i> Bulk Import via Text</h3>
        <p style="color:#64748b; font-size:14px; margin-bottom:20px;">Paste multiple questions at once using the format below. Each question block is separated by a blank line.</p>
        
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:15px; margin-bottom:20px; font-size:13px; color:#475569;">
            <strong style="color:#1e293b;">📋 Required Format:</strong><br>
            <code style="display:block; margin-top:8px; white-space:pre; line-height:1.8; color:#0369a1;">What is the correct answer?
A) Option text here
B) Option text here
C) Option text here
D) Option text here
ANSWER: B

Next question goes here after a blank line
A) ...
B) ...
C) ...
D) ...
ANSWER: A</code>
        </div>
        
        <form method="POST" action="admin.php?tab=manage_exam&job_id=<?= $job_id ?>">
            <input type="hidden" name="bulk_import_questions" value="1">
            <input type="hidden" name="job_id" value="<?= $job_id ?>">
            <textarea name="bulk_text" class="bulk-textarea" placeholder="Paste your questions here using the format shown above..."></textarea>
            <div style="margin-top:15px; display:flex; gap:10px; align-items:center;">
                <button type="submit" style="background:var(--primary,#ff9d2d); color:#fff; border:none; padding:12px 25px; border-radius:10px; font-weight:700; cursor:pointer; font-size:14px;">
                    <i class="fa-solid fa-file-import"></i> Import Questions
                </button>
                <span style="font-size:13px; color:#94a3b8;">Existing questions will be kept. Duplicates may be added.</span>
            </div>
        </form>
    </div>
</div>

<!-- ===== TAB 4: IMAGE/PDF UPLOAD ===== -->
<div id="tab-upload" class="exam-tab-panel">
    <div class="card">
        <h3 style="margin:0 0 5px; font-weight:800; color:#1e293b;"><i class="fa-solid fa-file-image" style="color:var(--primary,#ff9d2d);"></i> Import Questions from Image or PDF</h3>
        <p style="color:#64748b; font-size:14px; margin-bottom:20px;">
            Upload an image (JPG, PNG) of a printed exam sheet, or a PDF. The system will attempt to extract questions automatically.
            <strong>After upload, you can review and save each extracted question.</strong>
        </p>
        
        <div style="border:2px dashed #cbd5e1; border-radius:15px; padding:40px; text-align:center; margin-bottom:20px; cursor:pointer; transition:0.2s;"
             onclick="document.getElementById('exam_file_input').click()"
             ondragover="event.preventDefault(); this.style.borderColor='#ff9d2d'; this.style.background='#fff7ed';"
             ondragleave="this.style.borderColor='#cbd5e1'; this.style.background='transparent';"
             ondrop="handleFileDrop(event)">
            <i class="fa-solid fa-cloud-arrow-up" style="font-size:48px; color:#cbd5e1; margin-bottom:15px;"></i>
            <p style="font-size:16px; font-weight:700; color:#64748b; margin:0;">Click to upload or drag & drop</p>
            <p style="font-size:13px; color:#94a3b8; margin:8px 0 0;">Supports: JPG, PNG, GIF, PDF</p>
            <p id="upload_filename" style="font-size:13px; color:#ff9d2d; font-weight:700; margin-top:10px;"></p>
        </div>
        
        <form method="POST" enctype="multipart/form-data" action="admin.php?tab=manage_exam&job_id=<?= $job_id ?>" id="uploadExamForm">
            <input type="hidden" name="upload_exam_file" value="1">
            <input type="hidden" name="job_id" value="<?= $job_id ?>">
            <input type="file" name="exam_file" id="exam_file_input" accept=".jpg,.jpeg,.png,.gif,.pdf" style="display:none;" onchange="showUploadName(this)">
            <button type="submit" id="upload_submit_btn" style="background:var(--primary,#ff9d2d); color:#fff; border:none; padding:12px 25px; border-radius:10px; font-weight:700; cursor:pointer; font-size:14px; display:none;">
                <i class="fa-solid fa-magnifying-glass"></i> Extract Questions from File
            </button>
        </form>

        <?php
        // Show extracted results from session if any
        if (!empty($_SESSION['extracted_questions'])):
            $extracted = $_SESSION['extracted_questions'];
        ?>
        <div style="margin-top:30px;">
            <h4 style="font-weight:800; color:#1e293b; margin-bottom:15px;">
                <i class="fa-solid fa-wand-magic-sparkles" style="color:#ff9d2d;"></i> 
                Extracted Questions (<?= count($extracted) ?> found) — Review & Save
            </h4>
            <form method="POST" action="admin.php?tab=manage_exam&job_id=<?= $job_id ?>">
                <input type="hidden" name="save_extracted_questions" value="1">
                <input type="hidden" name="job_id" value="<?= $job_id ?>">
                <?php foreach($extracted as $ei => $eq): ?>
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:20px; margin-bottom:15px;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                            <strong style="color:#ff9d2d;">Extracted Q<?= $ei+1 ?></strong>
                            <label style="display:flex; align-items:center; gap:6px; font-size:13px; color:#10b981; font-weight:700; cursor:pointer;">
                                <input type="checkbox" name="save_q[]" value="<?= $ei ?>" checked> Save this question
                            </label>
                        </div>
                        <input type="text" name="eq_question[<?=$ei?>]" value="<?= htmlspecialchars($eq['question']) ?>" style="width:100%; padding:10px; border:1.5px solid #e2e8f0; border-radius:8px; margin-bottom:8px; font-family:inherit;">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px;">
                            <?php foreach(['a','b','c','d'] as $l): ?>
                                <input type="text" name="eq_option_<?=$l?>[<?=$ei?>]" value="<?= htmlspecialchars($eq['option_'.$l] ?? '') ?>" placeholder="Option <?= strtoupper($l) ?>" style="padding:8px; border:1.5px solid #e2e8f0; border-radius:8px;">
                            <?php endforeach; ?>
                        </div>
                        <select name="eq_correct[<?=$ei?>]" style="padding:8px; border:2px solid #10b981; border-radius:8px; background:#f0fdf4; font-weight:700; color:#065f46;">
                            <?php foreach(['A','B','C','D'] as $l): ?>
                                <option value="<?=$l?>" <?= (($eq['correct']??'A')==$l)?'selected':'' ?>>✅ Correct: Option <?=$l?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
                <button type="submit" style="background:#10b981; color:#fff; border:none; padding:12px 25px; border-radius:10px; font-weight:700; cursor:pointer; font-size:14px; width:100%;">
                    <i class="fa-solid fa-floppy-disk"></i> Save Selected Questions to Exam
                </button>
            </form>
        </div>
        <?php unset($_SESSION['extracted_questions']); endif; ?>
    </div>
</div>

<script>
function switchTab(id, btn) {
    document.querySelectorAll('.exam-tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.exam-tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    btn.classList.add('active');
}

function editQuestion(id) {
    document.getElementById('view-' + id).style.display = 'none';
    document.getElementById('edit-' + id).style.display = 'block';
    document.getElementById('qcard-' + id).classList.add('editing');
}

function cancelEdit(id) {
    document.getElementById('view-' + id).style.display = 'block';
    document.getElementById('edit-' + id).style.display = 'none';
    document.getElementById('qcard-' + id).classList.remove('editing');
}

function showUploadName(input) {
    if (input.files.length > 0) {
        document.getElementById('upload_filename').textContent = '📎 ' + input.files[0].name;
        document.getElementById('upload_submit_btn').style.display = 'inline-block';
    }
}

function handleFileDrop(event) {
    event.preventDefault();
    event.currentTarget.style.borderColor = '#cbd5e1';
    event.currentTarget.style.background = 'transparent';
    const file = event.dataTransfer.files[0];
    const input = document.getElementById('exam_file_input');
    const dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
    showUploadName(input);
}
</script>
