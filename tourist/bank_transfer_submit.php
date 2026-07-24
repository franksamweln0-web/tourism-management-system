<?php
require_once '../includes/auth.php';
requireRole('tourist');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: my_bookings.php'); exit(); }

$bookingId = $_POST['booking_id'] ?? 0;
$amount = $_POST['amount'] ?? 0;
$txnRef = trim($_POST['txn_ref'] ?? '');

$tourist = getTouristProfile();
$touristId = $tourist['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND tourist_id = ?");
$stmt->execute([$bookingId, $touristId]);
$booking = $stmt->fetch();

if (!$booking || $booking['payment_status'] === 'paid') {
    header('Location: my_bookings.php');
    exit();
}

$pdo->prepare("UPDATE bookings SET payment_status = 'pending_bank' WHERE id = ?")->execute([$bookingId]);
$pdo->prepare("INSERT INTO payments (booking_id, amount_paid, payment_method, transaction_reference, notes) VALUES (?, ?, 'bank_transfer', ?, 'Awaiting admin confirmation')")
    ->execute([$bookingId, $amount, $txnRef ?: 'MANUAL-' . strtoupper(uniqid())]);

$admins = $pdo->query("SELECT id FROM users WHERE role='admin'")->fetchAll();
foreach ($admins as $admin) {
    logNotification($admin['id'], 'payment', 'Bank Transfer Pending', "Booking {$booking['booking_reference']} - \${$amount} via bank transfer, awaiting confirmation.");
}

header('Location: pay.php?booking=' . $bookingId . '&msg=bank_pending');
exit();
