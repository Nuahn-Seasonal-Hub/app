<?php
// Launch gate: public domain shows only the Coming Soon page until launch (includes/gate.php).
require_once __DIR__ . '/../includes/gate.php';
require_once "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (isset($_POST['theme']) && isset($_SESSION['user_id'])) {
    $theme = $_POST['theme'] === 'dark' ? 'dark' : 'light';
    $_SESSION['theme'] = $theme;

    $stmt = $pdo->prepare("UPDATE users SET theme_preference = ? WHERE id = ?");
    $stmt->execute([$theme, $_SESSION['user_id']]);
}
?>
