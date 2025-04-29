
<?php
require_once 'includes/config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}
$stmt = $pdo->prepare("SELECT * FROM shows WHERE id = ?");
$stmt->execute([$_GET['id']]);
$show = $stmt->fetch();
if (!$show) {
    header("Location: index.php");
    exit;
}
include 'includes/header.php';
?>
<h1 class="text-2xl font-bold mb-4"><?php echo $show['title']; ?></h1>
<div class="bg-white p-4 rounded shadow">
    <p><strong>Genre:</strong> <?php echo $show['genre']; ?></p>
    <p><strong>Date:</strong> <?php echo $show['date']; ?></p>
    <p><strong>Time:</strong> <?php echo $show['time']; ?></p>
    <p><strong>Venue:</strong> <?php echo $show['venue']; ?></p>
    <p><strong>Price:</strong> $<?php echo number_format($show['price'], 2); ?></p>
    <p><strong>Description:</strong> <?php echo $show['description'] ?: 'No description available.'; ?></p>
    <form method="POST" action="book.php">
        <input type="hidden" name="show_id" value="<?php echo $show['id']; ?>">
        <div class="mb-4">
            <label for="tickets" class="block text-gray-700">Number of Tickets</label>
            <input type="number" id="tickets" name="tickets" min="1" class="w-full p-2 border rounded" required>
        </div>
        <button type="submit" class="bg-blue-600 text-white p-2 rounded">Book Now</button>
    </form>
</div>
<a href="index.php" class="text-blue-600 mt-4 inline-block">Back to Shows</a>
<?php include 'includes/footer.php'; ?>