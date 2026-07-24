<?php
require_once '../includes/auth.php';
requireRole('tourist');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: my_bookings.php'); exit(); }

$bookingId = $_POST['booking_id'] ?? 0;
$amount = intval($_POST['amount'] ?? 0);
$phoneRaw = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
$tourist = getTouristProfile();
$touristId = $tourist['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND tourist_id = ?");
$stmt->execute([$bookingId, $touristId]);
$booking = $stmt->fetch();

if (!$booking || $booking['payment_status'] === 'paid') {
    header('Location: my_bookings.php');
    exit();
}

$gw = $pdo->query("SELECT * FROM payment_gateways WHERE gateway='mpesa' AND status='active'")->fetch();
if (!$gw) {
    header('Location: pay.php?booking=' . $bookingId . '&msg=error');
    exit();
}

$country = $gw['country'] ?? 'KE';
$phone = ($country === 'TZ' ? '255' : '254') . $phoneRaw;
$consumerKey = $gw['api_key'];
$consumerSecret = $gw['api_secret'];
$passkey = $gw['api_passkey'];
$shortcode = $gw['shortcode'] ?: '174379';
$isLive = $gw['environment'] === 'live';

if ($country === 'TZ') {
    $apiUrl = $isLive ? 'https://openapi.m-pesa.com' : 'https://sandbox.m-pesa.com';
} else {
    $apiUrl = $isLive ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';
}

$timestamp = date('YmdHis');
$password = base64_encode($shortcode . $passkey . $timestamp);

$ch = curl_init("$apiUrl/oauth/v1/generate?grant_type=client_credentials");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . base64_encode("$consumerKey:$consumerSecret")]);
$auth = json_decode(curl_exec($ch), true);
curl_close($ch);

$token = $auth['access_token'] ?? '';
if (!$token) {
    header('Location: pay.php?booking=' . $bookingId . '&msg=error');
    exit();
}

$stkData = [
    'BusinessShortCode' => $shortcode,
    'Password' => $password,
    'Timestamp' => $timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => $amount,
    'PartyA' => $phone,
    'PartyB' => $shortcode,
    'PhoneNumber' => $phone,
    'CallBackURL' => 'https://' . $_SERVER['HTTP_HOST'] . '/tourism_system/ajax/mpesa_callback.php',
    'AccountReference' => $booking['booking_reference'],
    'TransactionDesc' => 'Tourism Booking Payment'
];

$ch = curl_init("$apiUrl/mpesa/stkpush/v1/processrequest");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($stkData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);
$resp = json_decode(curl_exec($ch), true);
curl_close($ch);

if (($resp['ResponseCode'] ?? '1') === '0') {
    $pdo->prepare("INSERT INTO payments (booking_id, amount_paid, payment_method, transaction_reference) VALUES (?, ?, 'mpesa', ?)")
        ->execute([$bookingId, $amount, $resp['CheckoutRequestID'] ?? '']);
    header('Location: pay.php?booking=' . $bookingId . '&msg=mpesa_sent');
} else {
    header('Location: pay.php?booking=' . $bookingId . '&msg=error');
}
