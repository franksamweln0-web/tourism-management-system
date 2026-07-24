<?php
require_once '../includes/auth.php';
requireLogin();

$input = json_decode(file_get_contents('php://input'), true);
$bookingId = $input['booking_id'] ?? 0;
$amount = $input['amount'] ?? 0;

if (!$bookingId || !$amount) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing params']);
    exit();
}

$gw = $pdo->query("SELECT * FROM payment_gateways WHERE gateway='paypal' AND status='active'")->fetch();
if (!$gw) {
    http_response_code(400);
    echo json_encode(['error' => 'PayPal not configured']);
    exit();
}

$clientId = $gw['api_key'];
$secret = $gw['api_secret'];
$isLive = $gw['environment'] === 'live';
$apiUrl = $isLive ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

$ch = curl_init("$apiUrl/v1/oauth2/token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$clientId:$secret");
curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$auth = json_decode(curl_exec($ch), true);
curl_close($ch);

if (empty($auth['access_token'])) {
    http_response_code(500);
    echo json_encode(['error' => 'PayPal auth failed']);
    exit();
}

$orderData = [
    'intent' => 'CAPTURE',
    'purchase_units' => [[
        'reference_id' => (string)$bookingId,
        'amount' => ['currency_code' => 'USD', 'value' => number_format($amount, 2, '.', '')]
    ]]
];

$ch = curl_init("$apiUrl/v2/checkout/orders");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $auth['access_token']
]);
$order = json_decode(curl_exec($ch), true);
curl_close($ch);

echo json_encode(['id' => $order['id'] ?? null, 'status' => $order['status'] ?? 'ERROR']);
