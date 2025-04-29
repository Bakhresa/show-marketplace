<?php
require_once 'includes/config.php';

// Log the callback data for debugging (optional)
$log_file = 'mpesa_callback.log';
$log = fopen($log_file, 'a');
fwrite($log, "Callback received at " . date('Y-m-d H:i:s') . "\n");

// Get the callback data
$callback_data = json_decode(file_get_contents('php://input'), true);
fwrite($log, print_r($callback_data, true) . "\n");

// Check if callback data is valid
if (!$callback_data || !isset($callback_data['Body']['stkCallback'])) {
    fwrite($log, "Invalid callback data\n");
    fclose($log);
    http_response_code(400);
    exit;
}

$stk_callback = $callback_data['Body']['stkCallback'];
$checkout_request_id = $stk_callback['CheckoutRequestID'];
$result_code = $stk_callback['ResultCode'];
$result_desc = $stk_callback['ResultDesc'];

// Find the booking associated with this CheckoutRequestID
$stmt = $pdo->prepare("SELECT id, user_id, total_price FROM bookings WHERE checkout_request_id = ?");
$stmt->execute([$checkout_request_id]);
$booking = $stmt->fetch();

if (!$booking) {
    fwrite($log, "Booking not found for CheckoutRequestID: $checkout_request_id\n");
    fclose($log);
    http_response_code(404);
    exit;
}

$booking_id = $booking['id'];
$user_id = $booking['user_id'];
$amount = $booking['total_price'];

// Prepare transaction data
$transaction_status = ($result_code == 0) ? 'completed' : 'failed';
$mpesa_receipt_number = '';
$phone_number = '';

if ($result_code == 0) {
    // Successful transaction
    $callback_metadata = $stk_callback['CallbackMetadata']['Item'];
    foreach ($callback_metadata as $item) {
        if ($item['Name'] === 'MpesaReceiptNumber') {
            $mpesa_receipt_number = $item['Value'];
        }
        if ($item['Name'] === 'PhoneNumber') {
            $phone_number = $item['Value'];
        }
    }

    // Update booking status to 'paid'
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'paid' WHERE id = ?");
    $stmt->execute([$booking_id]);
} else {
    // Failed transaction - get phone number from booking request
    $phone_number = 'N/A'; // You might need to store this earlier during STK Push initiation
    // Update booking status to 'failed'
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'failed' WHERE id = ?");
    $stmt->execute([$booking_id]);
}

// Insert transaction into the transactions table
$stmt = $pdo->prepare("INSERT INTO transactions (booking_id, user_id, amount, mpesa_receipt_number, phone_number, status, checkout_request_id) 
                       VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$booking_id, $user_id, $amount, $mpesa_receipt_number, $phone_number, $transaction_status, $checkout_request_id]);

fwrite($log, "Transaction recorded - Booking ID: $booking_id, Status: $transaction_status\n");
fclose($log);

// Respond to M-Pesa
http_response_code(200);
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Callback processed successfully']);
?>