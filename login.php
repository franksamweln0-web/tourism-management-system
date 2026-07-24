<?php
require_once 'includes/auth.php';
if (isLoggedIn()) {
    header('Location: ' . $_SESSION['user_role'] . '/dashboard.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['status'] === 'locked') {
                $error = 'Account locked due to too many failed attempts. Contact administrator.';
            } elseif ($user['status'] === 'inactive') {
                $error = 'Account is inactive. Contact administrator.';
            } elseif (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];

                $pdo->prepare("UPDATE users SET login_attempts = 0 WHERE id = ?")->execute([$user['id']]);
                logNotification($user['id'], 'login', 'Successful Login', 'You logged in successfully.');

                header('Location: ' . $user['role'] . '/dashboard.php');
                exit();
            } else {
                $attempts = $user['login_attempts'] + 1;
                if ($attempts >= 5) {
                    $pdo->prepare("UPDATE users SET login_attempts = ?, status = 'locked' WHERE id = ?")->execute([$attempts, $user['id']]);
                    $error = 'Account locked due to too many failed attempts. Contact administrator.';
                } else {
                    $pdo->prepare("UPDATE users SET login_attempts = ? WHERE id = ?")->execute([$attempts, $user['id']]);
                    $error = 'Invalid password. ' . (5 - $attempts) . ' attempts remaining.';
                }
            }
        } else {
            $error = 'User not found.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Tourism Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="TMS Safari">
    <meta name="theme-color" content="#2d6a4f">
    <script>if('serviceWorker'in navigator){window.addEventListener('load',()=>navigator.serviceWorker.register('sw.js'))}</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        @keyframes zoomBg {
            0% { transform: scale(1); }
            100% { transform: scale(1.1); }
        }
        @keyframes floatUp {
            0% { transform: translateY(0) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) rotate(360deg); opacity: 0; }
        }
        @keyframes drift {
            0% { transform: translateX(0); }
            50% { transform: translateX(30px); }
            100% { transform: translateX(0); }
        }
        @keyframes slideUp {
            0% { opacity: 0; transform: translateY(50px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulseSun {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.2); opacity: 0.6; }
        }
        @keyframes fadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: url('https://images.unsplash.com/photo-1516426122078-c23e76319801?q=80&w=1920') no-repeat center center fixed;
            background-size: cover;
            position: relative;
            overflow: hidden;
            animation: zoomBg 20s ease-in-out infinite alternate;
        }
        .login-page::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.4);
        }
        .safari-sun {
            position: absolute;
            top: 8%;
            right: 10%;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, #ffd700, #ff8c00);
            border-radius: 50%;
            box-shadow: 0 0 80px 20px rgba(255, 165, 0, 0.5);
            animation: pulseSun 4s ease-in-out infinite;
            z-index: 1;
        }
        .floating-animal {
            position: absolute;
            font-size: 2.5rem;
            z-index: 1;
            opacity: 0;
            pointer-events: none;
        }
        .floating-animal:nth-child(1) { left: 5%; bottom: -50px; animation: floatUp 15s linear infinite; font-size: 3rem; }
        .floating-animal:nth-child(2) { left: 20%; bottom: -80px; animation: floatUp 18s linear 3s infinite; font-size: 2rem; }
        .floating-animal:nth-child(3) { left: 50%; bottom: -60px; animation: floatUp 20s linear 6s infinite; font-size: 2.8rem; }
        .floating-animal:nth-child(4) { left: 70%; bottom: -70px; animation: floatUp 16s linear 9s infinite; font-size: 2.2rem; }
        .floating-animal:nth-child(5) { left: 85%; bottom: -40px; animation: floatUp 22s linear 12s infinite; font-size: 3.2rem; }
        .floating-animal:nth-child(6) { left: 35%; bottom: -90px; animation: floatUp 14s linear 2s infinite; font-size: 1.8rem; }

        .login-card {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 420px;
            margin: 20px;
            animation: slideUp 1s ease-out;
        }
        .login-card .card {
            backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.92);
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
        }
        .login-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .login-header .globe-icon {
            font-size: 3.5rem;
            color: #e67e22;
            display: inline-block;
            animation: drift 3s ease-in-out infinite;
        }
        .login-header h3 {
            margin-top: 0.5rem;
            font-weight: 800;
            color: #2c3e50;
            animation: fadeIn 1.5s ease-in;
        }
        .login-header p {
            animation: fadeIn 2s ease-in;
        }
        .login-header .tagline {
            color: #e67e22;
            font-style: italic;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        .form-control:focus {
            border-color: #e67e22;
            box-shadow: 0 0 0 0.2rem rgba(230, 126, 34, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #e67e22, #d35400);
            border: none;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(230, 126, 34, 0.4);
        }
        .safari-grass {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 60px;
            z-index: 1;
            pointer-events: none;
        }
        .safari-grass::before {
            content: '🌾🌿🌾🌿🌾🌿🌾🌿🌾🌿🌾🌿🌾🌿🌾🌿🌾🌿🌾🌿🌾🌿🌾🌿🌾🌿🌾🌿🌾🌿🌾🌿🌾🌿🌾🌿🌾🌿🌾🌿🌾🌿🌾🌿🌾';
            font-size: 28px;
            letter-spacing: 5px;
            line-height: 1;
            display: block;
            animation: drift 8s ease-in-out infinite;
        }
        @media (max-width: 768px) {
            .floating-animal, .safari-sun, .safari-grass { display: none; }
        }
    </style>
</head>
<body>
<div class="login-page">
    <div class="safari-sun"></div>
    <div class="floating-animal">🦁</div>
    <div class="floating-animal">🐘</div>
    <div class="floating-animal">🦒</div>
    <div class="floating-animal">🐆</div>
    <div class="floating-animal">🦏</div>
    <div class="floating-animal">🐃</div>
    <div class="safari-grass"></div>

    <div class="login-card">
        <div class="card shadow-lg">
            <div class="card-body p-4">
                <div class="login-header">
                    <i class="fas fa-globe-africa globe-icon"></i>
                    <h3>🐘 Welcome to the Safari 🦁</h3>
                    <p class="text-muted">Sign in to your account</p>
                    <p class="tagline">"Adventure awaits in the wild"</p>
                </div>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Username or Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-login w-100 py-2 text-white fw-bold">🦁 Let's Explore! 🦁</button>
                </form>
                <p class="text-center mt-3 mb-0">
                    Don't have an account? <a href="register.php">Register here</a>
                </p>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
