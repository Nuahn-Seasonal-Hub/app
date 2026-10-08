<?php
require_once "../config/init.php";
// Superadmins only. Promote a manager to superadmin (the schema has no is_superadmin column).
requireLogin('superadmin');

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("UPDATE users SET role='superadmin' WHERE id=? AND role='manager'");
$stmt->execute([$id]);

if ($stmt->rowCount() > 0) {
    $log = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
    $log->execute([$id, "Promoted to super-admin"]);
    flashSuccess("Manager promoted to super-admin!", "toast");
} else {
    flashError("Only managers can be promoted.", "toast");
}
header("Location: ../public/admin/manage_admins.php");
exit;
