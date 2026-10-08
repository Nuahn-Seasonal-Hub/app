<?php
require_once "../config/db.php";
require_once "../includes/notify.php";

$stmt = $pdo->query("SELECT s.id, u.email, u.name, s.expiry_date
                     FROM subscriptions s
                     JOIN users u ON s.user_id = u.id
                     WHERE DATEDIFF(s.expiry_date, CURDATE()) = 7 AND s.status='active'");

$reminders = $stmt->fetchAll();

foreach ($reminders as $sub) {
    $subject = "Subscription Expiry Reminder";
    $body = "Hello {$sub['name']},<br>Your subscription will expire on {$sub['expiry_date']}. Please renew to continue enjoying our services.";
    sendNotification($sub['email'], $subject, $body);

    $log = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
    $log->execute([$sub['id'], "Expiry reminder sent"]);
}
?>
