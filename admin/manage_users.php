<?php
require_once '../includes/auth.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $userId = $_POST['user_id'];
    if ($_POST['action'] === 'toggle_status') {
        $stmt = $pdo->prepare("UPDATE users SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
        $stmt->execute([$userId]);
    } elseif ($_POST['action'] === 'reset_attempts') {
        $stmt = $pdo->prepare("UPDATE users SET login_attempts = 0, status = 'active' WHERE id = ?");
        $stmt->execute([$userId]);
    }
    header('Location: manage_users.php');
    exit();
}

$users = $pdo->query("SELECT u.*, COALESCE(t.full_name, g.full_name, '(no profile)') as profile_name FROM users u LEFT JOIN tourists t ON u.id = t.user_id LEFT JOIN guides g ON u.id = g.user_id ORDER BY u.created_at DESC")->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-none d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <h6 class="sidebar-heading px-3 text-muted">Admin Panel</h6>
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="manage_users.php"><i class="fas fa-users"></i> Users</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_packages.php"><i class="fas fa-suitcase"></i> Packages</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_bookings.php"><i class="fas fa-book"></i> Bookings</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_payments.php"><i class="fas fa-credit-card"></i> Payments</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_guides.php"><i class="fas fa-user-tie"></i> Guides</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_accommodations.php"><i class="fas fa-hotel"></i> Accommodations</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_itineraries.php"><i class="fas fa-map"></i> Itineraries</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                </ul>
            </div>
        </nav>
        <main class="col-md-10 ms-sm-auto px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1>User Management</h1>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Attempts</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                            <td><span class="badge bg-secondary"><?= $u['role'] ?></span></td>
                            <td><span class="badge bg-<?= $u['status'] === 'active' ? 'success' : 'danger' ?>"><?= $u['status'] ?></span></td>
                            <td><?= $u['login_attempts'] ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <button type="submit" class="btn btn-sm btn-<?= $u['status'] === 'active' ? 'warning' : 'success' ?>">
                                        <?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                                <?php if ($u['login_attempts'] > 0 || $u['status'] === 'locked'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="action" value="reset_attempts">
                                    <button type="submit" class="btn btn-sm btn-info">Reset</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
