<?php require_once 'includes/auth.php'; ?>
<?php if (isLoggedIn()): ?>
    <?php header('Location: ' . $_SESSION['user_role'] . '/dashboard.php'); exit(); ?>
<?php endif; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tourism Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<section class="hero-section text-white text-center d-flex align-items-center">
    <div class="container">
        <h1 class="display-4 fw-bold">Welcome to Tourism Management System</h1>
        <p class="lead mt-3">Explore amazing destinations, book tour packages, and manage your travel experiences all in one place.</p>
        <div class="mt-4">
            <a href="register.php" class="btn btn-light btn-lg me-3">Get Started</a>
            <a href="login.php" class="btn btn-outline-light btn-lg">Login</a>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Our Tour Packages</h2>
        <div class="row">
            <?php
            $stmt = $pdo->query("SELECT * FROM tour_packages WHERE status = 'active' LIMIT 6");
            while ($pkg = $stmt->fetch()):
            ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($pkg['package_name']) ?></h5>
                        <h6 class="text-muted"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($pkg['destination']) ?></h6>
                        <p class="card-text"><?= htmlspecialchars(substr($pkg['description'], 0, 100)) ?>...</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-primary"><?= $pkg['duration_days'] ?> Days</span>
                            <span class="fw-bold text-success">$<?= number_format($pkg['price'], 2) ?></span>
                        </div>
                        <a href="login.php" class="btn btn-outline-primary w-100 mt-3">Book Now</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<section class="bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-5">Why Choose Us?</h2>
        <div class="row g-4">
            <div class="col-md-4 text-center">
                <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                <h5>Secure Booking</h5>
                <p>Your payments and personal data are protected with industry-standard security.</p>
            </div>
            <div class="col-md-4 text-center">
                <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                <h5>24/7 Support</h5>
                <p>Our team is always available to assist you before, during, and after your trip.</p>
            </div>
            <div class="col-md-4 text-center">
                <i class="fas fa-map-marked-alt fa-3x text-primary mb-3"></i>
                <h5>Expert Guides</h5>
                <p>Professional local guides with deep knowledge of every destination.</p>
            </div>
        </div>
    </div>
</section>

<footer class="bg-dark text-white text-center py-3">
    <p class="mb-0">&copy; <?= date('Y') ?> Tourism Management System. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>
