<?php
require_once '../includes/auth.php';
requireRole('admin');

try { $pdo->exec("ALTER TABLE tour_packages ADD COLUMN IF NOT EXISTS image_url VARCHAR(500) DEFAULT NULL"); } catch (Exception $e) {}
$hasImages = $pdo->query("SELECT COUNT(*) FROM tour_packages WHERE image_url IS NOT NULL")->fetchColumn();
if ($hasImages == 0) {
    $images = [
        1 => 'https://images.unsplash.com/photo-1504006833117-8886a355efbf?q=80&w=800&h=500&fit=crop',
        2 => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?q=80&w=800&h=500&fit=crop',
        3 => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?q=80&w=800&h=500&fit=crop',
        4 => 'https://images.unsplash.com/photo-1516426122078-c23e76319801?q=80&w=800&h=500&fit=crop',
        5 => 'https://images.unsplash.com/photo-1501785888041-af3ef285b470?q=80&w=800&h=500&fit=crop',
    ];
    $s = $pdo->prepare("UPDATE tour_packages SET image_url = ? WHERE id = ?");
    foreach ($images as $id => $url) { $s->execute([$url, $id]); }
}

try { $pdo->exec("ALTER TABLE tourists ADD COLUMN IF NOT EXISTS contact_number VARCHAR(20) DEFAULT NULL"); } catch (Exception $e) {}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS payment_gateways (id SERIAL PRIMARY KEY, gateway VARCHAR(50) NOT NULL UNIQUE, api_key TEXT, api_secret TEXT, api_passkey TEXT, environment VARCHAR(20) DEFAULT 'sandbox', shortcode VARCHAR(50), country VARCHAR(5) DEFAULT 'KE', status VARCHAR(20) DEFAULT 'active', updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    try { $pdo->exec("ALTER TABLE payment_gateways ADD COLUMN IF NOT EXISTS country VARCHAR(5) DEFAULT 'KE'"); } catch (Exception $e2) {}
    $pdo->exec("INSERT INTO payment_gateways (gateway, api_key, api_secret, environment) VALUES ('paypal', 'sb', 'sb', 'sandbox') ON CONFLICT DO NOTHING");
    $pdo->exec("INSERT INTO payment_gateways (gateway, api_key, api_secret, api_passkey, shortcode, country, environment) VALUES ('mpesa', 'YOUR_CONSUMER_KEY', 'YOUR_CONSUMER_SECRET', 'YOUR_PASSKEY', '174379', 'KE', 'sandbox') ON CONFLICT DO NOTHING");
    $pdo->exec("INSERT INTO payment_gateways (gateway, environment, status) VALUES ('bank_transfer', 'live', 'active') ON CONFLICT DO NOTHING");
} catch (Exception $e) {}

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalPackages = $pdo->query("SELECT COUNT(*) FROM tour_packages")->fetchColumn();
$totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(amount_paid), 0) FROM payments")->fetchColumn();
$pendingBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$recentBookings = $pdo->query("SELECT b.*, t.full_name, p.package_name FROM bookings b JOIN tourists t ON b.tourist_id = t.id JOIN tour_packages p ON b.package_id = p.id ORDER BY b.created_at DESC LIMIT 5")->fetchAll();

