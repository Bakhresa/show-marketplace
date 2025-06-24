<?php
session_start(); // Initialize session
require_once 'includes/config.php';
$body_class = 'graphic-bg'; // Apply the graphic background
$page_title = 'Admin Dashboard - Show Marketplace';

// Ensure PDO is available
if (!isset($pdo) || !$pdo instanceof PDO) {
    die("Database connection failed. Check includes/config.php.");
}

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    error_log("Unauthorized access attempt to admin dashboard. User ID: " . ($_SESSION['user_id'] ?? 'Not set') . ", is_admin: " . ($_SESSION['is_admin'] ?? 'Not set') . " at " . date('Y-m-d H:i:s'));
    header("Location: login.php?error=Please login as an admin to access this page.");
    exit;
}

$admin_id = (int)$_SESSION['user_id'];

// Handle adding a new show
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_show'])) {
    $title = trim($_POST['title'] ?? '');
    $genre = trim($_POST['genre'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $venue = trim($_POST['venue'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');

    // Validation
    if (empty($title)) {
        $error = "Show title is required.";
    } elseif (empty($genre)) {
        $error = "Genre is required.";
    } elseif (empty($date)) {
        $error = "Date is required.";
    } elseif (empty($time)) {
        $error = "Time is required.";
    } elseif (empty($venue)) {
        $error = "Venue is required.";
    } elseif ($price <= 0) {
        $error = "Price must be greater than 0.";
    } elseif (empty($description)) {
        $error = "Description is required.";
    } elseif (!empty($image_url) && !filter_var($image_url, FILTER_VALIDATE_URL)) {
        $error = "Invalid image URL format.";
    }

    // If no errors, save the show to the database
    if (!isset($error)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO shows (title, genre, date, time, venue, price, description, image_url, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$title, $genre, $date, $time, $venue, $price, $description, $image_url ?: null, $admin_id])) {
                $success = "Show added successfully!";
            } else {
                $error = "Failed to add show. Please try again.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
            error_log("Add show failed: " . $e->getMessage());
        }
    }
}

// Handle editing a show
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_show'])) {
    error_log("Edit show request received: " . print_r($_POST, true)); // Debug log

    $show_id = (int)($_POST['show_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $genre = trim($_POST['genre'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $venue = trim($_POST['venue'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');

    // Validation
    if (empty($title)) {
        $error = "Show title is required.";
    } elseif (empty($genre)) {
        $error = "Genre is required.";
    } elseif (empty($date)) {
        $error = "Date is required.";
    } elseif (empty($time)) {
        $error = "Time is required.";
    } elseif (empty($venue)) {
        $error = "Venue is required.";
    } elseif ($price <= 0) {
        $error = "Price must be greater than 0.";
    } elseif (empty($description)) {
        $error = "Description is required.";
    } elseif (!empty($image_url) && !filter_var($image_url, FILTER_VALIDATE_URL)) {
        $error = "Invalid image URL format.";
    }

    // If no errors, update the show in the database
    if (!isset($error) && $show_id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE shows SET title = ?, genre = ?, date = ?, time = ?, venue = ?, price = ?, description = ?, image_url = ? WHERE id = ?");
            $result = $stmt->execute([$title, $genre, $date, $time, $venue, $price, $description, $image_url ?: null, $show_id]);
            error_log("Database update result: " . ($result ? "Success" : "Failure")); // Debug log

            if ($result) {
                $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Show updated successfully!',
                        'show' => [
                            'id' => $show_id,
                            'title' => $title,
                            'genre' => $genre,
                            'date' => $date,
                            'time' => $time,
                            'venue' => $venue,
                            'price' => $price,
                            'description' => $description,
                            'image_url' => $image_url
                        ]
                    ]);
                    exit;
                } else {
                    $success = "Show updated successfully!";
                }
            } else {
                $error = "Failed to update show. Please try again.";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $error]);
                    exit;
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
            error_log("Edit show failed: " . $e->getMessage());
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $error]);
                exit;
            }
        }
    } else {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error ?: 'Invalid show ID']);
            exit;
        }
    }
}

// Handle booking deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_booking'])) {
    $booking_id = (int)($_POST['booking_id'] ?? 0);

    try {
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
        if ($stmt->execute([$booking_id])) {
            $success = "Booking deleted successfully.";
        } else {
            $error = "Failed to delete the booking. Please try again.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
        error_log("Delete booking failed: " . $e->getMessage());
    }
}

