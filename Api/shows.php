<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'get_shows') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM shows ORDER BY date ASC");
        $stmt->execute();
        $shows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'shows' => $shows
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action'
    ]);
}

exit;