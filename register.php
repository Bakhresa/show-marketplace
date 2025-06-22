<?php
// 1. Enable error reporting to debug live
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Include DB config
require_once 'includes/config.php';

$body_class = 'graphic-bg flex items-center justify-center min-h-screen';
$page_title = 'Register';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_or_phone = trim($_POST['email_or_phone'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $country = trim($_POST['country'] ?? '');

    $email = null;
    $phone_number = null;

    // 3. Validation
    if (empty($email_or_phone)) {
        $errors[] = "Email or phone number is required.";
    } else {
        if (filter_var($email_or_phone, FILTER_VALIDATE_EMAIL)) {
            $email = $email_or_phone;
        } elseif (preg_match('/^\+?[1-9]\d{1,14}$/', $email_or_phone)) {
            $phone_number = $email_or_phone;
        } else {
            $errors[] = "Invalid email or phone number format.";
        }
    }

    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if (empty($country)) {
        $errors[] = "Country is required.";
    }

    // 4. Check if email or phone already exists
    if (empty($errors)) {
        $query = "SELECT id FROM users WHERE ";
        $params = [];

        if (!empty($email)) {
            $query .= "email = ?";
            $params[] = $email;
        } elseif (!empty($phone_number)) {
            $query .= "phone_number = ?";
            $params[] = $phone_number;
        } else {
            $errors[] = "Missing email or phone for duplicate check.";
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);

            if ($stmt->fetch()) {
                $errors[] = "Email or phone number already exists.";
            }
        }
    }

    // 5. Insert into DB
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone_number, password, country, is_admin) 
                                   VALUES (?, ?, ?, ?, ?, 0)");
            $stmt->execute([$full_name, $email, $phone_number, $hashed_password, $country]);
            $success = "✅ Registration successful! <a href='login.php'>Login</a>";
        } catch (PDOException $e) {
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}

// Country list
$countries = [
    'US' => 'United States',
    'KE' => 'Kenya',
    'UK' => 'United Kingdom',
    'CA' => 'Canada',
    'AU' => 'Australia',
];

include 'includes/header.php';
?>
