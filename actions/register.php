<?php
// Launch gate: public domain shows only the Coming Soon page until launch (includes/gate.php).
require_once __DIR__ . '/../includes/gate.php';
require_once "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_once __DIR__ . '/../includes/marketplace/core.php';
    if (!mk_csrf_ok()) { header("Location: ../public/register.php?error=invalid"); exit; }
    $name = htmlspecialchars($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'] ?? 'client';
    // Public sign-up can only create client or provider accounts; staff accounts are created by existing staff.
    if (!in_array($role, ['client', 'provider'], true)) { $role = 'client'; }

    // ✅ Server-side validation
    $errors = [];
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";
    if (!preg_match("/[A-Z]/", $password)) $errors[] = "Password must contain an uppercase letter.";
    if (!preg_match("/[a-z]/", $password)) $errors[] = "Password must contain a lowercase letter.";
    if (!preg_match("/[0-9]/", $password)) $errors[] = "Password must contain a number.";
    if (!preg_match("/[\W]/", $password)) $errors[] = "Password must contain a special character.";

    if (!empty($errors)) {
        $_SESSION['register_errors'] = $errors;
        header("Location: ../public/register.php?error=weak_password");
        exit;
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    try {
        $stmt->execute([$name, $email, $hashed, $role]);
        $next = mk_safe_next($_POST['next'] ?? '');
        header("Location: ../public/login.php?success=registered" . ($next !== '' ? '&next=' . rawurlencode($next) : ''));
    } catch (Exception $e) {
        header("Location: ../public/register.php?error=email_taken");
    }
}
?>
