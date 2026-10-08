<?php
// public/login_action.php
require_once("../config/init.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // Which role are we logging in as? (for client/provider forms)
    $role = $_POST['role'] ?? null;

    // If role is provided (client/provider), enforce it
    if ($role) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$email, $role]);
    } else {
        // For manager/admin/superadmin login forms (no dropdown)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
    }

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role']      = $user['role'];

        // Redirect based on role
        switch ($user['role']) {
            case 'client':
                header("Location: client_home.php");
                break;
            case 'provider':
                header("Location: provider_home.php");
                break;
            case 'manager':
            case 'admin':
            case 'superadmin':
                header("Location: dashboard.php");
                break;
            default:
                flashError("Invalid role assignment.");
                header("Location: login.php");
        }
        exit;
    } else {
        flashError("Invalid credentials.");
        header("Location: login.php");
        exit;
    }
}
