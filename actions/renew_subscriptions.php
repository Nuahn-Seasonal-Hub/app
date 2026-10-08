<?php
require_once "../config/db.php";
require_once "../includes/notify.php";

// Fetch expired subscriptions
$stmt = $pdo->query("SELECT s.id, s.user_id, s.plan_id, p.duration, u.email, u.name
                     FROM subscriptions s
                     JOIN plans p ON s.plan_id = p.id
                     JOIN users u ON s.user_id = u.id
                     WHERE s.expiry_date < CURDATE() AND s.status='active'");

$expired = $stmt->fetchAll();

foreach ($expired as $sub) {
    $start_date = date("Y-m-d");
    $expiry_date = date("Y-m-d", strtotime("+{$sub['duration']} days"));

    $update = $pdo->prepare("UPDATE subscriptions 
                             SET start_date=?, expiry_date=?, status='active' 
                             WHERE id=?");
    $update->execute([$start_date, $expiry_date, $sub['id']]);

    // Send email notification
    $subject = "Subscription Renewed";
    $body = "Hello {$sub['name']},<br>Your subscription has been renewed until {$expiry_date}.";
    sendNotification($sub['email'], $subject, $body);

    // Log activity
    $log = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
    $log->execute([$sub['user_id'], "Subscription auto-renewed"]);
}
?>
