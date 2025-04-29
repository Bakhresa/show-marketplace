<?php
require_once 'includes/config.php';

// Clear session data
session_unset();
session_destroy();

// Redirect to login page
header("Location: login.php?success=Successfully logged out.");
exit;
?>