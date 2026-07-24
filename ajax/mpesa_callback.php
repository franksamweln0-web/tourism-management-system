<?php
require_once __DIR__ . '/../config/database.php';

$callbackData = json_decode(file_get_contents('php://input'), true);

$resultCode = $callbackData['Body']['stkCallback']['ResultCode'] ?? 1;
$checkoutId = $callbackData['Body']['stkCallback']['CheckoutRequestID'] ?? '';
$amount = $callbackData['Body']['stkCallback']['CallbackMetadata']['Item'][0]['Value'] ?? 0;
$txnRef = $callbackData['Body']['stkCallback']['CallbackMetadata']['Item'][1]['Value'] ?? '';
$phone = $callbackData['Body']['stkCallback']['CallbackMetadata']['Item'][4]['Value'] ?? '';

if ($resultCode == 0) {
    $stmt = $pdo->prepare("SELECT booking_id FROM payments WHERE transaction_reference = ?");
    $stmt->execute([$checkoutId]);
    $pay = $stmt->fetch();

    if ($pay) {
        $pdo->prepare("UPDATE payments SET transaction_reference = ?, notes = 'M-Pesa confirmed' WHERE transaction_reference = ?")
            ->execute([$txnRef, $checkoutId]);
        $pdo->prepare("UPDATE bookings SET payment_status = 'paid' WHERE id = ?")
            ->execute([$pay['booking_id']]);
    }
}

http_response_code(200);
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
