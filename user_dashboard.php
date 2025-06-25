<?php
// Include database configuration
require_once 'includes/config.php';

// Set page metadata
$body_class = ''; // No specific body class
$page_title = 'Dashboard - Show Marketplace';

// Ensure session is started and user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=Please login to access the dashboard.");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user details
try {
    $stmt = $pdo->prepare("SELECT name, email, phone_number AS phone FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        session_unset();
        session_destroy();
        header("Location: login.php?error=User not found. Please login again.");
        exit;
    }
} catch (PDOException $e) {
    error_log("User fetch failed: " . $e->getMessage());
    header("Location: login.php?error=Failed to load user data. Please try again.");
    exit;
}

// M-Pesa Sandbox Credentials (replace with your own from Safaricom Developer Portal)
$consumer_key = 'your_consumer_key'; // Replace with your Consumer Key
$consumer_secret = 'your_consumer_secret'; // Replace with your Consumer Secret
$passkey = 'your_passkey'; // Replace with your PassKey
$business_short_code = '174379'; // Safaricom's sandbox shortcode
$callback_url = 'https://market/callback.php'; // Replace with your Ngrok URL

// Function to get M-Pesa access token
function getAccessToken($consumer_key, $consumer_secret) {
    $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    $credentials = base64_encode($consumer_key . ':' . $consumer_secret);

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials, 'Content-Type: application/json']);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // Disable for sandbox
    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        error_log("cURL error: " . curl_error($curl));
        return null;
    }
    curl_close($curl);
    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

// Function to initiate STK Push
function initiateSTKPush($access_token, $phone_number, $amount, $booking_id, $business_short_code, $passkey, $callback_url) {
    $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    $timestamp = date('YmdHis');
    $password = base64_encode($business_short_code . $passkey . $timestamp);

    $payload = [
        'BusinessShortCode' => $business_short_code,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $phone_number,
        'PartyB' => $business_short_code,
        'PhoneNumber' => $phone_number,
        'CallBackURL' => $callback_url,
        'AccountReference' => 'Booking-' . $booking_id,
        'TransactionDesc' => 'Payment for booking ' . $booking_id
    ];

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token, 'Content-Type: application/json']);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // Disable for sandbox
    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        error_log("cURL error: " . curl_error($curl));
        return ['error' => 'cURL failure'];
    }
    curl_close($curl);
    return json_decode($response, true);
}

// Handle booking creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_show'])) {
    $show_id = (int)$_POST['show_id'];
    $tickets = (int)$_POST['tickets'];

    if ($tickets < 1) {
        $error = "Please select at least 1 ticket.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, title, date, venue, price FROM shows WHERE id = ?");
            $stmt->execute([$show_id]);
            $show = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$show) {
                $error = "Show not found.";
            } else {
                $total_price = $tickets * $show['price'];
                $stmt = $pdo->prepare("INSERT INTO bookings (user_id, show_id, tickets, total_price, status, show_title, show_date, show_venue) VALUES (?, ?, ?, ?, 'pending', ?, ?, ?)");
                if ($stmt->execute([$user_id, $show_id, $tickets, $total_price, $show['title'], $show['date'], $show['venue']])) {
                    $success = "Show booked successfully! Payment is pending.";
                } else {
                    $error = "Failed to book the show. Please try again.";
                }
            }
        } catch (PDOException $e) {
            error_log("Booking creation failed: " . $e->getMessage());
            $error = "Database error. Please try again.";
        }
    }
}

