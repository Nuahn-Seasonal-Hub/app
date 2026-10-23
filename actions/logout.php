<?php
// Launch gate: public domain shows only the Coming Soon page until launch (includes/gate.php).
require_once __DIR__ . '/../includes/gate.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
session_unset();
session_destroy();

// Redirect with success flag
header("Location: ../public/login.php?success=loggedout");
exit;
?>
