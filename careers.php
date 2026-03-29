<?php
session_start();
require_once 'db.php';

// Auto-create job tables if missing (Render fresh deployment safety)
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
} catch (PDOException $e) { /* ignore */ }

// Fetch global company info
$c_stmt = $pdo->query("SELECT * FROM company_info LIMIT 1");
$c = $c_stmt->fetch(PDO::FETCH_ASSOC);

$jobs_query = $pdo->prepare("SELECT * FROM jobs WHERE status = 'Open' AND closing_date >= NOW() ORDER BY id DESC");
$jobs_query->execute();
$open_jobs = $jobs_query->fetchAll(PDO::FETCH_ASSOC);

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_job'])) {
    $job_id = $_POST['job_id'];
    $name = $_POST['fullname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $gpa = floatval($_POST['gpa']);
    
    // Resume upload
    $resume_url = '';
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] == 0) {
        $ext = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
        $new_name = "resume_" . time() . ".$ext";
        if (move_uploaded_file($_FILES['resume']['tmp_name'], "uploads/$new_name")) {
            $resume_url = "uploads/$new_name";
        }
    }

    // Photo upload
    $photo_url = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $new_name = "photo_" . time() . ".$ext";
        if (move_uploaded_file($_FILES['photo']['tmp_name'], "uploads/$new_name")) {
            $photo_url = "uploads/$new_name";
        }
    }

    // Exam Score calculation
    $score = null;
    $questions = $pdo->prepare("SELECT id, correct_answer FROM job_questions WHERE job_id = ?");
    $questions->execute([$job_id]);
    $q_list = $questions->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($q_list) > 0) {
        $correct = 0;
        foreach ($q_list as $q) {
            $qid = $q['id'];
            if (isset($_POST["question_$qid"]) && $_POST["question_$qid"] === $q['correct_answer']) {
                $correct++;
            }
        }
        $score = ($correct / count($q_list)) * 100;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO job_applications (job_id, applicant_name, email, phone, gpa, photo_url, resume_url, exam_score, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt->execute([$job_id, $name, $email, $phone, $gpa, $photo_url, $resume_url, $score]);
        $msg = "Your application has been submitted successfully!";
        if ($score !== null) {
            $msg .= " Your screening exam score was " . number_format($score, 2) . "%.";
        }
    } catch (Exception $e) {
        $err = "Error submitting application. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Careers | <?= htmlspecialchars($c['company_name'] ?? 'Sheger Kurt') ?></title>
  
  <link rel="stylesheet" href="./assets/css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&family=Rubik:wght@400;500;600;700&family=Shadows+Into+Light&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .job-card {
        background: #fff;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        border: 1px solid #f8f9fa;
        margin-bottom: 30px;
        transition: 0.3s;
        text-align: left;
    }
    .job-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0,0,0,0.1);
    }
    .job-title {
        font-size: 24px;
        font-weight: 800;
        color: var(--rich-black-fogra-29);
        margin-bottom: 10px;
    }
    .job-meta {
        display: flex;
        gap: 15px;
        color: var(--spanish-gray);
        font-size: 14px;
        margin-bottom: 15px;
        font-weight: 500;
        flex-wrap: wrap;
    }
    .job-meta span {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .job-desc {
        color: var(--sonic-silver);
        line-height: 1.6;
        font-size: 15px;
        margin-bottom: 25px;
        white-space: pre-line;
    }
    .apply-btn {
        background: var(--deep-saffron);
        color: #fff;
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 700;
        text-transform: uppercase;
        border: none;
        cursor: pointer;
        transition: 0.3s;
    }
    .apply-btn:hover {
        background: var(--cinnabar);
    }
    
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.6);
        z-index: 1000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .modal-content {
        background: #fff;
        border-radius: 20px;
        padding: 30px;
        width: 100%;
        max-width: 700px;
        max-height: 90vh;
        overflow-y: auto;
    }
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        font-weight: 600;
        color: var(--rich-black-fogra-29);
        margin-bottom: 8px;
        font-size: 14px;
    }
    .form-group input, .form-group select {
        width: 100%;
        padding: 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-family: inherit;
    }
    .exam-section {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 20px;
        border-radius: 12px;
        margin-top: 30px;
    }
    .question-block {
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e2e8f0;
    }
    .question-block h4 {
        margin-bottom: 10px;
        font-size: 16px;
        color: #1e293b;
    }
    .options-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    .option-label {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #fff;
        padding: 10px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
    }
    .option-label:hover {
        background: #f1f5f9;
        border-color: var(--deep-saffron);
    }
  </style>
</head>

