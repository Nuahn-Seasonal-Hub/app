<?php
// includes/auth.php

require_once __DIR__ . '/ui.php'; // nu_url(): app-root URLs that work from any folder depth

// Roles that exist in users.role, plus the legacy 'admin' label still referenced in code.
if (!defined('STAFF_ROLES')) {
    define('STAFF_ROLES', ['superadmin', 'manager', 'admin']);
}

function isRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function isStaff() {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], STAFF_ROLES, true);
}

/**
 * Require a signed-in user. Optionally restrict to a role or list of roles:
 *   requireLogin();                 // any signed-in user
 *   requireLogin('client');         // clients only
 *   requireLogin(STAFF_ROLES);      // superadmin, manager (and legacy admin)
 * Guests go to the sign-in chooser; signed-in users without the role go to their own home.
 */
function requireLogin($roles = null) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . nu_url('public/auth.php') . "?error=unauthorized");
        exit;
    }
    if ($roles !== null && $roles !== '' && $roles !== []) {
        $roles = (array) $roles;
        if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
            if (function_exists('flashError')) {
                flashError("You don't have access to that page.");
            }
            redirectByRole($_SESSION['role'] ?? '');
        }
    }
}

function redirectByRole($role) {
    switch ($role) {
        case 'client':
            header("Location: " . nu_url('public/client_home.php'));
            break;
        case 'provider':
            header("Location: " . nu_url('public/provider_home.php'));
            break;
        case 'manager':
        case 'admin':
        case 'superadmin':
            header("Location: " . nu_url('public/dashboard.php'));
            break;
        default:
            header("Location: " . nu_url('public/auth.php'));
    }
    exit;
}
?>
