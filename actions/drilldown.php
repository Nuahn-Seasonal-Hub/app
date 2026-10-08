<?php
require_once "../config/db.php";
session_start();

$type = $_GET['type'] ?? '';
$filter = $_GET['filter'] ?? '';

if ($type === 'notifications') {
    $stmt = $pdo->prepare("SELECT subject, message, sent_at 
                           FROM notifications_log 
                           WHERE recipient_group=? ORDER BY sent_at DESC LIMIT 20");
    $stmt->execute([$filter]);
    $rows = $stmt->fetchAll();
    echo "<table class='table table-striped'><thead><tr><th>Subject</th><th>Message</th><th>Sent At</th></tr></thead><tbody>";
    foreach ($rows as $row) {
        echo "<tr><td>".htmlspecialchars($row['subject'])."</td><td>".htmlspecialchars($row['message'])."</td><td>{$row['sent_at']}</td></tr>";
    }
    echo "</tbody></table>";
}

if ($type === 'subscriptions') {
    $stmt = $pdo->prepare("SELECT u.name, u.email, s.start_date, s.expiry_date, s.status 
                           FROM subscriptions s 
                           JOIN users u ON s.user_id=u.id 
                           JOIN plans p ON s.plan_id=p.id 
                           WHERE p.name=? ORDER BY s.start_date DESC LIMIT 20");
    $stmt->execute([$filter]);
    $rows = $stmt->fetchAll();
    echo "<table class='table table-striped'><thead><tr><th>User</th><th>Email</th><th>Start</th><th>Expiry</th><th>Status</th></tr></thead><tbody>";
    foreach ($rows as $row) {
        echo "<tr><td>{$row['name']}</td><td>{$row['email']}</td><td>{$row['start_date']}</td><td>{$row['expiry_date']}</td><td>{$row['status']}</td></tr>";
    }
    echo "</tbody></table>";
}

if ($type === 'activity') {
    $stmt = $pdo->prepare("SELECT u.name, a.action, a.created_at 
                           FROM activity_logs a 
                           JOIN users u ON a.user_id=u.id 
                           WHERE DATE(a.created_at)=? ORDER BY a.created_at DESC LIMIT 20");
    $stmt->execute([$filter]);
    $rows = $stmt->fetchAll();
    echo "<table class='table table-striped'><thead><tr><th>User</th><th>Action</th><th>Date</th></tr></thead><tbody>";
    foreach ($rows as $row) {
        echo "<tr><td>{$row['name']}</td><td>{$row['action']}</td><td>{$row['created_at']}</td></tr>";
    }
    echo "</tbody></table>";
}
?>
