<?php
require_once '../includes/auth.php';
requireRole('agent');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $nationality = trim($_POST['nationality']);
    $passport = trim($_POST['passport_number']);
    $emergencyContact = trim($_POST['emergency_contact']);
    $emergencyPhone = trim($_POST['emergency_phone']);
    $address = trim($_POST['address']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($fullName) || empty($email) || empty($username) || empty($password)) {
        $error = 'Full name, email, username, and password are required.';
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);
        if ($check->fetch()) {
            $error = 'Username or email already exists.';
        } else {
            try {
                $pdo->beginTransaction();
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, phone, role) VALUES (?, ?, ?, ?, 'tourist')");
                $stmt->execute([$username, $hashed, $email, $phone]);
                $userId = $pdo->lastInsertId();

                $stmt = $pdo->prepare("INSERT INTO tourists (user_id, full_name, nationality, passport_number, emergency_contact, emergency_phone, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $fullName, $nationality, $passport, $emergencyContact, $emergencyPhone, $address]);

                $pdo->commit();
                $success = "Tourist '$fullName' registered successfully!";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Registration failed: ' . $e->getMessage();
            }
        }
    }
}
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid mt-4">
    <h2>Register Tourist</h2>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Nationality</label>
                        <input type="text" name="nationality" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Passport Number</label>
                        <input type="text" name="passport_number" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Emergency Contact</label>
                        <input type="text" name="emergency_contact" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Emergency Phone</label>
                        <input type="text" name="emergency_phone" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>
                <hr>
                <h5>Login Credentials</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Register Tourist</button>
            </form>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
