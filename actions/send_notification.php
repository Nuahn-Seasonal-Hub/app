<?php
require_once "../config/init.php";
require_once "../includes/notify.php";
require_once "../includes/flash_helper.php";
// Staff only: superadmin, manager (and legacy admin).
requireLogin(STAFF_ROLES);

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
    $inapp = false;

    foreach ($recipients as $user) {
        // Check preferences unless override
        $pref = false;
        if (!$override) {
            try {
                $prefs = $pdo->prepare("SELECT email_pref, inapp_pref FROM notification_preferences WHERE user_id=?");
                $prefs->execute([$user['id']]);
                $pref = $prefs->fetch();
            } catch (PDOException $e) {
                $pref = false; // notification_preferences table is not in the schema yet
            }
        }

        // Email
        if ($override || ($pref && $pref['email_pref'])) {
            sendNotification($user['email'], $subject, $message);
        }

        // In-app toast
        if ($override || ($pref && $pref['inapp_pref'])) {
            $inapp = true;
        }

        // Log activity
        $log = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
        $log->execute([$user['id'], "Admin notification sent"]);
    }

    if (!empty($inapp)) {
        $_SESSION['flash_toast']['info'][] = "Admin Notice: $subject";
    }

    // Record the send for history / analytics / CSV export
    $nlog = $pdo->prepare("INSERT INTO notifications_log (subject, message, recipient_group, override, sent_by) VALUES (?, ?, ?, ?, ?)");
    $nlog->execute([$subject, $message, $group, $override ? 1 : 0, $_SESSION['user_id']]);

    setFlash("success", "Notification sent successfully!", "alert");
    header("Location: ../public/admin/notifications.php");
}
?>
