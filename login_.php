<?php
// ──────────────────────────────────────────────────────────────
// 1. DEBUG SETTINGS — comment out in production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ──────────────────────────────────────────────────────────────

// 2. DATABASE CONNECTION
require_once 'includes/config.php'; // must define $pdo

// 3. SESSION & META
session_start();
$body_class = 'graphic-bg flex items-center justify-center min-h-screen';
$page_title = 'Login';

// 4. INITIALISE STATE
$errors = [];
$success = '';
$email_or_phone = '';
$password = '';

// 5. HANDLE FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ── Sanitize input ─────────────────────────────────────────
    $email_or_phone = trim($_POST['email_or_phone'] ?? '');
    $password       = $_POST['password'] ?? '';

    // ── Validate input ─────────────────────────────────────────
    if ($email_or_phone === '') {
        $errors[] = 'Email or phone number is required.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    // ── Authenticate ───────────────────────────────────────────
    if (empty($errors)) {
        $email        = filter_var($email_or_phone, FILTER_VALIDATE_EMAIL) ? $email_or_phone : null;
        $phone_number = preg_match('/^\+?[1-9]\d{1,14}$/', $email_or_phone) ? $email_or_phone : null;

        try {
            $sql = 'SELECT id, password, is_admin FROM users WHERE email = ? OR phone_number = ? LIMIT 1';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email ?: '', $phone_number ?: '']);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['is_admin'] = $user['is_admin'];
                header('Location: ' . ($user['is_admin'] ? 'admin_dashboard.php' : 'user_dashboard.php'));
                exit;
            } else {
                $errors[] = 'Invalid email/phone or password.';
            }
        } catch (PDOException $e) {
            error_log("Login failed: " . $e->getMessage());
            $errors[] = 'Login failed. Please try again later.';
        }
    }
}

// 6. PAGE TEMPLATE
include 'includes/header.php';
?>
<!-- ──────────────────────────────────────────────────────────── -->
<div class="w-full max-w-md p-6 bg-white rounded-lg shadow-md form-container">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Log in</h1>

    <?php if ($success): ?>
        <div class="mb-4 text-green-600"><?php echo $success; ?></div>
    <?php elseif ($errors): ?>
        <div class="mb-4">
            <?php foreach ($errors as $err): ?>
                <p class="text-red-500"><?php echo htmlspecialchars($err); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- LOGIN FORM -->
    <form method="POST" class="space-y-4">
        <!-- Email or Phone -->
        <div>
            <label for="email_or_phone" class="block text-sm font-medium text-gray-700 mb-1">
                Email or Phone Number
            </label>
            <input
                type="text"
                id="email_or_phone"
                name="email_or_phone"
                placeholder="user@example.com or +1234567890"
                value="<?php echo htmlspecialchars($email_or_phone); ?>"
                required
                class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
        </div>

        <!-- Submit -->
        <button
            type="submit"
            class="form-button w-full bg-purple-500 text-white p-3 rounded-lg hover:bg-purple-600 transition"
        >
            Log in
        </button>

        <!-- Register link -->
        <p class="text-center text-gray-600 text-sm mt-4">
            Don’t have an account?
            <a href="register.php" class="text-blue-600 underline">Register</a>
        </p>
    </form>
</div>
<!-- ──────────────────────────────────────────────────────────── -->
<?php include 'includes/footer.php'; ?>
