<?php
require_once 'includes/config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $show_id = $_POST['show_id'];
    $tickets = $_POST['tickets'];
    
    if ($tickets < 1) {
        $error = "Please select at least one ticket.";
    } else {
        $stmt = $pdo->prepare("SELECT price FROM shows WHERE id = ?");
        $stmt->execute([$show_id]);
        $show = $stmt->fetch();
        if ($show) {
            $total_price = $show['price'] * $tickets;
            $stmt = $pdo->prepare("INSERT INTO bookings (user_id, show_id, tickets, total_price) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$_SESSION['user_id'], $show_id, $tickets, $total_price])) {
                $success = "Booking successful! You'll receive a confirmation soon.";
            } else {
                $error = "Booking failed. Try again.";
            }
        } else {
            $error = "Invalid show selected.";
        }
    }
}
include 'includes/header.php';
?>
<h1 class="text-2xl font-bold mb-4">Booking Confirmation</h1>
<?php if (isset($success)): ?>
    <p class="text-green-500"><?php echo $success; ?></p>
    <a href="index.php" class="bg-blue-600 text-white p-2 rounded inline-block mt-4">Back to Shows</a>
<?php elseif (isset($error)): ?>
    <p class="text-red-500"><?php echo $error; ?></p>
    <a href="index.php" class="bg-blue-600 text-white p-2 rounded inline-block mt-4">Back to Shows</a>
<?php else: ?>
    <p class="text-red-500">Invalid request.</p>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>