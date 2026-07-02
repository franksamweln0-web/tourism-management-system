<?php
require_once '../includes/auth.php';
requireRole('agent');

$userId = $_SESSION['user_id'];
$todayBookings = $pdo->prepare("SELECT COUNT(*) FROM bookings b JOIN tourists t ON b.tourist_id = t.id WHERE b.booking_date = CURDATE()");
$todayBookings->execute();
$todayCount = $todayBookings->fetchColumn();
$pendingPayments = $pdo->query("SELECT COUNT(*) FROM bookings WHERE payment_status IN ('pending','partial')")->fetchColumn();
$recentBookings = $pdo->query("SELECT b.*, t.full_name, p.package_name FROM bookings b JOIN tourists t ON b.tourist_id = t.id JOIN tour_packages p ON b.package_id = p.id ORDER BY b.created_at DESC LIMIT 5")->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2>Agent Dashboard</h2>
            <p>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-primary"><div class="card-body"><h5><?= $todayCount ?></h5><p>Today's Bookings</p></div></div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-warning"><div class="card-body"><h5><?= $pendingPayments ?></h5><p>Pending Payments</p></div></div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-success"><div class="card-body">
                <a href="bookings.php" class="text-white text-decoration-none"><h5>Manage</h5><p>Bookings</p></a>
            </div></div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-info"><div class="card-body">
                <a href="register_tourist.php" class="text-white text-decoration-none"><h5>+ New</h5><p>Tourist</p></a>
            </div></div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Quick Actions</div>
                <div class="card-body">
                    <a href="bookings.php?action=new" class="btn btn-primary mb-2 w-100">Create New Booking</a>
                    <a href="register_tourist.php" class="btn btn-success mb-2 w-100">Register Tourist</a>
                    <a href="payments.php" class="btn btn-info mb-2 w-100">Process Payment</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Recent Bookings</div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead><tr><th>Ref</th><th>Tourist</th><th>Package</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($recentBookings as $b): ?>
                            <tr>
                                <td><?= htmlspecialchars($b['booking_reference']) ?></td>
                                <td><?= htmlspecialchars($b['full_name']) ?></td>
                                <td><?= htmlspecialchars($b['package_name']) ?></td>
                                <td><span class="badge bg-<?= $b['status'] === 'confirmed' ? 'success' : 'warning' ?>"><?= $b['status'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
