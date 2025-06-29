<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title><?php echo htmlspecialchars($page_title ?? 'Show Marketplace'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="styles/styles.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <!-- Include Chart.js for admin dashboard charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="js/script.js"></script>
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

        /* Mobile-optimized form elements */
        .form-input {
            transition: all 0.3s ease;
            min-height: 44px; /* Apple's recommended minimum touch target */
            font-size: 16px; /* Prevents zoom on iOS */
            padding: 12px 16px;
            border-radius: 8px;
            border: 2px solid #e5e7eb;
            background-color: white;
            -webkit-appearance: none; /* Remove default styling on iOS */
            appearance: none;
        }

        .form-input:focus {
            transform: scale(1.02);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
            border-color: #3b82f6;
            outline: none;
        }

        .form-button {
            transition: all 0.3s ease;
            min-height: 44px; /* Apple's recommended minimum touch target */
            font-size: 16px;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent; /* Remove tap highlight on mobile */
        }

        .form-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-button:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Global scrollbar styling */
        body {
            scrollbar-width: thin;
            scrollbar-color: #c084fc #ff5e62;
            -webkit-text-size-adjust: 100%; /* Prevent font scaling in landscape on iOS */
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

        /* Mobile hamburger menu */
        .hamburger {
            display: none;
            flex-direction: column;
            cursor: pointer;
            padding: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            position: relative;
            z-index: 1001;
        }

        .hamburger span {
            width: 25px;
            height: 3px;
            background-color: white;
            margin: 2px 0;
            transition: 0.3s;
            border-radius: 2px;
        }

        .hamburger.active span:nth-child(1) {
            transform: rotate(-45deg) translate(-5px, 6px);
        }

        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }

        .hamburger.active span:nth-child(3) {
            transform: rotate(45deg) translate(-5px, -6px);
        }

        /* Mobile navigation styling */
        .mobile-nav {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: rgba(30, 58, 138, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .mobile-nav a {
            display: block;
            padding: 16px 20px;
            color: white;
            text-decoration: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            font-size: 16px;
            font-weight: 500;
            min-height: 44px;
            line-height: 20px;
            -webkit-tap-highlight-color: transparent;
        }

        .mobile-nav a:last-child {
            border-bottom: none;
        }

        .mobile-nav a:hover,
        .mobile-nav a:active {
            background-color: rgba(255, 255, 255, 0.2);
            padding-left: 24px;
        }

        /* Desktop navigation */
        .desktop-nav a {
            transition: all 0.3s ease;
            padding: 8px 12px;
            border-radius: 4px;
            -webkit-tap-highlight-color: transparent;
        }

        .desktop-nav a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .hamburger {
                display: flex;
            }

            .desktop-nav {
                display: none;
            }

            /* Better mobile form container */
            .form-container {
                padding: 16px;
                margin: 16px;
                border-radius: 12px;
            }

            /* Adjust background shapes for mobile */
            .graphic-bg::before {
                width: 150px;
                height: 150px;
                top: 5%;
                left: 5%;
            }

            .graphic-bg::after {
                width: 200px;
                height: 200px;
                bottom: 5%;
                right: 5%;
            }

            /* Make header relative for mobile nav positioning */
            header {
                position: relative;
            }
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

        /* Prevent zoom on input focus for iOS */
        @media screen and (max-width: 768px) {
            select, textarea, input[type="text"], input[type="password"], 
            input[type="datetime"], input[type="datetime-local"], 
            input[type="date"], input[type="month"], input[type="time"], 
            input[type="week"], input[type="number"], input[type="email"], 
            input[type="url"], input[type="search"], input[type="tel"] {
                font-size: 16px !important;
            }
        }

        /* Auth page specific styling */
        .auth-link {
            display: inline-block;
            padding: 12px 20px;
            margin: 8px 4px;
            background-color: rgba(59, 130, 246, 0.1);
            border: 2px solid rgba(59, 130, 246, 0.3);
            border-radius: 8px;
            text-decoration: none;
            color: #3b82f6;
            text-align: center;
            min-height: 44px;
            line-height: 20px;
            transition: all 0.3s ease;
            -webkit-tap-highlight-color: transparent;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
        }

        .auth-link:hover,
        .auth-link:active {
            background-color: rgba(59, 130, 246, 0.2);
            border-color: rgba(59, 130, 246, 0.5);
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .auth-link {
                display: block;
                width: 100%;
                margin: 8px 0;
                text-align: center;
            }
        }
    </style>
</head>
<body class="<?php echo htmlspecialchars($body_class ?? ''); ?>">
    <header class="bg-blue-900 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl md:text-2xl font-bold">SHOW MARKETPLACE</h1>
            
            <!-- Desktop Navigation -->
            <nav class="desktop-nav hidden md:flex space-x-4">
                <?php if (basename($_SERVER['PHP_SELF']) === 'register.php' || basename($_SERVER['PHP_SELF']) === 'login.php'): ?>
                    <!-- Auth page navigation -->
                    <a href="index.php" class="text-white hover:underline">Home</a>
                    <?php if (basename($_SERVER['PHP_SELF']) === 'login.php'): ?>
                        <a href="register.php" class="text-white hover:underline">Register</a>
                    <?php else: ?>
                        <a href="login.php" class="text-white hover:underline">Login</a>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Normal page navigation -->
                    <a href="index.php" class="text-white hover:underline">Home</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="<?php echo $_SESSION['is_admin'] ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>" 
                           class="text-white hover:underline">
                            Dashboard
                        </a>
                        <a href="logout.php" class="text-white hover:underline">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="text-white hover:underline">Login</a>
                        <a href="register.php" class="text-white hover:underline">Register</a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>

            <!-- Mobile Hamburger Menu -->
            <div class="hamburger md:hidden" onclick="toggleMobileMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <nav id="mobileNav" class="mobile-nav md:hidden" style="display: none;">
            <?php if (basename($_SERVER['PHP_SELF']) === 'register.php' || basename($_SERVER['PHP_SELF']) === 'login.php'): ?>
                <!-- Auth page mobile navigation -->
                <a href="index.php">Home</a>
                <?php if (basename($_SERVER['PHP_SELF']) === 'login.php'): ?>
                    <a href="register.php">Register</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                <?php endif; ?>
            <?php else: ?>
                <!-- Normal page mobile navigation -->
                <a href="index.php">Home</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo $_SESSION['is_admin'] ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>">
                        Dashboard
                    </a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            <?php endif; ?>
        </nav>
    </header>

    <?php if (basename($_SERVER['PHP_SELF']) !== 'register.php' && basename($_SERVER['PHP_SELF']) !== 'login.php'): ?>
    <main class="container mx-auto p-4">
    <?php endif; ?>

<script>
function toggleMobileMenu() {
    const mobileNav = document.getElementById('mobileNav');
    const hamburger = document.querySelector('.hamburger');
    
    if (mobileNav && hamburger) {
        const isVisible = mobileNav.style.display === 'block';
        
        if (isVisible) {
            mobileNav.style.display = 'none';
            hamburger.classList.remove('active');
        } else {
            mobileNav.style.display = 'block';
            hamburger.classList.add('active');
        }
    }
}

// Close mobile menu when clicking outside
document.addEventListener('click', function(event) {
    const mobileNav = document.getElementById('mobileNav');
    const hamburger = document.querySelector('.hamburger');
    
    if (mobileNav && hamburger && 
        !hamburger.contains(event.target) && 
        !mobileNav.contains(event.target)) {
        mobileNav.style.display = 'none';
        hamburger.classList.remove('active');
    }
});

// Enhanced touch handling for mobile links
document.addEventListener('DOMContentLoaded', function() {
    // Ensure all links work properly on mobile
    const allLinks = document.querySelectorAll('a');
    allLinks.forEach(link => {
        // Add touch events for better mobile interaction
        link.addEventListener('touchstart', function(e) {
            // Prevent default to avoid double-tap issues
            this.style.opacity = '0.7';
        });
        
        link.addEventListener('touchend', function(e) {
            this.style.opacity = '1';
            // Small delay to ensure link activation
            setTimeout(() => {
                if (this.href && this.href !== '#') {
                    window.location.href = this.href;
                }
            }, 100);
        });

        link.addEventListener('touchcancel', function(e) {
            this.style.opacity = '1';
        });
    });

    // Special handling for auth links in forms
    const authLinks = document.querySelectorAll('.auth-link');
    authLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Add visual feedback
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
                if (this.href) {
                    window.location.href = this.href;
                }
            }, 150);
        });
    });
});
</script>

<?php if (basename($_SERVER['PHP_SELF']) !== 'register.php' && basename($_SERVER['PHP_SELF']) !== 'login.php'): ?>
</body>
</html>
<?php endif; ?>