<body id="top">

  <!-- HEADER -->
  <header class="header" data-header>
    <div class="container">
      <h1 style="margin: 0;">
        <a href="index.php" class="logo" style="text-decoration:none; display:flex; align-items:center;">
            <i class="fa-solid fa-utensils" style="color:var(--deep-saffron); margin-right:10px;"></i> 
            <?= htmlspecialchars($c['company_name'] ?? 'Sheger Kurt') ?><span class="span">.</span>
        </a>
      </h1>

      <nav class="navbar" data-navbar>
        <ul class="navbar-list">
          <li class="nav-item"><a href="index.php#home" class="navbar-link" data-nav-link>Home</a></li>
          <li class="nav-item"><a href="index.php#about" class="navbar-link" data-nav-link>About Us</a></li>
          <li class="nav-item"><a href="index.php#food-menu" class="navbar-link" data-nav-link>Menu</a></li>
          <li class="nav-item"><a href="careers.php" class="navbar-link" data-nav-link style="color: var(--deep-saffron);">Careers</a></li>
          <li class="nav-item"><a href="index.php#contact" class="navbar-link" data-nav-link>Contact</a></li>
        </ul>
      </nav>

      <div class="header-btn-group">
        <a href="login.php" class="btn btn-hover">Sign In / Admin</a>
      </div>
    </div>
  </header>

  <main>
    <article>
      <section class="section" style="padding-top: 150px; background: var(--isabelline); min-height: 80vh;">
        <div class="container">
          <div style="text-align: center; margin-bottom: 50px;">
            <h2 class="h2 section-title">Join Our <span class="span">Team</span>!</h2>
            <p class="section-text" style="max-width: 60ch; margin: 0 auto;">We are always looking for passionate people to join us. Check out our open positions below and take the screening exam to apply directly.</p>
          </div>

          <?php if($msg): ?>
            <div style="background: #22c55e; color: #fff; padding: 15px; border-radius: 10px; text-align: center; margin-bottom: 30px; font-weight: 700;">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($msg) ?>
            </div>
          <?php endif; ?>
          <?php if($err): ?>
            <div style="background: #ef4444; color: #fff; padding: 15px; border-radius: 10px; text-align: center; margin-bottom: 30px; font-weight: 700;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($err) ?>
            </div>
          <?php endif; ?>

          <?php if(empty($open_jobs)): ?>
            <div style="text-align:center; padding: 50px; background: #fff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                <i class="fa-solid fa-briefcase" style="font-size: 60px; color: #cbd5e1; margin-bottom: 20px;"></i>
                <h3>No Open Positions at the Moment</h3>
                <p style="color: #64748b;">Please check back later.</p>
            </div>
          <?php else: ?>
            <div style="max-width: 800px; margin: 0 auto;">
                <?php foreach($open_jobs as $job): 
                    $q_stmt = $pdo->prepare("SELECT * FROM job_questions WHERE job_id = ?");
                    $q_stmt->execute([$job['id']]);
                    $questions = $q_stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="job-card">
                    <h3 class="job-title"><?= htmlspecialchars($job['title']) ?></h3>
                    <div class="job-meta">
                        <span><i class="fa-solid fa-tag"></i> <?= htmlspecialchars($job['category']) ?></span>
                        <span><i class="fa-solid fa-clock"></i> <?= htmlspecialchars($job['type']) ?></span>
                        <span><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($job['location']) ?></span>
                        <span style="color:var(--cinnabar);"><i class="fa-solid fa-hourglass-end"></i> Closes: <?= date('M d, Y', strtotime($job['closing_date'])) ?></span>
                    </div>
                    <p class="job-desc"><?= htmlspecialchars($job['description']) ?></p>
                    <button class="apply-btn" 
                        data-job='<?= json_encode($job, JSON_HEX_APOS) ?>'
                        data-questions='<?= json_encode($questions, JSON_HEX_APOS) ?>'
                        onclick="openApplyModal(JSON.parse(this.dataset.job), JSON.parse(this.dataset.questions))">Apply Now &amp; Take Exam</button>
                </div>
                <?php endforeach; ?>
            </div>
          <?php endif; ?>

        </div>
      </section>
    </article>
  </main>

  <!-- Apply & Exam Modal -->
  <div class="modal-overlay" id="applyModal">
    <div class="modal-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px;">
            <h3 id="modal_job_title" style="font-size: 24px; font-weight: 800; color: #1e293b;">Apply for Position</h3>
            <button onclick="closeModal()" style="background:none; border:none; font-size: 24px; cursor: pointer; color:#64748b;">&times;</button>
        </div>
        
        <form method="POST" enctype="multipart/form-data" id="job_application_form">
            <input type="hidden" name="apply_job" value="1">
            <input type="hidden" name="job_id" id="modal_job_id">
            
            <!-- EXAM STEP -->
            <div id="exam_step" class="exam-section" style="display:none; margin-top:0;">
                <h3 style="color: var(--deep-saffron); margin-bottom: 5px; font-size: 20px;">Screening Exam</h3>
                <p style="color: #64748b; margin-bottom: 20px; font-size: 14px;">Please answer all the following questions to prove your eligibility before applying.</p>
                
                <div id="questions_list"></div>
                <div id="exam_error" style="color: #ef4444; font-weight: bold; margin-top: 15px; display: none;"></div>
                
                <button type="button" class="apply-btn" onclick="evaluateExam()" style="width:100%; margin-top: 25px; padding: 15px; font-size: 16px;">Check Results & Continue</button>
            </div>
            
            <!-- APPLICATION STEP -->
            <div id="app_step" style="display:none;">
                <div id="success_message" style="background:#e0f2fe; color:#0369a1; padding:15px; border-radius:10px; margin-bottom:20px; font-weight:700; display:none;">
                    <i class="fa-solid fa-circle-check"></i> Great job! You passed the screening exam. Please complete your details below.
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="form-group">
                        <label>Full Name <span style="color:red;">*</span></label>
                        <input type="text" name="fullname" id="app_fullname">
                    </div>
                    <div class="form-group">
                        <label>Email Address <span style="color:red;">*</span></label>
                        <input type="email" name="email" id="app_email">
                    </div>
                    <div class="form-group">
                        <label>Phone Number <span style="color:red;">*</span></label>
                        <input type="tel" name="phone" id="app_phone">
                    </div>
                    <div class="form-group">
                        <label>GPA / Last Score <span style="color:red;">*</span></label>
                        <input type="number" step="0.01" name="gpa" id="app_gpa" placeholder="e.g. 3.5">
                    </div>
                </div>
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="form-group">
                        <label>Profile Photo (Optional)</label>
                        <input type="file" name="photo" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label>Resume / CV (Optional)</label>
                        <input type="file" name="resume" accept=".pdf,.doc,.docx">
                    </div>
                </div>
                
                <button type="submit" class="apply-btn" style="width:100%; margin-top: 25px; padding: 15px; font-size: 16px;">Submit Application</button>
            </div>
        </form>
    </div>
  </div>

  <script>
    let currentQuestions = [];
    
    function setRequiredAppFields(isRequired) {
        document.getElementById('app_fullname').required = isRequired;
        document.getElementById('app_email').required = isRequired;
        document.getElementById('app_phone').required = isRequired;
        document.getElementById('app_gpa').required = isRequired;
    }

    function openApplyModal(job, questions) {
        document.getElementById('modal_job_title').innerText = 'Apply for: ' + job.title;
        document.getElementById('modal_job_id').value = job.id;
        document.getElementById('exam_error').style.display = 'none';
        
        currentQuestions = questions || [];
        const examStep = document.getElementById('exam_step');
        const appStep = document.getElementById('app_step');
        const qList = document.getElementById('questions_list');
        const successMsg = document.getElementById('success_message');
        
        qList.innerHTML = ''; // Clear previous
        
        if (currentQuestions.length > 0) {
            // Show Exam Step first
            examStep.style.display = 'block';
            appStep.style.display = 'none';
            successMsg.style.display = 'none';
            setRequiredAppFields(false); // Don't require app fields while taking exam
            
            currentQuestions.forEach((q, index) => {
                const block = document.createElement('div');
                block.className = 'question-block';
                block.innerHTML = `
                    <h4><span style="color:var(--cinnabar);">Q${index+1}.</span> ${q.question}</h4>
                    <div class="options-grid">
                        <label class="option-label"><input type="radio" name="question_${q.id}" value="A"> ${q.option_a}</label>
                        <label class="option-label"><input type="radio" name="question_${q.id}" value="B"> ${q.option_b}</label>
                        <label class="option-label"><input type="radio" name="question_${q.id}" value="C"> ${q.option_c}</label>
                        <label class="option-label"><input type="radio" name="question_${q.id}" value="D"> ${q.option_d}</label>
                    </div>
                `;
                qList.appendChild(block);
            });
        } else {
            // No exam, jump straight to Application Step
            examStep.style.display = 'none';
            appStep.style.display = 'block';
            successMsg.style.display = 'none';
            setRequiredAppFields(true);
        }
        
        document.getElementById('applyModal').style.display = 'flex';
    }
    
    function evaluateExam() {
        if (!currentQuestions || currentQuestions.length === 0) return;
        
        let correctAnswers = 0;
        let answeredQuestions = 0;
        
        const errorDiv = document.getElementById('exam_error');
        errorDiv.style.display = 'none';
        
        for (let i = 0; i < currentQuestions.length; i++) {
            const q = currentQuestions[i];
            const selected = document.querySelector(`input[name="question_${q.id}"]:checked`);
            
            if (selected) {
                answeredQuestions++;
                if (selected.value === q.correct_answer) {
                    correctAnswers++;
                }
            }
        }
        
        if (answeredQuestions < currentQuestions.length) {
            errorDiv.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Please answer all questions before submitting.';
            errorDiv.style.display = 'block';
            return;
        }
        
        const scorePercentage = (correctAnswers / currentQuestions.length) * 100;
        
        if (scorePercentage >= 50) {
            // Passed
            document.getElementById('exam_step').style.display = 'none';
            document.getElementById('app_step').style.display = 'block';
            document.getElementById('success_message').style.display = 'block';
            setRequiredAppFields(true);
        } else {
            // Failed
            errorDiv.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> We appreciate your interest, but you did not pass the minimum screening requirement of 50%. You cannot proceed with this application.';
            errorDiv.style.display = 'block';
        }
    }
    
    function closeModal() {
        document.getElementById('applyModal').style.display = 'none';
    }
  </script>

</body>
</html>
