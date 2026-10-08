<?php
// public/logout.php
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Regenerate session ID for security
session_start();
session_regenerate_id(true);

// Redirect to login page
header("Location: login.php?success=logged_out");
exit;