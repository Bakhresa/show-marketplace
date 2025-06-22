<?php
// ──────────────────────────────────────────────────────────────
// 1. DEBUG SETTINGS  – comment these two lines out when live
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ──────────────────────────────────────────────────────────────

// 2. DATABASE CONNECTION
require_once 'includes/config.php';   //  <- make sure this file sets $pdo (PDO instance)

// 3. PAGE META
$body_class = 'graphic-bg flex items-center justify-center min-h-screen';
$page_title = 'Register';

// 4. INITIALISE STATE
$errors  = [];
$success = '';

// 5. HANDLE FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Grab & trim fields ────────────────────────────────────
    $email_or_phone = trim($_POST['email_or_phone'] ?? '');
    $full_name      = trim($_POST['full_name']      ?? '');
    $password       =       $_POST['password']      ?? '';
    $country        = trim($_POST['country']        ?? '');

    // ── Prepare vars ─────────────────────────────────────────
    $email        = null;
    $phone_number = null;

    // ── Validate email / phone ───────────────────────────────
    if ($email_or_phone === '') {
        $errors[] = 'Email or phone number is required.';
    } elseif (filter_var($email_or_phone, FILTER_VALIDATE_EMAIL)) {
        $email = $email_or_phone;
    } elseif (preg_match('/^\+?[1-9]\d{1,14}$/', $email_or_phone)) {
        $phone_number = $email_or_phone;
    } else {
        $errors[] = 'Invalid email or phone number format.';
    }

    // ── Other validation ─────────────────────────────────────
    if ($full_name === '')              { $errors[] = 'Full name is required.';          }
    if ($password === '')               { $errors[] = 'Password is required.';           }
    elseif (strlen($password) < 6)      { $errors[] = 'Password must be at least 6 characters.'; }
    if ($country === '')                { $errors[] = 'Country is required.';            }

    // ── Duplicate-check in DB ────────────────────────────────
    if (empty($errors)) {
        $sql    = 'SELECT id FROM users WHERE '. ($email ? 'email = ?' : 'phone_number = ?');
        $params = [$email ?: $phone_number];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if ($stmt->fetch()) {
            $errors[] = 'Email or phone number already exists.';
        }
    }

    // ── Insert user ──────────────────────────────────────────
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (name, email, phone_number, password, country, is_admin)
                 VALUES (?, ?, ?, ?, ?, 0)'
            );
            $stmt->execute([$full_name, $email, $phone_number, $hashed_password, $country]);
            $success = 'Registration successful! <a href="login.php" class="text-blue-600 underline">Login</a>.';
        } catch (PDOException $e) {
            // You can log $e->getMessage() to a file instead of showing it.
            $errors[] = 'Registration failed: ' . $e->getMessage();
        }
    }
}

// 6. COUNTRY LIST  (simplified)
$countries = [
    'US' => 'United States',
    'KE' => 'Kenya',
    'UK' => 'United Kingdom',
    'CA' => 'Canada',
    'AU' => 'Australia',
];

// 7. PAGE LAYOUT
include 'includes/header.php';
?>
<!-- ──────────────────────────────────────────────────────────── -->
<div class="w-full max-w-md p-6 bg-white rounded-lg shadow-md form-container">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Create your account</h1>

    <?php if ($success): ?>
        <div class="mb-4 text-green-600"><?php echo $success; ?></div>
    <?php else: ?>
        <!-- Show validation errors -->
        <?php if ($errors): ?>
            <div class="mb-4">
                <?php foreach ($errors as $err): ?>
                    <p class="text-red-500"><?php echo htmlspecialchars($err); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- REGISTRATION FORM -->
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
                    value="<?php echo htmlspecialchars($email_or_phone ?? ''); ?>"
                    required
                    class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>

            <!-- Full Name -->
            <div>
                <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    value="<?php echo htmlspecialchars($full_name ?? ''); ?>"
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

            <!-- Country -->
            <div>
                <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                <select
                    id="country"
                    name="country"
                    required
                    class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <?php foreach ($countries as $code => $name): ?>
                        <option value="<?php echo $code; ?>"
                            <?php
                                $selected = ($country && $country === $code) ||
                                            (!$country && $code === 'US');
                                echo $selected ? 'selected' : '';
                            ?>
                        >
                            <?php echo htmlspecialchars($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Submit -->
            <button
                type="submit"
                class="form-button w-full bg-purple-500 text-white p-3 rounded-lg hover:bg-purple-600 transition"
            >
                Create account
            </button>

            <!-- Terms -->
            <p class="text-center text-gray-600 text-sm mt-4">
                By signing up, I agree to the
                <a href="#" class="text-blue-600 underline">Terms of Service</a> and
                <a href="#" class="text-blue-600 underline">Privacy Policy</a>.
            </p>
        </form>

        <!-- Login link -->
        <p class="text-center text-gray-600 text-sm mt-4">
            Already have an account?
            <a href="login.php" class="text-blue-600 underline">Log in</a>
        </p>
    <?php endif; ?>
</div>
<!-- ──────────────────────────────────────────────────────────── -->
<?php include 'includes/footer.php'; ?>
