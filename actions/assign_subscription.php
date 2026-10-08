<?php
require_once "../config/init.php";
require_once "../includes/flash_helper.php";
// Staff only: superadmin, manager (and legacy admin).
requireLogin(STAFF_ROLES);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = intval($_POST['user_id']);
    $plan_id = intval($_POST['plan_id']);

    // Fetch plan details
    $stmt = $pdo->prepare("SELECT name, duration FROM plans WHERE id = ?");
    $stmt->execute([$plan_id]);
    $plan = $stmt->fetch();

    if ($plan) {
        $start_date = date("Y-m-d");
        $expiry_date = date("Y-m-d", strtotime("+{$plan['duration']} days"));

        try {
            // subscriptions columns: client_id, plan (name), start_date, end_date, status
            $stmt = $pdo->prepare("INSERT INTO subscriptions (client_id, plan, start_date, end_date, status) 
                                   VALUES (?, ?, ?, ?, 'active')");
            $stmt->execute([$user_id, $plan['name'], $start_date, $expiry_date]);

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
