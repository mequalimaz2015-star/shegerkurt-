<?php
session_start();
require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Schema Healing for Production DB
    try { $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('Pending', 'Active', 'Disabled') DEFAULT 'Active'"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('Admin', 'Manager', 'Supervisor', 'Waiter') DEFAULT 'Admin'"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE users ADD COLUMN permissions TEXT DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if (($user['status'] ?? 'Active') !== 'Active') {
            $error = 'Your account is pending approval. Please contact the administrator.';
        } else {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_name'] = $user['full_name'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['admin_pic'] = $user['profile_pic'];
            $_SESSION['admin_perms'] = json_decode($user['permissions'] ?? '[]', true);
            
            header('Location: admin.php');
            exit;
        }
    } else {
        $error = 'Invalid email or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sheger Kurt Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff9d2d;
            --primary-dark: #e68a1a;
            --green: #195821;
            --green-dark: #0e3514;
        }
        * { box-sizing: border-box; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            background: #f8faf8;
        }

        /* ========== ANIMATED BACKGROUND ========== */
        .bg-layer {
            position: fixed;
            inset: 0;
            z-index: 0;
        }

        /* Base gradient */
        .bg-layer::before {
            content: '';
            position: absolute;
            inset: 0;
            background: 
                radial-gradient(ellipse at 20% 50%, rgba(25, 88, 33, 0.06) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(255, 157, 45, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 60% 80%, rgba(25, 88, 33, 0.05) 0%, transparent 50%),
                linear-gradient(160deg, #ffffff 0%, #f0f7f1 40%, #fdf8f3 70%, #f8faf8 100%);
        }

        /* Animated floating orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.4;
            animation: float-orb 12s ease-in-out infinite;
        }
        .orb-1 {
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(255, 157, 45, 0.18), transparent 70%);
            top: -100px; left: -100px;
            animation-duration: 14s;
        }
        .orb-2 {
            width: 350px; height: 350px;
            background: radial-gradient(circle, rgba(25, 88, 33, 0.15), transparent 70%);
            bottom: -80px; right: -80px;
            animation-duration: 18s;
            animation-delay: -4s;
        }
        .orb-3 {
            width: 250px; height: 250px;
            background: radial-gradient(circle, rgba(255, 157, 45, 0.12), transparent 70%);
            top: 50%; right: 20%;
            animation-duration: 10s;
            animation-delay: -6s;
        }
        .orb-4 {
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(34, 120, 45, 0.1), transparent 70%);
            bottom: 20%; left: 15%;
            animation-duration: 16s;
            animation-delay: -2s;
        }

        @keyframes float-orb {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(30px, -40px) scale(1.1); }
            50% { transform: translate(-20px, 20px) scale(0.95); }
            75% { transform: translate(40px, 30px) scale(1.05); }
        }

        /* Floating food particles */
        .particles {
            position: absolute;
            inset: 0;
            overflow: hidden;
        }
        .particle {
            position: absolute;
            font-size: 24px;
            opacity: 0;
            animation: rise-particle 8s ease-in-out infinite;
        }
        .particle:nth-child(1) { left: 10%; animation-delay: 0s; font-size: 20px; }
        .particle:nth-child(2) { left: 25%; animation-delay: 1.5s; font-size: 28px; }
        .particle:nth-child(3) { left: 40%; animation-delay: 3s; font-size: 18px; }
        .particle:nth-child(4) { left: 55%; animation-delay: 4.5s; font-size: 22px; }
        .particle:nth-child(5) { left: 70%; animation-delay: 2s; font-size: 26px; }
        .particle:nth-child(6) { left: 85%; animation-delay: 5.5s; font-size: 20px; }
        .particle:nth-child(7) { left: 15%; animation-delay: 7s; font-size: 24px; }
        .particle:nth-child(8) { left: 60%; animation-delay: 6s; font-size: 16px; }
        .particle:nth-child(9) { left: 35%; animation-delay: 3.5s; font-size: 30px; }
        .particle:nth-child(10) { left: 80%; animation-delay: 1s; font-size: 22px; }

        @keyframes rise-particle {
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 0.3; }
            90% { opacity: 0.15; }
            100% { transform: translateY(-100px) rotate(360deg); opacity: 0; }
        }

        /* Grid overlay */
        .grid-overlay {
            position: absolute;
            inset: 0;
            background-image: 
                linear-gradient(rgba(25,88,33,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(25,88,33,0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            animation: grid-shift 20s linear infinite;
        }
        @keyframes grid-shift {
            0% { transform: translate(0, 0); }
            100% { transform: translate(60px, 60px); }
        }

        /* ========== LOGIN CARD ========== */
        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 440px;
            padding: 20px;
            animation: card-entrance 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes card-entrance {
            from { opacity: 0; transform: translateY(40px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .login-card {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            padding: 50px 40px;
            border-radius: 28px;
            border: 1px solid rgba(25, 88, 33, 0.08);
            box-shadow: 
                0 30px 80px rgba(0, 0, 0, 0.08),
                0 0 0 1px rgba(255, 157, 45, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.8);
            position: relative;
            overflow: hidden;
        }

        /* Subtle shimmer on the card */
        .login-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(from 0deg, transparent, rgba(255,157,45,0.03), transparent, rgba(25,88,33,0.04), transparent);
            animation: rotate-shimmer 8s linear infinite;
        }
        @keyframes rotate-shimmer {
            100% { transform: rotate(360deg); }
        }

        .login-card > * {
            position: relative;
            z-index: 1;
        }

        /* Brand */
        .brand-section {
            text-align: center;
            margin-bottom: 35px;
        }
        .brand-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #fff;
            margin-bottom: 18px;
            box-shadow: 0 8px 25px rgba(255, 157, 45, 0.3);
            animation: icon-pulse 3s ease-in-out infinite;
        }
        @keyframes icon-pulse {
            0%, 100% { box-shadow: 0 8px 25px rgba(255, 157, 45, 0.3); }
            50% { box-shadow: 0 8px 35px rgba(255, 157, 45, 0.5); }
        }
        .brand-name {
            font-size: 28px;
            font-weight: 800;
            color: #1a1a1a;
            letter-spacing: -0.5px;
        }
        .brand-name span { color: var(--primary); }
        .brand-subtitle {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 6px;
            font-weight: 500;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        /* Input Groups */
        .input-group {
            margin-bottom: 22px;
            text-align: left;
        }
        .input-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 15px;
            transition: color 0.3s;
        }
        .input-wrap input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            background: rgba(0, 0, 0, 0.03);
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            font-size: 15px;
            color: #1e293b;
            transition: all 0.3s ease;
            outline: none;
        }
        .input-wrap input::placeholder {
            color: #94a3b8;
        }
        .input-wrap input:focus {
            border-color: var(--primary);
            background: rgba(255, 157, 45, 0.05);
            box-shadow: 0 0 0 4px rgba(255, 157, 45, 0.08);
        }
        .input-wrap input:focus + i,
        .input-wrap:focus-within i {
            color: var(--primary);
        }

        /* Remember me */
        .options-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
        }
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--primary);
            cursor: pointer;
        }
        .remember-me span {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
        }
        .forgot-link {
            font-size: 13px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        .forgot-link:hover { color: #ffb84d; }

        /* Button */
        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            letter-spacing: 0.5px;
        }
        .btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.15), transparent);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 157, 45, 0.35);
        }
        .btn:hover::before { opacity: 1; }
        .btn:active { transform: translateY(0); }

        /* Error message */
        .error-msg {
            background: rgba(239, 68, 68, 0.08);
            color: #dc2626;
            padding: 13px 18px;
            border-radius: 12px;
            margin-bottom: 22px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(239, 68, 68, 0.15);
            animation: shake 0.4s ease;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-8px); }
            75% { transform: translateX(8px); }
        }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(0,0,0,0.06);
        }
        .login-footer p {
            font-size: 13px;
            color: #94a3b8;
            font-weight: 500;
        }
        .login-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-card { padding: 35px 25px; }
            .brand-name { font-size: 24px; }
        }
    </style>
