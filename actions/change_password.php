<?php
require_once "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$current_password = $_POST['current_password'];
$new_password = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];

// Fetch current user
$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || !password_verify($current_password, $user['password'])) {
    header("Location: ../public/profile.php?error=wrong_current");
    exit;
}

if ($new_password !== $confirm_password) {
    header("Location: ../public/profile.php?error=nomatch");
    exit;
}

// ✅ Server-side validation rules
$errors = [];
if (strlen($new_password) < 8) {
    $errors[] = "Password must be at least 8 characters.";
}
if (!preg_match("/[A-Z]/", $new_password)) {
    $errors[] = "Password must contain at least one uppercase letter.";
}
if (!preg_match("/[a-z]/", $new_password)) {
    $errors[] = "Password must contain at least one lowercase letter.";
}
if (!preg_match("/[0-9]/", $new_password)) {
    $errors[] = "Password must contain at least one number.";
}
if (!preg_match("/[\W]/", $new_password)) {
    $errors[] = "Password must contain at least one special character.";
}

if (!empty($errors)) {
    // Pass errors back to profile page
    $_SESSION['password_errors'] = $errors;
    header("Location: ../public/profile.php?error=weak_password");
    exit;
}

// Hash new password
$hashed = password_hash($new_password, PASSWORD_DEFAULT);

// Update DB
$stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
$stmt->execute([$hashed, $user_id]);

header("Location: ../public/profile.php?success=password_updated");
?>
