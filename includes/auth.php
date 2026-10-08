<?php
// includes/auth.php

function isRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../public/auth.php?error=unauthorized");
        exit;
    }
}

function redirectByRole($role) {
    switch ($role) {
        case 'client':
            header("Location: ../public/client_home.php");
            break;
        case 'provider':
            header("Location: ../public/provider_home.php");
            break;
        case 'manager':
        case 'admin':
        case 'superadmin':
            header("Location: ../public/dashboard.php");
            break;
        default:
            header("Location: ../public/auth.php");
    }
    exit;
}
?>
