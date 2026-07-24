<?php
require_once '../includes/auth.php';
requireRole('agent');

$userId = $_SESSION['user_id'];
$todayBookings = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date = CURDATE()");
$todayBookings->execute();
$todayCount = $todayBookings->fetchColumn();
$totalTourists = $pdo->query("SELECT COUNT(*) FROM tourists")->fetchColumn();
$pendingPayments = $pdo->query("SELECT COUNT(*) FROM bookings WHERE payment_status IN ('pending','partial')")->fetchColumn();
$thisMonth = $pdo->query("SELECT COALESCE(SUM(amount_paid), 0) FROM payments WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())")->fetchColumn();
$recentBookings = $pdo->query("SELECT b.*, t.full_name, p.package_name FROM bookings b JOIN tourists t ON b.tourist_id = t.id JOIN tour_packages p ON b.package_id = p.id ORDER BY b.created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Dashboard - TMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        @keyframes fadeInUp { from { opacity:0; transform:translateY(30px); } to { opacity:1; transform:translateY(0); } }
        
        body { background: linear-gradient(135deg, #f5f7fa 0%, #e9edf5 100%); }
        
        .welcome-banner {
            background: linear-gradient(135deg, #4a1a3c 0%, #7b2d8e 50%, #d4a017 100%);
            border-radius: 20px;
            padding: 30px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            margin-bottom: 25px;
        }
        .welcome-banner::after {
            content: '🌍✈️🗺️';
            position: absolute;
            right: 20px;
            bottom: 10px;
            font-size: 3rem;
            opacity: 0.2;
        }
        .welcome-banner h1 { font-weight: 800; }
        .welcome-banner p { opacity: 0.9; }

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
        .stat-card:hover { transform: translateY(-8px); box-shadow: 0 15px 40px rgba(0,0,0,0.15); }
        .stat-card .card-body { padding: 22px; }
        .stat-card .stat-icon {
            width: 50px; height: 50px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 12px;
        }
        .stat-card .stat-number { font-size: 2rem; font-weight: 800; margin: 0; line-height: 1.2; }
        .stat-card .stat-label { font-size: 0.85rem; opacity: 0.85; margin: 0; }

        .card-gradient-purple { background: linear-gradient(135deg, #5b2c8e, #8e44ad); color: white; }
        .card-gradient-teal { background: linear-gradient(135deg, #0e7c7b, #17a589); color: white; }
        .card-gradient-orange { background: linear-gradient(135deg, #e07c1f, #f39c12); color: white; }
        .card-gradient-green { background: linear-gradient(135deg, #1e8449, #27ae60); color: white; }

        .content-card {
            border-radius: 18px;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.06);
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
            animation-delay: 0.4s;
            height: 100%;
        }
        .content-card .card-header {
            background: white;
            border-bottom: 2px solid #f0f2f5;
            font-weight: 700;
            font-size: 1rem;
            padding: 16px 20px;
            border-radius: 18px 18px 0 0 !important;
        }
        .content-card .card-body { padding: 20px; }

        .table-modern th {
            background: #f8f9fa;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #666;
            border: none;
            padding: 12px 14px;
        }
        .table-modern td {
            padding: 12px 14px;
            vertical-align: middle;
            border-color: #f0f2f5;
        }
        .table-modern tr { transition: background 0.2s; }
        .table-modern tr:hover { background: #f8f9fa; }

        .badge-status { padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 0.75rem; }

        .action-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .action-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 25px 15px;
            border-radius: 16px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            text-align: center;
        }
        .action-card .action-icon { font-size: 2rem; margin-bottom: 8px; }
        .action-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .action-card.green { background: linear-gradient(135deg, #e8f5e9, #c8e6c9); color: #2d6a4f; }
        .action-card.blue { background: linear-gradient(135deg, #e3f2fd, #bbdefb); color: #1a5276; }
        .action-card.gold { background: linear-gradient(135deg, #fff8e1, #ffecb3); color: #b8860b; }
        .action-card.purple { background: linear-gradient(135deg, #f3e5f5, #e1bee7); color: #7b1fa2; }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid px-4 py-4">
    <div class="welcome-banner">
        <h1>👋 Welcome, Agent <?= htmlspecialchars($_SESSION['username']) ?>!</h1>
        <p>✈️ Let's create unforgettable safari experiences for our guests today.</p>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3 stat-card">
            <div class="card card-gradient-purple">
                <div class="card-body">
                    <div class="stat-icon" style="background:rgba(255,255,255,0.2);">📅</div>
                    <h2 class="stat-number"><?= $todayCount ?></h2>
                    <p class="stat-label">Today's Bookings</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 stat-card">
            <div class="card card-gradient-teal">
                <div class="card-body">
                    <div class="stat-icon" style="background:rgba(255,255,255,0.2);">👥</div>
                    <h2 class="stat-number"><?= $totalTourists ?></h2>
                    <p class="stat-label">Registered Tourists</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 stat-card">
            <div class="card card-gradient-orange">
                <div class="card-body">
                    <div class="stat-icon" style="background:rgba(255,255,255,0.2);">⏳</div>
                    <h2 class="stat-number"><?= $pendingPayments ?></h2>
                    <p class="stat-label">Pending Payments</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 stat-card">
            <div class="card card-gradient-green">
                <div class="card-body">
                    <div class="stat-icon" style="background:rgba(255,255,255,0.2);">💰</div>
                    <h2 class="stat-number">$<?= number_format($thisMonth, 0) ?></h2>
                    <p class="stat-label">Revenue This Month</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-5">
            <div class="card content-card">
                <div class="card-header">⚡ Quick Actions</div>
                <div class="card-body">
                    <div class="action-grid">
                        <a href="bookings.php?action=new" class="action-card green">
                            <span class="action-icon">📋</span>
                            <span>New Booking</span>
                        </a>
                        <a href="register_tourist.php" class="action-card blue">
                            <span class="action-icon">👤</span>
                            <span>Register Tourist</span>
                        </a>
                        <a href="payments.php" class="action-card gold">
                            <span class="action-icon">💳</span>
                            <span>Process Payment</span>
                        </a>
                        <a href="bookings.php" class="action-card purple">
                            <span class="action-icon">📊</span>
                            <span>All Bookings</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card content-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>📋 Recent Bookings</span>
                    <a href="bookings.php" class="btn btn-sm btn-outline-primary rounded-pill">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead><tr><th>Ref</th><th>Tourist</th><th>Package</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($recentBookings as $b): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($b['booking_reference']) ?></strong></td>
                                    <td><?= htmlspecialchars($b['full_name']) ?></td>
                                    <td><?= htmlspecialchars($b['package_name']) ?></td>
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
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/script.js"></script>
</body>
</html>
