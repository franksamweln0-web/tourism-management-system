<?php
require_once '../includes/auth.php';
requireRole('tourist');

$tourist = getTouristProfile();
if (!$tourist) {
    $stmt = $pdo->prepare("INSERT INTO tourists (user_id, full_name) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['username']]);
    $tourist = getTouristProfile();
}
$touristId = $tourist['id'] ?? 0;

$bookings = $pdo->prepare("SELECT b.*, p.package_name, p.destination, p.duration_days FROM bookings b JOIN tour_packages p ON b.package_id = p.id WHERE b.tourist_id = ? ORDER BY b.created_at DESC");
$bookings->execute([$touristId]);
$myBookings = $bookings->fetchAll();

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - TMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background:linear-gradient(135deg, #f5f7fa 0%, #e9edf5 100%); }
        .page-card { border-radius:18px; border:none; box-shadow:0 5px 20px rgba(0,0,0,0.06); overflow:hidden; }
        .page-card .card-header { background:white; border-bottom:2px solid #f0f2f5; font-weight:700; font-size:1.1rem; padding:18px 22px; }
        .page-card .card-body { padding:0; }
        .table-modern th { background:#f8f9fa; font-weight:600; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.5px; color:#666; border:none; padding:12px 14px; }
        .table-modern td { padding:12px 14px; vertical-align:middle; border-color:#f0f2f5; }
        .table-modern tr { transition:background 0.2s; }
        .table-modern tr:hover { background:#f8f9fa; }
        .badge-status { padding:6px 14px; border-radius:20px; font-weight:600; font-size:0.75rem; }
        .btn-pay { border-radius:20px; font-weight:700; padding:6px 18px; }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid px-4 py-4">
    <h2 class="fw-bold mb-4">📋 My Bookings</h2>
    <?php if ($msg === 'booked'): ?><div class="alert alert-success">✅ Booking created successfully!</div><?php endif; ?>
    <?php if ($msg === 'paid'): ?><div class="alert alert-success">💰 Payment received! Thank you.</div><?php endif; ?>
    <?php if ($msg === 'already_paid'): ?><div class="alert alert-info">This booking is already paid.</div><?php endif; ?>
    <?php if (empty($myBookings)): ?>
    <div class="alert alert-info">You have no bookings. <a href="packages.php">Browse packages</a></div>
    <?php else: ?>
    <div class="card page-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>📌 Your Safari Bookings</span>
            <a href="packages.php" class="btn btn-sm btn-outline-success rounded-pill">+ New Booking</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-modern mb-0">
                    <thead><tr><th>Reference</th><th>Package</th><th>Destination</th><th>Date</th><th>Qty</th><th>Total</th><th>Payment</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($myBookings as $b): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($b['booking_reference']) ?></strong></td>
                            <td><?= htmlspecialchars($b['package_name']) ?></td>
                            <td><?= htmlspecialchars($b['destination']) ?></td>
                            <td><?= $b['booking_date'] ?></td>
                            <td><?= $b['participants'] ?></td>
                            <td><strong>$<?= number_format($b['total_cost'], 2) ?></strong></td>
                            <td>
                                <span class="badge badge-status bg-<?= $b['payment_status'] === 'paid' ? 'success' : ($b['payment_status'] === 'partial' ? 'warning text-dark' : 'secondary') ?>">
                                    <?= $b['payment_status'] ?? 'pending' ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-status bg-<?= $b['status'] === 'confirmed' ? 'success' : ($b['status'] === 'pending' ? 'warning text-dark' : 'danger') ?>">
                                    <?= $b['status'] ?>
                                </span>
                            </td>
                            <td>
                                <a href="my_itinerary.php?booking=<?= $b['id'] ?>" class="btn btn-sm btn-outline-info">🗺️</a>
                                <?php if (($b['payment_status'] ?? 'pending') !== 'paid'): ?>
                                <a href="pay.php?booking=<?= $b['id'] ?>" class="btn btn-sm btn-success btn-pay ms-1">💳 Pay Now</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
