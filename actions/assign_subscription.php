<?php
require_once "../config/db.php";
require_once "../includes/flash_helper.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = intval($_POST['user_id']);
    $plan_id = intval($_POST['plan_id']);

    // Fetch plan details
    $stmt = $pdo->prepare("SELECT duration FROM plans WHERE id = ?");
    $stmt->execute([$plan_id]);
    $plan = $stmt->fetch();

    if ($plan) {
        $start_date = date("Y-m-d");
        $expiry_date = date("Y-m-d", strtotime("+{$plan['duration']} days"));

        try {
            $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, plan_id, start_date, expiry_date, status) 
                                   VALUES (?, ?, ?, ?, 'active')
                                   ON DUPLICATE KEY UPDATE plan_id=?, start_date=?, expiry_date=?, status='active'");
            $stmt->execute([$user_id, $plan_id, $start_date, $expiry_date, $plan_id, $start_date, $expiry_date]);

            setFlash("success", "Subscription assigned successfully!", "alert");
            header("Location: ../public/admin/subscriptions.php");
        } catch (Exception $e) {
            setFlash("danger", "Failed to assign subscription.", "alert");
            header("Location: ../public/admin/assign_subscription.php");
        }
    } else {
        setFlash("danger", "Invalid plan selected.", "alert");
        header("Location: ../public/admin/assign_subscription.php");
    }
}
?>
