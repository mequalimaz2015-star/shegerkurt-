<?php
// Handle additions & deletions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = "Action completed successfully!";

    function logActivity($pdo, $action)
    {
        $admin = $_SESSION['admin_name'] ?? 'System';
        $stmt = $pdo->prepare("INSERT INTO activity_logs (action, admin_name) VALUES (?, ?)");
        $stmt->execute([$action, $admin]);
    }

    // 1. CHAT REPLY HANDLER (Top Priority for AJAX)
    if (isset($_POST['send_chat_reply'])) {
        $sid = $_POST['session_id'];
        $reply = trim($_POST['reply'] ?? '');
        $lat = $_POST['lat'] ?? null;
        $lng = $_POST['lng'] ?? null;
        $image_path = null;

        if (isset($_FILES['chat_image']) && $_FILES['chat_image']['error'] === 0) {
            $upload_dir = __DIR__ . "/../uploads/chat/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = pathinfo($_FILES['chat_image']['name'], PATHINFO_EXTENSION);
            $filename = 'admin_chat_' . time() . '_' . rand(100, 999) . '.' . $ext;
            if (move_uploaded_file($_FILES['chat_image']['tmp_name'], $upload_dir . $filename)) {
                $image_path = "uploads/chat/" . $filename;
            }
        }

        if ($sid && ($reply || $image_path || $lat)) {
            $stmt = $pdo->prepare("INSERT INTO chat_messages (session_id, sender, message, image_path, location_lat, location_lng, is_read) VALUES (?, 'Admin', ?, ?, ?, ?, 1)");
            $stmt->execute([$sid, $reply, $image_path, $lat, $lng]);
            logActivity($pdo, "Replied to chat session: $sid");

            // Clean exit for AJAX
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' || isset($_POST['ajax'])) {
                echo json_encode(['success' => true, 'message' => "Reply handled"]);
                exit;
            }
            $msg = "Reply sent successfully!";
        }
    }

    function moveToRecycleBin($pdo, $table, $id, $reason = "Manual Deletion")
    {
        $admin = $_SESSION['admin_name'] ?? 'System';
        // Fetch record first
        $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record) {
            $json_data = json_encode($record);
            $ins = $pdo->prepare("INSERT INTO recycle_bin (table_name, record_id, record_data, deleted_by, deletion_reason) VALUES (?, ?, ?, ?, ?)");
            $ins->execute([$table, $id, $json_data, $admin, $reason]);

            // Now delete from original
            $pdo->prepare("DELETE FROM `$table` WHERE id = ?")->execute([$id]);
            return true;
        }
        return false;
    }

    if (isset($_POST['add_menu'])) {
        $name = $_POST['name'];
        $category = $_POST['category'];
        $price = $_POST['price'];
        $description = $_POST['description'];
        $uom = $_POST['uom'] ?? 'pcs';
        $image_url = $_POST['image_url'];

        if (isset($_FILES['dish_photo']) && $_FILES['dish_photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['dish_photo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $upload_dir = __DIR__ . "/../uploads/menu/";
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $new_filename = "dish_" . time() . "." . $ext;
                $target = $upload_dir . $new_filename;
                if (move_uploaded_file($_FILES['dish_photo']['tmp_name'], $target)) {
                    $image_url = "uploads/menu/" . $new_filename;
                }
            }
        }
        $stmt = $pdo->prepare("INSERT INTO menu_items (name, category, description, price, uom, image_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $category, $description, $price, $uom, $image_url]);
        logActivity($pdo, "Added new dish to menu: " . $name);
        $msg = "Food item '$name' successfully added!";
    } elseif (isset($_POST['import_excel'])) {
        // Handle CSV/Excel import
        $imported = 0;
        if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0) {
            $file = $_FILES['excel_file']['tmp_name'];
            $filename = $_FILES['excel_file']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if ($ext === 'csv') {
                // Parse CSV file
                $handle = fopen($file, 'r');
                if ($handle) {
                    $row_num = 0;
                    $stmt = $pdo->prepare("INSERT INTO menu_items (name, category, description, price, uom, image_url) VALUES (?, ?, ?, ?, ?, '')");
                    while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                        $row_num++;
                        // Skip header row
                        if ($row_num === 1) {
                            $first_cell = strtolower(trim($row[0] ?? ''));
                            if (in_array($first_cell, ['name', 'item', 'no', 'number'])) continue;
                        }
                        // Expect: name, category, price, description, uom
                        $item_name = trim($row[0] ?? '');
                        $item_category = trim($row[1] ?? 'Main');
                        $item_price = floatval($row[2] ?? 0);
                        $item_desc = trim($row[3] ?? '');
                        $item_uom = trim($row[4] ?? 'pcs');

                        if (!empty($item_name) && $item_price > 0) {
                            $stmt->execute([$item_name, $item_category, $item_desc, $item_price, $item_uom]);
                            $imported++;
                        }
                    }
                    fclose($handle);
                }
            }
        }
        logActivity($pdo, "Imported $imported menu items from Excel/CSV");
        $msg = "$imported menu items successfully imported from file!";
    } elseif (isset($_POST['delete_menu'])) {
        $reason = $_POST['deletion_reason'] ?? "Cleaned up from menu";
        moveToRecycleBin($pdo, 'menu_items', $_POST['id'], $reason);
        logActivity($pdo, "Moved menu item ID: " . $_POST['id'] . " to Recycle Bin");
        $msg = "Food item moved to Recycle Bin!";
    } elseif (isset($_POST['update_reservation'])) {
        $id = $_POST['id'];
        $status = $_POST['status'];
        $table_number = $_POST['table_number'] ?? '';
        $pdo->prepare("UPDATE reservations SET status=?, table_number=? WHERE id=?")->execute([$status, $table_number, $id]);
        logActivity($pdo, "Updated reservation ID $id to status: $status, Table: $table_number");
        $msg = "Reservation updated!";
    } elseif (isset($_POST['register_employee'])) {
        $first_name = $_POST['first_name'];
        $middle_name = $_POST['middle_name'];
        $last_name = $_POST['last_name'];
        $full_name = trim($first_name . " " . $middle_name . " " . $last_name);
        $photo_url = "";
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['photo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $upload_dir = __DIR__ . "/../uploads/staff/";
                if (!is_dir($upload_dir))
                    mkdir($upload_dir, 0777, true);
                $new_filename = "emp_" . time() . "." . $ext;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $new_filename)) {
                    $photo_url = "uploads/staff/" . $new_filename;
                }
            }
        }
        try {
            $title = $_POST['title'] ?? '';
            $stmt = $pdo->prepare("INSERT INTO employees (title, name, first_name, middle_name, last_name, role, salary_type, email, phone, address, emergency_contact_name, emergency_contact_phone, date_of_birth, gender, salary, join_date, hire_date, bio, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $title,
                $full_name,
                $first_name,
                $middle_name,
                $last_name,
                $_POST['role'],
                $_POST['salary_type'],
                $_POST['email'],
                $_POST['phone'],
                $_POST['address'],
                $_POST['emergency_name'],
                $_POST['emergency_phone'],
                $_POST['dob'],
                $_POST['gender'],
                $_POST['salary'],
                $_POST['join_date'],
                $_POST['join_date'],
                $_POST['bio'],
                $photo_url
            ]);
            $emp_id = $pdo->lastInsertId();
            $id_number = 'BA-' . str_pad($emp_id, 3, '0', STR_PAD_LEFT);
            $pdo->prepare("UPDATE employees SET id_number = ? WHERE id = ?")->execute([$id_number, $emp_id]);

            logActivity($pdo, "Registered new employee: $full_name ($id_number)");
                        $msg = "Employee '$full_name' registered with ID: $id_number";
            echo "<script>window.onload = function() { showIDCard(" . json_encode([
                'id' => $emp_id,
                'id_number' => $id_number,
                'title' => $title,
                'name' => $full_name,
                'role' => $_POST['role'],
                'photo' => $photo_url
            ]) . "); }</script>";
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo "<script>alert('Error: Data overlap detected (duplicate email or ID).'); window.location.href='admin.php?tab=staff';</script>";
                exit;
            } else {
                error_log("Employee registration error: " . $e->getMessage());
                $msg = "Error registering employee: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['update_employee_status'])) {
        $pdo->prepare("UPDATE employees SET status=? WHERE id=?")->execute([$_POST['status'], $_POST['id']]);
        logActivity($pdo, "Updated employee ID " . $_POST['id'] . " status to " . $_POST['status']);
        $msg = "Employee status updated to " . $_POST['status'];
    } elseif (isset($_POST['mark_check_in'])) {
        $emp_id = $_POST['employee_id'];
        $date = date('Y-m-d');
        $time = date('Y-m-d H:i:s');
        // Prevent double check-in
        $check = $pdo->prepare("SELECT id FROM attendance WHERE employee_id=? AND attendance_date=?");
        $check->execute([$emp_id, $date]);
        if (!$check->fetch()) {
            // Determine if late (Standard shift: 08:00, Grace: 10 mins)
            $shift_start = strtotime($date . ' 08:00:00');
            $grace_period = 10 * 60; // 10 minutes
            $current_timestamp = strtotime($time);
            $status = 'Present';
            $late_minutes = 0;
            if ($current_timestamp > ($shift_start + $grace_period)) {
                $status = 'Late';
                $late_minutes = round(($current_timestamp - $shift_start) / 60);
            }
            $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, attendance_date, check_in, status, late_minutes) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$emp_id, $date, $time, $status, $late_minutes]);
            logActivity($pdo, "Employee ID $emp_id checked in at " . date('H:i'));
            $msg = "Check-in successful at " . date('H:i');
        } else {
            $msg = "Error: Employee already checked in for today!";
        }
    } elseif (isset($_POST['mark_check_out'])) {
        $emp_id = $_POST['employee_id'];
        $date = date('Y-m-d');
        $time = date('Y-m-d H:i:s');

        $rec = $pdo->prepare("SELECT * FROM attendance WHERE employee_id=? AND attendance_date=?");
        $rec->execute([$emp_id, $date]);
        $row = $rec->fetch();

        if ($row) {
            if (!$row['check_out']) {
                $check_in = strtotime($row['check_in']);
                $check_out = strtotime($time);

                $work_seconds = $check_out - $check_in;
                $work_hours = $work_seconds / 3600;

                $overtime = 0;
                if ($work_hours > 8) {
                    $overtime = $work_hours - 8;
                }

                // Half day logic (less than 4 hours)
                $status = $row['status'];
                if ($work_hours < 4) {
                    $status = 'Half Day';
                }

                $stmt = $pdo->prepare("UPDATE attendance SET check_out=?, work_hours=?, overtime_hours=?, status=? WHERE id=?");
                $stmt->execute([$time, $work_hours, $overtime, $status, $row['id']]);
                logActivity($pdo, "Employee ID $emp_id checked out at " . date('H:i'));
                $msg = "Check-out successful at " . date('H:i');
            } else {
                $msg = "Error: Employee already checked out for today!";
            }
        } else {
            $msg = "Error: This employee has not checked in today!";
        }
    } elseif (isset($_POST['add_attendance'])) {
        // Manual marking
        $emp_id = $_POST['employee_id'];
        $date = $_POST['attendance_date'];

        $check = $pdo->prepare("SELECT id FROM attendance WHERE employee_id=? AND attendance_date=?");
        $check->execute([$emp_id, $date]);
        if (!$check->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, attendance_date, status, notes, overtime_hours) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $emp_id,
                $date,
                $_POST['status'],
                $_POST['notes'] ?? '',
                $_POST['overtime_hours'] ?? 0
            ]);
            logActivity($pdo, "Manually marked attendance for employee ID $emp_id on $date");
        }
    } elseif (isset($_POST['add_salary_advance'])) {
        $stmt = $pdo->prepare("INSERT INTO salary_advances (employee_id, amount, advance_date, reason) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['employee_id'], $_POST['amount'], $_POST['advance_date'], $_POST['reason']]);
        logActivity($pdo, "Recorded salary advance for employee ID " . $_POST['employee_id']);
    } elseif (isset($_POST['add_payroll'])) {
        $emp_id = $_POST['employee_id'];
        $month_str = $_POST['salary_month'];
        list($year, $month) = explode('-', $month_str);

        $emp_stmt = $pdo->prepare("SELECT * FROM employees WHERE id=?");
        $emp_stmt->execute([$emp_id]);
        $emp = $emp_stmt->fetch();

        if (!$emp) {
            echo "<script>alert('Error: Employee not found.'); window.history.back();</script>";
            exit;
        }

        $att_stmt = $pdo->prepare("SELECT status, SUM(overtime_hours) as ot, COUNT(*) as sessions FROM attendance WHERE employee_id=? AND DATE_FORMAT(attendance_date, '%Y-%m') = ? GROUP BY status");
        $att_stmt->execute([$emp_id, $month_str]);
        $attendance_data = $att_stmt->fetchAll(PDO::FETCH_ASSOC);

        $present_days = 0;
        $absent_days = 0;
        $late_count = 0;
        $total_ot_hours = 0;
        foreach ($attendance_data as $row) {
            if ($row['status'] == 'Present')
                $present_days += $row['sessions'];
            if ($row['status'] == 'Absent')
                $absent_days += $row['sessions'];
            if ($row['status'] == 'Late') {
                $present_days += $row['sessions'];
                $late_count += $row['sessions'];
            }
            if ($row['status'] == 'Half Day') {
                $present_days += ($row['sessions'] * 0.5);
                $absent_days += ($row['sessions'] * 0.5);
            }
            $total_ot_hours += $row['ot'];
        }

        $ot_stmt = $pdo->prepare("SELECT SUM(overtime_hours) FROM attendance WHERE employee_id=? AND DATE_FORMAT(attendance_date, '%Y-%m') = ?");
        $ot_stmt->execute([$emp_id, $month_str]);
        $total_ot_hours = $ot_stmt->fetchColumn() ?: 0;

        $base_rate = (float) ($emp['salary'] ?? 0);
        $working_days_standard = 26;
        $daily_rate = $base_rate / $working_days_standard;
        $hourly_rate = $daily_rate / 8;

        $calc_base_salary = $base_rate;
        if ($emp['salary_type'] == 'Daily') {
            $calc_base_salary = $daily_rate * $present_days;
        } elseif ($emp['salary_type'] == 'Hourly') {
            $calc_base_salary = $hourly_rate * ($present_days * 8);
        }

        $overtime_amount = $total_ot_hours * $hourly_rate * 1.5;
        $late_penalty = $late_count * 50;
        $absent_deduction = $absent_days * $daily_rate;

        $adv_stmt = $pdo->prepare("SELECT SUM(amount) FROM salary_advances WHERE employee_id=? AND DATE_FORMAT(advance_date, '%Y-%m') = ?");
        $adv_stmt->execute([$emp_id, $month_str]);
        $advance_deduction = (float) ($adv_stmt->fetchColumn() ?: 0);

        $bonus = (float) ($_POST['bonus'] ?? 0);
        $other_deductions = (float) ($_POST['deductions'] ?? 0);

        $net_salary = ($calc_base_salary + $overtime_amount + $bonus) - ($absent_deduction + $late_penalty + $advance_deduction + $other_deductions);
        $stmt = $pdo->prepare("INSERT INTO payroll (employee_id, salary_month, year, base_salary, bonus, deductions, net_salary, working_days, present_days, absent_days, late_count, total_overtime_hours, overtime_amount, advance_deduction, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $emp_id,
            $month_str,
            $year,
            $calc_base_salary,
            $bonus,
            ($absent_deduction + $late_penalty + $other_deductions),
            $net_salary,
            $working_days_standard,
            $present_days,
            $absent_days,
            $late_count,
            $total_ot_hours,
            $overtime_amount,
            $advance_deduction,
            $_POST['status']
        ]);
        logActivity($pdo, "Generated automated payroll for " . $emp['name'] . " (" . $month_str . ")");
        $msg = "Payroll for " . $emp['name'] . " generated successfully!";
    } elseif (isset($_POST['update_payroll_status'])) {
        $pdo->prepare("UPDATE payroll SET status=? WHERE id=?")->execute([$_POST['status'], $_POST['id']]);
        logActivity($pdo, "Updated payroll ID " . $_POST['id'] . " status to " . $_POST['status']);
        $msg = "Payroll status updated to " . $_POST['status'];
    } elseif (isset($_POST['add_job'])) {
        $stmt = $pdo->prepare("INSERT INTO jobs (title, category, type, location, description, closing_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['title'], $_POST['category'], $_POST['type'], $_POST['location'], $_POST['description'], $_POST['closing_date']]);
        logActivity($pdo, "Posted new job: " . $_POST['title']);
        $msg = "New job listing '" . $_POST['title'] . "' successfully posted!";
    } elseif (isset($_POST['delete_job'])) {
        $reason = $_POST['deletion_reason'] ?? "Position closed / Expired";
        moveToRecycleBin($pdo, 'jobs', $_POST['id'], $reason);
        logActivity($pdo, "Moved job listing ID: " . $_POST['id'] . " to Recycle Bin");
        $msg = "Job listing moved to Recycle Bin!";
    } elseif (isset($_POST['update_job_status'])) {
        $pdo->prepare("UPDATE jobs SET status=? WHERE id=?")->execute([$_POST['status'], $_POST['id']]);
        logActivity($pdo, "Updated job ID " . $_POST['id'] . " status to " . $_POST['status']);
    } elseif (isset($_POST['add_job_question'])) {
        $stmt = $pdo->prepare("INSERT INTO job_questions (job_id, question, option_a, option_b, option_c, option_d, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['job_id'], $_POST['question'], $_POST['option_a'], $_POST['option_b'], $_POST['option_c'], $_POST['option_d'], $_POST['correct_answer']
        ]);
        logActivity($pdo, "Added an exam question to job ID: " . $_POST['job_id']);
        $_SESSION['success_msg'] = "Exam question successfully added!";
        header("Location: admin.php?tab=manage_exam&job_id=" . $_POST['job_id']);
        exit;
    } elseif (isset($_POST['delete_job_question'])) {
        $job_id = $_POST['job_id'];
        $pdo->prepare("DELETE FROM job_questions WHERE id=?")->execute([$_POST['id']]);
        logActivity($pdo, "Deleted exam question ID: " . $_POST['id']);
        $_SESSION['exam_msg'] = "Question removed!";
        header("Location: admin.php?tab=manage_exam&job_id=" . $job_id);
        exit;

    } elseif (isset($_POST['update_job_question'])) {
        $stmt = $pdo->prepare("UPDATE job_questions SET question=?, option_a=?, option_b=?, option_c=?, option_d=?, correct_answer=? WHERE id=?");
        $stmt->execute([
            $_POST['question'], $_POST['option_a'], $_POST['option_b'],
            $_POST['option_c'], $_POST['option_d'], $_POST['correct_answer'], $_POST['id']
        ]);
        logActivity($pdo, "Updated exam question ID: " . $_POST['id']);
        $_SESSION['exam_msg'] = "Question updated successfully!";
        header("Location: admin.php?tab=manage_exam&job_id=" . $_POST['job_id']);
        exit;

    } elseif (isset($_POST['bulk_import_questions'])) {
        $job_id = $_POST['job_id'];
        $raw = trim($_POST['bulk_text'] ?? '');
        $blocks = preg_split('/\n\s*\n/', $raw);
        $imported = 0;
        $stmt = $pdo->prepare("INSERT INTO job_questions (job_id, question, option_a, option_b, option_c, option_d, correct_answer) VALUES (?,?,?,?,?,?,?)");
        foreach ($blocks as $block) {
            $lines = array_values(array_filter(array_map('trim', explode("\n", trim($block)))));
            if (count($lines) < 6) continue;
            $question = $lines[0];
            $opts = ['a'=>'', 'b'=>'', 'c'=>'', 'd'=>''];
            $correct = 'A';
            foreach ($lines as $line) {
                if (preg_match('/^A[\)\.]\s*(.+)/i', $line, $m)) $opts['a'] = $m[1];
                elseif (preg_match('/^B[\)\.]\s*(.+)/i', $line, $m)) $opts['b'] = $m[1];
                elseif (preg_match('/^C[\)\.]\s*(.+)/i', $line, $m)) $opts['c'] = $m[1];
                elseif (preg_match('/^D[\)\.]\s*(.+)/i', $line, $m)) $opts['d'] = $m[1];
                elseif (preg_match('/^ANSWER\s*:\s*([ABCD])/i', $line, $m)) $correct = strtoupper($m[1]);
            }
            if ($question && $opts['a'] && $opts['b'] && $opts['c'] && $opts['d']) {
                $stmt->execute([$job_id, $question, $opts['a'], $opts['b'], $opts['c'], $opts['d'], $correct]);
                $imported++;
            }
        }
        logActivity($pdo, "Bulk imported $imported questions to job ID $job_id");
        $_SESSION['exam_msg'] = "$imported question(s) imported successfully via text!";
        header("Location: admin.php?tab=manage_exam&job_id=" . $job_id);
        exit;

    } elseif (isset($_POST['upload_exam_file'])) {
        $job_id = $_POST['job_id'];
        $extracted = [];
        if (isset($_FILES['exam_file']) && $_FILES['exam_file']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['exam_file']['name'], PATHINFO_EXTENSION));
            $tmp = $_FILES['exam_file']['tmp_name'];
            $text = '';
            if (in_array($ext, ['jpg','jpeg','png','gif'])) {
                // Use GD to get image and attempt basic line extraction
                // We read lines that look like questions from OCR-like heuristics
                // For real OCR you'd call an external API; here we do placeholder parsing
                // Store the file and prompt user to manually confirm
                $upload_dir = __DIR__ . '/../uploads/admin/exam_files/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $fname = 'exam_' . time() . '.' . $ext;
                move_uploaded_file($tmp, $upload_dir . $fname);
                // Since we cannot run real OCR server-side without a library,
                // extract placeholder and show image so admin can manually type
                $_SESSION['extracted_questions'] = [
                    ['question' => 'Review the uploaded image and type this question', 'option_a' => 'Option A', 'option_b' => 'Option B', 'option_c' => 'Option C', 'option_d' => 'Option D', 'correct' => 'A'],
                ];
                $_SESSION['exam_upload_image'] = 'uploads/admin/exam_files/' . $fname;
                $_SESSION['exam_msg'] = "Image uploaded! Please review and edit the extracted questions below, then save.";
            } elseif ($ext === 'pdf') {
                // Read PDF text using basic string scan (no external lib)
                $content = file_get_contents($tmp);
                preg_match_all('/\(([^\)]{5,200})\)/', $content, $matches);
                $all_strings = array_unique($matches[1]);
                // Group into basic Q+A blocks heuristically
                $question_text = '';
                $opts = ['a'=>'','b'=>'','c'=>'','d'=>''];
                $qi = 0;
                foreach ($all_strings as $s) {
                    $s = preg_replace('/[^\x20-\x7E]/', '', $s);
                    if (strlen($s) < 5) continue;
                    if (preg_match('/^[A-D][\)\.]\s/', $s)) {
                        $letter = strtolower($s[0]);
                        $opts[$letter] = trim(substr($s, 2));
                    } elseif (strlen($s) > 15 && !$question_text) {
                        $question_text = $s;
                    }
                    if ($question_text && $opts['a'] && $opts['b'] && $opts['c'] && $opts['d']) {
                        $extracted[] = ['question'=>$question_text,'option_a'=>$opts['a'],'option_b'=>$opts['b'],'option_c'=>$opts['c'],'option_d'=>$opts['d'],'correct'=>'A'];
                        $question_text = '';
                        $opts = ['a'=>'','b'=>'','c'=>'','d'=>''];
                        if (++$qi >= 30) break;
                    }
                }
                if (empty($extracted)) {
                    $extracted = [['question'=>'PDF parsed — type question here', 'option_a'=>'Option A','option_b'=>'Option B','option_c'=>'Option C','option_d'=>'Option D','correct'=>'A']];
                }
                $_SESSION['extracted_questions'] = $extracted;
                $_SESSION['exam_msg'] = count($extracted) . " question block(s) found in PDF. Review and save below.";
            }
        }
        header("Location: admin.php?tab=manage_exam&job_id=" . $job_id);
        exit;

    } elseif (isset($_POST['save_extracted_questions'])) {
        $job_id = $_POST['job_id'];
        $to_save = $_POST['save_q'] ?? [];
        $stmt = $pdo->prepare("INSERT INTO job_questions (job_id, question, option_a, option_b, option_c, option_d, correct_answer) VALUES (?,?,?,?,?,?,?)");
        $saved = 0;
        foreach ($to_save as $i) {
            $stmt->execute([
                $job_id,
                $_POST['eq_question'][$i] ?? '',
                $_POST['eq_option_a'][$i] ?? '',
                $_POST['eq_option_b'][$i] ?? '',
                $_POST['eq_option_c'][$i] ?? '',
                $_POST['eq_option_d'][$i] ?? '',
                $_POST['eq_correct'][$i] ?? 'A'
            ]);
            $saved++;
        }
        logActivity($pdo, "Saved $saved extracted questions to job ID $job_id");
        $_SESSION['exam_msg'] = "$saved question(s) saved from file import!";
        header("Location: admin.php?tab=manage_exam&job_id=" . $job_id);
        exit;

    } elseif (isset($_POST['update_menu'])) {
        $name = $_POST['name'];
        $category = $_POST['category'];
        $price = $_POST['price'];
        $uom = $_POST['uom'] ?? 'pcs';
        $description = $_POST['description'];
        $image_url = $_POST['image_url'];
        $id = $_POST['id'];
        if (isset($_FILES['dish_photo']) && $_FILES['dish_photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['dish_photo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $upload_dir = __DIR__ . "/../uploads/menu/";
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $new_filename = "dish_" . time() . "." . $ext;
                $target = $upload_dir . $new_filename;
                if (move_uploaded_file($_FILES['dish_photo']['tmp_name'], $target)) {
                    $image_url = "uploads/menu/" . $new_filename;
                }
            }
        }
        $stmt = $pdo->prepare("UPDATE menu_items SET name=?, category=?, description=?, price=?, uom=?, image_url=? WHERE id=?");
        $stmt->execute([$name, $category, $description, $price, $uom, $image_url, $id]);
        logActivity($pdo, "Updated menu item: " . $name);
        $msg = "Menu item '$name' successfully updated!";
    } elseif (isset($_POST['update_profile'])) {
        $name = $_POST['full_name'];
        $email = $_POST['email'];
        $user_id = $_SESSION['admin_id'];
        $profile_pic = $_SESSION['admin_pic'] ?? '';
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['profile_pic']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                // __DIR__ = foodie-master/admin_tabs, go up one level to get project root
                $abs_upload_dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR;
                if (!is_dir($abs_upload_dir)) {
                    mkdir($abs_upload_dir, 0777, true);
                }
                $new_filename = "admin_" . time() . "." . $ext;
                $moved = move_uploaded_file($_FILES['profile_pic']['tmp_name'], $abs_upload_dir . $new_filename);
                if ($moved) {
                    $profile_pic = "uploads/admin/" . $new_filename;
                }
            }
        }

        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, profile_pic = ? WHERE id = ?");
        $stmt->execute([$name, $email, $profile_pic, $user_id]);
        $_SESSION['admin_name'] = $name;
        $_SESSION['admin_email'] = $email;
        $_SESSION['admin_pic'] = $profile_pic;
        logActivity($pdo, "Updated admin profile details & picture");
        $msg = "Profile updated successfully!";
    } elseif (isset($_POST['change_password'])) {
        $old_pass = $_POST['old_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];
        $user_id = $_SESSION['admin_id'];

        $user = $pdo->query("SELECT password FROM users WHERE id = $user_id")->fetch();

        if (password_verify($old_pass, $user['password'])) {
            if ($new_pass === $confirm_pass) {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $user_id]);
                logActivity($pdo, "Changed admin password");
                $msg = "Password changed successfully!";
            } else {
                $error_msg = "Passwords do not match.";
                header("Location: admin.php?tab=profile&err=" . urlencode($error_msg));
                exit;
            }
        } else {
            $error_msg = "Incorrect old password.";
            header("Location: admin.php?tab=profile&err=" . urlencode($error_msg));
            exit;
        }
    } elseif (isset($_POST['update_employee'])) {
        $first_name = $_POST['first_name'];
        $middle_name = $_POST['middle_name'];
        $last_name = $_POST['last_name'];
        $full_name = trim($first_name . " " . $middle_name . " " . $last_name);
        $id = $_POST['id'];

        $update_photo = "";
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['photo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $upload_dir = __DIR__ . "/../uploads/staff/";
                if (!is_dir($upload_dir))
                    mkdir($upload_dir, 0777, true);
                $new_filename = "emp_" . time() . "." . $ext;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $new_filename)) {
                    $update_photo = ", photo='uploads/staff/$new_filename'";
                }
            }
        }
        $stmt = $pdo->prepare("UPDATE employees SET title=?, name=?, first_name=?, middle_name=?, last_name=?, role=?, email=?, phone=?, salary=?, salary_type=?, join_date=?, date_of_birth=?, gender=?, address=?, emergency_contact_name=?, emergency_contact_phone=?, bio=? $update_photo WHERE id=?");
        $stmt->execute([
            $_POST['title'] ?? '',
            $full_name,
            $first_name,
            $middle_name,
            $last_name,
            $_POST['role'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['salary'],
            $_POST['salary_type'],
            $_POST['join_date'],
            $_POST['dob'],
            $_POST['gender'],
            $_POST['address'],
            $_POST['emergency_name'],
            $_POST['emergency_phone'],
            $_POST['bio'] ?? '',
            $id
        ]);

        logActivity($pdo, "Updated detailed employee profile for: $full_name");
        $msg = "Employee profile for $full_name successfully updated!";
    } elseif (isset($_POST['delete_employee'])) {
        $reason = $_POST['deletion_reason'] ?? "Resigned / Terminated";
        moveToRecycleBin($pdo, 'employees', $_POST['id'], $reason);
        logActivity($pdo, "Moved employee ID: " . $_POST['id'] . " to Recycle Bin");
        $msg = "Employee record moved to Recycle Bin!";
    } elseif (isset($_POST['update_job'])) {
        $stmt = $pdo->prepare("UPDATE jobs SET title=?, category=?, type=?, location=?, description=?, closing_date=? WHERE id=?");
        $stmt->execute([$_POST['title'], $_POST['category'], $_POST['type'], $_POST['location'], $_POST['description'], $_POST['closing_date'], $_POST['id']]);
        logActivity($pdo, "Updated job listing: " . $_POST['title']);
    } elseif (isset($_POST['update_company'])) {
        // Handle CEO image
        $ceo_image = $_POST['existing_ceo_image'] ?? '';
        if (isset($_FILES['ceo_photo']) && $_FILES['ceo_photo']['error'] == 0) {
            $new_path = "uploads/ceo/ceo_" . time() . "." . pathinfo($_FILES['ceo_photo']['name'], PATHINFO_EXTENSION);
            if (!is_dir(__DIR__ . "/../uploads/ceo/"))
                mkdir(__DIR__ . "/../uploads/ceo/", 0777, true);
            if (move_uploaded_file($_FILES['ceo_photo']['tmp_name'], __DIR__ . "/../" . $new_path))
                $ceo_image = $new_path;
        } elseif (!empty($_POST['ceo_image_url'])) {
            $ceo_image = $_POST['ceo_image_url'];
        }
        // Handle Hero Image
        $hero_image = $_POST['existing_hero_image'] ?? '';
        if (isset($_FILES['hero_photo']) && $_FILES['hero_photo']['error'] == 0) {
            $new_path = "uploads/site/hero_" . time() . "." . pathinfo($_FILES['hero_photo']['name'], PATHINFO_EXTENSION);
            if (!is_dir(__DIR__ . "/../uploads/site/"))
                mkdir(__DIR__ . "/../uploads/site/", 0777, true);
            if (move_uploaded_file($_FILES['hero_photo']['tmp_name'], __DIR__ . "/../" . $new_path))
                $hero_image = $new_path;
        }
        // Handle About Images
        $about_main = $_POST['existing_about_main'] ?? '';
        if (isset($_FILES['about_main_photo']) && $_FILES['about_main_photo']['error'] == 0) {
            $new_path = "uploads/site/about_main_" . time() . "." . pathinfo($_FILES['about_main_photo']['name'], PATHINFO_EXTENSION);
            if (!is_dir(__DIR__ . "/../uploads/site/"))
                mkdir(__DIR__ . "/../uploads/site/", 0777, true);
            if (move_uploaded_file($_FILES['about_main_photo']['tmp_name'], __DIR__ . "/../" . $new_path))
                $about_main = $new_path;
        }
        $about_sub1 = $_POST['existing_about_sub1'] ?? '';
        if (isset($_FILES['about_sub1_photo']) && $_FILES['about_sub1_photo']['error'] == 0) {
            $new_path = "uploads/site/about_sub1_" . time() . "." . pathinfo($_FILES['about_sub1_photo']['name'], PATHINFO_EXTENSION);
            if (!is_dir(__DIR__ . "/../uploads/site/"))
                mkdir(__DIR__ . "/../uploads/site/", 0777, true);
            if (move_uploaded_file($_FILES['about_sub1_photo']['tmp_name'], __DIR__ . "/../" . $new_path))
                $about_sub1 = $new_path;
        }

        $about_sub2 = $_POST['existing_about_sub2'] ?? '';
        if (isset($_FILES['about_sub2_photo']) && $_FILES['about_sub2_photo']['error'] == 0) {
            $new_path = "uploads/site/about_sub2_" . time() . "." . pathinfo($_FILES['about_sub2_photo']['name'], PATHINFO_EXTENSION);
            if (!is_dir(__DIR__ . "/../uploads/site/"))
                mkdir(__DIR__ . "/../uploads/site/", 0777, true);
            if (move_uploaded_file($_FILES['about_sub2_photo']['tmp_name'], __DIR__ . "/../" . $new_path))
                $about_sub2 = $new_path;
        }

        // Handle Dev Photo
        $dev_image = $_POST['existing_dev_photo'] ?? '';
        if (isset($_FILES['dev_photo']) && $_FILES['dev_photo']['error'] == 0) {
            $new_path = "uploads/site/dev_" . time() . "." . pathinfo($_FILES['dev_photo']['name'], PATHINFO_EXTENSION);
            if (!is_dir(__DIR__ . "/../uploads/site/"))
                mkdir(__DIR__ . "/../uploads/site/", 0777, true);
            if (move_uploaded_file($_FILES['dev_photo']['tmp_name'], __DIR__ . "/../" . $new_path))
                $dev_image = $new_path;
        }
        // Handle Hero Video
        $hero_video = $_POST['existing_hero_video'] ?? '';
        if (isset($_FILES['hero_video_photo']) && $_FILES['hero_video_photo']['error'] == 0) {
            $new_path = "uploads/site/video_" . time() . "." . pathinfo($_FILES['hero_video_photo']['name'], PATHINFO_EXTENSION);
            if (!is_dir(__DIR__ . "/../uploads/site/"))
                mkdir(__DIR__ . "/../uploads/site/", 0777, true);
            if (move_uploaded_file($_FILES['hero_video_photo']['tmp_name'], __DIR__ . "/../" . $new_path))
                $hero_video = $new_path;
        } elseif (!empty($_POST['hero_video_url'])) {
            $hero_video = $_POST['hero_video_url'];
        }

        // Handle Hero Audio
        $hero_audio = $_POST['existing_hero_audio'] ?? '';
        if (isset($_FILES['hero_audio_photo']) && $_FILES['hero_audio_photo']['error'] == 0) {
            $new_path = "uploads/site/audio_" . time() . "." . pathinfo($_FILES['hero_audio_photo']['name'], PATHINFO_EXTENSION);
            if (!is_dir(__DIR__ . "/../uploads/site/"))
                mkdir(__DIR__ . "/../uploads/site/", 0777, true);
            if (move_uploaded_file($_FILES['hero_audio_photo']['tmp_name'], __DIR__ . "/../" . $new_path))
                $hero_audio = $new_path;
        }

        // Handle Hero 2 Image
        $hero2_image = $_POST['existing_hero2_image'] ?? '';
        if (isset($_FILES['hero2_photo']) && $_FILES['hero2_photo']['error'] == 0) {
            $new_path = "uploads/site/hero2_" . time() . "." . pathinfo($_FILES['hero2_photo']['name'], PATHINFO_EXTENSION);
            if (!is_dir(__DIR__ . "/../uploads/site/"))
                mkdir(__DIR__ . "/../uploads/site/", 0777, true);
            if (move_uploaded_file($_FILES['hero2_photo']['tmp_name'], __DIR__ . "/../" . $new_path))
                $hero2_image = $new_path;
        }

        // Handle Hero 3 Image
        $hero3_image = $_POST['existing_hero3_image'] ?? '';
        if (isset($_FILES['hero3_photo']) && $_FILES['hero3_photo']['error'] == 0) {
            $new_path = "uploads/site/hero3_" . time() . "." . pathinfo($_FILES['hero3_photo']['name'], PATHINFO_EXTENSION);
            if (!is_dir(__DIR__ . "/../uploads/site/"))
                mkdir(__DIR__ . "/../uploads/site/", 0777, true);
            if (move_uploaded_file($_FILES['hero3_photo']['tmp_name'], __DIR__ . "/../" . $new_path))
                $hero3_image = $new_path;
        }

        // Handle QR Code
        $qr_image = $_POST['qr_code_image'] ?? '';
        if (isset($_FILES['qr_code_file']) && $_FILES['qr_code_file']['error'] == 0) {
            $new_path = "uploads/site/qr_" . time() . "." . pathinfo($_FILES['qr_code_file']['name'], PATHINFO_EXTENSION);
            if (!is_dir(__DIR__ . "/../uploads/site/"))
                mkdir(__DIR__ . "/../uploads/site/", 0777, true);
            if (move_uploaded_file($_FILES['qr_code_file']['tmp_name'], __DIR__ . "/../" . $new_path))
                $qr_image = $new_path;
        }
        // Handle Footer Background Image (The illustration)
        $footer_bg = $_POST['existing_footer_bg'] ?? $c['footer_bg_image'] ?? '';
        if (isset($_FILES['footer_bg_image']) && $_FILES['footer_bg_image']['error'] == 0) {
            $new_path = "uploads/site/footer_" . time() . "." . pathinfo($_FILES['footer_bg_image']['name'], PATHINFO_EXTENSION);
            if (!is_dir(__DIR__ . "/../uploads/site/")) mkdir(__DIR__ . "/../uploads/site/", 0777, true);
            if (move_uploaded_file($_FILES['footer_bg_image']['tmp_name'], __DIR__ . "/../" . $new_path)) $footer_bg = $new_path;
        } elseif (!empty($_POST['footer_bg_image_url'])) {
            $footer_bg = $_POST['footer_bg_image_url'];
        }

        // Handle Delivery Background
        $delivery_bg = $_POST['existing_delivery_bg'] ?? $c['delivery_image'] ?? '';
        if (isset($_FILES['delivery_image']) && $_FILES['delivery_image']['error'] == 0) {
            $new_path = "uploads/site/delivery_bg_" . time() . "." . pathinfo($_FILES['delivery_image']['name'], PATHINFO_EXTENSION);
            if (!is_dir(__DIR__ . "/../uploads/site/")) mkdir(__DIR__ . "/../uploads/site/", 0777, true);
            if (move_uploaded_file($_FILES['delivery_image']['tmp_name'], __DIR__ . "/../" . $new_path)) $delivery_bg = $new_path;
        } elseif (!empty($_POST['delivery_image_url'])) {
            $delivery_bg = $_POST['delivery_image_url'];
        }

        // Handle Delivery Rider
        $delivery_rider = $_POST['existing_delivery_rider'] ?? $c['delivery_rider_image'] ?? '';
        if (isset($_FILES['delivery_rider_image']) && $_FILES['delivery_rider_image']['error'] == 0) {
            $new_path = "uploads/site/rider_" . time() . "." . pathinfo($_FILES['delivery_rider_image']['name'], PATHINFO_EXTENSION);
            if (!is_dir(__DIR__ . "/../uploads/site/")) mkdir(__DIR__ . "/../uploads/site/", 0777, true);
            if (move_uploaded_file($_FILES['delivery_rider_image']['tmp_name'], __DIR__ . "/../" . $new_path)) $delivery_rider = $new_path;
        } elseif (!empty($_POST['delivery_rider_image_url'])) {
            $delivery_rider = $_POST['delivery_rider_image_url'];
        }

        // Schema Healing: Ensure newly added columns exist before updating
        $new_cols = [
            'google_maps_url', 'google_rating', 'google_rating_count',
            'tiktok', 'linkedin', 'telegram', 'whatsapp', 'facebook', 'instagram', 'twitter',
            'ceo_name', 'ceo_title', 'ceo_message', 'ceo_image',
            'hero_video', 'hero_audio',
            'hero2_title', 'hero2_subtitle', 'hero2_button_text', 'hero2_image',
            'hero3_title', 'hero3_subtitle', 'hero3_button_text', 'hero3_image',
            'history_title', 'history_text1', 'history_text2',
            'dev_whatsapp', 'dev_telegram', 'dev_linkedin',
            'bank_name', 'account_name', 'account_number', 'qr_code_image',
            'footer_text', 'opening_hours_1', 'opening_hours_2', 'opening_hours_3',
            'footer_bg_image', 'delivery_rider_image', 'delivery_title', 'delivery_text', 'delivery_image'
        ];
        foreach ($new_cols as $c) {
            try { $pdo->exec("ALTER TABLE company_info ADD COLUMN `$c` TEXT"); } catch (Exception $e) {}
        }

        $stmt = $pdo->prepare("UPDATE company_info SET 
            company_name=?, email=?, phone=?, address=?, 
            google_maps_url=?, google_rating=?, google_rating_count=?,
            about_text=?, 
            facebook=?, instagram=?, twitter=?, tiktok=?, linkedin=?, telegram=?, whatsapp=?, 
            ceo_name=?, ceo_title=?, ceo_message=?, ceo_image=?, 
            hero_title=?, hero_subtitle=?, hero_button_text=?, hero_image=?, 
            hero_video=?, hero_audio=?,
            hero2_title=?, hero2_subtitle=?, hero2_button_text=?, hero2_image=?,
            hero3_title=?, hero3_subtitle=?, hero3_button_text=?, hero3_image=?,
            about_subtitle=?, about_image_main=?, about_image_sub1=?, about_image_sub2=?,
            history_title=?, history_text1=?, history_text2=?,
            dev_name=?, dev_email=?, dev_phone=?, dev_photo=?, 
            dev_whatsapp=?, dev_telegram=?, dev_linkedin=?,
            bank_name=?, account_name=?, account_number=?, qr_code_image=?,
            copyright_text=?, footer_text=?, opening_hours_1=?, opening_hours_2=?, opening_hours_3=?,
            footer_bg_image=?, delivery_rider_image=?, delivery_title=?, delivery_text=?, delivery_image=?
            WHERE id=1");

        $stmt->execute([
            $_POST['company_name'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['address'],
            $_POST['google_maps_url'] ?? '',
            $_POST['google_rating'] ?? 4.5,
            $_POST['google_rating_count'] ?? 100,
            $_POST['about_text'],
            $_POST['facebook'],
            $_POST['instagram'],
            $_POST['twitter'] ?? '',
            $_POST['tiktok'] ?? '',
            $_POST['linkedin'] ?? '',
            $_POST['telegram'] ?? '',
            $_POST['whatsapp'] ?? '',
            $_POST['ceo_name'] ?? '',
            $_POST['ceo_title'] ?? '',
            $_POST['ceo_message'] ?? '',
            $ceo_image,
            $_POST['hero_title'] ?? '',
            $_POST['hero_subtitle'] ?? '',
            $_POST['hero_button_text'] ?? '',
            $hero_image,
            $hero_video,
            $hero_audio,
            $_POST['hero2_title'] ?? '',
            $_POST['hero2_subtitle'] ?? '',
            $_POST['hero2_button_text'] ?? '',
            $hero2_image,
            $_POST['hero3_title'] ?? '',
            $_POST['hero3_subtitle'] ?? '',
            $_POST['hero3_button_text'] ?? '',
            $hero3_image,
            $_POST['about_subtitle'] ?? '',
            $about_main,
            $about_sub1,
            $about_sub2,
            $_POST['history_title'] ?? '',
            $_POST['history_text1'] ?? '',
            $_POST['history_text2'] ?? '',
            $_POST['dev_name'] ?? '',
            $_POST['dev_email'] ?? '',
            $_POST['dev_phone'] ?? '',
            $dev_image,
            $_POST['dev_whatsapp'] ?? '',
            $_POST['dev_telegram'] ?? '',
            $_POST['dev_linkedin'] ?? '',
            $_POST['bank_name'] ?? '',
            $_POST['account_name'] ?? '',
            $_POST['account_number'] ?? '',
            $qr_image,
            $_POST['copyright_text'] ?? '',
            $_POST['footer_text'] ?? '',
            $_POST['opening_hours_1'] ?? '',
            $_POST['opening_hours_2'] ?? '',
            $_POST['opening_hours_3'] ?? '',
            $footer_bg,
            $delivery_rider,
            $_POST['delivery_title'] ?? '',
            $_POST['delivery_text'] ?? '',
            $delivery_bg
        ]);
        logActivity($pdo, "Updated full website company information");
        $_SESSION['success_msg'] = "Website information updated successfully!";
        header("Location: admin.php?tab=company");
        exit;
    } elseif (isset($_POST['update_construction_info'])) {
        // Handle Review Image
        $review_image = $_POST['existing_review_image'] ?? '';
        if (isset($_FILES['review_photo']) && $_FILES['review_photo']['error'] == 0) {
            $new_path = "uploads/const/review_" . time() . "." . pathinfo($_FILES['review_photo']['name'], PATHINFO_EXTENSION);
            if (!is_dir(__DIR__ . "/../uploads/const/"))
                mkdir(__DIR__ . "/../uploads/const/", 0777, true);
            if (move_uploaded_file($_FILES['review_photo']['tmp_name'], __DIR__ . "/../" . $new_path))
                $review_image = $new_path;
        }

        // Handle Hero Image
        $hero_image = $_POST['existing_hero_image'] ?? '';
        if (isset($_FILES['hero_photo']) && $_FILES['hero_photo']['error'] == 0) {
            $new_path = "uploads/const/hero_" . time() . "." . pathinfo($_FILES['hero_photo']['name'], PATHINFO_EXTENSION);
            if (!is_dir(__DIR__ . "/../uploads/const/"))
                mkdir(__DIR__ . "/../uploads/const/", 0777, true);
            if (move_uploaded_file($_FILES['hero_photo']['tmp_name'], __DIR__ . "/../" . $new_path))
                $hero_image = $new_path;
        }

        // Handle Hero Video
        $hero_video = $_POST['existing_hero_video'] ?? '';
        if (isset($_FILES['hero_video_photo']) && $_FILES['hero_video_photo']['error'] == 0) {
            $new_path = "uploads/const/video_" . time() . "." . pathinfo($_FILES['hero_video_photo']['name'], PATHINFO_EXTENSION);
            if (!is_dir(__DIR__ . "/../uploads/const/"))
                mkdir(__DIR__ . "/../uploads/const/", 0777, true);
            if (move_uploaded_file($_FILES['hero_video_photo']['tmp_name'], __DIR__ . "/../" . $new_path))
                $hero_video = $new_path;
        } elseif (!empty($_POST['hero_video_url'])) {
            $hero_video = $_POST['hero_video_url'];
        }

        $stmt = $pdo->prepare("UPDATE construction_info SET 
            company_name=?, hero_title=?, hero_subtitle=?, hero_description=?, hero_image=?, hero_video=?,
            why_choose_us_title=?, why_choose_us_subtitle=?, services_title=?, services_subtitle=?,
            projects_title=?, projects_subtitle=?, reviews_title=?, reviews_subtitle=?, quote_title=?, quote_subtitle=?,
            email=?, phone=?, address=?, 
            ome_page_url=?, blog_url=?, portfolio_url=?, 
            why_choose_us_msg=?, services_desc=?, review_image=?, review_text=?, 
            facebook=?, twitter=?, linkedin=?, google_plus=?, youtube=?, instagram=? 
            WHERE id=1");

        $stmt->execute([
            $_POST['company_name'],
            $_POST['hero_title'],
            $_POST['hero_subtitle'],
            $_POST['hero_description'],
            $hero_image,
            $hero_video,
            $_POST['why_choose_us_title'],
            $_POST['why_choose_us_subtitle'],
            $_POST['services_title'],
            $_POST['services_subtitle'],
            $_POST['projects_title'],
            $_POST['projects_subtitle'],
            $_POST['reviews_title'],
            $_POST['reviews_subtitle'],
            $_POST['quote_title'],
            $_POST['quote_subtitle'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['address'],
            $_POST['ome_page_url'],
            $_POST['blog_url'],
            $_POST['portfolio_url'],
            $_POST['why_choose_us_msg'],
            $_POST['services_desc'],
            $review_image,
            $_POST['review_text'],
            $_POST['facebook'],
            $_POST['twitter'],
            $_POST['linkedin'],
            $_POST['google_plus'],
            $_POST['youtube'],
            $_POST['instagram']
        ]);
        logActivity($pdo, "Updated construction portal info");
        $msg = "Construction portal info updated successfully!";
    } elseif (isset($_POST['save_const_project'])) {
        $title = $_POST['title'] ?? $_POST['name'];
        $desc = $_POST['description'];
        $status = $_POST['status'];
        $start = $_POST['start_date'];
        $completion = $_POST['completion_date'];
        $id = $_POST['id'] ?? '';
        $image_url = $_POST['existing_image'] ?? '';

        if (isset($_FILES['project_photo']) && $_FILES['project_photo']['error'] == 0) {
            $new_path = "uploads/projects/proj_" . time() . "." . pathinfo($_FILES['project_photo']['name'], PATHINFO_EXTENSION);
            if (!is_dir(__DIR__ . "/../uploads/projects/"))
                mkdir(__DIR__ . "/../uploads/projects/", 0777, true);
            if (move_uploaded_file($_FILES['project_photo']['tmp_name'], __DIR__ . "/../" . $new_path))
                $image_url = $new_path;
        }
        if ($id) {
            $stmt = $pdo->prepare("UPDATE construction_projects SET title=?, description=?, status=?, start_date=?, completion_date=?, image_url=? WHERE id=?");
            $stmt->execute([$title, $desc, $status, $start, $completion, $image_url, $id]);
            logActivity($pdo, "Updated construction project: " . $title);
        } else {
            $stmt = $pdo->prepare("INSERT INTO construction_projects (title, description, status, start_date, completion_date, image_url) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $desc, $status, $start, $completion, $image_url]);
            logActivity($pdo, "Added new construction project: " . $title);
        }
        $msg = "Project saved successfully!";
    } elseif (isset($_POST['delete_const_project'])) {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM construction_projects WHERE id=?")->execute([$id]);
        logActivity($pdo, "Removed construction project ID: " . $id);
        $msg = "Project removed.";
    } elseif (isset($_POST['save_const_feature'])) {
        $title = $_POST['title'];
        $desc = $_POST['description'];
        $icon = $_POST['icon_class'];
        $id = $_POST['id'] ?? '';

        if ($id) {
            $stmt = $pdo->prepare("UPDATE construction_features SET title=?, description=?, icon_class=? WHERE id=?");
            $stmt->execute([$title, $desc, $icon, $id]);
            logActivity($pdo, "Updated construction highlight: " . $title);
        } else {
            $stmt = $pdo->prepare("INSERT INTO construction_features (title, description, icon_class) VALUES (?, ?, ?)");
            $stmt->execute([$title, $desc, $icon]);
            logActivity($pdo, "Added new construction highlight: " . $title);
        }
        $msg = "Highlight saved successfully!";
    } elseif (isset($_POST['delete_const_feature'])) {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM construction_features WHERE id=?")->execute([$id]);
        logActivity($pdo, "Removed construction highlight ID: " . $id);
        $msg = "Highlight removed.";
    } elseif (isset($_POST['save_const_service'])) {
        $title = $_POST['title'];
        $desc = $_POST['description'];
        $btn_text = $_POST['button_text'];
        $btn_url = $_POST['button_url'];
        $id = $_POST['id'] ?? '';
        $image_url = $_POST['existing_image'] ?? '';

        if (isset($_FILES['service_photo']) && $_FILES['service_photo']['error'] == 0) {
            $new_path = "uploads/const/serv_" . time() . "." . pathinfo($_FILES['service_photo']['name'], PATHINFO_EXTENSION);
            if (!is_dir(__DIR__ . "/../uploads/const/"))
                mkdir(__DIR__ . "/../uploads/const/", 0777, true);
            if (move_uploaded_file($_FILES['service_photo']['tmp_name'], __DIR__ . "/../" . $new_path))
                $image_url = $new_path;
        }

        if ($id) {
            $stmt = $pdo->prepare("UPDATE `construction_services` SET `title`=?, `description`=?, `image_url`=?, `button_text`=?, `button_url`=? WHERE `id`=?");
            $stmt->execute([$title, $desc, $image_url, $btn_text, $btn_url, $id]);
            logActivity($pdo, "Updated construction service: " . $title);
        } else {
            $stmt = $pdo->prepare("INSERT INTO `construction_services` (`title`, `description`, `image_url`, `button_text`, `button_url`) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $desc, $image_url, $btn_text, $btn_url]);
            logActivity($pdo, "Added new construction service: " . $title);
        }
        $msg = "Service saved successfully!";
    } elseif (isset($_POST['delete_const_service'])) {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM construction_services WHERE id=?")->execute([$id]);
        logActivity($pdo, "Removed construction service ID: " . $id);
        $msg = "Service removed.";
    } elseif (isset($_POST['save_const_testimonial'])) {
        $name = $_POST['client_name'];
        $role = $_POST['client_role'];
        $msg_text = $_POST['message'];
        $rating = $_POST['rating'] ?? 5;
        $id = $_POST['id'] ?? '';
        $image_url = $_POST['existing_image'] ?? '';

        if (isset($_FILES['testimonial_photo']) && $_FILES['testimonial_photo']['error'] == 0) {
            $new_path = "uploads/const/testi_" . time() . "." . pathinfo($_FILES['testimonial_photo']['name'], PATHINFO_EXTENSION);
            if (!is_dir(__DIR__ . "/../uploads/const/"))
                mkdir(__DIR__ . "/../uploads/const/", 0777, true);
            if (move_uploaded_file($_FILES['testimonial_photo']['tmp_name'], __DIR__ . "/../" . $new_path))
                $image_url = $new_path;
        }

        if ($id) {
            $stmt = $pdo->prepare("UPDATE construction_testimonials SET client_name=?, client_role=?, message=?, image_url=?, rating=? WHERE id=?");
            $stmt->execute([$name, $role, $msg_text, $image_url, $rating, $id]);
            logActivity($pdo, "Updated construction testimonial from: " . $name);
        } else {
            $stmt = $pdo->prepare("INSERT INTO construction_testimonials (client_name, client_role, message, image_url, rating) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $role, $msg_text, $image_url, $rating]);
            logActivity($pdo, "Added new construction testimonial from: " . $name);
        }
        $msg = "Testimonial saved successfully!";
    } elseif (isset($_POST['delete_const_testimonial'])) {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM construction_testimonials WHERE id=?")->execute([$id]);
        logActivity($pdo, "Removed construction testimonial ID: " . $id);
        $msg = "Testimonial removed.";
    } elseif (isset($_POST['save_const_equipment'])) {
        $name = $_POST['name'];
        $serial = $_POST['serial_number'];
        $desc = $_POST['description'];
        $status = $_POST['status'];
        $id = $_POST['id'] ?? '';
        $image_url = $_POST['existing_image'] ?? '';

        if (isset($_FILES['equipment_photo']) && $_FILES['equipment_photo']['error'] == 0) {
            $new_path = "uploads/equipment/equip_" . time() . "." . pathinfo($_FILES['equipment_photo']['name'], PATHINFO_EXTENSION);
            if (!is_dir(__DIR__ . "/../uploads/equipment/"))
                mkdir(__DIR__ . "/../uploads/equipment/", 0777, true);
            if (move_uploaded_file($_FILES['equipment_photo']['tmp_name'], __DIR__ . "/../" . $new_path))
                $image_url = $new_path;
        }

        if ($id) {
            $stmt = $pdo->prepare("UPDATE construction_equipment SET name=?, serial_number=?, description=?, status=?, image_url=? WHERE id=?");
            $stmt->execute([$name, $serial, $desc, $status, $image_url, $id]);
            logActivity($pdo, "Updated equipment: " . $name);
        } else {
            $stmt = $pdo->prepare("INSERT INTO construction_equipment (name, serial_number, description, status, image_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $serial, $desc, $status, $image_url]);
            logActivity($pdo, "Added new equipment: " . $name);
        }
        $msg = "Equipment details saved!";
    } elseif (isset($_POST['delete_const_equipment'])) {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM construction_equipment WHERE id=?")->execute([$id]);
        logActivity($pdo, "Removed equipment ID: " . $id);
        $msg = "Equipment removed from inventory.";
    } elseif (isset($_POST['update_const_quote'])) {
        $id = $_POST['id'];
        $status = $_POST['status'];
        $pdo->prepare("UPDATE construction_quotes SET status=? WHERE id=?")->execute([$status, $id]);
        logActivity($pdo, "Updated construction quote ID " . $id . " to " . $status);
        $msg = "Quote status updated.";
    } elseif (isset($_POST['update_application_status'])) {
        $status = $_POST['status'];
        $id = $_POST['id'];
        $pdo->prepare("UPDATE job_applications SET status=? WHERE id=?")->execute([$status, $id]);
        if ($status === 'Accepted') {
            // Check if already in staff to avoid duplicates
            $check = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE email = (SELECT email FROM job_applications WHERE id = ?)");
            $check->execute([$id]);
            if ($check->fetchColumn() == 0) {
                // Insert into employees
                $stmt = $pdo->prepare("INSERT INTO employees (name, role, email, phone, photo, bio, join_date, hire_date, status) 
                    SELECT a.applicant_name, j.title, a.email, a.phone, a.photo_url, a.cover_letter, CURDATE(), CURDATE(), 'Active' 
                    FROM job_applications a 
                    JOIN jobs j ON a.job_id = j.id 
                    WHERE a.id = ?");
                $stmt->execute([$id]);

                $emp_id = $pdo->lastInsertId();
                $id_number = 'BA-' . str_pad($emp_id, 3, '0', STR_PAD_LEFT);
                $pdo->prepare("UPDATE employees SET id_number = ? WHERE id = ?")->execute([$id_number, $emp_id]);

                logActivity($pdo, "Auto-added accepted applicant ID " . $id . " to staff directory ($id_number)");
            }
        }

        logActivity($pdo, "Updated application ID " . $id . " to " . $status);
    } elseif (isset($_POST['delete_application'])) {
        $reason = $_POST['deletion_reason'] ?? "Rejected after review";
        moveToRecycleBin($pdo, 'job_applications', $_POST['id'], $reason);
        logActivity($pdo, "Moved job application ID: " . $_POST['id'] . " to Recycle Bin");
        $msg = "Application moved to Recycle Bin!";
    } elseif (isset($_POST['bulk_status'])) {
        $ids = explode(',', $_POST['bulk_ids']);
        $status = $_POST['bulk_status'];
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $pdo->prepare("UPDATE job_applications SET status=? WHERE id IN ($placeholders)");
        $stmt->execute(array_merge([$status], $ids));

        if ($status === 'Accepted') {
            foreach ($ids as $id) {
                $check = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE email = (SELECT email FROM job_applications WHERE id = ?)");
                $check->execute([$id]);
                if ($check->fetchColumn() == 0) {
                    $stmt = $pdo->prepare("INSERT INTO employees (name, role, email, phone, photo, bio, join_date, hire_date, status) 
                        SELECT a.applicant_name, j.title, a.email, a.phone, a.photo_url, a.cover_letter, CURDATE(), CURDATE(), 'Active' 
                        FROM job_applications a 
                        JOIN jobs j ON a.job_id = j.id 
                        WHERE a.id = ?");
                    $stmt->execute([$id]);

                    $emp_id = $pdo->lastInsertId();
                    $id_number = 'BA-' . str_pad($emp_id, 3, '0', STR_PAD_LEFT);
                    $pdo->prepare("UPDATE employees SET id_number = ? WHERE id = ?")->execute([$id_number, $emp_id]);
                }
            }
            logActivity($pdo, "Auto-added bulk accepted applicants to staff directory");
        }
        logActivity($pdo, "Bulk updated " . count($ids) . " applications to $status");
        $msg = count($ids) . " applications updated to $status!";
    } elseif (isset($_POST['bulk_delete'])) {
        $ids = explode(',', $_POST['bulk_ids']);
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $pdo->prepare("DELETE FROM job_applications WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        logActivity($pdo, "Bulk deleted " . count($ids) . " job applications");
        $msg = count($ids) . " applications permanently removed!";
    } elseif (isset($_POST['add_gallery'])) {
        $category = $_POST['category'];
        $title = $_POST['title'];
        $image_url = "";
        if (isset($_FILES['gallery_photo']) && $_FILES['gallery_photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['gallery_photo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $upload_dir = __DIR__ . "/../uploads/gallery/";
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $new_filename = "gallery_" . time() . "." . $ext;
                $target = $upload_dir . $new_filename;
                if (move_uploaded_file($_FILES['gallery_photo']['tmp_name'], $target)) {
                    $image_url = "uploads/gallery/" . $new_filename;
                }
            }
        }
        $description = $_POST['description'] ?? '';
        if ($image_url) {
            $stmt = $pdo->prepare("INSERT INTO gallery (image_url, category, title, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$image_url, $category, $title, $description]);
            logActivity($pdo, "Added new image to gallery: " . ($title ?: $category));
            $msg = "Gallery image uploaded successfully!";
        } else {
            $msg = "Failed to upload image. Please ensure you selected a file.";
        }
    } elseif (isset($_POST['add_service'])) {
        $title = $_POST['title'];
        $icon = $_POST['icon'] ?? 'fa-concierge-bell';
        $video_url = $_POST['video_url'] ?? '';
        $description = $_POST['description'] ?? '';
        $image_url = "";

        if (isset($_FILES['service_photo']) && $_FILES['service_photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['service_photo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $upload_dir = __DIR__ . "/../uploads/services/";
                if (!is_dir($upload_dir))
                    mkdir($upload_dir, 0777, true);
                $new_filename = "service_" . time() . "." . $ext;
                if (move_uploaded_file($_FILES['service_photo']['tmp_name'], $upload_dir . $new_filename)) {
                    $image_url = "uploads/services/" . $new_filename;
                }
            }
        }

        $category = $_POST['category'] ?? 'Others';

        $stmt = $pdo->prepare("INSERT INTO services (title, icon, video_url, description, image_url, category) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $icon, $video_url, $description, $image_url, $category]);
        logActivity($pdo, "Added new service: " . $title);
        $msg = "Service '$title' added successfully!";
    } elseif (isset($_POST['delete_service'])) {
        $reason = $_POST['deletion_reason'] ?? "Service discontinued";
        moveToRecycleBin($pdo, 'services', $_POST['id'], $reason);
        logActivity($pdo, "Moved service ID: " . $_POST['id'] . " to Recycle Bin");
        $msg = "Service moved to Recycle Bin!";
    } elseif (isset($_POST['update_service'])) {
        $id = $_POST['id'];
        $title = $_POST['title'];
        $icon = $_POST['icon'] ?? 'fa-concierge-bell';
        $category = $_POST['category'] ?? 'Others';
        $status = $_POST['status'] ?? 'Active';
        $video_url = $_POST['video_url'] ?? '';
        $description = $_POST['description'] ?? '';
        $image_url = $_POST['existing_image'] ?? ''; // keep current by default
        // Replace image only if a new file was uploaded
        if (isset($_FILES['service_photo']) && $_FILES['service_photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['service_photo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $upload_dir = __DIR__ . "/../uploads/services/";
                if (!is_dir($upload_dir))
                    mkdir($upload_dir, 0777, true);
                $new_filename = "service_" . time() . "." . $ext;
                if (move_uploaded_file($_FILES['service_photo']['tmp_name'], $upload_dir . $new_filename)) {
                    $image_url = "uploads/services/" . $new_filename;
                }
            }
        }
        $stmt = $pdo->prepare("UPDATE services SET title=?, icon=?, category=?, status=?, video_url=?, description=?, image_url=? WHERE id=?");
        $stmt->execute([$title, $icon, $category, $status, $video_url, $description, $image_url, $id]);
        logActivity($pdo, "Updated service: " . $title);
        $msg = "Service '$title' updated successfully!";
    } elseif (isset($_POST['restore_item'])) {
        $trash_id = $_POST['trash_id'];
        $trash = $pdo->query("SELECT * FROM recycle_bin WHERE id = $trash_id")->fetch(PDO::FETCH_ASSOC);
        if ($trash) {
            $table = $trash['table_name'];
            $record = json_decode($trash['record_data'], true);

            $cols = array_keys($record);
            $cols_str = "`" . implode("`,`", $cols) . "`";
            $placeholders = str_repeat('?,', count($cols) - 1) . '?';

            $stmt = $pdo->prepare("INSERT INTO `$table` ($cols_str) VALUES ($placeholders)");
            $stmt->execute(array_values($record));

            $pdo->prepare("DELETE FROM recycle_bin WHERE id = ?")->execute([$trash_id]);
            logActivity($pdo, "Restored archived item from " . $table);
            $msg = "Data successfully restored to " . $table . "!";
        }
    } elseif (isset($_POST['purge_item'])) {
        $trash_id = $_POST['trash_id'];
        $pdo->prepare("DELETE FROM recycle_bin WHERE id = ?")->execute([$trash_id]);
        logActivity($pdo, "Permanently purged item from Recycle Bin");
        $msg = "Item permanently removed!";
    } elseif (isset($_POST['save_team_member'])) {
        $name = $_POST['name'];
        $role = $_POST['role'];
        $order = $_POST['order_index'] ?? 0;
        $id = $_POST['id'] ?? '';
        $image_url = $_POST['existing_image'] ?? '';

        if (isset($_FILES['member_photo']) && $_FILES['member_photo']['error'] == 0) {
            $new_path = "uploads/team/member_" . time() . "." . pathinfo($_FILES['member_photo']['name'], PATHINFO_EXTENSION);
            if (!is_dir(__DIR__ . "/../uploads/team/"))
                mkdir(__DIR__ . "/../uploads/team/", 0777, true);
            if (move_uploaded_file($_FILES['member_photo']['tmp_name'], __DIR__ . "/../" . $new_path))
                $image_url = $new_path;
        }

        if ($id) {
            $stmt = $pdo->prepare("UPDATE team_members SET name=?, role=?, image_url=?, order_index=? WHERE id=?");
            $stmt->execute([$name, $role, $image_url, $order, $id]);
            logActivity($pdo, "Updated team member: " . $name);
        } else {
            $stmt = $pdo->prepare("INSERT INTO team_members (name, role, image_url, order_index) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $role, $image_url, $order]);
            logActivity($pdo, "Added new team member: " . $name);
        }
        $msg = "Team member details saved!";
    } elseif (isset($_POST['delete_team_member'])) {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM team_members WHERE id=?")->execute([$id]);
        logActivity($pdo, "Removed team member ID: " . $id);
        $msg = "Team member removed from the list.";
    } elseif (isset($_POST['update_user_permissions'])) {
        $user_id = $_POST['user_id'];
        $role = $_POST['role'];
        $perms = isset($_POST['perms']) ? json_encode($_POST['perms']) : '[]';

        $stmt = $pdo->prepare("UPDATE users SET role = ?, permissions = ? WHERE id = ?");
        $stmt->execute([$role, $perms, $user_id]);
        logActivity($pdo, "Updated permissions for User ID: " . $user_id);
        $msg = "User permissions updated successfully!";
    } elseif (isset($_POST['add_admin_user'])) {
        $name = $_POST['full_name'];
        $email = $_POST['email'];
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $perms = isset($_POST['perms']) ? json_encode($_POST['perms']) : '[]';

        try {
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, permissions) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $pass, $role, $perms]);
            logActivity($pdo, "Created new admin user: " . $name);
            $msg = "New user account created successfully!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $msg = "Error: This email address is already registered.";
            } else {
                $msg = "Error: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['delete_admin_user'])) {
        $user_id = $_POST['user_id'];
        if ($user_id != 1) { // Protect primary admin
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
            logActivity($pdo, "Deleted admin user ID: " . $user_id);
            $msg = "User account deleted!";
        } else {
            $msg = "Error: Cannot delete primary administrator.";
        }
    } elseif (isset($_POST['approve_user'])) {
        $user_id = $_POST['user_id'];
        $pdo->prepare("UPDATE users SET status = 'Active' WHERE id = ?")->execute([$user_id]);
        logActivity($pdo, "Approved User account for ID: " . $user_id);
        $msg = "Account approved successfully!";
    } elseif (isset($_POST['disable_user'])) {
        $user_id = $_POST['user_id'];
        $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $curr = $stmt->fetchColumn();
        $new = ($curr === 'Active') ? 'Disabled' : 'Active';
        $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$new, $user_id]);
        logActivity($pdo, "Toggled status for User ID: $user_id to $new");
        $msg = "User status updated to $new!";
    } elseif (isset($_POST['complete_reset'])) {
        $reset_id = $_POST['reset_id'];
        $pdo->prepare("UPDATE password_resets SET status = 'Completed' WHERE id = ?")->execute([$reset_id]);
        logActivity($pdo, "Marked password reset ID $reset_id as completed");
        $msg = "Reset request marked as handled.";
    } elseif (isset($_POST['reset_admin_password'])) {
        $user_id = $_POST['user_id'];
        $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$new_pass, $user_id]);
        
        // Also mark any pending reset for this user's email as completed
        $email = $pdo->query("SELECT email FROM users WHERE id = $user_id")->fetchColumn();
        $pdo->prepare("UPDATE password_resets SET status = 'Completed' WHERE email = ?")->execute([$email]);

        logActivity($pdo, "Reset password for user ID: " . $user_id);
        $msg = "Password reset successfully!";
    } elseif (isset($_POST['update_const_quote'])) {
        $id = $_POST['id'];
        $status = $_POST['status'];
        $admin_reply = $_POST['admin_reply'] ?? '';

        $pdo->prepare("UPDATE construction_quotes SET status=?, admin_reply=? WHERE id=?")->execute([$status, $admin_reply, $id]);
        logActivity($pdo, "Updated construction quote ID $id to $status");
        $msg = "Quote status updated and reply saved!";
    } elseif (isset($_POST['delete_single_activity'])) {
        $id = $_POST['id'];
        logActivity($pdo, "Deleted activity log entry ID: " . $id);
        $pdo->prepare("DELETE FROM activity_logs WHERE id=?")->execute([$id]);
        $msg = "Activity log entry removed!";
    } elseif (isset($_POST['bulk_delete_activities'])) {
        $ids = explode(',', $_POST['activity_bulk_ids']);
        $ids = array_filter($ids, 'is_numeric');
        if (count($ids) > 0) {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            logActivity($pdo, "Bulk deleted " . count($ids) . " activity log entries");
            $stmt = $pdo->prepare("DELETE FROM activity_logs WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $msg = count($ids) . " activity log entries deleted!";
        } else {
            $msg = "No entries selected for deletion.";
        }
    } elseif (isset($_POST['clear_all_activities'])) {
        $count = $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
        $pdo->exec("DELETE FROM activity_logs");
        logActivity($pdo, "Cleared all activity logs ($count entries)");
        $msg = "All $count activity log entries have been cleared!";
    } elseif (isset($_POST['bulk_delete_menu'])) {
        $ids = array_filter(explode(',', $_POST['menu_bulk_ids']), 'is_numeric');
        if (count($ids) > 0) {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $pdo->prepare("DELETE FROM menu_items WHERE id IN ($placeholders)")->execute($ids);
            logActivity($pdo, "Bulk deleted " . count($ids) . " menu items");
            $msg = count($ids) . " menu items deleted!";
        } else {
            $msg = "No items selected.";
        }
    } elseif (isset($_POST['bulk_delete_staff'])) {
        $ids = array_filter(explode(',', $_POST['staff_bulk_ids']), 'is_numeric');
        if (count($ids) > 0) {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $pdo->prepare("DELETE FROM employees WHERE id IN ($placeholders)")->execute($ids);
            logActivity($pdo, "Bulk deleted " . count($ids) . " employees");
            $msg = count($ids) . " employee records deleted!";
        } else {
            $msg = "No employees selected.";
        }
    } elseif (isset($_POST['bulk_delete_jobs'])) {
        $ids = array_filter(explode(',', $_POST['jobs_bulk_ids']), 'is_numeric');
        if (count($ids) > 0) {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $pdo->prepare("DELETE FROM jobs WHERE id IN ($placeholders)")->execute($ids);
            logActivity($pdo, "Bulk deleted " . count($ids) . " job listings");
            $msg = count($ids) . " job listings deleted!";
        } else {
            $msg = "No jobs selected.";
        }
    } elseif (isset($_POST['bulk_delete_gallery'])) {
        $ids = array_filter(explode(',', $_POST['gallery_bulk_ids']), 'is_numeric');
        if (count($ids) > 0) {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $pdo->prepare("DELETE FROM gallery WHERE id IN ($placeholders)")->execute($ids);
            logActivity($pdo, "Bulk deleted " . count($ids) . " gallery images");
            $msg = count($ids) . " gallery images deleted!";
        } else {
            $msg = "No images selected.";
        }
    } elseif (isset($_POST['bulk_delete_services'])) {
        $ids = array_filter(explode(',', $_POST['services_bulk_ids']), 'is_numeric');
        if (count($ids) > 0) {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $pdo->prepare("DELETE FROM services WHERE id IN ($placeholders)")->execute($ids);
            logActivity($pdo, "Bulk deleted " . count($ids) . " services");
            $msg = count($ids) . " services deleted!";
        } else {
            $msg = "No services selected.";
        }
    } elseif (isset($_POST['bulk_delete_team'])) {
        $ids = array_filter(explode(',', $_POST['team_bulk_ids']), 'is_numeric');
        if (count($ids) > 0) {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $pdo->prepare("DELETE FROM team_members WHERE id IN ($placeholders)")->execute($ids);
            logActivity($pdo, "Bulk deleted " . count($ids) . " team members");
            $msg = count($ids) . " team members deleted!";
        }
    } elseif (isset($_POST['bulk_delete_const_equipment'])) {
        $ids = array_filter(explode(',', $_POST['equip_bulk_ids']), 'is_numeric');
        if (count($ids) > 0) {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $pdo->prepare("DELETE FROM construction_equipment WHERE id IN ($placeholders)")->execute($ids);
            logActivity($pdo, "Bulk deleted " . count($ids) . " construction equipment");
            $msg = count($ids) . " equipment items deleted!";
        } else {
            $msg = "No equipment selected.";
        }
    } elseif (isset($_POST['bulk_delete_const_features'])) {
        $ids = array_filter(explode(',', $_POST['cfeatures_bulk_ids']), 'is_numeric');
        if (count($ids) > 0) {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $pdo->prepare("DELETE FROM construction_features WHERE id IN ($placeholders)")->execute($ids);
            logActivity($pdo, "Bulk deleted " . count($ids) . " construction highlights");
            $msg = count($ids) . " highlights deleted!";
        } else {
            $msg = "No highlights selected.";
        }
    } elseif (isset($_POST['bulk_delete_const_projects'])) {
        $ids = array_filter(explode(',', $_POST['cproj_bulk_ids']), 'is_numeric');
        if (count($ids) > 0) {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $pdo->prepare("DELETE FROM construction_projects WHERE id IN ($placeholders)")->execute($ids);
            logActivity($pdo, "Bulk deleted " . count($ids) . " construction projects");
            $msg = count($ids) . " projects deleted!";
        } else {
            $msg = "No projects selected.";
        }
    } elseif (isset($_POST['bulk_delete_const_services'])) {
        $ids = array_filter(explode(',', $_POST['csvc_bulk_ids']), 'is_numeric');
        if (count($ids) > 0) {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $pdo->prepare("DELETE FROM construction_services WHERE id IN ($placeholders)")->execute($ids);
            logActivity($pdo, "Bulk deleted " . count($ids) . " construction services");
            $msg = count($ids) . " construction services deleted!";
        } else {
            $msg = "No services selected.";
        }
    } elseif (isset($_POST['bulk_delete_const_quotes'])) {
        $ids = array_filter(explode(',', $_POST['cquotes_bulk_ids']), 'is_numeric');
        if (count($ids) > 0) {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $pdo->prepare("DELETE FROM construction_quotes WHERE id IN ($placeholders)")->execute($ids);
            logActivity($pdo, "Bulk deleted " . count($ids) . " construction quotes");
            $msg = count($ids) . " quote requests deleted!";
        } else {
            $msg = "No quotes selected.";
        }
    } elseif (isset($_POST['edit_promo'])) {
        $id = $_POST['promo_id'];
        $title = $_POST['title'];
        $desc = $_POST['description'];

        if (isset($_FILES['promo_photo']) && $_FILES['promo_photo']['error'] == 0) {
            $new_path = "uploads/promos/promo_" . time() . "." . pathinfo($_FILES['promo_photo']['name'], PATHINFO_EXTENSION);
            if (!is_dir(__DIR__ . "/../uploads/promos/")) mkdir(__DIR__ . "/../uploads/promos/", 0777, true);
            if (move_uploaded_file($_FILES['promo_photo']['tmp_name'], __DIR__ . "/../" . $new_path)) {
                $image_url = "./" . $new_path;
                $pdo->prepare("UPDATE promo_items SET image_url=? WHERE id=?")->execute([$image_url, $id]);
            }
        }

        $stmt = $pdo->prepare("UPDATE promo_items SET title=?, description=? WHERE id=?");
        $stmt->execute([$title, $desc, $id]);
        logActivity($pdo, "Updated promo: " . $title);
        $msg = "Promo item updated successfully!";
    } elseif (isset($_POST['add_promo'])) {
        $title = $_POST['title'];
        $desc = $_POST['description'];
        $image_url = '';

        if (isset($_FILES['promo_photo']) && $_FILES['promo_photo']['error'] == 0) {
            $new_path = "uploads/promos/promo_" . time() . "." . pathinfo($_FILES['promo_photo']['name'], PATHINFO_EXTENSION);
            if (!is_dir(__DIR__ . "/../uploads/promos/")) mkdir(__DIR__ . "/../uploads/promos/", 0777, true);
            if (move_uploaded_file($_FILES['promo_photo']['tmp_name'], __DIR__ . "/../" . $new_path)) $image_url = "./" . $new_path;
        }

        $stmt = $pdo->prepare("INSERT INTO promo_items (title, description, image_url) VALUES (?, ?, ?)");
        $stmt->execute([$title, $desc, $image_url]);
        logActivity($pdo, "Added new promo: " . $title);
        $msg = "Promo item added successfully!";
    } elseif (isset($_POST['delete_promo'])) {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM promo_items WHERE id=?")->execute([$id]);
        logActivity($pdo, "Removed promo item ID: " . $id);
        $msg = "Promo item removed from website.";
    } elseif (isset($_POST['add_blog'])) {
        $title = $_POST['title'];
        $author = $_POST['author'];
        $content = $_POST['content'];
        $category = $_POST['category'];
        $image_url = $_POST['image_url'] ?? '';
        $created_date = $_POST['created_date'] ?? date('Y-m-d');

        if (isset($_FILES['blog_photo']) && $_FILES['blog_photo']['error'] == 0) {
            $new_path = "uploads/blogs/blog_" . time() . "." . pathinfo($_FILES['blog_photo']['name'], PATHINFO_EXTENSION);
            if (!is_dir(__DIR__ . "/../uploads/blogs/")) mkdir(__DIR__ . "/../uploads/blogs/", 0777, true);
            if (move_uploaded_file($_FILES['blog_photo']['tmp_name'], __DIR__ . "/../" . $new_path)) $image_url = $new_path;
        }

        $stmt = $pdo->prepare("INSERT INTO blogs (title, author, content, category, image_url, created_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $author, $content, $category, $image_url, $created_date]);
        logActivity($pdo, "Published new blog post: " . $title);
        $msg = "Article '$title' published successfully!";
    } elseif (isset($_POST['delete_blog'])) {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM blogs WHERE id=?")->execute([$id]);
        logActivity($pdo, "Removed blog post ID: " . $id);
        $msg = "Article removed from website.";
    } elseif (isset($_POST['edit_gallery'])) {
        $id = $_POST['id'];
        $title = $_POST['title'];
        $category = $_POST['category'];
        $desc = $_POST['description'];
        $image_url = $_POST['existing_image'];

        if (isset($_FILES['gallery_photo']) && $_FILES['gallery_photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['gallery_photo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $upload_dir = __DIR__ . "/../uploads/gallery/";
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $new_filename = "gallery_" . time() . "." . $ext;
                $target = $upload_dir . $new_filename;
                if (move_uploaded_file($_FILES['gallery_photo']['tmp_name'], $target)) {
                    $image_url = "uploads/gallery/" . $new_filename;
                }
            }
        }

        $stmt = $pdo->prepare("UPDATE gallery SET title=?, category=?, description=?, image_url=? WHERE id=?");
        $stmt->execute([$title, $category, $desc, $image_url, $id]);
        logActivity($pdo, "Updated gallery image details: " . $title);
        $msg = "Gallery image details updated!";
    } elseif (isset($_POST['bulk_delete_gallery'])) {
        $ids = array_filter(explode(',', $_POST['gallery_bulk_ids']), 'is_numeric');
        if (!empty($ids)) {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $pdo->prepare("DELETE FROM gallery WHERE id IN ($placeholders)")->execute($ids);
            logActivity($pdo, "Bulk deleted " . count($ids) . " gallery images");
            $msg = "Selected images removed.";
        }
    } elseif (isset($_POST['bulk_delete_admin_users'])) {
        $ids = array_filter(explode(',', $_POST['users_bulk_ids']), 'is_numeric');
        $current_admin_id = $_SESSION['admin_id'] ?? 0;
        // Filter out root (1) and self to be double safe
        $ids = array_filter($ids, function ($id) use ($current_admin_id) {
            return $id != 1 && $id != $current_admin_id;
        });
        if (count($ids) > 0) {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders)")->execute($ids);
            logActivity($pdo, "Bulk deleted " . count($ids) . " admin users");
            $msg = count($ids) . " admin users removed!";
        } else {
            $msg = "No eligible users selected.";
        }
    } elseif (isset($_POST['add_table'])) {
        $name = $_POST['table_name'];
        $desc = $_POST['description'];
        $capacity = $_POST['capacity'];
        $image_url = './assets/images/table_1_mesob.png'; // Default
        if (isset($_FILES['table_image']) && $_FILES['table_image']['error'] == 0) {
            $upload_dir = __DIR__ . "/../assets/images/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = pathinfo($_FILES['table_image']['name'], PATHINFO_EXTENSION);
            $new_name = "table_" . time() . "." . $ext;
            if (move_uploaded_file($_FILES['table_image']['tmp_name'], $upload_dir . $new_name)) {
                $image_url = "./assets/images/$new_name";
            }
        }
        $stmt = $pdo->prepare("INSERT INTO restaurant_tables (table_name, description, image_url, capacity) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $desc, $image_url, $capacity]);
        logActivity($pdo, "Added new restaurant table: $name");
        $msg = "Table created successfully!";
    } elseif (isset($_POST['update_table'])) {
        $id = $_POST['id'];
        $name = $_POST['table_name'];
        $desc = $_POST['description'];
        $capacity = $_POST['capacity'];
        $status = $_POST['status'];
        $image_url = $_POST['existing_image'];
        if (isset($_FILES['table_image']) && $_FILES['table_image']['error'] == 0) {
            $upload_dir = __DIR__ . "/../assets/images/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = pathinfo($_FILES['table_image']['name'], PATHINFO_EXTENSION);
            $new_name = "table_" . time() . "." . $ext;
            if (move_uploaded_file($_FILES['table_image']['tmp_name'], $upload_dir . $new_name)) {
                $image_url = "./assets/images/$new_name";
            }
        }
        $stmt = $pdo->prepare("UPDATE restaurant_tables SET table_name=?, description=?, capacity=?, image_url=?, status=? WHERE id=?");
        $stmt->execute([$name, $desc, $capacity, $image_url, $status, $id]);
        logActivity($pdo, "Updated restaurant table ID $id: $name");
        $msg = "Table updated successfully!";
    } elseif (isset($_POST['delete_table'])) {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM restaurant_tables WHERE id=?")->execute([$id]);
        logActivity($pdo, "Deleted restaurant table ID $id");
        $msg = "Table removed!";
    }

    header("Location: admin.php?tab=" . ($_GET['tab'] ?? 'dashboard') . "&msg=" . urlencode($msg));
    exit;
}
?>