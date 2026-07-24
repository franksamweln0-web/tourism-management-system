<?php
require_once '../includes/auth.php';
requireRole('guide');

$guide = getGuideProfile();
$guideId = $guide['id'] ?? 0;

$myAssignments = $pdo->prepare("SELECT ga.*, b.booking_reference, b.booking_date, b.participants, p.package_name, p.destination, t.full_name as tourist_name FROM guide_assignments ga JOIN bookings b ON ga.booking_id = b.id JOIN tour_packages p ON b.package_id = p.id JOIN tourists t ON b.tourist_id = t.id WHERE ga.guide_id = ? ORDER BY ga.assignment_date DESC");
$myAssignments->execute([$guideId]);
$assignments = $myAssignments->fetchAll();

$completedCount = 0;
$upcomingCount = 0;
foreach ($assignments as $a) {
    if ($a['status'] === 'completed') $completedCount++;
    if ($a['status'] === 'assigned' && $a['booking_date'] >= date('Y-m-d')) $upcomingCount++;
}

$nextTour = $pdo->prepare("SELECT ga.*, b.booking_reference, p.package_name, p.destination, b.booking_date FROM guide_assignments ga JOIN bookings b ON ga.booking_id = b.id JOIN tour_packages p ON b.package_id = p.id WHERE ga.guide_id = ? AND ga.status = 'assigned' AND b.booking_date >= CURDATE() ORDER BY b.booking_date ASC LIMIT 1");
$nextTour->execute([$guideId]);
$next = $nextTour->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guide Dashboard - TMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        @keyframes fadeInUp { from { opacity:0; transform:translateY(30px); } to { opacity:1; transform:translateY(0); } }
        @keyframes pulse { 0%,100% { transform:scale(1); } 50% { transform:scale(1.05); } }
        
        body { background: linear-gradient(135deg, #f5f7fa 0%, #e9edf5 100%); }
        
        .welcome-banner {
            background: linear-gradient(135deg, #1a3c34 0%, #2d6a4f 40%, #5b8c2e 70%, #d4a017 100%);
            border-radius: 20px;
            padding: 30px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            margin-bottom: 25px;
        }
        .welcome-banner::after {
            content: '🦁🏔️🌿';
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

        .card-gradient-green { background: linear-gradient(135deg, #2d6a4f, #40916c); color: white; }
        .card-gradient-blue { background: linear-gradient(135deg, #1a5276, #2e86c1); color: white; }
        .card-gradient-gold { background: linear-gradient(135deg, #b8860b, #f1c40f); color: white; }
        .card-gradient-sunset { background: linear-gradient(135deg, #a8430e, #e67e22); color: white; }

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

        .next-tour-card {
            background: linear-gradient(135deg, #fff8e1, #fff3cd);
            border: 2px solid #f1c40f;
            border-radius: 16px;
            padding: 18px 22px;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        .next-tour-card .tour-label { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: #b8860b; font-weight: 700; }
        .next-tour-card h4 { font-weight: 800; margin: 5px 0; }

        .profile-section { text-align: center; padding: 10px 0; }
        .profile-avatar {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2d6a4f, #d4a017);
            display: flex; align-items: center; justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 12px;
            box-shadow: 0 8px 25px rgba(45,106,79,0.3);
        }
        .profile-detail { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f2f5; }
        .profile-detail:last-child { border-bottom: none; }
        .profile-detail .label { color: #999; font-size: 0.85rem; }
        .profile-detail .value { font-weight: 600; }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid px-4 py-4">
    <div class="welcome-banner">
        <h1>🦁 Welcome, Guide <?= htmlspecialchars($guide['full_name'] ?? $_SESSION['username']) ?>!</h1>
        <p>🌿 The wild is calling! Lead your next safari with confidence.</p>
    </div>

    <?php if ($next): ?>
    <div class="next-tour-card d-flex justify-content-between align-items-center">
        <div>
            <div class="tour-label">⬆ Next Tour</div>
            <h4>🌍 <?= htmlspecialchars($next['package_name']) ?> — <?= htmlspecialchars($next['destination']) ?></h4>
            <span class="text-muted">📅 <?= $next['booking_date'] ?> | Ref: <?= htmlspecialchars($next['booking_reference']) ?></span>
        </div>
        <span style="font-size:3rem;">🗓️</span>
    </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-md-3 stat-card">
            <div class="card card-gradient-green">
                <div class="card-body">
                    <div class="stat-icon" style="background:rgba(255,255,255,0.2);">🎒</div>
                    <h2 class="stat-number"><?= count($assignments) ?></h2>
                    <p class="stat-label">Total Assignments</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 stat-card">
            <div class="card card-gradient-blue">
                <div class="card-body">
                    <div class="stat-icon" style="background:rgba(255,255,255,0.2);">🔜</div>
                    <h2 class="stat-number"><?= $upcomingCount ?></h2>
                    <p class="stat-label">Upcoming Tours</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 stat-card">
            <div class="card card-gradient-gold">
                <div class="card-body">
                    <div class="stat-icon" style="background:rgba(255,255,255,0.2);">✅</div>
                    <h2 class="stat-number"><?= $completedCount ?></h2>
                    <p class="stat-label">Completed Tours</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 stat-card">
            <div class="card card-gradient-sunset">
                <div class="card-body">
                    <div class="stat-icon" style="background:rgba(255,255,255,0.2);">🌟</div>
                    <h2 class="stat-number"><?= htmlspecialchars(ucfirst($guide['availability'] ?? 'N/A')) ?></h2>
                    <p class="stat-label">Availability</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-8">
            <div class="card content-card">
                <div class="card-header">📋 My Tour Assignments</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead><tr><th>Ref</th><th>Package</th><th>Destination</th><th>Date</th><th>Tourist</th><th>Group</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php if (empty($assignments)): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No assignments yet.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($assignments as $a): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($a['booking_reference']) ?></strong></td>
                                    <td><?= htmlspecialchars($a['package_name']) ?></td>
                                    <td><?= htmlspecialchars($a['destination']) ?></td>
                                    <td><?= $a['booking_date'] ?></td>
                                    <td><?= htmlspecialchars($a['tourist_name']) ?></td>
                                    <td><?= $a['participants'] ?> pax</td>
                                    <td>
                                        <span class="badge badge-status bg-<?= $a['status'] === 'assigned' ? 'primary' : ($a['status'] === 'completed' ? 'success' : 'danger') ?>">
                                            <?= $a['status'] ?>
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
                <div class="card-header">👤 My Profile</div>
                <div class="card-body">
                    <div class="profile-section">
                        <div class="profile-avatar"><?= strtoupper(substr($guide['full_name'] ?? $_SESSION['username'], 0, 1)) ?></div>
                        <h5 class="fw-bold"><?= htmlspecialchars($guide['full_name'] ?? '-') ?></h5>
                    </div>
                    <hr>
                    <div class="profile-detail"><span class="label">Languages</span><span class="value"><?= htmlspecialchars($guide['languages'] ?? '-') ?></span></div>
                    <div class="profile-detail"><span class="label">Specialization</span><span class="value"><?= htmlspecialchars($guide['specialization'] ?? '-') ?></span></div>
                    <div class="profile-detail"><span class="label">Contact</span><span class="value"><?= htmlspecialchars($guide['contact_number'] ?? '-') ?></span></div>
                    <div class="profile-detail">
                        <span class="label">Availability</span>
                        <span class="badge bg-<?= ($guide['availability'] ?? '') === 'available' ? 'success' : 'warning' ?>">
                            <?= $guide['availability'] ?? 'N/A' ?>
                        </span>
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