// Handle payment initiation with M-Pesa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    $phone_number = trim($_POST['phone_number']);

    if (!preg_match('/^2547\d{8}$/', $phone_number)) {
        $error = "Invalid phone number. Use format 254768908765.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, total_price, status FROM bookings WHERE id = ? AND user_id = ?");
            $stmt->execute([$booking_id, $user_id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking || $booking['status'] !== 'pending') {
                $error = "Booking not found or already processed.";
            } else {
                $stmt = $pdo->prepare("UPDATE bookings SET phone_number = ? WHERE id = ?");
                $stmt->execute([$phone_number, $booking_id]);

                $access_token = getAccessToken($consumer_key, $consumer_secret);
                if (!$access_token) {
                    $error = "Failed to authenticate with M-Pesa. Please try again.";
                } else {
                    $response = initiateSTKPush($access_token, $phone_number, $booking['total_price'], $booking_id, $business_short_code, $passkey, $callback_url);

                    if (isset($response['ResponseCode']) && $response['ResponseCode'] == '0') {
                        $checkout_request_id = $response['CheckoutRequestID'];
                        $stmt = $pdo->prepare("UPDATE bookings SET checkout_request_id = ? WHERE id = ?");
                        $stmt->execute([$checkout_request_id, $booking_id]);
                        $success = "Payment request sent to your phone. Please enter your M-Pesa PIN to complete the payment.";
                    } elseif (isset($response['error'])) {
                        $error = "Payment initiation failed due to a network issue.";
                    } else {
                        $error = "Failed to initiate payment. Please try again.";
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("Payment initiation failed: " . $e->getMessage());
            $error = "Database error during payment. Please try again.";
        }
    }
}

// Handle booking deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_booking'])) {
    $booking_id = (int)$_POST['booking_id'];

    try {
        $stmt = $pdo->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ?");
        $stmt->execute([$booking_id, $user_id]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
            if ($stmt->execute([$booking_id])) {
                $success = "Booking deleted successfully.";
            } else {
                $error = "Failed to delete the booking. Please try again.";
            }
        } else {
            $error = "Booking not found or cannot be deleted.";
        }
    } catch (PDOException $e) {
        error_log("Booking deletion failed: " . $e->getMessage());
        $error = "Database error. Please try again.";
    }
}

// Handle sending a message to admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message = trim($_POST['message']);

    if (empty($message)) {
        $chat_error = "Message cannot be empty.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE is_admin = 1 LIMIT 1");
            $stmt->execute();
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$admin) {
                $chat_error = "No admin available to chat with.";
            } else {
                $admin_id = $admin['id'];
                $stmt = $pdo->prepare("INSERT INTO messages (sender_id, sender_type, receiver_id, receiver_type, message) VALUES (?, 'user', ?, 'admin', ?)");
                if ($stmt->execute([$user_id, $admin_id, $message])) {
                    $chat_success = "Message sent successfully!";
                } else {
                    $chat_error = "Failed to send message. Please try again.";
                }
            }
        } catch (PDOException $e) {
            error_log("Message send failed: " . $e->getMessage());
            $chat_error = "Database error. Please try again.";
        }
    }
}

// Fetch all shows
try {
    $stmt = $pdo->prepare("SELECT * FROM shows ORDER BY date ASC");
    $stmt->execute();
    $shows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Shows fetch failed: " . $e->getMessage());
    $shows = [];
}

// Fetch user's bookings
try {
    $stmt = $pdo->prepare("SELECT b.id, b.show_id, b.tickets, b.total_price, b.status, b.created_at, b.show_title AS title, b.show_date AS date, b.show_venue AS venue FROM bookings b WHERE b.user_id = ? ORDER BY b.created_at DESC");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Bookings fetch failed: " . $e->getMessage());
    $bookings = [];
}

// Fetch chat messages (simplified until API is ready)
try {
    $admin_id = $pdo->query("SELECT id FROM users WHERE is_admin = 1 LIMIT 1")->fetchColumn();
    if ($admin_id) {
        $stmt = $pdo->prepare("SELECT m.*, u.name AS sender_name FROM messages m LEFT JOIN users u ON m.sender_id = u.id AND m.sender_type = 'user' WHERE (m.sender_id = ? AND m.sender_type = 'user' AND m.receiver_id = ? AND m.receiver_type = 'admin') OR (m.sender_id = ? AND m.sender_type = 'admin' AND m.receiver_id = ? AND m.receiver_type = 'user') ORDER BY m.created_at ASC");
        $stmt->execute([$user_id, $admin_id, $admin_id, $user_id]);
        $chat_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND sender_type = 'admin' AND receiver_id = ? AND receiver_type = 'user' AND is_read = 0");
        $stmt->execute([$admin_id, $user_id]);

        $stmt = $pdo->prepare("SELECT COUNT(id) as unread_count FROM messages WHERE sender_id = ? AND sender_type = 'admin' AND receiver_id = ? AND receiver_type = 'user' AND is_read = 0");
        $stmt->execute([$admin_id, $user_id]);
        $unread_count = $stmt->fetchColumn() ?: 0;
    } else {
        $chat_messages = [];
        $unread_count = 0;
    }
} catch (PDOException $e) {
    error_log("Chat fetch failed: " . $e->getMessage());
    $chat_messages = [];
    $unread_count = 0;
}

