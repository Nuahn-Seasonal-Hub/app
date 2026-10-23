<?php
// Launch gate: public domain shows only the Coming Soon page until launch (includes/gate.php).
require_once __DIR__ . '/../includes/gate.php';
$_SESSION['flash']['info'][] = "Product added to cart.";
header("Location: ../public/cart.php");
?>