</head>
<body>

    <!-- Animated Background -->
    <div class="bg-layer">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
        <div class="orb orb-4"></div>
        <div class="grid-overlay"></div>
        <div class="particles">
            <div class="particle">🍖</div>
            <div class="particle">🔥</div>
            <div class="particle">🥩</div>
            <div class="particle">🍽️</div>
            <div class="particle">🌶️</div>
            <div class="particle">🥘</div>
            <div class="particle">✨</div>
            <div class="particle">🍖</div>
            <div class="particle">🔥</div>
            <div class="particle">🥩</div>
        </div>
    </div>

    <!-- Login Card -->
    <div class="login-wrapper">
        <div class="login-card">
            <div class="brand-section">
                <div class="brand-icon">
                    <i class="fa-solid fa-utensils"></i>
                </div>
                <div class="brand-name">Sheger Kurt<span>.</span></div>
                <div class="brand-subtitle">Admin Portal</div>
            </div>

            <?php if ($error): ?>
                <div class="error-msg">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="input-group">
                    <label>Email Address</label>
                    <div class="input-wrap">
                        <input type="text" name="email" required placeholder="admin@shegerkurt.com">
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

                <div class="options-row">
                    <label class="remember-me">
                        <input type="checkbox"> <span>Remember me</span>
                    </label>
                    <a href="forgot.php" class="forgot-link">Forgot password?</a>
                </div>

                <button type="submit" class="btn">
                    <i class="fa-solid fa-right-to-bracket"></i>&nbsp; Sign In
                </button>
            </form>

            <div style="text-align: center; margin-top: 25px;">
                <p style="font-size: 14px; color: #64748b;">Don't have an account? <a href="signup.php" style="color: var(--primary); font-weight: 700; text-decoration: none;">Sign Up</a></p>
            </div>

            <div class="login-footer">
                <p><a href="index.php"><i class="fa-solid fa-arrow-left"></i> Back to Website</a></p>
            </div>
        </div>
    </div>

</body>
</html>
