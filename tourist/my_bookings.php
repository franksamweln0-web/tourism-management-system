<?php
require_once '../includes/auth.php';
requireRole('tourist');

$tourist = getTouristProfile();
$touristId = $tourist['id'];

$bookings = $pdo->prepare("SELECT b.*, p.package_name, p.destination, p.duration_days FROM bookings b JOIN tour_packages p ON b.package_id = p.id WHERE b.tourist_id = ? ORDER BY b.created_at DESC");
$bookings->execute([$touristId]);
$myBookings = $bookings->fetchAll();

$msg = $_GET['msg'] ?? '';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid mt-4">
    <h2>My Bookings</h2>
    <?php if ($msg === 'booked'): ?><div class="alert alert-success">Booking created successfully! Reference will be shown below.</div><?php endif; ?>
    <?php if (empty($myBookings)): ?>
    <div class="alert alert-info">You have no bookings. <a href="packages.php">Browse packages</a></div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead><tr><th>Reference</th><th>Package</th><th>Destination</th><th>Date</th><th>Participants</th><th>Total</th><th>Payment</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($myBookings as $b): ?>
                <tr>
                    <td><?= htmlspecialchars($b['booking_reference']) ?></td>
                    <td><?= htmlspecialchars($b['package_name']) ?></td>
                    <td><?= htmlspecialchars($b['destination']) ?></td>
                    <td><?= $b['booking_date'] ?></td>
                    <td><?= $b['participants'] ?></td>
                    <td>$<?= number_format($b['total_cost'], 2) ?></td>
                    <td>
                        <span class="badge bg-<?= $b['payment_status'] === 'paid' ? 'success' : ($b['payment_status'] === 'partial' ? 'warning' : 'danger') ?>"><?= $b['payment_status'] ?></span>
                    </td>
                    <td>
                        <span class="badge bg-<?= $b['status'] === 'confirmed' ? 'success' : ($b['status'] === 'pending' ? 'warning' : 'secondary') ?>"><?= $b['status'] ?></span>
                    </td>
                    <td>
                        <a href="my_itinerary.php?booking=<?= $b['id'] ?>" class="btn btn-sm btn-info">Itinerary</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
