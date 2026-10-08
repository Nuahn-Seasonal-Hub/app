<?php
require_once "../config/db.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = htmlspecialchars($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'] ?? 'client';

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
        header("Location: ../public/login.php?success=registered");
    } catch (Exception $e) {
        header("Location: ../public/register.php?error=email_taken");
    }
}
?>
