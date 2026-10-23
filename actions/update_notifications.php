<?php
// Launch gate: public domain shows only the Coming Soon page until launch (includes/gate.php).
require_once __DIR__ . '/../includes/gate.php';
require_once "../config/db.php";
require_once "../includes/flash_helper.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$user_id = $_SESSION['user_id'];

$email_pref = isset($_POST['email_pref']) ? 1 : 0;
$sms_pref   = isset($_POST['sms_pref']) ? 1 : 0;
$inapp_pref = isset($_POST['inapp_pref']) ? 1 : 0;

$stmt = $pdo->prepare("UPDATE notification_preferences 
                       SET email_pref=?, sms_pref=?, inapp_pref=? 
                       WHERE user_id=?");
$stmt->execute([$email_pref, $sms_pref, $inapp_pref, $user_id]);

setFlash("success", "Notification preferences updated!", "toast");
header("Location: ../public/profile_notifications.php");
?>
