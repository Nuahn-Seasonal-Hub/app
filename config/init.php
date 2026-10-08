<?php
// config/init.php

// Start session once, globally
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load database connection
require_once __DIR__ . "/db.php";

// Load authentication helpers
require_once __DIR__ . "/../includes/auth.php";

// Load flash message helpers (optional, if you created flash_helpers.php)
require_once __DIR__ . "/../includes/flash_helpers.php";


// Set timeout duration (e.g., 30 minutes)
define('SESSION_TIMEOUT', 1800); // 1800 seconds = 30 minutes

// Check if last activity is set
if (isset($_SESSION['LAST_ACTIVITY'])) {
    if (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT) {
        // Too long since last activity → logout
        session_unset();
        session_destroy();
        header("Location: ../public/auth.php?message=timeout");
        exit;
    }
}

// Update last activity timestamp
$_SESSION['LAST_ACTIVITY'] = time();
