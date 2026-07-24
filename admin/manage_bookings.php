<?php
require_once '../includes/auth.php';
requireRole('admin');

$msg = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingId = $_POST['booking_id'] ?? 0;

    if (isset($_POST['confirm'])) {
        $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?")->execute([$bookingId]);
        header('Location: manage_bookings.php?msg=confirmed');
        exit();
    }
    if (isset($_POST['reject'])) {
        $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?")->execute([$bookingId]);
        header('Location: manage_bookings.php?msg=cancelled');
        exit();
    }
    if (isset($_POST['mark_paid'])) {
        $ref = 'MAN-' . strtoupper(uniqid());
        $pdo->prepare("UPDATE bookings SET payment_status = 'paid' WHERE id = ?")->execute([$bookingId]);
        $pdo->prepare("INSERT INTO payments (booking_id, amount_paid, payment_method, transaction_reference) SELECT id, total_cost, 'cash', ? FROM bookings WHERE id = ?")->execute([$ref, $bookingId]);
        header('Location: manage_bookings.php?msg=paid');
        exit();
    }
    if (isset($_POST['confirm_bank'])) {
        $pdo->prepare("UPDATE bookings SET payment_status = 'paid' WHERE id = ?")->execute([$bookingId]);
        $pdo->prepare("UPDATE payments SET notes = 'Confirmed by admin' WHERE booking_id = ?")->execute([$bookingId]);
        header('Location: manage_bookings.php?msg=bank_confirmed');
        exit();
    }
}

$bookings = $pdo->query("SELECT b.*, t.full_name, t.user_id as tourist_user_id, p.package_name FROM bookings b JOIN tourists t ON b.tourist_id = t.id JOIN tour_packages p ON b.package_id = p.id ORDER BY b.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - TMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f0f2f5; }
        .page-header { display:flex; justify-content:space-between; align-items:center; padding:20px 0; }
        .page-header h1 { font-weight:800; font-size:1.5rem; }
        .table-modern th { background:#f8f9fa; font-weight:600; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.5px; color:#666; border:none; padding:12px 14px; }
        .table-modern td { padding:12px 14px; vertical-align:middle; border-color:#f0f2f5; }
        .table-modern tr { transition:background 0.2s; }
        .table-modern tr:hover { background:#f8f9fa; }
        .badge-status { padding:6px 14px; border-radius:20px; font-weight:600; font-size:0.75rem; }
        .btn-action { border-radius:20px; font-size:0.8rem; font-weight:600; padding:5px 16px; }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-none d-md-block bg-white sidebar shadow-sm" style="min-height:100vh; border-right:1px solid #eef0f2;">
            <div class="position-sticky pt-4">
                <h6 class="px-3 mb-3" style="font-size:0.75rem; text-transform:uppercase; letter-spacing:1px; color:#999;">Admin Panel</h6>
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_users.php"><i class="fas fa-users me-2"></i> Users</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_packages.php"><i class="fas fa-suitcase me-2"></i> Packages</a></li>
                    <li class="nav-item"><a class="nav-link active" href="manage_bookings.php"><i class="fas fa-book me-2"></i> Bookings</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_payments.php"><i class="fas fa-credit-card me-2"></i> Payments</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_guides.php"><i class="fas fa-user-tie me-2"></i> Guides</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_accommodations.php"><i class="fas fa-hotel me-2"></i> Accommodations</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_itineraries.php"><i class="fas fa-map me-2"></i> Itineraries</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php"><i class="fas fa-chart-bar me-2"></i> Reports</a></li>
                </ul>
            </div>
        </nav>
        <main class="col-md-10 ms-sm-auto px-4 py-4">
            <div class="page-header">
                <h1>📋 Manage Bookings</h1>
            </div>
            <?php if ($msg === 'confirmed'): ?><div class="alert alert-success">✅ Booking confirmed successfully!</div><?php endif; ?>
            <?php if ($msg === 'cancelled'): ?><div class="alert alert-warning">❌ Booking cancelled.</div><?php endif; ?>
            <?php if ($msg === 'paid'): ?><div class="alert alert-success">💰 Payment marked as paid!</div><?php endif; ?>
            <?php if ($msg === 'bank_confirmed'): ?><div class="alert alert-success">🏦 Bank transfer confirmed! Payment recorded.</div><?php endif; ?>
            <div class="card border-0 shadow-sm" style="border-radius:18px;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead><tr><th>Reference</th><th>Tourist</th><th>Package</th><th>Date</th><th>Qty</th><th>Total</th><th>Payment</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($b['booking_reference']) ?></strong></td>
                                    <td><?= htmlspecialchars($b['full_name']) ?></td>
                                    <td><?= htmlspecialchars($b['package_name']) ?></td>
                                    <td><?= $b['booking_date'] ?></td>
                                    <td><?= $b['participants'] ?></td>
                                    <td><strong>$<?= number_format($b['total_cost'], 2) ?></strong></td>
                                    <td>
                                        <span class="badge badge-status bg-<?= 
                                            $b['payment_status'] === 'paid' ? 'success' : 
                                            ($b['payment_status'] === 'pending_bank' ? 'info text-white' : 
                                            ($b['payment_status'] === 'partial' ? 'warning text-dark' : 'secondary')) ?>">
                                            <?= $b['payment_status'] === 'pending_bank' ? 'Bank Transfer' : ($b['payment_status'] ?? 'pending') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-status bg-<?= $b['status'] === 'confirmed' ? 'success' : ($b['status'] === 'pending' ? 'warning text-dark' : 'danger') ?>">
                                            <?= $b['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($b['status'] === 'pending'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                            <button type="submit" name="confirm" class="btn btn-success btn-action btn-sm">✅ Confirm</button>
                                            <button type="submit" name="reject" class="btn btn-danger btn-action btn-sm">❌ Reject</button>
                                        </form>
                                        <?php elseif ($b['payment_status'] === 'pending_bank'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                            <button type="submit" name="confirm_bank" class="btn btn-primary btn-action btn-sm">🏦 Confirm Bank Transfer</button>
                                        </form>
                                        <?php elseif ($b['status'] === 'confirmed' && $b['payment_status'] !== 'paid'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                            <button type="submit" name="mark_paid" class="btn btn-info btn-action btn-sm text-white">💰 Mark Paid</button>
                                        </form>
                                        <?php else: ?>
                                        <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
