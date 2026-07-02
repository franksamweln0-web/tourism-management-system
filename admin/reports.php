<?php
require_once '../includes/auth.php';
requireRole('admin');

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

$stmt = $pdo->prepare("SELECT COUNT(*) as total_bookings, COALESCE(SUM(total_cost),0) as revenue FROM bookings WHERE booking_date BETWEEN ? AND ? AND status != 'cancelled'");
$stmt->execute([$dateFrom, $dateTo]);
$summary = $stmt->fetch();

$popular = $pdo->prepare("SELECT p.package_name, COUNT(b.id) as booking_count, COALESCE(SUM(b.total_cost),0) as revenue FROM bookings b JOIN tour_packages p ON b.package_id = p.id WHERE b.booking_date BETWEEN ? AND ? AND b.status != 'cancelled' GROUP BY p.id ORDER BY booking_count DESC LIMIT 5");
$popular->execute([$dateFrom, $dateTo]);
$popularPackages = $popular->fetchAll();

$revenueReport = $pdo->prepare("SELECT DATE(payment_date) as day, SUM(amount_paid) as total FROM payments WHERE payment_date BETWEEN ? AND ? GROUP BY DAY(payment_date) ORDER BY day");
$revenueReport->execute([$dateFrom, $dateTo]);
$dailyRevenue = $revenueReport->fetchAll();

$touristDemo = $pdo->query("SELECT nationality, COUNT(*) as count FROM tourists WHERE nationality IS NOT NULL AND nationality != '' GROUP BY nationality ORDER BY count DESC LIMIT 5")->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-none d-md-block bg-light sidebar"><?php include 'sidebar.php'; ?></nav>
        <main class="col-md-10 ms-sm-auto px-4">
            <div class="pt-3 pb-2 mb-3 border-bottom">
                <h1>Reports & Analytics</h1>
            </div>
            <form method="GET" class="row g-3 mb-4">
                <div class="col-auto"><label class="form-label">From</label><input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>"></div>
                <div class="col-auto"><label class="form-label">To</label><input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>"></div>
                <div class="col-auto"><label>&nbsp;</label><button type="submit" class="btn btn-primary form-control">Filter</button></div>
            </form>
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">Period Summary</div>
                        <div class="card-body">
                            <p><strong>Total Bookings:</strong> <?= $summary['total_bookings'] ?></p>
                            <p><strong>Total Revenue:</strong> $<?= number_format($summary['revenue'], 2) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">Tourist Demographics</div>
                        <div class="card-body">
                            <?php if ($touristDemo): ?>
                            <ul class="list-group">
                                <?php foreach ($touristDemo as $d): ?>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span><?= htmlspecialchars($d['nationality']) ?></span>
                                    <span class="badge bg-primary"><?= $d['count'] ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php else: ?>
                            <p class="text-muted">No demographic data available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">Popular Packages</div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <thead><tr><th>Package</th><th>Bookings</th><th>Revenue</th></tr></thead>
                                <tbody>
                                    <?php foreach ($popularPackages as $p): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($p['package_name']) ?></td>
                                        <td><?= $p['booking_count'] ?></td>
                                        <td>$<?= number_format($p['revenue'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">Daily Revenue</div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <thead><tr><th>Date</th><th>Revenue</th></tr></thead>
                                <tbody>
                                    <?php foreach ($dailyRevenue as $d): ?>
                                    <tr>
                                        <td><?= $d['day'] ?></td>
                                        <td>$<?= number_format($d['total'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($dailyRevenue)): ?>
                                    <tr><td colspan="2" class="text-muted">No data for this period.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
