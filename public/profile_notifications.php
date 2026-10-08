<?php
require_once "../config/db.php";
session_start();

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM notification_preferences WHERE user_id=?");
$stmt->execute([$user_id]);
$prefs = $stmt->fetch();

include "../includes/header.php";
?>

<div class="container mt-4">
  <h2 class="mb-4">Notification Preferences</h2>
  <form action="../actions/update_notifications.php" method="POST">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="email_pref" id="email_pref"
             <?php if($prefs['email_pref']) echo "checked"; ?>>
      <label class="form-check-label" for="email_pref">Email Notifications</label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="sms_pref" id="sms_pref"
             <?php if($prefs['sms_pref']) echo "checked"; ?>>
      <label class="form-check-label" for="sms_pref">SMS Notifications</label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="inapp_pref" id="inapp_pref"
             <?php if($prefs['inapp_pref']) echo "checked"; ?>>
      <label class="form-check-label" for="inapp_pref">In App Notifications</label>
    </div>
    <button type="submit" class="btn btn-primary mt-3">Save Preferences</button>
  </form>
</div>

<?php include "../includes/footer.php"; ?>
