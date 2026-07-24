<?php
session_start();
require_once __DIR__ . '/../config/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['user_role'] !== $role) {
        header('Location: ../index.php');
        exit();
    }
}

function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function getTouristProfile() {
    global $pdo;
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare("SELECT * FROM tourists WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function getGuideProfile() {
    global $pdo;
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare("SELECT * FROM guides WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function generateReference() {
    return 'TMS-' . strtoupper(uniqid());
}

function logNotification($userId, $type, $subject, $message) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, subject, message, delivery_status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$userId, $type, $subject, $message]);
}

function getPackageImage($pkg) {
    $defaults = [
        'Safari Adventure' => 'https://images.unsplash.com/photo-1504006833117-8886a355efbf?q=80&w=800&h=500&fit=crop',
        'Beach Holiday'    => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?q=80&w=800&h=500&fit=crop',
        'Mountain Trek'      => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?q=80&w=800&h=500&fit=crop',
        'Cultural Tour'      => 'https://images.unsplash.com/photo-1516426122078-c23e76319801?q=80&w=800&h=500&fit=crop',
        'Lake Excursion'     => 'https://images.unsplash.com/photo-1501785888041-af3ef285b470?q=80&w=800&h=500&fit=crop',
        'Serengeti Safari'   => 'https://images.unsplash.com/photo-60XLoOgwkfA?q=80&w=800&h=500&fit=crop',
        'Ngorongoro Crater'  => 'https://images.pexels.com/photos/30630770/pexels-photo-30630770.jpeg?auto=compress&cs=tinysrgb&w=800&h=500&fit=crop',
        'Lake Manyara'       => 'https://images.unsplash.com/photo-eCT0UCyFhjA?q=80&w=800&h=500&fit=crop',
    ];
    if (!empty($pkg['image_url'])) return $pkg['image_url'];
    return $defaults[$pkg['package_name']] ?? 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?q=80&w=800&h=500&fit=crop';
}
