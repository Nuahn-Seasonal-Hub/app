<?php
require_once "../config/init.php";
require_once "../includes/flash_helper.php";
// Staff only: superadmin, manager (and legacy admin).
requireLogin(STAFF_ROLES);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = intval($_POST['id']);
    $name = htmlspecialchars($_POST['name']);
    $price = floatval($_POST['price']);
    $duration = intval($_POST['duration']);

    try {
        $stmt = $pdo->prepare("UPDATE plans SET name=?, price=?, duration=? WHERE id=?");
        $stmt->execute([$name, $price, $duration, $id]);

        setFlash("success", "Plan updated successfully!", "alert");
        header("Location: ../public/admin/subscriptions.php");
    } catch (Exception $e) {
        setFlash("danger", "Failed to update plan.", "alert");
        header("Location: ../public/admin/edit_plan.php?id=$id");
    }
}
?>
