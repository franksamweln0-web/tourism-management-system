<?php
require_once '../includes/auth.php';
requireRole('agent');

$tourists = $pdo->query("SELECT t.id, t.full_name FROM tourists t JOIN users u ON t.user_id = u.id WHERE u.status = 'active' ORDER BY t.full_name")->fetchAll();
$packages = $pdo->query("SELECT * FROM tour_packages WHERE status = 'active' ORDER BY package_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_booking'])) {
    $touristId = $_POST['tourist_id'];
    $packageId = $_POST['package_id'];
    $bookingDate = $_POST['booking_date'];
    $participants = $_POST['participants'];

    $pkgStmt = $pdo->prepare("SELECT * FROM tour_packages WHERE id = ?");
    $pkgStmt->execute([$packageId]);
    $pkg = $pkgStmt->fetch();

    $ref = generateReference();
    $totalCost = $pkg['price'] * $participants;

    $stmt = $pdo->prepare("INSERT INTO bookings (booking_reference, tourist_id, package_id, booking_date, participants, total_cost, status) VALUES (?,?,?,?,?,?,'pending')");
    $stmt->execute([$ref, $touristId, $packageId, $bookingDate, $participants, $totalCost]);

    $touristStmt = $pdo->prepare("SELECT user_id FROM tourists WHERE id = ?");
    $touristStmt->execute([$touristId]);
    $t = $touristStmt->fetch();
    if ($t) {
        logNotification($t['user_id'], 'booking', 'Booking Created', "Your booking $ref has been created.");
    }

    header('Location: bookings.php?msg=created');
    exit();
}

$msg = $_GET['msg'] ?? '';
$bookings = $pdo->query("SELECT b.*, t.full_name, p.package_name FROM bookings b JOIN tourists t ON b.tourist_id = t.id JOIN tour_packages p ON b.package_id = p.id ORDER BY b.created_at DESC")->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center">
        <h2>Bookings</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bookingModal">+ New Booking</button>
    </div>
    <?php if ($msg === 'created'): ?><div class="alert alert-success mt-2">Booking created successfully!</div><?php endif; ?>
    <div class="table-responsive mt-3">
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
</div>

<div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5>New Booking</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tourist</label>
                        <select name="tourist_id" class="form-control" required>
                            <option value="">Select tourist...</option>
                            <?php foreach ($tourists as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tour Package</label>
                        <select name="package_id" class="form-control" id="pkgSelect" required>
                            <option value="">Select package...</option>
                            <?php foreach ($packages as $p): ?>
                            <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>"><?= htmlspecialchars($p['package_name']) ?> ($<?= $p['price'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Booking Date</label>
                        <input type="date" name="booking_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Participants</label>
                        <input type="number" name="participants" class="form-control" id="participants" min="1" value="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total Cost</label>
                        <input type="text" id="totalCost" class="form-control" readonly value="$0.00">
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" name="create_booking" class="btn btn-primary">Create Booking</button></div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('pkgSelect')?.addEventListener('change', calcTotal);
document.getElementById('participants')?.addEventListener('input', calcTotal);
function calcTotal() {
    const sel = document.getElementById('pkgSelect');
    const price = sel.options[sel.selectedIndex]?.dataset?.price || 0;
    const qty = document.getElementById('participants').value || 1;
    document.getElementById('totalCost').value = '$' + (price * qty).toFixed(2);
}
</script>
<?php include '../includes/footer.php'; ?>
