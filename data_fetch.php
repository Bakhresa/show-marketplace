<?php
function get_users($pdo) {
    $stmt = $pdo->prepare("SELECT id, name, email, phone FROM users WHERE is_admin = 0 ORDER BY name ASC");
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_user_bookings($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT id, show_id, tickets, total_price, status, created_at, show_title, show_date, show_venue 
                           FROM bookings 
                           WHERE user_id = ? 
                           ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function get_user_transactions($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT t.id, t.booking_id, t.amount, t.mpesa_receipt_number, t.phone_number, t.transaction_date, t.status 
                           FROM transactions t 
                           WHERE t.user_id = ? 
                           ORDER BY t.transaction_date DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function get_shows($pdo) {
    $stmt = $pdo->prepare("SELECT s.* FROM shows s ORDER BY s.date ASC");
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_bookings($pdo) {
    $stmt = $pdo->prepare("SELECT b.id, b.user_id, b.show_id, b.tickets, b.total_price, b.status, b.created_at, b.show_title AS show_title, u.name AS user_name 
                           FROM bookings b 
                           JOIN users u ON b.user_id = u.id 
                           ORDER BY b.created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll();
}
?>