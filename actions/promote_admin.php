<?php
require_once "../config/db.php";
require_once "../includes/flash_helper.php";
session_start();

if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== true) {
    header("Location: ../public/admin/dashboard.php?error=unauthorized");
    exit;
}

$id = intval($_GET['id']);
$stmt = $pdo->prepare("UPDATE users SET is_superadmin=1 WHERE id=? AND role='admin'");
$stmt->execute([$id]);

$log = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
$log->execute([$id, "Promoted to super-admin"]);

setFlash("success", "Admin promoted to super-admin!", "alert");
header("Location: ../public/admin/manage_admins.php");
?>
