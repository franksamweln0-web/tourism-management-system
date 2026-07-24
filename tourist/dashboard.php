<?php
require_once '../includes/auth.php';
requireRole('tourist');

$tourist = getTouristProfile();
if (!$tourist) {
    $stmt = $pdo->prepare("INSERT INTO tourists (user_id, full_name) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['username']]);
    $tourist = getTouristProfile();
}
$touristId = $tourist['id'] ?? 0;

$myBookings = $pdo->prepare("SELECT b.*, p.package_name, p.destination, p.duration_days, p.price FROM bookings b JOIN tour_packages p ON b.package_id = p.id WHERE b.tourist_id = ? ORDER BY b.created_at DESC LIMIT 5");
$myBookings->execute([$touristId]);
$bookings = $myBookings->fetchAll();

$pendingCount = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE tourist_id = ? AND status = 'pending'");
$pendingCount->execute([$touristId]);
$confirmedCount = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE tourist_id = ? AND status = 'confirmed'");
$confirmedCount->execute([$touristId]);

$notifications = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY sent_at DESC LIMIT 5");
$notifications->execute([$_SESSION['user_id']]);
$notifs = $notifications->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - TMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        @keyframes fadeInUp { from { opacity:0; transform:translateY(30px); } to { opacity:1; transform:translateY(0); } }
        @keyframes float { 0%,100% { transform:translateY(0); } 50% { transform:translateY(-8px); } }
        
        body { background: linear-gradient(135deg, #f5f7fa 0%, #e9edf5 100%); }
        
        .welcome-banner {
            background: linear-gradient(135deg, #1a3c34 0%, #2d6a4f 50%, #d4a017 100%);
            border-radius: 20px;
            padding: 30px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            margin-bottom: 25px;
        }
        .welcome-banner::after {
            content: '🦁🐘🦒';
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
        }
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
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

        .wildlife-showcase {
            position: relative;
            border-radius: 18px;
            overflow: hidden;
            min-height: 160px;
            cursor: pointer;
            border: none;
        }
        .wildlife-showcase .bg-animal {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            animation: kenBurns 12s ease-in-out infinite alternate;
        }
        @keyframes kenBurns {
            0% { transform: scale(1); }
            100% { transform: scale(1.1); }
        }
        .wildlife-showcase .overlay-content {
            position: relative;
            z-index: 2;
            padding: 28px 22px;
            background: rgba(0,0,0,0.35);
            backdrop-filter: blur(2px);
            height: 100%;
            min-height: 160px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            transition: background 0.4s;
        }
        .wildlife-showcase:hover .overlay-content {
            background: rgba(0,0,0,0.5);
        }
        .wildlife-showcase .explore-text {
            font-size: 2.5rem;
            font-weight: 900;
            color: white;
            text-shadow: 0 4px 20px rgba(0,0,0,0.5);
            letter-spacing: 1px;
        }
        .wildlife-showcase .explore-sub {
            color: rgba(255,255,255,0.9);
            font-size: 1rem;
            font-weight: 500;
        }
        .wildlife-showcase .animal-badge {
            display: inline-block;
            font-size: 1.8rem;
            margin-top: 8px;
            animation: floatBadge 2s ease-in-out infinite;
        }
        @keyframes floatBadge {
            0%,100% { transform: translateY(0) rotate(-5deg); }
            50% { transform: translateY(-6px) rotate(5deg); }
        }
        .wildlife-showcase:hover .animal-badge {
            animation: none;
            transform: scale(1.3);
        }

        .stat-card .card { position: relative; overflow: hidden; }
        .stat-card a { display: block; height: 100%; }
        .stat-card .card-img-overlay-bg {
            position: absolute; inset: 0;
            background-size: cover; background-position: center;
            opacity: 0.25; transition: opacity 0.4s;
        }
        .stat-card:hover .card-img-overlay-bg { opacity: 0.4; }
        .card-gradient-green { background: linear-gradient(135deg, #2d6a4f, #40916c); color: white; }
        .card-gradient-blue { background: linear-gradient(135deg, #1a5276, #2e86c1); color: white; }

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

        .profile-section {
            text-align: center;
            padding: 10px 0;
        }
        .profile-avatar {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2d6a4f, #d4a017);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 12px;
            box-shadow: 0 8px 25px rgba(45,106,79,0.3);
        }
        .notif-item {
            padding: 10px 0;
            border-bottom: 1px solid #f0f2f5;
        }
        .notif-item:last-child { border-bottom: none; }
        .notif-icon { font-size: 1.2rem; margin-right: 10px; }

        .quick-action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 16px;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: 2px dashed #ddd;
            color: #555;
        }
        .quick-action-btn:hover {
            border-color: #2d6a4f;
            color: #2d6a4f;
            background: rgba(45,106,79,0.05);
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid px-4 py-4">
    <div class="welcome-banner">
        <h1>🌍 Safari Adventure Awaits!</h1>
        <p>Welcome back, <?= htmlspecialchars($tourist['full_name'] ?? $_SESSION['username']) ?>! Ready for your next journey?</p>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4 stat-card">
            <a href="my_bookings.php" class="text-decoration-none">
            <div class="card card-gradient-green">
                <div class="card-img-overlay-bg" style="background-image:url('https://images.unsplash.com/photo-1504006833117-8886a355efbf?q=80&w=800&h=500&fit=crop');"></div>
                <div class="card-body position-relative">
                    <div class="stat-icon" style="background:rgba(255,255,255,0.2);">🎒</div>
                    <h2 class="stat-number"><?= $pendingCount->fetchColumn() ?></h2>
                    <p class="stat-label">Pending</p>
                </div>
            </div>
            </a>
        </div>
        <div class="col-md-4 stat-card">
            <a href="my_bookings.php" class="text-decoration-none">
            <div class="card card-gradient-blue">
                <div class="card-img-overlay-bg" style="background-image:url('https://images.unsplash.com/photo-1516426122078-c23e76319801?q=80&w=800&h=500&fit=crop');"></div>
                <div class="card-body position-relative">
                    <div class="stat-icon" style="background:rgba(255,255,255,0.2);">✅</div>
                    <h2 class="stat-number"><?= $confirmedCount->fetchColumn() ?></h2>
                    <p class="stat-label">Confirmed</p>
                </div>
            </div>
            </a>
        </div>
        <div class="col-md-4 stat-card">
            <a href="packages.php" class="text-decoration-none">
                <div class="card wildlife-showcase shadow">
                    <div class="bg-animal" style="background-image:url('https://images.unsplash.com/photo-1504006833117-8886a355efbf?q=80&w=800&h=500&fit=crop');"></div>
                    <div class="overlay-content">
                        <div class="explore-text">🐾 Explore</div>
                        <div class="explore-sub">Amazing Wildlife Destinations →</div>
                        <div class="animal-badge">🦁🐘🦒</div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-7">
            <div class="card content-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>📋 My Recent Bookings</span>
                    <a href="packages.php" class="btn btn-sm btn-outline-success rounded-pill">Browse Packages</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($bookings)): ?>
                    <div class="text-center py-5">
                        <div style="font-size:3rem;">🦁</div>
                        <p class="text-muted mt-2">No bookings yet! Start your safari adventure today.</p>
                        <a href="packages.php" class="btn btn-success rounded-pill px-4">Explore Packages</a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead><tr><th>Reference</th><th>Package</th><th>Destination</th><th>Date</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($b['booking_reference']) ?></strong></td>
                                    <td><?= htmlspecialchars($b['package_name']) ?></td>
                                    <td><?= htmlspecialchars($b['destination']) ?></td>
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <a href="profile.php" class="text-decoration-none" style="color:inherit;">
            <div class="card content-card">
                <div class="card-header">👤 My Profile</div>
                <div class="card-body">
                    <div class="profile-section">
                        <div class="profile-avatar"><?= strtoupper(substr($tourist['full_name'] ?? $_SESSION['username'], 0, 1)) ?></div>
                        <h5 class="fw-bold"><?= htmlspecialchars($tourist['full_name'] ?? '-') ?></h5>
                    </div>
                    <hr>
                    <div class="row mb-2"><div class="col-5 text-muted">Nationality</div><div class="col-7 fw-medium"><?= htmlspecialchars($tourist['nationality'] ?? '-') ?></div></div>
                    <div class="row mb-2"><div class="col-5 text-muted">Passport</div><div class="col-7 fw-medium"><?= htmlspecialchars($tourist['passport_number'] ?? '-') ?></div></div>
                    <div class="row mb-2"><div class="col-5 text-muted">Contact</div><div class="col-7 fw-medium"><?= htmlspecialchars($tourist['contact_number'] ?? '-') ?></div></div>
                    <div class="text-center mt-3"><span class="badge bg-success rounded-pill px-3 py-2">✏️ Edit Profile →</span></div>
                </div>
            </div>
            </a>

            <div class="card content-card mt-4">
                <div class="card-header">🔔 Notifications</div>
                <div class="card-body">
                    <?php if (empty($notifs)): ?>
                    <p class="text-muted text-center mb-0">No notifications yet.</p>
                    <?php else: ?>
                    <?php foreach ($notifs as $n): ?>
                    <div class="notif-item d-flex align-items-start">
                        <span class="notif-icon">📬</span>
                        <div>
                            <strong><?= htmlspecialchars($n['subject']) ?></strong><br>
                            <small class="text-muted"><?= $n['sent_at'] ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card content-card mt-4">
                <div class="card-header">⚡ Quick Actions</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6"><a href="packages.php" class="quick-action-btn"><span>🎒</span> Packages</a></div>
                        <div class="col-6"><a href="my_bookings.php" class="quick-action-btn"><span>📋</span> My Bookings</a></div>
                        <div class="col-6"><a href="profile.php" class="quick-action-btn"><span>👤</span> Profile</a></div>
                        <div class="col-6"><a href="../logout.php" class="quick-action-btn"><span>🚪</span> Logout</a></div>
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