// Handle show deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_show'])) {
    $show_id = (int)($_POST['show_id'] ?? 0);

    try {
        $stmt = $pdo->prepare("DELETE FROM shows WHERE id = ?");
        if ($stmt->execute([$show_id])) {
            $success = "Show deleted successfully. Existing bookings remain unaffected.";
        } else {
            $error = "Failed to delete the show. Please try again.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
        error_log("Delete show failed: " . $e->getMessage());
    }
}

// Handle sending a message to user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver_id = (int)($_POST['receiver_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if (empty($message)) {
        $admin_chat_error = "Message cannot be empty.";
    } elseif ($receiver_id <= 0) {
        $admin_chat_error = "Invalid receiver ID.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, sender_type, receiver_id, receiver_type, message) 
                                   VALUES (?, 'admin', ?, 'user', ?)");
            if ($stmt->execute([$admin_id, $receiver_id, $message])) {
                $admin_chat_success = "Message sent successfully!";
            } else {
                $admin_chat_error = "Failed to send message. Please try again.";
            }
        } catch (PDOException $e) {
            $admin_chat_error = "Database error: " . $e->getMessage();
            error_log("Send message failed: " . $e->getMessage());
        }
    }
}

// Fetch data (mimicking user_dashboard.php logic via a shared data file)
require_once 'data_fetch.php'; // Assume this file contains functions like get_users(), get_shows(), get_bookings()
$all_users = get_users($pdo) ?: []; // Fallback to empty array if fetch fails
$user_data = [];
foreach ($all_users as $user) {
    $user_id = $user['id'];
    $user_data[$user_id] = [
        'details' => $user,
        'bookings' => get_user_bookings($pdo, $user_id) ?: [],
        'transactions' => get_user_transactions($pdo, $user_id) ?: []
    ];
}
$all_shows = get_shows($pdo) ?: [];
$bookings = get_bookings($pdo) ?: [];

