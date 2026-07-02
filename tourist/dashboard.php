<?php
require_once '../includes/auth.php';
requireRole('tourist');

$tourist = getTouristProfile();
$touristId = $tourist['id'] ?? 0;

$myBookings = $pdo->prepare("SELECT b.*, p.package_name, p.destination, p.duration_days FROM bookings b JOIN tour_packages p ON b.package_id = p.id WHERE b.tourist_id = ? ORDER BY b.created_at DESC LIMIT 5");
$myBookings->execute([$touristId]);
$bookings = $myBookings->fetchAll();

$notifications = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY sent_at DESC LIMIT 5");
$notifications->execute([$_SESSION['user_id']]);
$notifs = $notifications->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2>Tourist Dashboard</h2>
            <p>Welcome, <?= htmlspecialchars($tourist['full_name'] ?? $_SESSION['username']) ?></p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card text-white bg-primary"><div class="card-body"><h5><?= count($bookings) ?></h5><p>My Bookings</p></div></div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card text-white bg-success"><div class="card-body">
                <a href="packages.php" class="text-white text-decoration-none"><h5>Browse</h5><p>Tour Packages</p></a>
            </div></div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">My Recent Bookings</div>
                <div class="card-body">
                    <?php if (empty($bookings)): ?>
                    <p class="text-muted">No bookings yet. <a href="packages.php">Browse packages</a></p>
                    <?php else: ?>
                    <table class="table table-sm">
                        <thead><tr><th>Reference</th><th>Package</th><th>Destination</th><th>Date</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td><?= htmlspecialchars($b['booking_reference']) ?></td>
                                <td><?= htmlspecialchars($b['package_name']) ?></td>
                                <td><?= htmlspecialchars($b['destination']) ?></td>
                                <td><?= $b['booking_date'] ?></td>
                                <td>
                                    <span class="badge bg-<?= $b['status'] === 'confirmed' ? 'success' : ($b['status'] === 'pending' ? 'warning' : 'danger') ?>"><?= $b['status'] ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <a href="my_bookings.php" class="btn btn-sm btn-primary">View All</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">Notifications</div>
                <div class="card-body">
                    <?php if (empty($notifs)): ?>
                    <p class="text-muted">No notifications.</p>
                    <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($notifs as $n): ?>
                        <li class="list-group-item">
                            <small class="text-muted"><?= $n['sent_at'] ?></small><br>
                            <strong><?= htmlspecialchars($n['subject']) ?></strong>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header">My Profile</div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?= htmlspecialchars($tourist['full_name'] ?? '-') ?></p>
                    <p><strong>Nationality:</strong> <?= htmlspecialchars($tourist['nationality'] ?? '-') ?></p>
                    <p><strong>Passport:</strong> <?= htmlspecialchars($tourist['passport_number'] ?? '-') ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
