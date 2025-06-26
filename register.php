<?php
// register.php — Mobile Optimized
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/config.php';

$body_class = 'graphic-bg flex items-center justify-center min-h-screen';
$page_title = 'Register';

$errors = [];
$success = '';

$email_or_phone = '';
$full_name = '';
$password = '';
$confirm_password = ''; // New field variable
$country = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_or_phone = trim($_POST['email_or_phone'] ?? '');
    $full_name      = trim($_POST['full_name'] ?? '');
    $password       = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? ''; // New field value
    $country        = trim($_POST['country'] ?? '');

    $email = null;
    $phone_number = null;

    if ($email_or_phone === '') {
        $errors[] = 'Email or phone number is required.';
    } elseif (filter_var($email_or_phone, FILTER_VALIDATE_EMAIL)) {
        $email = $email_or_phone;
    } elseif (preg_match('/^\+?[1-9]\d{1,14}$/', $email_or_phone)) {
        $phone_number = $email_or_phone;
    } else {
        $errors[] = 'Invalid email or phone number format.';
    }

    if ($full_name === '') {
        $errors[] = 'Full name is required.';
    }
    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($confirm_password === '') {
        $errors[] = 'Please confirm your password.';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    if ($country === '') {
        $errors[] = 'Country is required.';
    }

    if (empty($errors)) {
        $sql = 'SELECT id FROM users WHERE ' . ($email ? 'email = ?' : 'phone_number = ?');
        $params = [$email ?: $phone_number];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if ($stmt->fetch()) {
            $errors[] = 'Email or phone number already exists.';
        }
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (name, email, phone_number, password, country, is_admin)
                 VALUES (?, ?, ?, ?, ?, 0)'
            );
            $stmt->execute([$full_name, $email, $phone_number, $hashed_password, $country]);
            $success = '✅ Registration successful! <a href="login.php" class="text-blue-600 underline">Login</a>.';
            $email_or_phone = $full_name = $password = $confirm_password = $country = '';
        } catch (PDOException $e) {
            $errors[] = 'Registration failed: ' . $e->getMessage();
        }
    }
}

$countries = [
    'US' => 'United States',
    'KE' => 'Kenya',
    'UK' => 'United Kingdom',
    'CA' => 'Canada',
    'AU' => 'Australia',
];

include 'includes/header.php';
?>
<div class="min-h-screen flex items-center justify-center px-2">
<div class="w-full max-w-md p-6 bg-white rounded-lg shadow-md form-container">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Create your account</h1>

    <?php if ($success): ?>
        <div class="mb-4 text-green-600"><?php echo $success; ?></div>
    <?php else: ?>
        <?php if ($errors): ?>
            <div class="mb-4">
                <?php foreach ($errors as $err): ?>
                    <p class="text-red-500"><?php echo htmlspecialchars($err); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label for="email_or_phone" class="block text-sm font-medium text-gray-700 mb-1">
                    Email or Phone Number
                </label>
                <input type="text" id="email_or_phone" name="email_or_phone" placeholder="you@example.com or +254700000000"
                    value="<?php echo htmlspecialchars($email_or_phone); ?>" required
                    class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required
                    class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="relative">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" id="password" name="password" required
                    class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <span class="password-toggle absolute right-2 top-1/2 transform -translate-y-1/2 cursor-pointer text-gray-500"
                      onclick="togglePassword('password')">&#128065;</span>
            </div>

            <div class="relative">
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                    class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <span class="password-toggle absolute right-2 top-1/2 transform -translate-y-1/2 cursor-pointer text-gray-500"
                      onclick="togglePassword('confirm_password')">&#128065;</span>
            </div>

            <div>
                <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                <select id="country" name="country" required
                    class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Select Country --</option>
                    <?php foreach ($countries as $code => $name): ?>
                        <option value="<?php echo $code; ?>" <?php echo ($country === $code) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit"
                class="form-button w-full bg-purple-500 text-white p-3 rounded-lg hover:bg-purple-600 transition">
                Create account
            </button>

            <p class="text-center text-gray-600 text-sm mt-4">
                By signing up, I agree to the
                <a href="#" class="text-blue-600 underline">Terms of Service</a> and
                <a href="#" class="text-blue-600 underline">Privacy Policy</a>.
            </p>
        </form>

        <p class="text-center text-gray-600 text-sm mt-4">
            Already have an account?
            <a href="login.php" class="text-blue-600 underline">Log in</a>
        </p>
    <?php endif; ?>
</div>
</div>
<script>
  document.querySelectorAll('input, select').forEach(el => {
    el.addEventListener('focus', () => {
      setTimeout(() => {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }, 300);
    });
  });

  function togglePassword(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const toggleIcon = passwordField.nextElementSibling;
    if (passwordField.type === 'password') {
      passwordField.type = 'text';
      toggleIcon.textContent = '👁️'; // Open eye when visible
    } else {
      passwordField.type = 'password';
      toggleIcon.textContent = '👁️‍🗨️'; // Closed eye when hidden
    }
  }
</script>
<?php include 'includes/footer.php'; ?>
