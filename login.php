<?php
require_once 'includes/config.php';
$body_class = 'graphic-bg flex items-center justify-center min-h-screen'; // Use the graphic background
$page_title = 'Login';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_or_phone = trim($_POST['email_or_phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);

    // Validation
    if (empty($email_or_phone)) {
        $errors[] = "Email or phone number is required.";
    } else {
        if (filter_var($email_or_phone, FILTER_VALIDATE_EMAIL)) {
            $email = $email_or_phone;
            $phone_number = null;
        } elseif (preg_match('/^\+?[1-9]\d{1,14}$/', $email_or_phone)) {
            $email = null;
            $phone_number = $email_or_phone;
        } else {
            $errors[] = "Invalid email or phone number format. Use email (e.g., user@example.com) or phone number (e.g., +1234567890).";
        }
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    // If no validation errors, attempt login
    if (empty($errors)) {
        $query = "SELECT id, name, email, phone_number, password, is_admin FROM users WHERE ";
        $params = [];
        
        if ($email) {
            $query .= "email = ?";
            $params[] = $email;
        } else {
            $query .= "phone_number = ?";
            $params[] = $phone_number;
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['is_admin'] = $user['is_admin'];

            if ($remember_me) {
                $token = bin2hex(random_bytes(16));
                setcookie('remember_me', $token, time() + (30 * 24 * 60 * 60), '/');
                $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                $stmt->execute([$token, $user['id']]);
            }

            if ($user['is_admin']) {
                header('Location: admin_dashboard.php');
            } else {
                header('Location: dashboard.php');
            }
            exit;
        } else {
            $errors[] = "Invalid email/phone number or password.";
        }
    }
}

include 'includes/header.php';
?>

<div class="w-full max-w-md p-6 bg-white rounded-lg shadow-md form-container">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Sign in to your account</h1>

    <?php if (!empty($errors)): ?>
        <div class="mb-4">
            <?php foreach ($errors as $error): ?>
                <p class="text-red-500"><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <!-- Email or Phone Number -->
        <div>
            <label for="email_or_phone" class="block text-sm font-medium text-gray-700 mb-1">Email or Phone Number</label>
            <input type="text" id="email_or_phone" name="email_or_phone" value="<?php echo htmlspecialchars($_POST['email_or_phone'] ?? ''); ?>" 
                   class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                   placeholder="user@example.com or +1234567890" required>
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input type="password" id="password" name="password" 
                   class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>

        <!-- Remember Me and Forgot Password -->
        <div class="flex justify-between items-center">
            <div class="flex items-center">
                <input type="checkbox" id="remember_me" name="remember_me" 
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="remember_me" class="ml-2 block text-sm text-gray-700">
                    Remember me on this device
                </label>
            </div>
            <a href="#" class="text-sm text-blue-600 hover:underline">Forgot your password?</a>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="form-button w-full bg-purple-500 text-white p-3 rounded-lg hover:bg-purple-600 transition">
            Sign In
        </button>
    </form>

    <!-- Create Account Link -->
    <p class="text-center text-gray-600 text-sm mt-4">
        New to Show Marketplace? <a href="register.php" class="text-blue-600 hover:underline">Create account</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>