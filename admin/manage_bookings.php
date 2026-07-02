<?php
require_once '../includes/auth.php';
requireRole('admin');

$bookings = $pdo->query("SELECT b.*, t.full_name, t.user_id as tourist_user_id, p.package_name FROM bookings b JOIN tourists t ON b.tourist_id = t.id JOIN tour_packages p ON b.package_id = p.id ORDER BY b.created_at DESC")->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-none d-md-block bg-light sidebar"><?php include 'sidebar.php'; ?></nav>
        <main class="col-md-10 ms-sm-auto px-4">
            <div class="pt-3 pb-2 mb-3 border-bottom"><h1>Manage Bookings</h1></div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>Reference</th><th>Tourist</th><th>Package</th><th>Date</th><th>Participants</th><th>Total</th><th>Payment</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td><?= htmlspecialchars($b['booking_reference']) ?></td>
                            <td><?= htmlspecialchars($b['full_name']) ?></td>
                            <td><?= htmlspecialchars($b['package_name']) ?></td>
                            <td><?= $b['booking_date'] ?></td>
                            <td><?= $b['participants'] ?></td>
                            <td>$<?= number_format($b['total_cost'], 2) ?></td>
                            <td><span class="badge bg-<?= $b['payment_status'] === 'paid' ? 'success' : ($b['payment_status'] === 'partial' ? 'warning' : 'danger') ?>"><?= $b['payment_status'] ?></span></td>
                            <td><span class="badge bg-<?= $b['status'] === 'confirmed' ? 'success' : ($b['status'] === 'pending' ? 'warning' : 'secondary') ?>"><?= $b['status'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