include 'includes/header.php';
?>
<div class="welcome-section">
    <h1 class="text-2xl font-bold mb-4">User Dashboard</h1>
    <p class="text-gray-600 mb-6">Welcome, <?php echo htmlspecialchars($user['name'] ?? 'User'); ?>! Explore and book shows below.</p>

    <!-- User Details Section -->
    <div class="mb-6">
        <h2 class="text-xl font-semibold mb-2">Your Details</h2>
        <div class="card p-4">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name'] ?? 'Not set'); ?></p>
            <?php if (isset($user['email'])): ?>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email'] ?? 'Not set'); ?></p>
            <?php endif; ?>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?? 'Not set'); ?></p>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <p class="text-green-500"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <p class="text-red-500"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <?php if (isset($chat_success)): ?>
        <p class="text-green-500"><?php echo htmlspecialchars($chat_success); ?></p>
    <?php endif; ?>
    <?php if (isset($chat_error)): ?>
        <p class="text-red-500"><?php echo htmlspecialchars($chat_error); ?></p>
    <?php endif; ?>

    <!-- Available Shows Section -->
    <div class="mt-6">
        <h2 class="text-xl font-semibold mb-2">Available Shows</h2>
        <div id="shows-container">
            <?php if (empty($shows)): ?>
                <p class="text-gray-600">No shows available at the moment.</p>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php foreach ($shows as $show): ?>
                        <div id="user-show-card-<?php echo $show['id']; ?>" class="card p-4 flex flex-row space-x-4">
                            <div class="w-2/3">
                                <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($show['title']); ?></h3>
                                <p><strong>Date:</strong> <?php echo htmlspecialchars($show['date']); ?></p>
                                <p><strong>Venue:</strong> <?php echo htmlspecialchars($show['venue']); ?></p>
                                <p><strong>Price:</strong> $<?php echo number_format($show['price'], 2); ?> per ticket</p>
                                <form method="POST">
                                    <input type="hidden" name="show_id" value="<?php echo $show['id']; ?>">
                                    <div class="mb-2">
                                        <label for="tickets-<?php echo $show['id']; ?>" class="block text-gray-700 font-medium mb-1">Number of Tickets</label>
                                        <input type="number" id="tickets-<?php echo $show['id']; ?>" name="tickets" min="1" value="1" class="w-full p-2 border rounded-lg" required>
                                    </div>
                                    <button type="submit" name="book_show" class="bg-blue-600 text-white p-2 rounded-lg w-full">Book Now</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- User's Bookings Section -->
    <div class="mt-10">
        <h2 class="text-xl font-semibold mb-2">Your Bookings</h2>
        <?php if (empty($bookings)): ?>
            <p class="text-gray-600">You have no bookings yet.</p>
        <?php else: ?>
            <h3 class="text-lg font-medium mb-2">Pending Payment</h3>
            <?php
            $pending_bookings = array_filter($bookings, fn($booking) => $booking['status'] === 'pending');
            if (empty($pending_bookings)):
            ?>
                <p class="text-gray-600">No pending bookings.</p>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($pending_bookings as $booking): ?>
                        <div class="card p-4">
                            <h4 class="text-md font-semibold"><?php echo htmlspecialchars($booking['title']); ?></h4>
                            <p><strong>Date:</strong> <?php echo htmlspecialchars($booking['date'] ?? 'N/A'); ?></p>
                            <p><strong>Venue:</strong> <?php echo htmlspecialchars($booking['venue'] ?? 'N/A'); ?></p>
                            <p><strong>Tickets:</strong> <?php echo htmlspecialchars($booking['tickets']); ?></p>
                            <p><strong>Total Price:</strong> $<?php echo number_format($booking['total_price'], 2); ?></p>
                            <p><strong>Status:</strong> Pending Payment</p>
                            <p><strong>Booked On:</strong> <?php echo htmlspecialchars($booking['created_at']); ?></p>
                            <div class="mt-2 flex space-x-2">
                                <form method="POST">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                    <div class="mb-2">
                                        <label for="phone_number-<?php echo $booking['id']; ?>" class="block text-gray-700 font-medium mb-1">M-Pesa Phone Number</label>
                                        <input type="text" id="phone_number-<?php echo $booking['id']; ?>" name="phone_number" placeholder="2547XXXXXXXX" class="w-full p-2 border rounded-lg" required>
                                    </div>
                                    <button type="submit" name="pay_booking" class="bg-green-600 text-white p-2 rounded-lg w-full">Pay Now</button>
                                </form>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this booking?');">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                    <button type="submit" name="delete_booking" class="bg-red-600 text-white p-2 rounded-lg w-full">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h3 class="text-lg font-medium mb-2 mt-6">Attended Shows (Paid)</h3>
            <?php
            $paid_bookings = array_filter($bookings, fn($booking) => $booking['status'] === 'paid');
            if (empty($paid_bookings)):
            ?>
                <p class="text-gray-600">No attended shows.</p>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($paid_bookings as $booking): ?>
                        <div class="card p-4">
                            <h4 class="text-md font-semibold"><?php echo htmlspecialchars($booking['title']); ?></h4>
                            <p><strong>Date:</strong> <?php echo htmlspecialchars($booking['date'] ?? 'N/A'); ?></p>
                            <p><strong>Venue:</strong> <?php echo htmlspecialchars($booking['venue'] ?? 'N/A'); ?></p>
                            <p><strong>Tickets:</strong> <?php echo htmlspecialchars($booking['tickets']); ?></p>
                            <p><strong>Total Price:</strong> $<?php echo number_format($booking['total_price'], 2); ?></p>
                            <p><strong>Status:</strong> Paid</p>
                            <p><strong>Booked On:</strong> <?php echo htmlspecialchars($booking['created_at']); ?></p>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this booking? This action cannot be undone.');">
                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                <button type="submit" name="delete_booking" class="bg-red-600 text-white p-2 rounded-lg w-full">Delete</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Chat Section -->
    <div class="mt-10">
        <h2 class="text-xl font-semibold mb-2">
            Chat with Admin
            <span id="unread-count" class="ml-2 bg-red-500 text-white text-xs font-bold rounded-full px-2 py-1 <?php echo $unread_count > 0 ? '' : 'hidden'; ?>">
                <?php echo $unread_count; ?>
            </span>
        </h2>
        <div class="card p-4">
            <div id="chat-window" class="border rounded-lg p-4 h-64 overflow-y-auto bg-gray-50 mb-4">
                <?php if (empty($chat_messages)): ?>
                    <p class="text-gray-600">No messages yet. Start a conversation with the admin!</p>
                <?php else: ?>
                    <?php foreach ($chat_messages as $message): ?>
                        <div class="mb-2 <?php echo $message['sender_type'] === 'user' ? 'text-right' : 'text-left'; ?>">
                            <p class="inline-block p-2 rounded-lg <?php echo $message['sender_type'] === 'user' ? 'bg-blue-500 text-white' : 'bg-gray-300 text-gray-800'; ?>">
                                <strong><?php echo $message['sender_type'] === 'user' ? 'You' : 'Admin'; ?>:</strong> 
                                <?php echo htmlspecialchars($message['message']); ?>
                            </p>
                            <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($message['created_at']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <form method="POST">
                <div class="flex space-x-2">
                    <textarea name="message" class="w-full p-2 border rounded-lg" rows="2" placeholder="Type your message..." required></textarea>
                    <button type="submit" name="send_message" class="bg-blue-600 text-white p-2 rounded-lg">Send</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>