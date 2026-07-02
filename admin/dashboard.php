<?php
require_once '../includes/auth.php';
requireRole('admin');

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalPackages = $pdo->query("SELECT COUNT(*) FROM tour_packages")->fetchColumn();
$totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(amount_paid), 0) FROM payments")->fetchColumn();
$recentBookings = $pdo->query("SELECT b.*, t.full_name, p.package_name FROM bookings b JOIN tourists t ON b.tourist_id = t.id JOIN tour_packages p ON b.package_id = p.id ORDER BY b.created_at DESC LIMIT 5")->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-none d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <h6 class="sidebar-heading px-3 text-muted">Admin Panel</h6>
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_users.php"><i class="fas fa-users"></i> Users</a></li>
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
                <h1>Dashboard</h1>
                <span>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title"><?= $totalUsers ?></h5>
                            <p>Total Users</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title"><?= $totalPackages ?></h5>
                            <p>Tour Packages</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title"><?= $totalBookings ?></h5>
                            <p>Total Bookings</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title">$<?= number_format($totalRevenue, 2) ?></h5>
                            <p>Total Revenue</p>
                        </div>
                    </div>
                </div>
            </div>
            <h4 class="mt-4">Recent Bookings</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>Reference</th><th>Tourist</th><th>Package</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentBookings as $b): ?>
                        <tr>
                            <td><?= htmlspecialchars($b['booking_reference']) ?></td>
                            <td><?= htmlspecialchars($b['full_name']) ?></td>
                            <td><?= htmlspecialchars($b['package_name']) ?></td>
                            <td><?= $b['booking_date'] ?></td>
                            <td><span class="badge bg-<?= $b['status'] === 'confirmed' ? 'success' : ($b['status'] === 'pending' ? 'warning' : 'danger') ?>"><?= $b['status'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
