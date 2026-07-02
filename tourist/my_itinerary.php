<?php
require_once '../includes/auth.php';
requireRole('tourist');

$tourist = getTouristProfile();
$bookingId = $_GET['booking'] ?? 0;

$booking = $pdo->prepare("SELECT b.*, p.package_name, p.destination, p.duration_days FROM bookings b JOIN tour_packages p ON b.package_id = p.id WHERE b.id = ? AND b.tourist_id = ?");
$booking->execute([$bookingId, $tourist['id']]);
$bk = $booking->fetch();

if (!$bk) {
    header('Location: my_bookings.php');
    exit();
}

$itinerary = $pdo->prepare("SELECT * FROM itineraries WHERE package_id = ? ORDER BY day_number");
$itinerary->execute([$bk['package_id']]);
$items = $itinerary->fetchAll();

$assignments = $pdo->prepare("SELECT g.full_name, g.languages, g.specialization, g.contact_number FROM guide_assignments ga JOIN guides g ON ga.guide_id = g.id WHERE ga.booking_id = ? AND ga.status = 'assigned'");
$assignments->execute([$bookingId]);
$guides = $assignments->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="my_bookings.php">My Bookings</a></li>
            <li class="breadcrumb-item active">Itinerary</li>
        </ol>
    </nav>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><?= htmlspecialchars($bk['package_name']) ?></h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Destination:</strong> <?= htmlspecialchars($bk['destination']) ?></p>
                    <p><strong>Duration:</strong> <?= $bk['duration_days'] ?> Days</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Booking Reference:</strong> <?= htmlspecialchars($bk['booking_reference']) ?></p>
                    <p><strong>Travel Date:</strong> <?= $bk['booking_date'] ?></p>
                </div>
            </div>
        </div>
    </div>

    <h4>Itinerary</h4>
    <?php if (empty($items)): ?>
    <div class="alert alert-info">Itinerary details are being prepared. Please check back later.</div>
    <?php else: ?>
    <div class="timeline">
        <?php foreach ($items as $i): ?>
        <div class="card mb-3">
            <div class="card-header">
                <strong>Day <?= $i['day_number'] ?></strong>
                <?php if ($i['timing']): ?><span class="text-muted ms-3"><?= htmlspecialchars($i['timing']) ?></span><?php endif; ?>
            </div>
            <div class="card-body">
                <p><?= htmlspecialchars($i['activity']) ?></p>
                <?php if ($i['location']): ?><p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($i['location']) ?></p><?php endif; ?>
                <?php if ($i['notes']): ?><small class="text-muted"><?= htmlspecialchars($i['notes']) ?></small><?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($guides)): ?>
    <h4 class="mt-4">Your Tour Guides</h4>
    <div class="row">
        <?php foreach ($guides as $g): ?>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5><?= htmlspecialchars($g['full_name']) ?></h5>
                    <p><strong>Languages:</strong> <?= htmlspecialchars($g['languages'] ?? '-') ?></p>
                    <p><strong>Specialization:</strong> <?= htmlspecialchars($g['specialization'] ?? '-') ?></p>
                    <p><strong>Contact:</strong> <?= htmlspecialchars($g['contact_number'] ?? '-') ?></p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
