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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: url('assets/img/safari.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
        }
        .login-page::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.5);
        }
        .login-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            margin: 20px;
        }
        .login-card .card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 15px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .login-header i {
            font-size: 3rem;
            color: #007bff;
        }
        .login-header h3 {
            margin-top: 0.5rem;
            font-weight: 700;
            color: #333;
        }
    </style>
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div class="card shadow-lg">
            <div class="card-body p-4">
                <div class="login-header">
                    <i class="fas fa-globe-africa"></i>
                    <h3>Welcome Back</h3>
                    <p class="text-muted">Sign in to your account</p>
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
                    <button type="submit" class="btn btn-primary w-100 py-2">Login</button>
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
