<?php
require_once '../includes/auth.php';
requireRole('tourist');

$tourist = getTouristProfile();
$touristId = $tourist['id'];

$search = $_GET['search'] ?? '';
$dest = $_GET['destination'] ?? '';

$sql = "SELECT * FROM tour_packages WHERE status = 'active'";
$params = [];
if ($search) {
    $sql .= " AND (package_name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($dest) {
    $sql .= " AND destination LIKE ?";
    $params[] = "%$dest%";
}
$sql .= " ORDER BY package_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$packages = $stmt->fetchAll();

$destinations = $pdo->query("SELECT DISTINCT destination FROM tour_packages WHERE status = 'active' ORDER BY destination")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_package'])) {
    $packageId = $_POST['package_id'];
    $bookingDate = $_POST['booking_date'];
    $participants = $_POST['participants'];

    $pkgStmt = $pdo->prepare("SELECT * FROM tour_packages WHERE id = ?");
    $pkgStmt->execute([$packageId]);
    $pkg = $pkgStmt->fetch();

    if (!$pkg) {
        $error = 'Package not found.';
    } elseif ($pkg['max_capacity'] < $participants) {
        $error = 'Not enough capacity. Maximum is ' . $pkg['max_capacity'] . ' participants.';
    } else {
        $ref = generateReference();
        $totalCost = $pkg['price'] * $participants;

        $insert = $pdo->prepare("INSERT INTO bookings (booking_reference, tourist_id, package_id, booking_date, participants, total_cost, status) VALUES (?,?,?,?,?,?,'pending')");
        $insert->execute([$ref, $touristId, $packageId, $bookingDate, $participants, $totalCost]);

        logNotification($_SESSION['user_id'], 'booking', 'Booking Confirmed', "Your booking $ref has been created. Total: $$totalCost");

        header('Location: my_bookings.php?msg=booked');
        exit();
    }
}
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid mt-4">
    <h2>Tour Packages</h2>
    <?php if (isset($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-6">
            <input type="text" name="search" class="form-control" placeholder="Search packages..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-4">
            <select name="destination" class="form-control">
                <option value="">All Destinations</option>
                <?php foreach ($destinations as $d): ?>
                <option value="<?= htmlspecialchars($d['destination']) ?>" <?= $dest === $d['destination'] ? 'selected' : '' ?>><?= htmlspecialchars($d['destination']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>
    <div class="row">
        <?php if (empty($packages)): ?>
        <div class="col-12"><p class="text-muted">No packages found.</p></div>
        <?php endif; ?>
        <?php foreach ($packages as $pkg): ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($pkg['package_name']) ?></h5>
                    <h6 class="text-muted"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($pkg['destination']) ?></h6>
                    <p><span class="badge bg-info"><?= $pkg['duration_days'] ?> Days</span></p>
                    <p class="card-text"><?= htmlspecialchars(substr($pkg['description'], 0, 150)) ?>...</p>
                    <p><strong>Inclusions:</strong> <?= htmlspecialchars($pkg['inclusions'] ?? 'N/A') ?></p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-success fs-5">$<?= number_format($pkg['price'], 2) ?></span>
                        <span class="text-muted">Max <?= $pkg['max_capacity'] ?> pax</span>
                    </div>
                    <button class="btn btn-primary w-100 mt-3" data-bs-toggle="modal" data-bs-target="#bookModal<?= $pkg['id'] ?>">Book Now</button>
                </div>
            </div>
        </div>

        <div class="modal fade" id="bookModal<?= $pkg['id'] ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header"><h5>Book: <?= htmlspecialchars($pkg['package_name']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <input type="hidden" name="package_id" value="<?= $pkg['id'] ?>">
                            <p><strong>Price:</strong> $<?= number_format($pkg['price'], 2) ?> per person</p>
                            <div class="mb-3">
                                <label class="form-label">Travel Date</label>
                                <input type="date" name="booking_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Number of Participants</label>
                                <input type="number" name="participants" class="form-control" min="1" max="<?= $pkg['max_capacity'] ?>" value="1" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Estimated Total</label>
                                <input type="text" class="form-control" value="$<?= number_format($pkg['price'], 2) ?>" readonly>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="book_package" class="btn btn-success">Confirm Booking</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
