<?php
require_once 'includes/config.php';
$body_class = 'graphic-bg flex items-center justify-center min-h-screen'; // Use the graphic background
$page_title = 'Register';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_or_phone = trim($_POST['email_or_phone'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $country = trim($_POST['country'] ?? '');

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

    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    if (empty($country)) {
        $errors[] = "Country is required.";
    }

    // Check if email or phone number already exists
    if (empty($errors)) {
        $query = "SELECT id FROM users WHERE ";
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
        if ($stmt->fetch()) {
            $errors[] = "Email or phone number is already registered.";
        }
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone_number, password, country, is_admin) VALUES (?, ?, ?, ?, ?, 0)");
        if ($stmt->execute([$full_name, $email, $phone_number, $hashed_password, $country])) {
            $success = "Registration successful! You can now <a href='login.php' class='text-blue-600 underline'>login</a>.";
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}

// List of countries (simplified for the dropdown)
$countries = [
    'US' => 'United States',
    'KE' => 'Kenya',
    'UK' => 'United Kingdom',
    'CA' => 'Canada',
    'AU' => 'Australia',
    // Add more countries as needed
];

include 'includes/header.php';
?>

<div class="w-full max-w-md p-6 bg-white rounded-lg shadow-md form-container">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Create your account</h1>

    <?php if (!empty($errors)): ?>
        <div class="mb-4">
            <?php foreach ($errors as $error): ?>
                <p class="text-red-500"><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="mb-4">
            <p class="text-green-500"><?php echo $success; ?></p>
        </div>
    <?php else: ?>
        <form method="POST" class="space-y-4">
            <!-- Email or Phone Number -->
            <div>
                <label for="email_or_phone" class="block text-sm font-medium text-gray-700 mb-1">Email or Phone Number</label>
                <input type="text" id="email_or_phone" name="email_or_phone" value="<?php echo htmlspecialchars($_POST['email_or_phone'] ?? ''); ?>" 
                       class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       placeholder="user@example.com or +1234567890" required>
            </div>

            <!-- Full Name -->
            <div>
                <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full name</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" 
                       class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" id="password" name="password" 
                       class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>

            <!-- Country -->
            <div>
                <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                <select id="country" name="country" 
                        class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <?php foreach ($countries as $code => $name): ?>
                        <option value="<?php echo htmlspecialchars($code); ?>" 
                                <?php echo (isset($_POST['country']) && $_POST['country'] === $code) || (!isset($_POST['country']) && $code === 'US') ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="form-button w-full bg-purple-500 text-white p-3 rounded-lg hover:bg-purple-600 transition">
                Create account
            </button>

            <!-- Privacy Policy Link -->
            <p class="text-center text-gray-600 text-sm mt-4">
                By signing up, I agree to the 
                <a href="#" class="text-blue-600 underline">Terms of Service</a> and 
                <a href="#" class="text-blue-600 underline">Privacy Policy</a>.
            </p>
        </form>

        <!-- Login Link -->
        <p class="text-center text-gray-600 text-sm mt-4">
            Already have an account? <a href="login.php" class="text-blue-600 underline">Log in</a>
        </p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>