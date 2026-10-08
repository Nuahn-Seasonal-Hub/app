<?php
// public/login_action.php
require_once "../config/init.php"; // init.php should load db, session_start, and auth helpers

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // If role is passed (client/provider forms), enforce it
    $role = $_POST['role'] ?? null;

    if ($role) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$email, $role]);
    } else {
        // For manager/admin/superadmin login forms
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
    }

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        // Store session data
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['theme']     = $user['theme_preference'] ?? 'light';
        $_SESSION['user_name'] = $user['name'];

        // ✅ Centralized redirect
        redirectByRole($user['role']);
    } else {
        // Invalid login
        flashError("Invalid email, password, or role.");
        header("Location: ../public/login.php?error=invalid");
        exit;
    }
}
