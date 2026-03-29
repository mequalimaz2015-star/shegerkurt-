<?php
require_once 'db.php';

$results = [];

function run($pdo, $sql, $label) {
    global $results;
    try {
        $pdo->exec($sql);
        $results[] = "✅ $label";
    } catch (PDOException $e) {
        $results[] = "⚠️ $label: " . $e->getMessage();
    }
}

// 1. Create job_questions
run($pdo, "CREATE TABLE IF NOT EXISTS job_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    question TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_answer CHAR(1) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)", "job_questions table");

// 2. Create job_applications
run($pdo, "CREATE TABLE IF NOT EXISTS job_applications (
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
)", "job_applications table");

// 3. Patch chat_messages columns
run($pdo, "ALTER TABLE chat_messages ADD COLUMN IF NOT EXISTS image_path VARCHAR(255) DEFAULT NULL", "chat_messages.image_path");
run($pdo, "ALTER TABLE chat_messages ADD COLUMN IF NOT EXISTS location_lat VARCHAR(50) DEFAULT NULL", "chat_messages.location_lat");
run($pdo, "ALTER TABLE chat_messages ADD COLUMN IF NOT EXISTS location_lng VARCHAR(50) DEFAULT NULL", "chat_messages.location_lng");

// 4. Seed sample staff (only if empty)
$count = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
if ($count == 0) {
    $staff = [
        ['Mr.', 'Abebe Girma Tadesse', 'Abebe', 'Girma', 'Tadesse', 'Head Chef', 15000, 'Monthly', 'abebe@shegerkurt.com', '0911223344', 'Addis Ababa', 'Tigist Abebe', '0922334455', '1988-05-12', 'Male', '2020-01-15'],
        ['Ms.', 'Tigist Bekele Worku', 'Tigist', 'Bekele', 'Worku', 'Waitress', 8000, 'Monthly', 'tigist@shegerkurt.com', '0922334455', 'Addis Ababa', 'Lemma Bekele', '0933445566', '1995-08-20', 'Female', '2021-03-10'],
        ['Mr.', 'Dawit Haile Mekonen', 'Dawit', 'Haile', 'Mekonen', 'Bartender', 9500, 'Monthly', 'dawit@shegerkurt.com', '0933445566', 'Addis Ababa', 'Sara Haile', '0944556677', '1990-11-30', 'Male', '2021-06-01'],
        ['Ms.', 'Marta Tesfaye Alemu', 'Marta', 'Tesfaye', 'Alemu', 'Cashier', 7500, 'Monthly', 'marta@shegerkurt.com', '0944556677', 'Addis Ababa', 'Yonas Tesfaye', '0955667788', '1997-03-14', 'Female', '2022-01-20'],
        ['Mr.', 'Yonas Tadesse Girma', 'Yonas', 'Tadesse', 'Girma', 'Security', 6000, 'Monthly', 'yonas@shegerkurt.com', '0955667788', 'Addis Ababa', 'Almaz Tadesse', '0966778899', '1992-07-22', 'Male', '2022-04-05'],
    ];
    $estmt = $pdo->prepare("INSERT INTO employees (title, name, first_name, middle_name, last_name, role, salary, salary_type, email, phone, address, emergency_contact_name, emergency_contact_phone, date_of_birth, gender, join_date, hire_date, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'Active')");
    foreach ($staff as $emp) {
        $estmt->execute($emp);
        $new_id = $pdo->lastInsertId();
        $id_num = 'SK-' . str_pad($new_id, 3, '0', STR_PAD_LEFT);
        $pdo->prepare("UPDATE employees SET id_number=? WHERE id=?")->execute([$id_num, $new_id]);
    }
    $results[] = "✅ 5 sample staff employees seeded";
} else {
    $results[] = "ℹ️ Staff already exist ($count records), skipped seeding";
}

// 5. Seed sample jobs (only if empty)
$jcount = $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
if ($jcount == 0) {
    $jobs = [
        ['Senior Bartender', 'Bar & Beverage', 'Full-Time', 'Addis Ababa', "We are looking for an experienced Senior Bartender.\n\nRequirements:\n- 3+ years bartending experience\n- Knowledge of Ethiopian and international drinks\n- Strong customer service skills", date('Y-m-d', strtotime('+30 days')), 'Open'],
        ['Kurt Specialist Chef', 'Kitchen', 'Full-Time', 'Addis Ababa', "Join our kitchen team as a Kurt Specialist Chef.\n\nRequirements:\n- Experience in Ethiopian meat preparation\n- Food safety certificate\n- Passion for traditional cuisine", date('Y-m-d', strtotime('+25 days')), 'Open'],
        ['Restaurant Supervisor', 'Management', 'Full-Time', 'Addis Ababa', "We need a skilled Restaurant Supervisor to oversee daily operations.\n\nRequirements:\n- 5+ years in restaurant management\n- Leadership and communication skills\n- Customer-oriented mindset", date('Y-m-d', strtotime('+20 days')), 'Open'],
        ['Delivery Rider', 'Delivery', 'Part-Time', 'Addis Ababa', "We are hiring Delivery Riders for our growing delivery service.\n\nRequirements:\n- Valid motorcycle license\n- Knowledge of Addis Ababa routes\n- Reliable and punctual", date('Y-m-d', strtotime('+15 days')), 'Open'],
    ];
    $jstmt = $pdo->prepare("INSERT INTO jobs (title, category, type, location, description, closing_date, status) VALUES (?,?,?,?,?,?,?)");
    foreach ($jobs as $job) $jstmt->execute($job);
    $results[] = "✅ 4 sample job listings seeded";
} else {
    $results[] = "ℹ️ Jobs already exist ($jcount records), skipped seeding";
}

?>
<!DOCTYPE html>
<html>
<head>
<title>Fix Missing Tables</title>
<style>
body { font-family: sans-serif; max-width: 700px; margin: 50px auto; padding: 20px; }
.result { padding: 10px 15px; margin: 8px 0; border-radius: 8px; font-size: 14px; background: #f8fafc; border-left: 4px solid #10b981; }
.result:has(⚠️) { border-color: #f59e0b; }
h2 { color: #1e293b; }
.btn { display: inline-block; padding: 12px 24px; background: #ff9d2d; color: #fff; text-decoration: none; border-radius: 10px; font-weight: 700; margin-top: 20px; }
</style>
</head>
<body>
<h2>🔧 Fix Missing Tables & Seed Data</h2>
<?php foreach ($results as $r): ?>
<div class="result"><?= $r ?></div>
<?php endforeach; ?>
<br>
<a href="admin.php?tab=applications" class="btn">→ Check Job Applications</a>
&nbsp;
<a href="admin.php?tab=staff" class="btn" style="background:#1e293b">→ Check Staff</a>
&nbsp;
<a href="admin.php?tab=jobs" class="btn" style="background:#6366f1">→ Check Jobs</a>
</body>
</html>