$popular = $pdo->query("SELECT p.package_name, COUNT(b.id) as cnt FROM bookings b JOIN tour_packages p ON b.package_id = p.id GROUP BY p.id ORDER BY cnt DESC LIMIT 3")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        @keyframes fadeInUp { from { opacity:0; transform:translateY(30px); } to { opacity:1; transform:translateY(0); } }
        @keyframes countUp { from { opacity:0; transform:scale(0.5); } to { opacity:1; transform:scale(1); } }
        @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
        
        body { background: #f0f2f5; }
        
        .welcome-banner {
            background: linear-gradient(135deg, #1a2a3a 0%, #2c5f2d 50%, #d4a017 100%);
            border-radius: 20px;
            padding: 30px;
            color: white;
            margin-bottom: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            position: relative;
            overflow: hidden;
        }
        .welcome-banner::after {
            content: '🦁🐘🦒';
            position: absolute;
            right: 20px;
            bottom: 10px;
            font-size: 3rem;
            opacity: 0.2;
        }
        .welcome-banner h1 { font-weight: 800; font-size: 1.8rem; }
        .welcome-banner p { opacity: 0.9; margin: 0; }

        .stat-card {
            border-radius: 18px;
            border: none;
            overflow: hidden;
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s ease forwards;
            opacity: 0;
            position: relative;
        }
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        .stat-card:nth-child(5) { animation-delay: 0.5s; }
        
        .stat-card:hover { transform: translateY(-8px); box-shadow: 0 15px 40px rgba(0,0,0,0.15); }
        .stat-card .card-body { padding: 22px; }
        .stat-card .stat-icon {
            width: 50px; height: 50px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 12px;
        }
        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 800;
            margin: 0;
            line-height: 1.2;
        }
        .stat-card .stat-label {
            font-size: 0.85rem;
            opacity: 0.85;
            margin: 0;
        }

        .card-gradient-green { background: linear-gradient(135deg, #2d6a4f, #40916c); color: white; }
        .card-gradient-orange { background: linear-gradient(135deg, #e07c1f, #f39c12); color: white; }
        .card-gradient-blue { background: linear-gradient(135deg, #1a5276, #2e86c1); color: white; }
        .card-gradient-gold { background: linear-gradient(135deg, #b8860b, #f1c40f); color: white; }
        .card-gradient-red { background: linear-gradient(135deg, #922b21, #e74c3c); color: white; }

        .content-card {
            border-radius: 18px;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.06);
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
            animation-delay: 0.5s;
            height: 100%;
        }
        .content-card .card-header {
            background: white;
            border-bottom: 2px solid #f0f2f5;
            font-weight: 700;
            font-size: 1.1rem;
            padding: 18px 22px;
            border-radius: 18px 18px 0 0 !important;
        }
        .content-card .card-body { padding: 20px 22px; }

        .quick-link {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            border-radius: 12px;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
            background: #f8f9fa;
            margin-bottom: 8px;
        }
        .quick-link:hover {
            background: linear-gradient(135deg, #2d6a4f, #40916c);
            color: white;
            transform: translateX(5px);
        }
        .quick-link i { width: 35px; font-size: 1.2rem; }

        .badge-status {
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        .table-modern th {
            background: #f8f9fa;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #666;
            border: none;
            padding: 12px 15px;
        }
        .table-modern td {
            padding: 12px 15px;
            vertical-align: middle;
            border-color: #f0f2f5;
        }
        .table-modern tr { transition: background 0.2s; }
        .table-modern tr:hover { background: #f8f9fa; }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-none d-md-block bg-white sidebar shadow-sm" style="min-height:100vh; border-right:1px solid #eef0f2;">
            <div class="position-sticky pt-4">
                <h6 class="px-3 mb-3" style="font-size:0.75rem; text-transform:uppercase; letter-spacing:1px; color:#999;">Admin Panel</h6>
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_users.php"><i class="fas fa-users me-2"></i> Users</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_packages.php"><i class="fas fa-suitcase me-2"></i> Packages</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_bookings.php"><i class="fas fa-book me-2"></i> Bookings</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_payments.php"><i class="fas fa-credit-card me-2"></i> Payments</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_guides.php"><i class="fas fa-user-tie me-2"></i> Guides</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_accommodations.php"><i class="fas fa-hotel me-2"></i> Accommodations</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_itineraries.php"><i class="fas fa-map me-2"></i> Itineraries</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php"><i class="fas fa-chart-bar me-2"></i> Reports</a></li>
                </ul>
            </div>
        </nav>
        <main class="col-md-10 ms-sm-auto px-4 py-4">
            <div class="welcome-banner">
                <h1>👋 Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h1>
                <p>🌍 Here's what's happening with your tourism business today.</p>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3 stat-card">
                    <div class="card card-gradient-green">
                        <div class="card-body">
                            <div class="stat-icon" style="background:rgba(255,255,255,0.2);">👥</div>
                            <h2 class="stat-number"><?= $totalUsers ?></h2>
                            <p class="stat-label">Total Users</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 stat-card">
                    <div class="card card-gradient-orange">
                        <div class="card-body">
                            <div class="stat-icon" style="background:rgba(255,255,255,0.2);">🎒</div>
                            <h2 class="stat-number"><?= $totalPackages ?></h2>
                            <p class="stat-label">Tour Packages</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 stat-card">
                    <div class="card card-gradient-blue">
                        <div class="card-body">
                            <div class="stat-icon" style="background:rgba(255,255,255,0.2);">📋</div>
                            <h2 class="stat-number"><?= $totalBookings ?></h2>
                            <p class="stat-label">Total Bookings</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 stat-card">
                    <div class="card card-gradient-gold">
                        <div class="card-body">
                            <div class="stat-icon" style="background:rgba(255,255,255,0.2);">💰</div>
                            <h2 class="stat-number">$<?= number_format($totalRevenue, 0) ?></h2>
                            <p class="stat-label">Total Revenue</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-8">
                    <div class="card content-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>📅 Recent Bookings</span>
                            <a href="manage_bookings.php" class="btn btn-sm btn-outline-primary rounded-pill">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-modern mb-0">
                                    <thead><tr><th>Reference</th><th>Tourist</th><th>Package</th><th>Date</th><th>Status</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($recentBookings as $b): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($b['booking_reference']) ?></strong></td>
                                            <td><?= htmlspecialchars($b['full_name']) ?></td>
                                            <td><?= htmlspecialchars($b['package_name']) ?></td>
                                            <td><?= $b['booking_date'] ?></td>
                                            <td>
                                                <span class="badge badge-status bg-<?= $b['status'] === 'confirmed' ? 'success' : ($b['status'] === 'pending' ? 'warning text-dark' : 'danger') ?>">
                                                    <?= $b['status'] ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card content-card">
                        <div class="card-header">⚡ Quick Actions</div>
                        <div class="card-body">
                            <a href="manage_packages.php" class="quick-link"><i class="fas fa-plus-circle text-success"></i> Add Package</a>
                            <a href="manage_users.php" class="quick-link"><i class="fas fa-user-plus text-primary"></i> Manage Users</a>
                            <a href="manage_guides.php" class="quick-link"><i class="fas fa-user-tie text-warning"></i> Manage Guides</a>
                            <a href="reports.php" class="quick-link"><i class="fas fa-chart-line text-info"></i> View Reports</a>
                            <a href="manage_bookings.php" class="quick-link"><i class="fas fa-check-circle text-success"></i> Pending (<?= $pendingBookings ?>)</a>
                        </div>
                    </div>
                    <div class="card content-card mt-4">
                        <div class="card-header">🏆 Popular Packages</div>
                        <div class="card-body">
                            <?php if (empty($popular)): ?>
                                <p class="text-muted mb-0">No bookings yet</p>
                            <?php else: ?>
                                <?php $i = 1; foreach ($popular as $p): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <span style="font-size:1.5rem; margin-right:12px;"><?= $i == 1 ? '🥇' : ($i == 2 ? '🥈' : '🥉') ?></span>
                                    <div>
                                        <strong><?= htmlspecialchars($p['package_name']) ?></strong>
                                        <br><small class="text-muted"><?= $p['cnt'] ?> bookings</small>
                                    </div>
                                </div>
                                <?php $i++; endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/script.js"></script>
</body>
</html>
