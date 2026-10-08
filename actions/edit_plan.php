<?php
require_once "../config/db.php";
require_once "../includes/flash_helper.php";
session_start();

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
