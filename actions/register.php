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

    $parts = preg_split('/\s+/', trim((string) ($_POST['name'] ?? '')), 2);
    $first = $parts[0] ?? $name;
    $last  = $parts[1] ?? null;

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, firstname, lastname) VALUES (?, ?, ?, ?, ?, ?)");
    try {
        $stmt->execute([$name, $email, $hashed, $role, $first, $last]);
        $uid = (int) $pdo->lastInsertId();
        require_once __DIR__ . '/../includes/onboarding.php';
        ob_ensure_users_schema();
        ob_email_send_verification($uid);

        session_regenerate_id(true);
        $_SESSION['user_id']   = $uid;
        $_SESSION['role']      = $role;
        $_SESSION['theme']     = 'light';
        $_SESSION['user_name'] = $name;

        $next = mk_safe_next($_POST['next'] ?? '');
        if ($next !== '') {
            $target = strpos($next, 'public/') === 0 ? '../' . $next : '../public/' . $next;
            header("Location: " . $target);
            exit;
        }
        header("Location: ../public/" . ($role === 'provider' ? 'provider_onboarding.php?step=2' : 'client_home.php'));
    } catch (Exception $e) {
        header("Location: ../public/register.php?error=email_taken");
    }
}
?>
