<?php
require_once '../includes/auth.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['paypal', 'mpesa', 'bank_transfer'] as $gw) {
        $key = $_POST[$gw . '_key'] ?? '';
        $secret = $_POST[$gw . '_secret'] ?? '';
        $passkey = $_POST[$gw . '_passkey'] ?? '';
        $shortcode = $_POST[$gw . '_shortcode'] ?? '';
        $country = $_POST[$gw . '_country'] ?? 'KE';
        $env = $_POST[$gw . '_env'] ?? 'sandbox';
        $status = isset($_POST[$gw . '_status']) ? 'active' : 'inactive';
        $pdo->prepare("UPDATE payment_gateways SET api_key=?, api_secret=?, api_passkey=?, shortcode=?, country=?, environment=?, status=? WHERE gateway=?")
            ->execute([$key, $secret, $passkey, $shortcode, $country, $env, $status, $gw]);
    }
    header('Location: payment_settings.php?msg=saved');
    exit();
}

$gateways = $pdo->query("SELECT * FROM payment_gateways")->fetchAll();
$paypal = []; $mpesa = []; $bank = [];
foreach ($gateways as $g) {
    if ($g['gateway'] === 'paypal') $paypal = $g;
    if ($g['gateway'] === 'mpesa') $mpesa = $g;
    if ($g['gateway'] === 'bank_transfer') $bank = $g;
}
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Settings - TMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background:#f0f2f5; }
        .gw-card { border-radius:18px; border:none; box-shadow:0 5px 20px rgba(0,0,0,0.06); overflow:hidden; }
        .gw-card .card-header { font-weight:700; font-size:1.1rem; padding:18px 22px; background:white; border-bottom:2px solid #f0f2f5; }
        .gw-card .card-body { padding:22px; }
        .form-label { font-weight:600; font-size:0.85rem; color:#555; }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-none d-md-block bg-white sidebar shadow-sm" style="min-height:100vh;border-right:1px solid #eef0f2;">
            <div class="position-sticky pt-4">
                <h6 class="px-3 mb-3" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;color:#999;">Admin Panel</h6>
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_users.php"><i class="fas fa-users me-2"></i> Users</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_packages.php"><i class="fas fa-suitcase me-2"></i> Packages</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_bookings.php"><i class="fas fa-book me-2"></i> Bookings</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_payments.php"><i class="fas fa-credit-card me-2"></i> Payments</a></li>
                    <li class="nav-item"><a class="nav-link active" href="payment_settings.php"><i class="fas fa-cog me-2"></i> Payment Settings</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_guides.php"><i class="fas fa-user-tie me-2"></i> Guides</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_accommodations.php"><i class="fas fa-hotel me-2"></i> Accommodations</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_itineraries.php"><i class="fas fa-map me-2"></i> Itineraries</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php"><i class="fas fa-chart-bar me-2"></i> Reports</a></li>
                </ul>
            </div>
        </nav>
        <main class="col-md-10 ms-sm-auto px-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="fw-bold" style="font-size:1.5rem;">⚙️ Payment Gateway Settings</h1>
            </div>
            <?php if ($msg === 'saved'): ?><div class="alert alert-success">✅ Settings saved successfully!</div><?php endif; ?>
            <form method="POST">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card gw-card mb-4">
                            <div class="card-header"><span style="font-size:1.5rem;margin-right:10px;">💳</span> PayPal</div>
                            <div class="card-body">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="paypal_status" id="ppStatus" <?= ($paypal['status'] ?? '') === 'active' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold" for="ppStatus">Enable PayPal</label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Client ID</label>
                                    <input type="text" name="paypal_key" class="form-control" value="<?= htmlspecialchars($paypal['api_key'] ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Secret</label>
                                    <input type="text" name="paypal_secret" class="form-control" value="<?= htmlspecialchars($paypal['api_secret'] ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Environment</label>
                                    <select name="paypal_env" class="form-control">
                                        <option value="sandbox" <?= ($paypal['environment'] ?? '') === 'sandbox' ? 'selected' : '' ?>>Sandbox (Test)</option>
                                        <option value="live" <?= ($paypal['environment'] ?? '') === 'live' ? 'selected' : '' ?>>Live</option>
                                    </select>
                                </div>
                                <small class="text-muted">Get keys from <a href="https://developer.paypal.com" target="_blank">PayPal Developer</a></small>
                            </div>
                        </div>

                        <div class="card gw-card">
                            <div class="card-header"><span style="font-size:1.5rem;margin-right:10px;">🏦</span> Bank Transfer</div>
                            <div class="card-body">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="bank_transfer_status" id="btStatus" <?= ($bank['status'] ?? '') === 'active' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold" for="btStatus">Enable Bank Transfer</label>
                                </div>
                                <p class="text-muted small">Enter your business bank details shown to tourists:</p>
                                <div class="mb-3">
                                    <label class="form-label">Bank Name</label>
                                    <input type="text" name="bank_transfer_key" class="form-control" value="<?= htmlspecialchars($bank['api_key'] ?? '') ?>" placeholder="e.g. CRDB Bank">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Account Name</label>
                                    <input type="text" name="bank_transfer_secret" class="form-control" value="<?= htmlspecialchars($bank['api_secret'] ?? '') ?>" placeholder="e.g. Safari Tours Ltd">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Account Number</label>
                                    <input type="text" name="bank_transfer_passkey" class="form-control" value="<?= htmlspecialchars($bank['api_passkey'] ?? '') ?>" placeholder="e.g. 1234567890">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Branch / Swift Code</label>
                                    <input type="text" name="bank_transfer_shortcode" class="form-control" value="<?= htmlspecialchars($bank['shortcode'] ?? '') ?>" placeholder="e.g. CORUTZTZ">
                                </div>
                                <small class="text-muted">Tourists will see these details to make manual transfers.</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card gw-card">
                            <div class="card-header"><span style="font-size:1.5rem;margin-right:10px;">📱</span> M-Pesa (Kenya & Tanzania)</div>
                            <div class="card-body">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="mpesa_status" id="mpStatus" <?= ($mpesa['status'] ?? '') === 'active' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold" for="mpStatus">Enable M-Pesa</label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">🇰🇪 Country</label>
                                    <select name="mpesa_country" class="form-control">
                                        <option value="KE" <?= ($mpesa['country'] ?? 'KE') === 'KE' ? 'selected' : '' ?>>Kenya (Safaricom Daraja API)</option>
                                        <option value="TZ" <?= ($mpesa['country'] ?? 'KE') === 'TZ' ? 'selected' : '' ?>>Tanzania (Vodacom M-Pesa API)</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Consumer Key</label>
                                    <input type="text" name="mpesa_key" class="form-control" value="<?= htmlspecialchars($mpesa['api_key'] ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Consumer Secret</label>
                                    <input type="text" name="mpesa_secret" class="form-control" value="<?= htmlspecialchars($mpesa['api_secret'] ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">API Passkey</label>
                                    <input type="text" name="mpesa_passkey" class="form-control" value="<?= htmlspecialchars($mpesa['api_passkey'] ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Shortcode (Business Paybill)</label>
                                    <input type="text" name="mpesa_shortcode" class="form-control" value="<?= htmlspecialchars($mpesa['shortcode'] ?? '174379') ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Environment</label>
                                    <select name="mpesa_env" class="form-control">
                                        <option value="sandbox" <?= ($mpesa['environment'] ?? '') === 'sandbox' ? 'selected' : '' ?>>Sandbox (Test)</option>
                                        <option value="live" <?= ($mpesa['environment'] ?? '') === 'live' ? 'selected' : '' ?>>Live</option>
                                    </select>
                                </div>
                                <small class="text-muted">
                                    🇰🇪 Kenya: <a href="https://developer.safaricom.co.ke" target="_blank">Safaricom Developer</a><br>
                                    🇹🇿 Tanzania: <a href="https://openapi.m-pesa.com" target="_blank">Vodacom M-Pesa</a>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-success btn-lg mt-4 px-5">💾 Save Settings</button>
            </form>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
