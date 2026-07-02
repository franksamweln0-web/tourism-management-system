<?php
require_once '../includes/auth.php';
requireRole('agent');

$pendingBookings = $pdo->query("SELECT b.id, b.booking_reference, t.full_name, b.total_cost, b.payment_status FROM bookings b JOIN tourists t ON b.tourist_id = t.id WHERE b.payment_status IN ('pending','partial') ORDER BY b.created_at DESC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment'])) {
    $bookingId = $_POST['booking_id'];
    $amountPaid = $_POST['amount_paid'];
    $method = $_POST['payment_method'];
    $ref = $_POST['transaction_reference'] ?? '';

    $stmt = $pdo->prepare("INSERT INTO payments (booking_id, amount_paid, payment_method, transaction_reference) VALUES (?,?,?,?)");
    $stmt->execute([$bookingId, $amountPaid, $method, $ref]);

    $totalStmt = $pdo->prepare("SELECT total_cost FROM bookings WHERE id = ?");
    $totalStmt->execute([$bookingId]);
    $total = $totalStmt->fetchColumn();

    $paidStmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid),0) FROM payments WHERE booking_id = ?");
    $paidStmt->execute([$bookingId]);
    $totalPaid = $paidStmt->fetchColumn();

    if ($totalPaid >= $total) {
        $pdo->prepare("UPDATE bookings SET payment_status = 'paid', status = 'confirmed' WHERE id = ?")->execute([$bookingId]);
    } else {
        $pdo->prepare("UPDATE bookings SET payment_status = 'partial' WHERE id = ?")->execute([$bookingId]);
    }

    header('Location: payments.php?msg=recorded');
    exit();
}

$msg = $_GET['msg'] ?? '';
$payments = $pdo->query("SELECT pay.*, b.booking_reference, t.full_name FROM payments pay JOIN bookings b ON pay.booking_id = b.id JOIN tourists t ON b.tourist_id = t.id ORDER BY pay.payment_date DESC LIMIT 20")->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center">
        <h2>Payment Processing</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#payModal">+ Record Payment</button>
    </div>
    <?php if ($msg === 'recorded'): ?><div class="alert alert-success mt-2">Payment recorded successfully!</div><?php endif; ?>
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Pending Payments</div>
                <div class="card-body">
                    <?php if (count($pendingBookings) === 0): ?>
                        <p class="text-muted">No pending payments.</p>
                    <?php else: ?>
                    <table class="table table-sm">
                        <thead><tr><th>Reference</th><th>Tourist</th><th>Total</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($pendingBookings as $pb): ?>
                            <tr>
                                <td><?= htmlspecialchars($pb['booking_reference']) ?></td>
                                <td><?= htmlspecialchars($pb['full_name']) ?></td>
                                <td>$<?= number_format($pb['total_cost'], 2) ?></td>
                                <td><span class="badge bg-<?= $pb['payment_status'] === 'partial' ? 'warning' : 'danger' ?>"><?= $pb['payment_status'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="card mt-3">
        <div class="card-header">Recent Payments</div>
        <div class="card-body">
            <table class="table table-sm">
                <thead><tr><th>Booking</th><th>Tourist</th><th>Amount</th><th>Method</th><th>Date</th></tr></thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['booking_reference']) ?></td>
                        <td><?= htmlspecialchars($p['full_name']) ?></td>
                        <td>$<?= number_format($p['amount_paid'], 2) ?></td>
                        <td><?= strtoupper($p['payment_method']) ?></td>
                        <td><?= $p['payment_date'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="payModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5>Record Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Booking</label>
                        <select name="booking_id" class="form-control" required>
                            <option value="">Select booking...</option>
                            <?php foreach ($pendingBookings as $pb): ?>
                            <option value="<?= $pb['id'] ?>"><?= htmlspecialchars($pb['booking_reference']) ?> - <?= htmlspecialchars($pb['full_name']) ?> ($<?= number_format($pb['total_cost'], 2) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount Paid ($)</label>
                        <input type="number" step="0.01" name="amount_paid" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-control" required>
                            <option value="cash">Cash</option>
                            <option value="mpesa">M-Pesa</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="online">Online</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Transaction Reference</label>
                        <input type="text" name="transaction_reference" class="form-control" placeholder="Optional reference number">
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" name="record_payment" class="btn btn-primary">Record Payment</button></div>
            </form>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
