<?php
require_once 'config/database.php';

echo "<h2>🦁 Package Image Setup</h2>";

try {
    $pdo->exec("ALTER TABLE tour_packages ADD COLUMN image_url VARCHAR(500) DEFAULT NULL AFTER inclusions");
    echo "✅ Column 'image_url' added successfully!<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "✅ Column 'image_url' already exists.<br>";
    } else {
        echo "⚠️ Could not add column: " . $e->getMessage() . "<br>";
    }
}

$images = [
    1 => ['url' => 'https://images.unsplash.com/photo-1504006833117-8886a355efbf?q=80&w=800&h=500&fit=crop', 'name' => 'Safari Adventure'],
    2 => ['url' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?q=80&w=800&h=500&fit=crop', 'name' => 'Beach Holiday'],
    3 => ['url' => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?q=80&w=800&h=500&fit=crop', 'name' => 'Mountain Trek'],
    4 => ['url' => 'https://images.unsplash.com/photo-1516426122078-c23e76319801?q=80&w=800&h=500&fit=crop', 'name' => 'Cultural Tour'],
    5 => ['url' => 'https://images.unsplash.com/photo-1501785888041-af3ef285b470?q=80&w=800&h=500&fit=crop', 'name' => 'Lake Excursion'],
];

$stmt = $pdo->prepare("UPDATE tour_packages SET image_url = ? WHERE id = ?");
$count = 0;
foreach ($images as $id => $img) {
    $stmt->execute([$img['url'], $id]);
    if ($stmt->rowCount()) {
        echo "✅ Set image for: {$img['name']}<br>";
        $count++;
    }
}
echo "<br>📸 <strong>$count packages updated with animal photos!</strong><br>";

$all = $pdo->query("SELECT id, package_name, image_url FROM tour_packages");
echo "<br><table border='1' cellpadding='8' style='border-collapse:collapse;'>";
echo "<tr><th>ID</th><th>Package</th><th>Image URL</th></tr>";
foreach ($all as $row) {
    $url = $row['image_url'] ? '<a href="'.htmlspecialchars($row['image_url']).'" target="_blank">View Image</a>' : '<span style="color:red">MISSING</span>';
    echo "<tr><td>{$row['id']}</td><td>{$row['package_name']}</td><td>$url</td></tr>";
}
echo "</table>";

echo "<br><br><a href='tourist/packages.php'>👉 View Packages Page</a>";
