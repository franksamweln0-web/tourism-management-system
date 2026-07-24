<?php
require_once __DIR__ . '/config/database.php';

$added = 0;
$skipped = 0;
$errors = [];

$packages = [
    [
        'package_name' => 'Serengeti Safari',
        'destination'  => 'Tanzania',
        'duration_days' => 7,
        'price'        => 2500.00,
        'max_capacity' => 20,
        'description'  => 'Experience the awe-inspiring Serengeti National Park with its vast savannahs, abundant wildlife including the Big Five, and the Great Migration. Enjoy game drives, guided walks, and luxury tented accommodations under the African stars.',
        'inclusions'   => 'Game drives, park fees, guide, accommodation, meals, airport transfers',
        'image_url'    => 'https://images.unsplash.com/photo-60XLoOgwkfA?q=80&w=800&h=500&fit=crop',
    ],
    [
        'package_name' => 'Ngorongoro Crater',
        'destination'  => 'Tanzania',
        'duration_days' => 4,
        'price'        => 1800.00,
        'max_capacity' => 15,
        'description'  => 'Descend into the world-famous Ngorongoro Crater, a UNESCO World Heritage Site and one of Africa\'s most stunning natural wonders. Spot dense wildlife concentrations including black rhinos, lions, and elephants in this unique caldera ecosystem.',
        'inclusions'   => 'Crater fees, guide, 4x4 transport, accommodation, meals, park fees',
        'image_url'    => 'https://images.pexels.com/photos/30630770/pexels-photo-30630770.jpeg?auto=compress&cs=tinysrgb&w=800&h=500&fit=crop',
    ],
    [
        'package_name' => 'Lake Manyara',
        'destination'  => 'Tanzania',
        'duration_days' => 3,
        'price'        => 1500.00,
        'max_capacity' => 25,
        'description'  => 'Explore the breathtaking Lake Manyara National Park, famous for its tree-climbing lions, vast flocks of flamingos, and diverse birdlife. Enjoy game drives along the lake shore and through groundwater forests with stunning Rift Valley views.',
        'inclusions'   => 'Park fees, guide, boat safari, accommodation, meals, transport',
        'image_url'    => 'https://images.unsplash.com/photo-eCT0UCyFhjA?q=80&w=800&h=500&fit=crop',
    ],
];

$check = $pdo->prepare("SELECT COUNT(*) FROM tour_packages WHERE package_name = ?");

foreach ($packages as $pkg) {
    $check->execute([$pkg['package_name']]);
    if ($check->fetchColumn() > 0) {
        $skipped++;
        continue;
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO tour_packages (package_name, destination, duration_days, price, max_capacity, description, inclusions, image_url, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
        $stmt->execute([
            $pkg['package_name'],
            $pkg['destination'],
            $pkg['duration_days'],
            $pkg['price'],
            $pkg['max_capacity'],
            $pkg['description'],
            $pkg['inclusions'],
            $pkg['image_url'],
        ]);
        $added++;
    } catch (Exception $e) {
        $errors[] = $pkg['package_name'] . ': ' . $e->getMessage();
    }
}

$total = count($packages);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seed Packages - Tourism System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-body">
            <h3 class="card-title">Package Seeding Results</h3>
            <ul class="list-group mt-3">
                <li class="list-group-item list-group-item-success">Packages added: <strong><?= $added ?></strong></li>
                <li class="list-group-item list-group-item-warning">Packages skipped (already exist): <strong><?= $skipped ?></strong></li>
                <li class="list-group-item list-group-item-info">Total processed: <strong><?= $total ?></strong></li>
            </ul>
            <?php if ($errors): ?>
                <div class="alert alert-danger mt-3">
                    <strong>Errors:</strong>
                    <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>
            <?php if ($added > 0): ?>
                <div class="alert alert-success mt-3">
                    Successfully added <?= $added ?> new package(s).
                </div>
            <?php elseif ($skipped === $total): ?>
                <div class="alert alert-info mt-3">All packages already exist in the database.</div>
            <?php endif; ?>
            <p><a href="admin/manage_packages.php" class="btn btn-primary">Manage Packages</a></p>
        </div>
    </div>
</div>
</body>
</html>
