<?php
session_start();
require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Email already registered!";
        } else {
            // Schema Healing for Production DB
            try { $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('Pending', 'Active', 'Disabled') DEFAULT 'Active'"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('Admin', 'Manager', 'Supervisor', 'Waiter') DEFAULT 'Admin'"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE users ADD COLUMN permissions TEXT DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}

            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, status, role) VALUES (?, ?, ?, 'Pending', 'Admin')");
            if ($stmt->execute([$full_name, $email, $hashed])) {
                $success = "Registration successful! Please wait for Admin approval.";
            } else {
                $error = "Registration failed. Try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Sheger Kurt Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff9d2d;
            --primary-dark: #e68a1a;
            --green: #195821;
        }
        * { box-sizing: border-box; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f8faf8; }
        .bg-layer { position: fixed; inset: 0; z-index: 0; background: linear-gradient(160deg, #ffffff 0%, #f0f7f1 40%, #fdf8f3 70%, #f8faf8 100%); }
        .login-wrapper { position: relative; z-index: 10; width: 100%; max-width: 480px; padding: 20px; }
        .login-card { background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(30px); padding: 40px; border-radius: 28px; border: 1px solid rgba(25,88,33,0.08); box-shadow: 0 30px 80px rgba(0,0,0,0.08); position: relative; overflow: hidden; }
        .brand-section { text-align: center; margin-bottom: 30px; }
        .brand-icon { width: 56px; height: 56px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 15px; display: inline-flex; align-items: center; justify-content: center; font-size: 24px; color: #fff; margin-bottom: 15px; }
        .brand-name { font-size: 24px; font-weight: 800; color: #1a1a1a; }
        .brand-name span { color: var(--primary); }
        .input-group { margin-bottom: 18px; }
        .input-group label { display: block; font-size: 11px; font-weight: 600; margin-bottom: 6px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; }
        .input-wrap { position: relative; }
        .input-wrap i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px; }
        .input-wrap input { width: 100%; padding: 12px 16px 12px 48px; background: rgba(0,0,0,0.03); border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 14px; outline: none; transition: 0.3s; }
        .input-wrap input:focus { border-color: var(--primary); background: rgba(255,157,45,0.05); }
        .btn { width: 100%; padding: 14px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: #fff; border: none; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(255,157,45,0.3); }
        .error-msg { background: #fef2f2; color: #dc2626; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 13px; border: 1px solid #fee2e2; }
        .success-msg { background: #f0fdf4; color: #16a34a; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 13px; border: 1px solid #dcfce7; }
    </style>
</head>
<body>
    <div class="bg-layer"></div>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="brand-section">
                <div class="brand-icon"><i class="fa-solid fa-user-plus"></i></div>
                <div class="brand-name">Create <span>Account</span></div>
                <p style="font-size: 13px; color: #64748b; margin-top: 5px;">Register for the Sheger Kurt Admin Portal</p>
            </div>

            <?php if ($error): ?><div class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> <?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="success-msg"><i class="fa-solid fa-circle-check"></i> <?= $success ?></div><?php endif; ?>

            <form method="POST">
                <div class="input-group">
                    <label>Full Name</label>
                    <div class="input-wrap">
                        <input type="text" name="full_name" required placeholder="John Doe">
                        <i class="fa-solid fa-user"></i>
                    </div>
                </div>
                <div class="input-group">
                    <label>Email Address</label>
                    <div class="input-wrap">
                        <input type="email" name="email" required placeholder="john@example.com">
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <div class="input-wrap">
                        <input type="password" name="password" required placeholder="••••••••">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                </div>
                <div class="input-group">
                    <label>Confirm Password</label>
                    <div class="input-wrap">
                        <input type="password" name="confirm_password" required placeholder="••••••••">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                </div>

                <button type="submit" class="btn">Create Request</button>
            </form>

            <div style="text-align: center; margin-top: 25px;">
                <p style="font-size: 13px; color: #64748b;">Already have an account? <a href="login.php" style="color: var(--primary); font-weight: 700; text-decoration: none;">Sign In</a></p>
            </div>
        </div>
    </div>
</body>
</html>
