<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
$action = $_GET['action'] ?? '';

if ($action === 'get_unread_count') {
    if ($is_admin) {
        // For admin: Get unread message count for each user
        $stmt = $pdo->prepare("SELECT m.sender_id, u.name, COUNT(m.id) as unread_count 
                               FROM messages m 
                               JOIN users u ON m.sender_id = u.id 
                               WHERE m.sender_type = 'user' AND m.receiver_id = ? AND m.receiver_type = 'admin' AND m.is_read = 0 
                               GROUP BY m.sender_id");
        $stmt->execute([$user_id]);
        $unread_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['unread_counts' => $unread_counts]);
    } else {
        // For user: Get unread message count from admin
        $admin_id = $pdo->query("SELECT id FROM users WHERE is_admin = 1 LIMIT 1")->fetchColumn();
        if ($admin_id) {
            $stmt = $pdo->prepare("SELECT COUNT(id) as unread_count 
                                   FROM messages 
                                   WHERE sender_id = ? AND sender_type = 'admin' AND receiver_id = ? AND receiver_type = 'user' AND is_read = 0");
            $stmt->execute([$admin_id, $user_id]);
            $unread_count = $stmt->fetchColumn();
            echo json_encode(['unread_count' => (int)$unread_count]);
        } else {
            echo json_encode(['unread_count' => 0]);
        }
    }
} elseif ($action === 'get_messages') {
    if ($is_admin) {
        $selected_user_id = (int)($_GET['user_id'] ?? 0);
        if ($selected_user_id) {
            $stmt = $pdo->prepare("SELECT m.*, u.name AS sender_name 
                                   FROM messages m 
                                   LEFT JOIN users u ON m.sender_id = u.id AND m.sender_type = 'user' 
                                   WHERE (m.sender_id = ? AND m.sender_type = 'user' AND m.receiver_id = ? AND m.receiver_type = 'admin') 
                                      OR (m.sender_id = ? AND m.sender_type = 'admin' AND m.receiver_id = ? AND m.receiver_type = 'user') 
                                   ORDER BY m.created_at ASC");
            $stmt->execute([$selected_user_id, $user_id, $user_id, $selected_user_id]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Mark messages from user as read
            $stmt = $pdo->prepare("UPDATE messages 
                                   SET is_read = 1 
                                   WHERE sender_id = ? AND sender_type = 'user' AND receiver_id = ? AND receiver_type = 'admin' AND is_read = 0");
            $stmt->execute([$selected_user_id, $user_id]);

            echo json_encode(['messages' => $messages]);
        } else {
            echo json_encode(['messages' => []]);
        }
    } else {
        $admin_id = $pdo->query("SELECT id FROM users WHERE is_admin = 1 LIMIT 1")->fetchColumn();
        if ($admin_id) {
            $stmt = $pdo->prepare("SELECT m.*, u.name AS sender_name 
                                   FROM messages m 
                                   LEFT JOIN users u ON m.sender_id = u.id AND m.sender_type = 'user' 
                                   WHERE (m.sender_id = ? AND m.sender_type = 'user' AND m.receiver_id = ? AND m.receiver_type = 'admin') 
                                      OR (m.sender_id = ? AND m.sender_type = 'admin' AND m.receiver_id = ? AND m.receiver_type = 'user') 
                                   ORDER BY m.created_at ASC");
            $stmt->execute([$user_id, $admin_id, $admin_id, $user_id]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Mark messages from admin as read
            $stmt = $pdo->prepare("UPDATE messages 
                                   SET is_read = 1 
                                   WHERE sender_id = ? AND sender_type = 'admin' AND receiver_id = ? AND receiver_type = 'user' AND is_read = 0");
            $stmt->execute([$admin_id, $user_id]);

            echo json_encode(['messages' => $messages]);
        } else {
            echo json_encode(['messages' => []]);
        }
    }
} else {
    echo json_encode(['error' => 'Invalid action']);
}
?>