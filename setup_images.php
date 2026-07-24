<?php
require_once 'config/database.php';

try {
    $pdo->exec("ALTER TABLE tour_packages ADD COLUMN IF NOT EXISTS image_url VARCHAR(500) DEFAULT NULL AFTER inclusions");
    echo "Column added or already exists.<br>";

    $images = [
        1 => 'https://images.unsplash.com/photo-1504006833117-8886a355efbf?q=80&w=800&h=500&fit=crop',
        2 => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?q=80&w=800&h=500&fit=crop',
        3 => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?q=80&w=800&h=500&fit=crop',
        4 => 'https://images.unsplash.com/photo-1516426122078-c23e76319801?q=80&w=800&h=500&fit=crop',
        5 => 'https://images.unsplash.com/photo-1501785888041-af3ef285b470?q=80&w=800&h=500&fit=crop',
    ];

    $stmt = $pdo->prepare("UPDATE tour_packages SET image_url = ? WHERE id = ?");
    foreach ($images as $id => $url) {
        $stmt->execute([$url, $id]);
        echo "Package $id updated.<br>";
    }

    echo "<br>Done! <a href='tourist/packages.php'>View Packages</a>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
