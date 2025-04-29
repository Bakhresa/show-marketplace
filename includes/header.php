<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'Show Marketplace'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="/assets/css/styles.css" rel="stylesheet">
    <!-- Include Chart.js for admin dashboard charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        /* Smooth scrolling for the entire page */
        html {
            scroll-behavior: smooth;
        }

        /* Gradient background with subtle shapes */
        .graphic-bg {
            background: linear-gradient(135deg, #ff5e62 0%, #c084fc 100%);
            position: relative;
            overflow-y: auto; /* Allow vertical scrolling */
            overflow-x: hidden; /* Prevent horizontal scrolling */
            min-height: 100vh;
        }

        /* Subtle SVG shapes in the background */
        .graphic-bg::before,
        .graphic-bg::after {
            content: '';
            position: absolute;
            opacity: 0.1;
            background-repeat: no-repeat;
            background-size: contain;
        }

        .graphic-bg::before {
            width: 300px;
            height: 300px;
            top: 10%;
            left: 10%;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Ccircle cx='100' cy='100' r='80' fill='none' stroke='%23FFFFFF' stroke-width='20'/%3E%3C/svg%3E");
        }

        .graphic-bg::after {
            width: 400px;
            height: 400px;
            bottom: 10%;
            right: 10%;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Cpath d='M50 50L150 50L100 150Z' fill='none' stroke='%23FFFFFF' stroke-width='20'/%3E%3C/svg%3E");
        }

        /* Ensure form elements have smooth transitions */
        .form-input {
            transition: all 0.3s ease;
        }

        .form-input:focus {
            transform: scale(1.02);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }

        .form-button {
            transition: all 0.3s ease;
        }

        .form-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Global scrollbar styling */
        body {
            scrollbar-width: thin;
            scrollbar-color: #c084fc #ff5e62;
        }

        body::-webkit-scrollbar {
            width: 8px;
        }

        body::-webkit-scrollbar-track {
            background: #ff5e62;
        }

        body::-webkit-scrollbar-thumb {
            background: #c084fc;
            border-radius: 4px;
        }

        /* Ensure the form container is scrollable on small screens */
        @media (max-height: 600px) {
            .form-container {
                max-height: 80vh;
                overflow-y: auto;
                scrollbar-width: thin;
                scrollbar-color: #c084fc #ff5e62;
            }

            .form-container::-webkit-scrollbar {
                width: 8px;
            }

            .form-container::-webkit-scrollbar-track {
                background: #ff5e62;
            }

            .form-container::-webkit-scrollbar-thumb {
                background: #c084fc;
                border-radius: 4px;
            }
        }
    </style>
</head>
<body class="<?php echo htmlspecialchars($body_class ?? ''); ?>">
<?php if (basename($_SERVER['PHP_SELF']) !== 'register.php' && basename($_SERVER['PHP_SELF']) !== 'login.php'): ?>
    <header class="bg-blue-900 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">SHOW MARKETPLACE</h1>
            <nav>
                <a href="index.php" class="text-white hover:underline">Home</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo $_SESSION['is_admin'] ? 'admin_dashboard.php' : 'dashboard.php'; ?>" 
                       class="text-white hover:underline ml-4">
                        Dashboard
                    </a>
                    <a href="logout.php" class="text-white hover:underline ml-4">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="text-white hover:underline ml-4">Login</a>
                    <a href="register.php" class="text-white hover:underline ml-4">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="container mx-auto p-4">
<?php endif; ?>