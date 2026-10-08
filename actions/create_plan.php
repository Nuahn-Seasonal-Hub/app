<?php
require_once "../config/init.php";
require_once "../includes/flash_helper.php";
// Staff only: superadmin, manager (and legacy admin).
requireLogin(STAFF_ROLES);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = htmlspecialchars($_POST['name']);
    $price = floatval($_POST['price']);
    $duration = intval($_POST['duration']);

    try {
        $stmt = $pdo->prepare("INSERT INTO plans (name, price, duration) VALUES (?, ?, ?)");
        $stmt->execute([$name, $price, $duration]);

        setFlash("success", "Plan created successfully!", "alert");
        header("Location: ../public/admin/subscriptions.php");
    } catch (Exception $e) {
        setFlash("danger", "Failed to create plan.", "alert");
        header("Location: ../public/admin/create_plan.php");
    }
}
?>
