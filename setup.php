<?php
require_once __DIR__ . '/config/database.php';

$output = [];
$output[] = "<h2>Tourism System - Full Setup (PostgreSQL)</h2>";

$tables = [
    "CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone VARCHAR(20),
        role VARCHAR(20) NOT NULL DEFAULT 'tourist' CHECK (role IN ('admin','agent','guide','tourist')),
        status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive','locked')),
        login_attempts INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS tourists (
        id SERIAL PRIMARY KEY,
        user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        full_name VARCHAR(100) NOT NULL,
        nationality VARCHAR(50),
        date_of_birth DATE,
        passport_number VARCHAR(50),
        emergency_contact VARCHAR(100),
        emergency_phone VARCHAR(20),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS tour_packages (
        id SERIAL PRIMARY KEY,
        package_name VARCHAR(150) NOT NULL,
        destination VARCHAR(100) NOT NULL,
        duration_days INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        max_capacity INT NOT NULL,
        description TEXT,
        inclusions TEXT,
        image_url VARCHAR(500) DEFAULT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive')),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS bookings (
        id SERIAL PRIMARY KEY,
        booking_reference VARCHAR(20) UNIQUE NOT NULL,
        tourist_id INT NOT NULL REFERENCES tourists(id) ON DELETE CASCADE,
        package_id INT NOT NULL REFERENCES tour_packages(id) ON DELETE CASCADE,
        booking_date DATE NOT NULL,
        participants INT NOT NULL,
        total_cost DECIMAL(10,2) NOT NULL,
        payment_status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (payment_status IN ('pending','partial','paid','cancelled')),
        status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('confirmed','pending','cancelled','completed')),
        special_requests TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS payments (
        id SERIAL PRIMARY KEY,
        booking_id INT NOT NULL REFERENCES bookings(id) ON DELETE CASCADE,
        amount_paid DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(20) NOT NULL CHECK (payment_method IN ('cash','online','bank_transfer','mpesa')),
        transaction_reference VARCHAR(100),
        payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS guides (
        id SERIAL PRIMARY KEY,
        user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        full_name VARCHAR(100) NOT NULL,
        languages TEXT,
        specialization VARCHAR(200),
        contact_number VARCHAR(20),
        availability VARCHAR(20) NOT NULL DEFAULT 'available' CHECK (availability IN ('available','occupied','unavailable')),
        rating DECIMAL(2,1) DEFAULT 0.0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS guide_assignments (
        id SERIAL PRIMARY KEY,
        guide_id INT NOT NULL REFERENCES guides(id) ON DELETE CASCADE,
        booking_id INT NOT NULL REFERENCES bookings(id) ON DELETE CASCADE,
        assignment_date DATE NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'assigned' CHECK (status IN ('assigned','completed','cancelled')),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS accommodations (
        id SERIAL PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        location VARCHAR(200) NOT NULL,
        contact_phone VARCHAR(20),
        contact_email VARCHAR(100),
        room_capacity INT NOT NULL,
        price_per_night DECIMAL(10,2) NOT NULL,
        description TEXT,
        status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive')),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS package_accommodations (
        id SERIAL PRIMARY KEY,
        package_id INT NOT NULL REFERENCES tour_packages(id) ON DELETE CASCADE,
        accommodation_id INT NOT NULL REFERENCES accommodations(id) ON DELETE CASCADE,
        nights INT NOT NULL DEFAULT 1
    )",
    "CREATE TABLE IF NOT EXISTS itineraries (
        id SERIAL PRIMARY KEY,
        package_id INT NOT NULL REFERENCES tour_packages(id) ON DELETE CASCADE,
        day_number INT NOT NULL,
        activity TEXT NOT NULL,
        location VARCHAR(200),
        timing VARCHAR(100),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS notifications (
        id SERIAL PRIMARY KEY,
        user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        type VARCHAR(50) NOT NULL,
        subject VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        delivery_status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (delivery_status IN ('sent','failed','pending'))
    )"
];

foreach ($tables as $sql) {
    try {
        $pdo->exec($sql);
        preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/', $sql, $m);
        $output[] = "✅ {$m[1]} table ready";
    } catch (Exception $e) {
        $output[] = "⚠️ " . $e->getMessage();
    }
}

$adminCheck = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
if ($adminCheck == 0) {
    $hash = password_hash('admin123', PASSWORD_BCRYPT);
    $pdo->prepare("INSERT INTO users (username, password, email, phone, role, status) VALUES (?, ?, ?, ?, 'admin', 'active') ON CONFLICT DO NOTHING")->execute(['admin', $hash, 'admin@tourism.com', '+255700000000']);
    $output[] = "✅ admin user created (admin / admin123)";
} else {
    $output[] = "✅ admin user already exists";
}

$existingPackages = $pdo->query("SELECT COUNT(*) FROM tour_packages")->fetchColumn();
if ($existingPackages == 0) {
    $packages = [
        ['Safari Adventure', 'Serengeti National Park', 5, 1500.00, 20, 'Experience the wild beauty of Serengeti with game drives and camping.', 'Game drives, camping, meals, park fees', 'https://images.unsplash.com/photo-60XLoOgwkfA?q=80&w=800&h=500&fit=crop'],
        ['Beach Holiday', 'Zanzibar', 7, 2000.00, 30, 'Relax on the pristine beaches of Zanzibar with water sports.', 'Hotel, meals, water sports, snorkeling', null],
        ['Mountain Trek', 'Mount Kilimanjaro', 8, 2500.00, 15, 'Conquer the highest peak in Africa with expert guides.', 'Guide, permits, camping equipment, meals', null],
        ['Cultural Tour', 'Arusha', 4, 800.00, 25, 'Explore local cultures, markets, and traditional villages.', 'Transport, guide, meals, cultural fees', null],
        ['Lake Excursion', 'Lake Victoria', 3, 600.00, 30, 'Boat rides, fishing, and bird watching at Lake Victoria.', 'Boat ride, meals, guide, fishing equipment', null],
        ['Serengeti Safari', 'Tanzania', 7, 2500.00, 20, 'Experience the awe-inspiring Serengeti National Park with its vast savannahs, abundant wildlife including the Big Five, and the Great Migration.', 'Game drives, park fees, guide, accommodation, meals, airport transfers', 'https://images.unsplash.com/photo-60XLoOgwkfA?q=80&w=800&h=500&fit=crop'],
        ['Ngorongoro Crater', 'Tanzania', 4, 1800.00, 15, 'Descend into the world-famous Ngorongoro Crater, a UNESCO World Heritage Site.', 'Crater fees, guide, 4x4 transport, accommodation, meals, park fees', 'https://images.pexels.com/photos/30630770/pexels-photo-30630770.jpeg?auto=compress&cs=tinysrgb&w=800&h=500&fit=crop'],
        ['Lake Manyara', 'Tanzania', 3, 1500.00, 25, 'Explore the breathtaking Lake Manyara National Park, famous for its tree-climbing lions.', 'Park fees, guide, boat safari, accommodation, meals, transport', 'https://images.unsplash.com/photo-eCT0UCyFhjA?q=80&w=800&h=500&fit=crop'],
    ];
    $stmt = $pdo->prepare("INSERT INTO tour_packages (package_name, destination, duration_days, price, max_capacity, description, inclusions, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($packages as $p) {
        $stmt->execute($p);
    }
    $output[] = "✅ 8 packages created with HD images";
} else {
    $output[] = "✅ $existingPackages packages already exist";
}

$output[] = "<hr><strong>Setup complete!</strong>";
$output[] = "<p><a href='admin/manage_packages.php'>Manage Packages</a> | <a href='register.php'>Register</a></p>";
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Setup</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><div class="container mt-4"><div class="card"><div class="card-body"><?= implode("<br>", $output) ?></div></div></div></body>
</html>
