<?php
require_once '../includes/auth.php';
requireRole('tourist');

$tourist = getTouristProfile();
$touristId = $tourist['id'] ?? 0;

$bookingId = $_GET['booking'] ?? 0;
$stmt = $pdo->prepare("SELECT b.*, p.package_name, p.destination FROM bookings b JOIN tour_packages p ON b.package_id = p.id WHERE b.id = ? AND b.tourist_id = ?");
$stmt->execute([$bookingId, $touristId]);
$booking = $stmt->fetch();

if (!$booking) { header('Location: my_bookings.php'); exit(); }
if ($booking['payment_status'] === 'paid') { header('Location: my_bookings.php?msg=already_paid'); exit(); }

$paypalGw = $pdo->query("SELECT * FROM payment_gateways WHERE gateway='paypal' AND status='active'")->fetch();
$mpesaGw = $pdo->query("SELECT * FROM payment_gateways WHERE gateway='mpesa' AND status='active'")->fetch();
$bankGw = $pdo->query("SELECT * FROM payment_gateways WHERE gateway='bank_transfer' AND status='active'")->fetch();

$msg = $_GET['msg'] ?? '';
$mpesaCountry = $mpesaGw['country'] ?? 'KE';
$phonePrefix = $mpesaCountry === 'TZ' ? '+255' : '+254';
$phoneExample = $mpesaCountry === 'TZ' ? '712345678' : '712345678';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay - <?= htmlspecialchars($booking['booking_reference']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <style>
         body { background:linear-gradient(135deg, #f5f7fa 0%, #e9edf5 100%); }
         .pay-card { border-radius:20px; border:none; box-shadow:0 10px 40px rgba(0,0,0,0.08); overflow:hidden; }
         .pay-card .card-header { font-weight:700; font-size:1.1rem; padding:20px 24px; background:white; border-bottom:2px solid #f0f2f5; }
         .pay-card .card-body { padding:24px; }
         .summary-row { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f0f2f5; }
         .summary-row.total { border-bottom:none; font-size:1.2rem; font-weight:800; color:#2d6a4f; }
         .method-card { padding:20px; border-radius:16px; border:2px solid #e0e0e0; cursor:pointer; transition:all 0.3s; text-align:center; }
         .method-card:hover { border-color:#2d6a4f; background:#f0f9f4; }
         .method-card.active { border-color:#2d6a4f; background:#e8f5e9; }
         .method-card .icon { font-size:2.5rem; margin-bottom:10px; }
         .method-card .name { font-weight:700; }
         .payment-section { display:none; }
         .payment-section.active { display:block; }
         .status-badge { display:inline-block; padding:4px 12px; border-radius:20px; font-size:0.75rem; font-weight:600; }
         .bank-detail { background:#f8f9fa; border-radius:12px; padding:15px; margin-bottom:10px; }
         .bank-detail .label { font-size:0.8rem; color:#999; text-transform:uppercase; letter-spacing:0.5px; }
         .bank-detail .value { font-weight:700; font-size:1.1rem; }
         .qr-container { background:#fff; border-radius:16px; padding:20px; text-align:center; border:2px dashed #e0e0e0; }
         .qr-container img { max-width:180px; height:auto; }
         .qr-label { font-size:0.75rem; color:#999; margin-top:8px; word-break:break-all; }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="container py-4">
    <?php if ($msg === 'mpesa_sent'): ?>
    <div class="alert alert-success">✅ STK push sent! Check your phone and enter PIN to complete.</div>
    <?php endif; ?>
    <?php if ($msg === 'bank_pending'): ?>
    <div class="alert alert-info">🏦 Your bank transfer is noted. Admin will confirm once payment is received.</div>
    <?php endif; ?>
    <?php if ($msg === 'error'): ?>
    <div class="alert alert-danger">❌ Payment failed. Please try again or choose another method.</div>
    <?php endif; ?>
    <div class="row g-4">
        <div class="col-md-5">
            <div class="card pay-card">
                <div class="card-header">📄 Order Summary</div>
                <div class="card-body">
                    <div class="summary-row"><span>Reference</span><strong><?= htmlspecialchars($booking['booking_reference']) ?></strong></div>
                    <div class="summary-row"><span>Package</span><strong><?= htmlspecialchars($booking['package_name']) ?></strong></div>
                    <div class="summary-row"><span>Destination</span><strong><?= htmlspecialchars($booking['destination']) ?></strong></div>
                    <div class="summary-row"><span>Travel Date</span><strong><?= $booking['booking_date'] ?></strong></div>
                    <div class="summary-row"><span>Participants</span><strong><?= $booking['participants'] ?></strong></div>
                    <div class="summary-row total">
                        <span>Total Due</span>
                        <span>$<?= number_format($booking['total_cost'], 2) ?></span>
                    </div>
                    <div class="mt-3">
                        <span class="status-badge bg-<?= $booking['status'] === 'confirmed' ? 'success' : 'warning text-dark' ?>"><?= strtoupper($booking['status']) ?></span>
                        <span class="status-badge bg-secondary ms-2">PAYMENT: PENDING</span>
                    </div>
                    <div class="qr-container mt-4">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode('Booking: '.$booking['booking_reference'].' | Package: '.$booking['package_name'].' | Amount: $'.number_format($booking['total_cost'], 2).' | TMS') ?>" alt="QR Code">
                        <div class="qr-label">Scan for payment reference</div>
                    </div>
                </div>
            </div>
            <?php if ($bankGw): ?>
            <div class="card pay-card mt-4">
                <div class="card-header">🏦 Bank Transfer Details</div>
                <div class="card-body">
                    <div class="bank-detail">
                        <div class="label">Bank</div>
                        <div class="value"><?= htmlspecialchars($bankGw['api_key'] ?: '—') ?></div>
                    </div>
                    <div class="bank-detail">
                        <div class="label">Account Name</div>
                        <div class="value"><?= htmlspecialchars($bankGw['api_secret'] ?: '—') ?></div>
                    </div>
                    <div class="bank-detail">
                        <div class="label">Account Number</div>
                        <div class="value"><?= htmlspecialchars($bankGw['api_passkey'] ?: '—') ?></div>
                    </div>
                    <div class="bank-detail">
                        <div class="label">Branch / Swift</div>
                        <div class="value"><?= htmlspecialchars($bankGw['shortcode'] ?: '—') ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="col-md-7">
            <div class="card pay-card">
                <div class="card-header">💳 Choose Payment Method</div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <?php if ($paypalGw): ?>
                        <div class="col-md-4">
                            <div class="method-card" onclick="selectMethod('paypal')" id="m-paypal">
                                <div class="icon">💳</div>
                                <div class="name">PayPal</div>
                                <small class="text-muted">Credit card / PayPal</small>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($mpesaGw): ?>
                        <div class="col-md-4">
                            <div class="method-card" onclick="selectMethod('mpesa')" id="m-mpesa">
                                <div class="icon">📱</div>
                                <div class="name">M-Pesa</div>
                                <small class="text-muted"><?= $mpesaCountry === 'KE' ? 'Kenya' : 'Tanzania' ?></small>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($bankGw): ?>
                        <div class="col-md-4">
                            <div class="method-card" onclick="selectMethod('bank')" id="m-bank">
                                <div class="icon">🏦</div>
                                <div class="name">Bank Transfer</div>
                                <small class="text-muted">Direct bank deposit</small>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!$paypalGw && !$mpesaGw && !$bankGw): ?>
                        <div class="col-12 text-center py-4"><p class="text-muted">No payment methods active. Contact admin.</p></div>
                        <?php endif; ?>
                    </div>

                    <div id="section-paypal" class="payment-section">
                        <h5 class="fw-bold mb-3">💳 PayPal / Card</h5>
                        <div id="paypal-button-container"></div>
                        <p class="text-muted small mt-2">Secure payment via PayPal.</p>
                    </div>

                    <div id="section-mpesa" class="payment-section">
                        <h5 class="fw-bold mb-3">📱 M-Pesa (<?= $mpesaCountry === 'KE' ? 'Kenya' : 'Tanzania' ?>)</h5>
                        <?php $mpesaShortcode = $mpesaGw['shortcode'] ?? ''; ?>
                        <?php if ($mpesaShortcode): ?>
                        <div class="qr-container mb-3">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode('M-Pesa Paybill/Till: '.$mpesaShortcode.' | Amount: $'.number_format($booking['total_cost'], 2).' | Ref: '.$booking['booking_reference']) ?>" alt="M-Pesa QR">
                            <div class="qr-label">Paybill/Till: <?= htmlspecialchars($mpesaShortcode) ?> — scan or use manually</div>
                        </div>
                        <?php endif; ?>
                        <form method="POST" action="mpesa_stk.php">
                            <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                            <input type="hidden" name="amount" value="<?= $booking['total_cost'] ?>">
                            <div class="mb-3">
                                <label class="form-label">M-Pesa Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><?= $phonePrefix ?></span>
                                    <input type="text" name="phone" class="form-control" placeholder="<?= $phoneExample ?>" required pattern="[0-9]{9}">
                                </div>
                                <small class="text-muted">Enter 9-digit number after <?= $phonePrefix ?></small>
                            </div>
                            <button type="submit" class="btn btn-success btn-lg w-100">📱 Pay $<?= number_format($booking['total_cost'], 2) ?></button>
                        </form>
                    </div>

                    <div id="section-bank" class="payment-section">
                        <h5 class="fw-bold mb-3">🏦 Bank Transfer</h5>
                        <div class="alert alert-info">
                            <strong>📋 Instructions:</strong> Transfer the exact amount to our bank account below, then click "I've Transferred" to notify the admin.
                        </div>
                        <div class="bank-detail">
                            <div class="label">Bank</div>
                            <div class="value"><?= htmlspecialchars($bankGw['api_key'] ?: '—') ?></div>
                        </div>
                        <div class="bank-detail">
                            <div class="label">Account Name</div>
                            <div class="value"><?= htmlspecialchars($bankGw['api_secret'] ?: '—') ?></div>
                        </div>
                        <div class="bank-detail">
                            <div class="label">Account Number</div>
                            <div class="value"><?= htmlspecialchars($bankGw['api_passkey'] ?: '—') ?></div>
                        </div>
                        <div class="bank-detail">
                            <div class="label">Amount to Transfer</div>
                            <div class="value" style="color:#2d6a4f;">$<?= number_format($booking['total_cost'], 2) ?></div>
                        </div>
                        <div class="qr-container mt-3 mb-3">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode('Bank: '.($bankGw['api_key']?:'N/A').' | Acct: '.($bankGw['api_passkey']?:'N/A').' | Name: '.($bankGw['api_secret']?:'N/A').' | Ref: '.$booking['booking_reference']) ?>" alt="Bank QR">
                            <div class="qr-label">Scan to save bank details</div>
                        </div>
                        <form method="POST" action="bank_transfer_submit.php">
                            <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                            <input type="hidden" name="amount" value="<?= $booking['total_cost'] ?>">
                            <div class="mb-3">
                                <label class="form-label">Transaction Reference (optional)</label>
                                <input type="text" name="txn_ref" class="form-control" placeholder="e.g. receipt or ref number">
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100">🏦 I've Transferred</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($paypalGw): ?>
<script src="https://www.paypal.com/sdk/js?client-id=<?= htmlspecialchars($paypalGw['api_key']) ?>&currency=USD"></script>
<?php endif; ?>
<script>
function selectMethod(method) {
    document.querySelectorAll('.method-card').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('.payment-section').forEach(s => s.classList.remove('active'));
    document.getElementById('m-' + method).classList.add('active');
    document.getElementById('section-' + method).classList.add('active');
    if (method === 'paypal') renderPayPal();
}
<?php if ($paypalGw): ?>
function renderPayPal() {
    if (typeof paypal === 'undefined') { document.getElementById('paypal-button-container').innerHTML = '<p class="text-danger">PayPal SDK not loaded. Check Client ID.</p>'; return; }
    paypal.Buttons({
        createOrder: function() {
            return fetch('../ajax/create_paypal_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ booking_id: <?= $booking['id'] ?>, amount: <?= $booking['total_cost'] ?> })
            }).then(r => r.json()).then(d => d.id);
        },
        onApprove: function(data) {
            return fetch('../ajax/capture_paypal_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: data.orderID, booking_id: <?= $booking['id'] ?> })
            }).then(r => r.json()).then(d => {
                if (d.status === 'COMPLETED') window.location.href = 'my_bookings.php?msg=paid';
                else alert('Payment failed: ' + (d.message || 'Unknown error'));
            });
        },
        onError: function(err) { alert('PayPal error: ' + err.message); }
    }).render('#paypal-button-container');
}
<?php endif; ?>
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
