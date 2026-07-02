<?php
require_once '../includes/auth.php';
requireRole('admin');

$payments = $pdo->query("SELECT pay.*, b.booking_reference, t.full_name FROM payments pay JOIN bookings b ON pay.booking_id = b.id JOIN tourists t ON b.tourist_id = t.id ORDER BY pay.payment_date DESC")->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-none d-md-block bg-light sidebar"><?php include 'sidebar.php'; ?></nav>
        <main class="col-md-10 ms-sm-auto px-4">
            <div class="pt-3 pb-2 mb-3 border-bottom"><h1>Payment Records</h1></div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>#</th><th>Booking</th><th>Tourist</th><th>Amount</th><th>Method</th><th>Reference</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td><?= $pay['id'] ?></td>
                            <td><?= htmlspecialchars($pay['booking_reference']) ?></td>
                            <td><?= htmlspecialchars($pay['full_name']) ?></td>
                            <td>$<?= number_format($pay['amount_paid'], 2) ?></td>
                            <td><?= strtoupper($pay['payment_method']) ?></td>
                            <td><?= htmlspecialchars($pay['transaction_reference'] ?? '-') ?></td>
                            <td><?= $pay['payment_date'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
