<?php
require_once '../includes/auth.php';
requireRole('guide');

$guide = getGuideProfile();
$guideId = $guide['id'] ?? 0;

$myAssignments = $pdo->prepare("SELECT ga.*, b.booking_reference, b.booking_date, b.participants, p.package_name, p.destination, t.full_name as tourist_name FROM guide_assignments ga JOIN bookings b ON ga.booking_id = b.id JOIN tour_packages p ON b.package_id = p.id JOIN tourists t ON b.tourist_id = t.id WHERE ga.guide_id = ? ORDER BY ga.assignment_date DESC");
$myAssignments->execute([$guideId]);
$assignments = $myAssignments->fetchAll();

$nextTour = $pdo->prepare("SELECT ga.*, b.booking_reference, p.package_name, p.destination, b.booking_date FROM guide_assignments ga JOIN bookings b ON ga.booking_id = b.id JOIN tour_packages p ON b.package_id = p.id WHERE ga.guide_id = ? AND ga.status = 'assigned' AND b.booking_date >= CURDATE() ORDER BY b.booking_date ASC LIMIT 1");
$nextTour->execute([$guideId]);
$next = $nextTour->fetch();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2>Guide Dashboard</h2>
            <p>Welcome, <?= htmlspecialchars($guide['full_name'] ?? $_SESSION['username']) ?></p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card text-white bg-primary"><div class="card-body"><h5><?= count($assignments) ?></h5><p>Total Assignments</p></div></div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-white bg-success"><div class="card-body"><h5><?= $guide['availability'] ?? 'N/A' ?></h5><p>Status</p></div></div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-white bg-info"><div class="card-body"><h5><?= $guide['languages'] ?? '-' ?></h5><p>Languages</p></div></div>
        </div>
    </div>
    <?php if ($next): ?>
    <div class="alert alert-info">
        <strong>Next Tour:</strong> <?= htmlspecialchars($next['package_name']) ?> - <?= htmlspecialchars($next['destination']) ?> on <?= $next['booking_date'] ?> (Ref: <?= htmlspecialchars($next['booking_reference']) ?>)
    </div>
    <?php endif; ?>
    <div class="card mt-3">
        <div class="card-header">My Tour Assignments</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>Ref</th><th>Package</th><th>Destination</th><th>Date</th><th>Tourist</th><th>Participants</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if (empty($assignments)): ?>
                        <tr><td colspan="7" class="text-muted">No assignments yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($assignments as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['booking_reference']) ?></td>
                            <td><?= htmlspecialchars($a['package_name']) ?></td>
                            <td><?= htmlspecialchars($a['destination']) ?></td>
                            <td><?= $a['booking_date'] ?></td>
                            <td><?= htmlspecialchars($a['tourist_name']) ?></td>
                            <td><?= $a['participants'] ?></td>
                            <td><span class="badge bg-<?= $a['status'] === 'assigned' ? 'primary' : ($a['status'] === 'completed' ? 'success' : 'danger') ?>"><?= $a['status'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card mt-3">
        <div class="card-header">My Profile</div>
        <div class="card-body">
            <p><strong>Name:</strong> <?= htmlspecialchars($guide['full_name'] ?? '-') ?></p>
            <p><strong>Languages:</strong> <?= htmlspecialchars($guide['languages'] ?? '-') ?></p>
            <p><strong>Specialization:</strong> <?= htmlspecialchars($guide['specialization'] ?? '-') ?></p>
            <p><strong>Contact:</strong> <?= htmlspecialchars($guide['contact_number'] ?? '-') ?></p>
            <p><strong>Availability:</strong> <span class="badge bg-<?= ($guide['availability'] ?? '') === 'available' ? 'success' : 'warning' ?>"><?= $guide['availability'] ?? 'N/A' ?></span></p>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
