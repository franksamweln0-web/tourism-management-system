<?php
require_once '../includes/auth.php';
requireLogin();

$input = json_decode(file_get_contents('php://input'), true);
$orderId = $input['order_id'] ?? '';
$bookingId = $input['booking_id'] ?? 0;

if (!$orderId || !$bookingId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing params']);
    exit();
}

$gw = $pdo->query("SELECT * FROM payment_gateways WHERE gateway='paypal' AND status='active'")->fetch();
if (!$gw) { http_response_code(400); echo json_encode(['error' => 'PayPal not configured']); exit(); }

$isLive = $gw['environment'] === 'live';
$apiUrl = $isLive ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

$ch = curl_init("$apiUrl/v1/oauth2/token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $gw['api_key'] . ':' . $gw['api_secret']);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
$auth = json_decode(curl_exec($ch), true);
curl_close($ch);

$ch = curl_init("$apiUrl/v2/checkout/orders/$orderId/capture");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . ($auth['access_token'] ?? '')
]);
$capture = json_decode(curl_exec($ch), true);
curl_close($ch);

if (($capture['status'] ?? '') === 'COMPLETED') {
    $amount = $capture['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? 0;
    $txnRef = $capture['purchase_units'][0]['payments']['captures'][0]['id'] ?? '';

    $pdo->prepare("UPDATE bookings SET payment_status = 'paid' WHERE id = ?")->execute([$bookingId]);
    $pdo->prepare("INSERT INTO payments (booking_id, amount_paid, payment_method, transaction_reference) VALUES (?, ?, 'online', ?)")
        ->execute([$bookingId, $amount, $txnRef]);

    echo json_encode(['status' => 'COMPLETED']);
} else {
    echo json_encode(['status' => 'FAILED', 'message' => $capture['message'] ?? 'Capture failed']);
}
