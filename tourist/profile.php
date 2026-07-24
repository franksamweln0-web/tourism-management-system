<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../includes/auth.php';
requireRole('tourist');
global $pdo;

$tourist = getTouristProfile();
if (!$tourist) {
    $stmt = $pdo->prepare("INSERT INTO tourists (user_id, full_name) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['username']]);
    $tourist = getTouristProfile();
}
$userId = $_SESSION['user_id'];
$userInfo = getCurrentUser();
$email = $userInfo['email'] ?? $_SESSION['username'];
$msg = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE tourists SET full_name=?, nationality=?, passport_number=?, contact_number=?, date_of_birth=?, emergency_contact=?, emergency_phone=?, address=? WHERE user_id=?");
    $stmt->execute([
        trim($_POST['full_name'] ?? ''),
        trim($_POST['nationality'] ?? ''),
        trim($_POST['passport_number'] ?? ''),
        trim($_POST['contact_number'] ?? ''),
        $_POST['date_of_birth'] ?? null,
        trim($_POST['emergency_contact'] ?? ''),
        trim($_POST['emergency_phone'] ?? ''),
        trim($_POST['address'] ?? ''),
        $userId
    ]);
    header('Location: profile.php?msg=updated');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - TMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background:linear-gradient(135deg, #f5f7fa 0%, #e9edf5 100%); }
        .profile-card { border-radius:20px; border:none; box-shadow:0 10px 40px rgba(0,0,0,0.08); overflow:hidden; }
        .profile-card .card-header { font-weight:700; font-size:1.1rem; padding:20px 24px; background:white; border-bottom:2px solid #f0f2f5; }
        .profile-card .card-body { padding:24px; }
        .avatar-section { text-align:center; padding:20px 0; }
        .avatar-circle {
            width:100px; height:100px; border-radius:50%;
            background:linear-gradient(135deg, #2d6a4f, #d4a017);
            display:flex; align-items:center; justify-content:center;
            font-size:3rem; margin:0 auto 12px;
            box-shadow:0 8px 25px rgba(45,106,79,0.3);
        }
        .form-label { font-weight:600; font-size:0.85rem; color:#555; }
        .btn-save { border-radius:30px; font-weight:700; padding:12px 40px; }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card profile-card">
                <div class="card-header d-flex align-items-center">
                    <span style="font-size:1.5rem;margin-right:10px;">👤</span> My Profile
                </div>
                <div class="card-body">
                    <?php if ($msg === 'updated'): ?>
                    <div class="alert alert-success">✅ Profile updated successfully!</div>
                    <?php endif; ?>
                    <div class="avatar-section">
                        <div class="avatar-circle"><?= strtoupper(substr($tourist['full_name'] ?? $_SESSION['username'], 0, 1)) ?></div>
                        <h4 class="fw-bold"><?= htmlspecialchars($tourist['full_name'] ?? '') ?></h4>
                        <p class="text-muted"><?= htmlspecialchars($_SESSION['username']) ?></p>
                    </div>
                    <hr>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($tourist['full_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nationality</label>
                                <input type="text" name="nationality" class="form-control" value="<?= htmlspecialchars($tourist['nationality'] ?? '') ?>" placeholder="e.g. Tanzanian">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Passport Number</label>
                                <input type="text" name="passport_number" class="form-control" value="<?= htmlspecialchars($tourist['passport_number'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" class="form-control" value="<?= htmlspecialchars($tourist['contact_number'] ?? '') ?>" placeholder="+255 712 345 678">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="date_of_birth" class="form-control" value="<?= $tourist['date_of_birth'] ?? '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($email) ?>" disabled>
                                <small class="text-muted">Email cannot be changed here</small>
                            </div>
                        </div>
                        <hr>
                        <h6 class="fw-bold mb-3">🚨 Emergency Contact</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Emergency Contact Name</label>
                                <input type="text" name="emergency_contact" class="form-control" value="<?= htmlspecialchars($tourist['emergency_contact'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Emergency Phone</label>
                                <input type="text" name="emergency_phone" class="form-control" value="<?= htmlspecialchars($tourist['emergency_phone'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($tourist['address'] ?? '') ?></textarea>
                        </div>
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-success btn-save">💾 Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
