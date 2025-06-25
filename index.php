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
<style>
    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
    }
    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }
    .welcome-section {
        background-image: url('images/backImage2.jpg');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        flex: 1 0 auto; /* Allow the section to grow but not shrink below content */
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        padding: 20px;
        color: #333;
    }
    .welcome-section a {
        color: #1e40af;
        text-decoration: underline;
    }
    .welcome-section a:hover {
        color: #1e3a8a;
    }
    footer {
        flex-shrink: 0; /* Prevent footer from shrinking */
        width: 100%;
        background-color: #f8f8f8;
        padding: 10px 0;
        text-align: center;
        border-top: 1px solid #e5e7eb;
    }
</style>
<div class="welcome-section">
    <h1 class="text-2xl font-bold mb-4">Welcome to Show Marketplace</h1>
    <?php if (isset($_GET['success'])): ?>
        <p class="text-green-500"><?php echo htmlspecialchars($_GET['success']); ?></p>
    <?php endif; ?>
    <p class="text-gray-600">Please <a href="login.php" class="text-blue-600">login</a> or <a href="register.php" class="text-blue-600">register</a> to explore and book shows.</p>
</div>
<?php include 'includes/footer.php'; ?>