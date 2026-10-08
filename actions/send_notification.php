<?php
require_once "../config/db.php";
require_once "../includes/notify.php";
require_once "../includes/flash_helper.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $subject = htmlspecialchars($_POST['subject']);
    $message = htmlspecialchars($_POST['message']);
    $group   = $_POST['recipient_group'];
    $override = isset($_POST['override']) ? true : false;

    // Build query based on recipient group
    $query = "SELECT id, name, email FROM users";
    if ($group !== "all") {
        $query .= " WHERE role = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$group]);
    } else {
        $stmt = $pdo->query($query);
    }
    $recipients = $stmt->fetchAll();

    foreach ($recipients as $user) {
        // Check preferences unless override
        if (!$override) {
            $prefs = $pdo->prepare("SELECT email_pref, inapp_pref FROM notification_preferences WHERE user_id=?");
            $prefs->execute([$user['id']]);
            $pref = $prefs->fetch();
        }

        // Email
        if ($override || ($pref && $pref['email_pref'])) {
            sendNotification($user['email'], $subject, $message);
        }

        // In-app toast
        if ($override || ($pref && $pref['inapp_pref'])) {
            $_SESSION['flash_toast']['info'][] = "Admin Notice: $subject";
        }

        // Log activity
        $log = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
        $log->execute([$user['id'], "Admin notification sent"]);
    }

    setFlash("success", "Notification sent successfully!", "alert");
    header("Location: ../public/admin/notifications.php");
}
?>