// Data for charts (with fallback if queries fail)
$bookings_per_show_labels = [];
$bookings_per_show_counts = [];
try {
    $bookings_per_show_data = $pdo->query("SELECT b.show_title AS title, COUNT(b.id) AS booking_count 
                                           FROM bookings b 
                                           GROUP BY b.show_title")->fetchAll() ?: [];
    $bookings_per_show_labels = array_column($bookings_per_show_data, 'title');
    $bookings_per_show_counts = array_column($bookings_per_show_data, 'booking_count');
} catch (PDOException $e) {
    error_log("Fetch bookings per show failed: " . $e->getMessage());
}

$revenue_per_show_labels = [];
$revenue_per_show_values = [];
try {
    $revenue_per_show_data = $pdo->query("SELECT b.show_title AS title, SUM(b.total_price) AS total_revenue 
                                          FROM bookings b 
                                          GROUP BY b.show_title")->fetchAll() ?: [];
    $revenue_per_show_labels = array_column($revenue_per_show_data, 'title');
    $revenue_per_show_values = array_column($revenue_per_show_data, 'total_revenue');
} catch (PDOException $e) {
    error_log("Fetch revenue per show failed: " . $e->getMessage());
}

$status_distribution_labels = [];
$status_distribution_counts = [];
try {
    $status_distribution_data = $pdo->query("SELECT status, COUNT(id) AS count 
                                             FROM bookings 
                                             GROUP BY status")->fetchAll() ?: [];
    $status_distribution_labels = array_column($status_distribution_data, 'status');
    $status_distribution_counts = array_column($status_distribution_data, 'count');
} catch (PDOException $e) {
    error_log("Fetch status distribution failed: " . $e->getMessage());
}

// Fetch users who have sent messages to the admin
$chat_users = [];
try {
    $stmt = $pdo->prepare("SELECT DISTINCT u.id, u.name 
                           FROM messages m 
                           JOIN users u ON m.sender_id = u.id 
                           WHERE m.sender_type = 'user' AND m.receiver_id = ? AND m.receiver_type = 'admin'");
    $stmt->execute([$admin_id]);
    $chat_users = $stmt->fetchAll() ?: [];
} catch (PDOException $e) {
    error_log("Fetch chat users failed: " . $e->getMessage());
}

// Fetch initial unread counts for each user
$unread_counts = [];
try {
    $stmt = $pdo->prepare("SELECT m.sender_id, COUNT(m.id) as unread_count 
                           FROM messages m 
                           WHERE m.sender_type = 'user' AND m.receiver_id = ? AND m.receiver_type = 'admin' AND m.is_read = 0 
                           GROUP BY m.sender_id");
    $stmt->execute([$admin_id]);
    $unread_counts_result = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($unread_counts_result as $row) {
        $unread_counts[$row['sender_id']] = $row['unread_count'];
    }
} catch (PDOException $e) {
    error_log("Fetch unread counts failed: " . $e->getMessage());
}

// Fetch chat messages for the selected user
$selected_user_id = isset($_GET['chat_user_id']) ? (int)$_GET['chat_user_id'] : (isset($chat_users[0]['id']) ? $chat_users[0]['id'] : 0);
$chat_messages = [];
if ($selected_user_id) {
    try {
        $stmt = $pdo->prepare("SELECT m.*, u.name AS sender_name 
                               FROM messages m 
                               LEFT JOIN users u ON m.sender_id = u.id AND m.sender_type = 'user' 
                               WHERE (m.sender_id = ? AND m.sender_type = 'user' AND m.receiver_id = ? AND m.receiver_type = 'admin') 
                                  OR (m.sender_id = ? AND m.sender_type = 'admin' AND m.receiver_id = ? AND m.receiver_type = 'user') 
                               ORDER BY m.created_at ASC");
        $stmt->execute([$selected_user_id, $admin_id, $admin_id, $selected_user_id]);
        $chat_messages = $stmt->fetchAll() ?: [];
        // Mark messages from user as read
        $stmt = $pdo->prepare("UPDATE messages m 
                               SET is_read = 1 
                               WHERE sender_id = ? AND sender_type = 'user' AND receiver_id = ? AND m.receiver_type = 'admin' AND is_read = 0");
        $stmt->execute([$selected_user_id, $admin_id]);
    } catch (PDOException $e) {
        error_log("Fetch chat messages failed: " . $e->getMessage());
    }
}

// API Endpoints for Chat
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    if ($_GET['action'] === 'get_messages' && isset($_GET['user_id'])) {
        $user_id = (int)$_GET['user_id'];
        $stmt = $pdo->prepare("SELECT m.*, u.name AS sender_name 
                               FROM messages m 
                               LEFT JOIN users u ON m.sender_id = u.id AND m.sender_type = 'user' 
                               WHERE (m.sender_id = ? AND m.sender_type = 'user' AND m.receiver_id = ? AND m.receiver_type = 'admin') 
                                  OR (m.sender_id = ? AND m.sender_type = 'admin' AND m.receiver_id = ? AND m.receiver_type = 'user') 
                               ORDER BY m.created_at ASC");
        $stmt->execute([$user_id, $admin_id, $admin_id, $user_id]);
        $messages = $stmt->fetchAll() ?: [];
        echo json_encode(['success' => true, 'messages' => $messages]);
        exit;
    } elseif ($_GET['action'] === 'get_unread_count') {
        $stmt = $pdo->prepare("SELECT sender_id, COUNT(id) as unread_count 
                               FROM messages 
                               WHERE sender_type = 'user' AND receiver_id = ? AND receiver_type = 'admin' AND is_read = 0 
                               GROUP BY sender_id");
        $stmt->execute([$admin_id]);
        $unread_counts = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        echo json_encode(['success' => true, 'unread_counts' => $unread_counts]);
        exit;
    }
}

include 'includes/header.php';
?>

<style>
/* Ensure the modal body is scrollable and override any conflicting styles */
.modal-scrollable {
    overflow-y: auto !important;
    -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
    max-height: calc(90vh - 136px); /* Subtract header and footer heights (assuming 56px each + padding) */
}

/* Lock the background completely when the modal is open */
body.modal-open {
    position: fixed;
    width: 100%;
    height: 100%;
    overflow: hidden;
}
</style>

<div class="py-6 min-h-screen">
    <h1 class="text-3xl font-bold text-white text-center mb-6">Admin Dashboard</h1>
    <p class="text-gray-200 text-center mb-6">Manage bookings, shows, users, and analyze data.</p>

    <?php if (isset($success)): ?>
        <p class="text-green-500 text-center"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <p class="text-red-500 text-center"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <?php if (isset($admin_chat_success)): ?>
        <p class="text-green-500 text-center"><?php echo htmlspecialchars($admin_chat_success); ?></p>
    <?php endif; ?>
    <?php if (isset($admin_chat_error)): ?>
        <p class="text-red-500 text-center"><?php echo htmlspecialchars($admin_chat_error); ?></p>
    <?php endif; ?>

    <!-- Add Show Form -->
    <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-md mb-8">
        <h2 class="text-2xl font-bold mb-4 text-gray-800">Add New Show</h2>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="add_show" value="1">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Show Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                       class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div>
                <label for="genre" class="block text-sm font-medium text-gray-700 mb-1">Genre</label>
                <input type="text" id="genre" name="genre" value="<?php echo htmlspecialchars($_POST['genre'] ?? ''); ?>" 
                       class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div>
                <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($_POST['date'] ?? ''); ?>" 
                       class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div>
                <label for="time" class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                <input type="time" id="time" name="time" value="<?php echo htmlspecialchars($_POST['time'] ?? ''); ?>" 
                       class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div>
                <label for="venue" class="block text-sm font-medium text-gray-700 mb-1">Venue</label>
                <input type="text" id="venue" name="venue" value="<?php echo htmlspecialchars($_POST['venue'] ?? ''); ?>" 
                       class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div>
                <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price per Ticket ($)</label>
                <input type="number" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" 
                       class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea id="description" name="description" 
                          class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                          rows="3" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>
            <div>
                <label for="image_url" class="block text-sm font-medium text-gray-700 mb-1">Image URL (Optional)</label>
                <input type="url" id="image_url" name="image_url" value="<?php echo htmlspecialchars($_POST['image_url'] ?? ''); ?>" 
                       class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       placeholder="https://example.com/image.jpg">
            </div>
            <button type="submit" class="form-button w-full bg-purple-500 text-white p-3 rounded-lg hover:bg-purple-600 transition">
                Add Show
            </button>
        </form>
    </div>

    <!-- Users Overview Section -->
    <div class="mb-10 max-w-6xl mx-auto">
        <h2 class="text-2xl font-bold text-white mb-4">Users Overview</h2>
        <?php if (empty($all_users)): ?>
            <p class="text-gray-200 text-center">No users found.</p>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($user_data as $user_id => $data): ?>
                    <div class="card bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-xl font-semibold mb-2 text-gray-800"><?php echo htmlspecialchars($data['details']['name'] ?? 'Unknown'); ?></h3>
                        <div class="mb-4">
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($data['details']['email'] ?? 'Not provided'); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($data['details']['phone'] ?? 'Not provided'); ?></p>
                        </div>
                        <h4 class="text-lg font-medium mb-2 text-gray-800">Bookings</h4>
                        <?php if (empty($data['bookings'])): ?>
                            <p class="text-gray-600">No bookings found for this user.</p>
                        <?php else: ?>
                            <div class="overflow-x-auto mb-4">
                                <table class="w-full bg-white border">
                                    <thead>
                                        <tr>
                                            <th class="py-2 px-4 border-b">Booking ID</th>
                                            <th class="py-2 px-4 border-b">Show</th>
                                            <th class="py-2 px-4 border-b">Date</th>
                                            <th class="py-2 px-4 border-b">Venue</th>
                                            <th class="py-2 px-4 border-b">Tickets</th>
                                            <th class="py-2 px-4 border-b">Total Price</th>
                                            <th class="py-2 px-4 border-b">Status</th>
                                            <th class="py-2 px-4 border-b">Booked On</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data['bookings'] as $booking): ?>
                                            <tr>
                                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($booking['id'] ?? 'N/A'); ?></td>
                                                <td class="py-2 px-4 border-b">
                                                    <?php echo htmlspecialchars($booking['show_title'] ?? 'Unknown'); ?>
                                                    <?php if (is_null($booking['show_id'] ?? null)): ?>
                                                        <span class="text-red-500 text-sm">(Deleted)</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($booking['show_date'] ?? 'N/A'); ?></td>
                                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($booking['show_venue'] ?? 'N/A'); ?></td>
                                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($booking['tickets'] ?? 0); ?></td>
                                                <td class="py-2 px-4 border-b">$<?php echo number_format($booking['total_price'] ?? 0, 2); ?></td>
                                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($booking['status'] ?? 'N/A'); ?></td>
                                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($booking['created_at'] ?? 'N/A'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        <h4 class="text-lg font-medium mb-2 text-gray-800">Transactions</h4>
                        <?php if (empty($data['transactions'])): ?>
                            <p class="text-gray-600">No transactions found for this user.</p>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full bg-white border">
                                    <thead>
                                        <tr>
                                            <th class="py-2 px-4 border-b">Transaction ID</th>
                                            <th class="py-2 px-4 border-b">Booking ID</th>
                                            <th class="py-2 px-4 border-b">Amount</th>
                                            <th class="py-2 px-4 border-b">M-Pesa Receipt</th>
                                            <th class="py-2 px-4 border-b">Phone Number</th>
                                            <th class="py-2 px-4 border-b">Date</th>
                                            <th class="py-2 px-4 border-b">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data['transactions'] as $transaction): ?>
                                            <tr>
                                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($transaction['id'] ?? 'N/A'); ?></td>
                                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($transaction['booking_id'] ?? 'N/A'); ?></td>
                                                <td class="py-2 px-4 border-b">$<?php echo number_format($transaction['amount'] ?? 0, 2); ?></td>
                                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($transaction['mpesa_receipt_number'] ?? 'N/A'); ?></td>
                                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($transaction['phone_number'] ?? 'N/A'); ?></td>
                                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($transaction['transaction_date'] ?? 'N/A'); ?></td>
                                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($transaction['status'] ?? 'N/A'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- All Shows Section -->
    <div class="mb-10 max-w-6xl mx-auto">
        <h2 class="text-2xl font-bold text-white mb-4">All Shows</h2>
        <?php if (empty($all_shows)): ?>
            <p class="text-gray-200 text-center">No shows available.</p>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($all_shows as $show): ?>
                    <div id="show-card-<?php echo $show['id']; ?>" class="card bg-white p-6 rounded-lg shadow-md">
                        <?php if (!empty($show['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($show['image_url']); ?>" alt="<?php echo htmlspecialchars($show['title']); ?>" 
                                 class="w-full h-48 object-cover rounded-md mb-4 show-image">
                        <?php endif; ?>
                        <h3 class="text-lg font-semibold text-gray-800 show-title"><?php echo htmlspecialchars($show['title']); ?></h3>
                        <p><strong>Genre:</strong> <span class="show-genre"><?php echo htmlspecialchars($show['genre']); ?></span></p>
                        <p><strong>Date:</strong> <span class="show-date"><?php echo htmlspecialchars($show['date']); ?></span></p>
                        <p><strong>Time:</strong> <span class="show-time"><?php echo htmlspecialchars($show['time']); ?></span></p>
                        <p><strong>Venue:</strong> <span class="show-venue"><?php echo htmlspecialchars($show['venue']); ?></span></p>
                        <p><strong>Price:</strong> $<span class="show-price"><?php echo number_format($show['price'], 2); ?></span> per ticket</p>
                        <p><strong>Description:</strong> <span class="show-description"><?php echo htmlspecialchars($show['description']); ?></span></p>
                        <div class="flex space-x-2 mt-2">
                            <button onclick="openEditModal(<?php echo $show['id']; ?>, '<?php echo addslashes(htmlspecialchars($show['title'])); ?>', '<?php echo addslashes(htmlspecialchars($show['genre'])); ?>', '<?php echo $show['date']; ?>', '<?php echo $show['time']; ?>', '<?php echo addslashes(htmlspecialchars($show['venue'])); ?>', <?php echo $show['price']; ?>, '<?php echo addslashes(htmlspecialchars($show['description'])); ?>', '<?php echo addslashes(htmlspecialchars($show['image_url'] ?? '')); ?>', 'show-card-<?php echo $show['id']; ?>')" 
                                    class="bg-blue-600 text-white p-2 rounded-lg flex-1">
                                Edit Show
                            </button>
                            <form method="POST" class="flex-1" onsubmit="return confirm('Are you sure you want to delete this show? Existing bookings will remain unaffected. This action cannot be undone.');">
                                <input type="hidden" name="show_id" value="<?php echo $show['id']; ?>">
                                <button type="submit" name="delete_show" class="bg-red-600 text-white p-2 rounded-lg w-full">
                                    Delete Show
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Edit Show Modal -->
    <div id="editShowModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 overflow-hidden">
        <div class="bg-white rounded-lg shadow-md w-full max-w-2xl flex flex-col max-h-[90vh]">
            <div class="p-6 border-b flex-shrink-0">
                <h2 class="text-2xl font-bold text-gray-800">Edit Show</h2>
            </div>
            <div class="modal-scrollable flex-1 p-6">
                <form id="editShowForm" method="POST" class="space-y-4">
                    <input type="hidden" name="edit_show" value="1">
                    <input type="hidden" id="edit_show_id" name="show_id">
                    <div>
                        <label for="edit_title" class="block text-sm font-medium text-gray-700 mb-1">Show Title</label>
                        <input type="text" id="edit_title" name="title" 
                               class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label for="edit_genre" class="block text-sm font-medium text-gray-700 mb-1">Genre</label>
                        <input type="text" id="edit_genre" name="genre" 
                               class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label for="edit_date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input type="date" id="edit_date" name="date" 
                               class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label for="edit_time" class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                        <input type="time" id="edit_time" name="time" 
                               class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label for="edit_venue" class="block text-sm font-medium text-gray-700 mb-1">Venue</label>
                        <input type="text" id="edit_venue" name="venue" 
                               class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label for="edit_price" class="block text-sm font-medium text-gray-700 mb-1">Price per Ticket ($)</label>
                        <input type="number" id="edit_price" name="price" step="0.01" 
                               class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label for="edit_description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea id="edit_description" name="description" 
                                  class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                  rows="10" required></textarea>
                    </div>
                    <div>
                        <label for="edit_image_url" class="block text-sm font-medium text-gray-700 mb-1">Image URL (Optional)</label>
                        <input type="url" id="edit_image_url" name="image_url" 
                               class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                               placeholder="https://example.com/image.jpg">
                    </div>
                </form>
            </div>
            <div class="p-6 border-t flex space-x-2 flex-shrink-0">
                <button type="button" onclick="document.getElementById('editShowForm').dispatchEvent(new Event('submit'));" 
                        class="form-button w-full bg-blue-500 text-white p-3 rounded-lg hover:bg-blue-600 transition">
                    Update Show
                </button>
                <button type="button" onclick="closeEditModal()" class="form-button w-full bg-gray-500 text-white p-3 rounded-lg hover:bg-gray-600 transition">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Bookings Table -->
    <div class="mb-10 max-w-6xl mx-auto">
        <h2 class="text-2xl font-bold text-white mb-4">All Bookings</h2>
        <?php if (empty($bookings)): ?>
            <p class="text-gray-200 text-center">No bookings found.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full bg-white border rounded-lg shadow-md">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b">Booking ID</th>
                            <th class="py-2 px-4 border-b">User</th>
                            <th class="py-2 px-4 border-b">Show</th>
                            <th class="py-2 px-4 border-b">Tickets</th>
                            <th class="py-2 px-4 border-b">Total Price</th>
                            <th class="py-2 px-4 border-b">Status</th>
                            <th class="py-2 px-4 border-b">Booked On</th>
                            <th class="py-2 px-4 border-b">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($booking['id'] ?? 'N/A'); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($booking['user_name'] ?? 'Unknown'); ?></td>
                                <td class="py-2 px-4 border-b">
                                    <?php echo htmlspecialchars($booking['show_title'] ?? 'Unknown'); ?>
                                    <?php if (is_null($booking['show_id'] ?? null)): ?>
                                        <span class="text-red-500 text-sm">(Deleted)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($booking['tickets'] ?? 0); ?></td>
                                <td class="py-2 px-4 border-b">$<?php echo number_format($booking['total_price'] ?? 0, 2); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($booking['status'] ?? 'N/A'); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($booking['created_at'] ?? 'N/A'); ?></td>
                                <td class="py-2 px-4 border-b">
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this booking?');">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id'] ?? 0; ?>">
                                        <button type="submit" name="delete_booking" class="bg-red-600 text-white px-3 py-1 rounded">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10 max-w-6xl mx-auto">
        <div class="card bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Bookings per Show</h3>
            <canvas id="bookingsPerShowChart"></canvas>
        </div>
        <div class="card bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Revenue per Show</h3>
            <canvas id="revenuePerShowChart"></canvas>
        </div>
        <div class="card bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Booking Status Distribution</h3>
            <canvas id="statusDistributionChart"></canvas>
        </div>
    </div>

    <!-- Chat Section with Live Updates -->
    <div class="mt-10 max-w-6xl mx-auto">
        <h2 class="text-2xl font-bold text-white mb-4">User Messages</h2>
        <div class="card bg-white p-6 rounded-lg shadow-md">
            <div class="mb-4">
                <h3 class="text-lg font-medium text-gray-800 mb-2">Select a User to Chat</h3>
                <?php if (empty($chat_users)): ?>
                    <p class="text-gray-600">No messages from users yet.</p>
                <?php else: ?>
                    <div id="chat-users-list" class="flex space-x-2 overflow-x-auto">
                        <?php foreach ($chat_users as $chat_user): ?>
                            <a href="admin_dashboard.php?chat_user_id=<?php echo $chat_user['id']; ?>" 
                               class="p-2 bg-blue-100 rounded-lg <?php echo $selected_user_id === $chat_user['id'] ? 'bg-blue-500 text-white' : ''; ?> relative"
                               data-user-id="<?php echo $chat_user['id']; ?>">
                                <?php echo htmlspecialchars($chat_user['name'] ?? 'Unknown'); ?>
                                <?php if (isset($unread_counts[$chat_user['id']]) && $unread_counts[$chat_user['id']] > 0): ?>
                                    <span class="unread-count ml-2 bg-red-500 text-white text-xs font-bold rounded-full px-2 py-1"
                                          data-user-id="<?php echo $chat_user['id']; ?>">
                                        <?php echo $unread_counts[$chat_user['id']]; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($selected_user_id): ?>
                <div id="admin-chat-window" class="border rounded-lg p-4 h-64 overflow-y-auto bg-gray-50 mb-4"></div>
                <form method="POST" id="chat-form">
                    <input type="hidden" name="receiver_id" value="<?php echo $selected_user_id; ?>">
                    <div class="flex space-x-2">
                        <textarea name="message" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                  rows="2" placeholder="Type your message..." required></textarea>
                        <button type="submit" name="send_message" class="bg-blue-600 text-white p-2 rounded-lg">Send</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Bookings per Show Chart (Bar Chart)
    const bookingsPerShowCtx = document.getElementById('bookingsPerShowChart').getContext('2d');
    new Chart(bookingsPerShowCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($bookings_per_show_labels); ?>,
            datasets: [{
                label: 'Number of Bookings',
                data: <?php echo json_encode($bookings_per_show_counts); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Bookings'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Show'
                    }
                }
            }
        }
    });

    // Revenue per Show Chart (Bar Chart)
    const revenuePerShowCtx = document.getElementById('revenuePerShowChart').getContext('2d');
    new Chart(revenuePerShowCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($revenue_per_show_labels); ?>,
            datasets: [{
                label: 'Total Revenue ($)',
                data: <?php echo json_encode($revenue_per_show_values); ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Revenue ($)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Show'
                    }
                }
            }
        }
    });

    // Booking Status Distribution Chart (Pie Chart)
    const statusDistributionCtx = document.getElementById('statusDistributionChart').getContext('2d');
    new Chart(statusDistributionCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($status_distribution_labels); ?>,
            datasets: [{
                label: 'Booking Status',
                data: <?php echo json_encode($status_distribution_counts); ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.6)',  // Pending
                    'rgba(54, 162, 235, 0.6)',  // Paid
                    'rgba(255, 206, 86, 0.6)'   // Failed
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Booking Status Distribution'
                }
            }
        }
    });

    const selectedUserId = <?php echo json_encode($selected_user_id); ?>;
    const chatWindow = document.getElementById('admin-chat-window');
    const chatForm = document.getElementById('chat-form');

    function updateChat() {
        if (!selectedUserId) return;
        fetch(`admin_dashboard.php?action=get_messages&user_id=${selectedUserId}`, {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                chatWindow.innerHTML = data.messages.map(msg => {
                    const alignment = msg.sender_type === 'admin' ? 'text-right' : 'text-left';
                    const bgColor = msg.sender_type === 'admin' ? 'bg-blue-500 text-white' : 'bg-gray-300 text-gray-800';
                    const sender = msg.sender_type === 'admin' ? 'You' : htmlspecialchars(msg.sender_name || 'Unknown');
                    return `<div class="mb-2 ${alignment}"><p class="inline-block p-2 rounded-lg ${bgColor}"><strong>${sender}:</strong> ${htmlspecialchars(msg.message)}</p><p class="text-xs text-gray-500 mt-1">${htmlspecialchars(msg.created_at)}</p></div>`;
                }).join('');
                chatWindow.scrollTop = chatWindow.scrollHeight;
            }
        })
        .catch(error => console.error('Error fetching messages:', error));
    }

    function updateUnreadCounts() {
        fetch('admin_dashboard.php?action=get_unread_count', {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const userList = document.getElementById('chat-users-list');
                data.unread_counts.forEach(count => {
                    const userElement = userList.querySelector(`a[data-user-id="${count.sender_id}"]`);
                    if (userElement) {
                        let badge = userElement.querySelector('.unread-count');
                        if (count.unread_count > 0) {
                            if (!badge) {
                                badge = document.createElement('span');
                                badge.className = 'unread-count ml-2 bg-red-500 text-white text-xs font-bold rounded-full px-2 py-1';
                                badge.setAttribute('data-user-id', count.sender_id);
                                userElement.appendChild(badge);
                            }
                            badge.textContent = count.unread_count;
                        } else if (badge) {
                            badge.remove();
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Error fetching unread counts:', error));
    }

    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('admin_dashboard.php', {
                method: 'POST',
                body: formData,
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    this.querySelector('textarea').value = '';
                    updateChat();
                } else {
                    console.error('Message send failed:', data.message);
                }
            })
            .catch(error => console.error('Error sending message:', error));
        });
    }

    updateChat();
    updateUnreadCounts();
    setInterval(() => { updateChat(); updateUnreadCounts(); }, 5000); // Poll every 5 seconds

    let currentShowCardId = null;

    function openEditModal(id, title, genre, date, time, venue, price, description, image_url, showCardId) {
        currentShowCardId = showCardId;
        document.body.classList.add('modal-open');

        const modal = document.getElementById('editShowModal');
        modal.classList.remove('hidden');

        document.getElementById('edit_show_id').value = id;
        document.getElementById('edit_title').value = title;
        document.getElementById('edit_genre').value = genre;
        document.getElementById('edit_date').value = date;
        document.getElementById('edit_time').value = time;
        document.getElementById('edit_venue').value = venue;
        document.getElementById('edit_price').value = price;
        document.getElementById('edit_description').value = description;
        document.getElementById('edit_image_url').value = image_url;

        const modalBody = document.querySelector('#editShowModal .modal-scrollable');
        modalBody.scrollTop = 0;

        const showCard = document.getElementById(showCardId);
        if (showCard) {
            showCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function closeEditModal() {
        document.body.classList.remove('modal-open');
        document.getElementById('editShowModal').classList.add('hidden');

        if (currentShowCardId) {
            const showCard = document.getElementById(currentShowCardId);
            if (showCard) {
                showCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            currentShowCardId = null;
        }
    }

    document.getElementById('editShowForm').addEventListener('submit', function(event) {
        event.preventDefault();
        console.log("Form submitted");

        const formData = new FormData(this);
        fetch('admin_dashboard.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok: ' + response.statusText);
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const showCard = document.getElementById(currentShowCardId);
                if (showCard) {
                    const show = data.show;
                    const imageElement = showCard.querySelector('.show-image');
                    if (imageElement) {
                        if (show.image_url) {
                            imageElement.src = show.image_url;
                            imageElement.alt = show.title;
                        } else {
                            imageElement.remove();
                        }
                    } else if (show.image_url) {
                        const newImage = document.createElement('img');
                        newImage.src = show.image_url;
                        newImage.alt = show.title;
                        newImage.className = 'w-full h-48 object-cover rounded-md mb-4 show-image';
                        showCard.insertBefore(newImage, showCard.firstChild);
                    }
                    showCard.querySelector('.show-title').textContent = show.title;
                    showCard.querySelector('.show-genre').textContent = show.genre;
                    showCard.querySelector('.show-date').textContent = show.date;
                    showCard.querySelector('.show-time').textContent = show.time;
                    showCard.querySelector('.show-venue').textContent = show.venue;
                    showCard.querySelector('.show-price').textContent = parseFloat(show.price).toFixed(2);
                    showCard.querySelector('.show-description').textContent = show.description;
                    const editButton = showCard.querySelector('button[onclick^="openEditModal"]');
                    if (editButton) {
                        editButton.setAttribute('onclick', `openEditModal(${show.id}, '${show.title.replace(/'/g, "\\'")}', '${show.genre.replace(/'/g, "\\'")}', '${show.date}', '${show.time}', '${show.venue.replace(/'/g, "\\'")}', ${show.price}, '${show.description.replace(/'/g, "\\'")}', '${(show.image_url || '').replace(/'/g, "\\'")}', '${currentShowCardId}')`);
                    }
                }
                const successMessage = document.createElement('p');
                successMessage.className = 'text-green-500 text-center';
                successMessage.textContent = data.message;
                document.querySelector('.py-6').insertBefore(successMessage, document.querySelector('.py-6').firstChild);
                setTimeout(() => successMessage.remove(), 3000);
                closeEditModal();
            } else {
                const errorMessage = document.createElement('p');
                errorMessage.className = 'text-red-500 text-center';
                errorMessage.textContent = data.message || 'Failed to update show.';
                document.querySelector('.py-6').insertBefore(errorMessage, document.querySelector('.py-6').firstChild);
                setTimeout(() => errorMessage.remove(), 3000);
            }
        })
        .catch(error => {
            console.error('Error updating show:', error);
            const errorMessage = document.createElement('p');
            errorMessage.className = 'text-red-500 text-center';
            errorMessage.textContent = 'An error occurred while updating the show: ' + error.message;
            document.querySelector('.py-6').insertBefore(errorMessage, document.querySelector('.py-6').firstChild);
            setTimeout(() => errorMessage.remove(), 3000);
        });
    });

    document.getElementById('editShowModal').addEventListener('click', function(event) {
        if (event.target === this) {
            closeEditModal();
        }
    });

    // Helper function for HTML escaping in JavaScript
    function htmlspecialchars(str) {
        return str
            .replace(/&/g, "&")
            .replace(/</g, "<")
            .replace(/>/g, ">")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
</script>

<?php include 'includes/footer.php'; ?>