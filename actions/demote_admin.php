<?php
require_once "../config/init.php";
// Superadmins only. Demote another superadmin to manager (never yourself).
requireLogin('superadmin');

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("UPDATE users SET role='manager' WHERE id=? AND role='superadmin' AND id<>?");
$stmt->execute([$id, $_SESSION['user_id']]);

if ($stmt->rowCount() > 0) {
    $log = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
    $log->execute([$id, "Demoted from super-admin"]);
    flashSuccess("Super-admin privileges removed.", "toast");
} else {
    flashError("That account can't be demoted.", "toast");
}
header("Location: ../public/admin/manage_admins.php");
exit;
