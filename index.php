<?php
require_once 'includes/config.php';
$body_class = ''; // No specific body class for this page
$page_title = 'Home - Show Marketplace'; // Set the page title

// Redirect logged-in users to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: admin_dashboard.php");
    exit;
}

include 'includes/header.php';
?>
<div class="welcome-section">
    <h1 class="text-2xl font-bold mb-4">Welcome to Show Marketplace</h1>
    <?php if (isset($_GET['success'])): ?>
        <p class="text-green-500"><?php echo htmlspecialchars($_GET['success']); ?></p>
    <?php endif; ?>
    <p class="text-gray-600">Please <a href="login.php" class="text-blue-600">login</a> or <a href="register.php" class="text-blue-600">register</a> to explore and book shows.</p>
</div>
<?php include 'includes/footer.php'; ?>