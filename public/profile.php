<?php
include "../includes/header.php";
require_once "../config/db.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<h2 class="mb-4">Profile Settings</h2>
<form action="../actions/update_profile.php" method="POST" enctype="multipart/form-data">
  <div class="mb-3">
    <label for="firstname" class="form-label">First Name</label>
    <input type="text" class="form-control" id="firstname" name="firstname" 
           value="<?php echo htmlspecialchars($user['firstname']); ?>">
  </div>
  <div class="mb-3">
    <label for="lastname" class="form-label">Last Name</label>
    <input type="text" class="form-control" id="lastname" name="lastname" 
           value="<?php echo htmlspecialchars($user['lastname']); ?>">
  </div>
  <div class="mb-3">
    <label for="contact_info" class="form-label">Contact Info</label>
    <input type="text" class="form-control" id="contact_info" name="contact_info" 
           value="<?php echo htmlspecialchars($user['contact_info']); ?>">
  </div>
  <div class="mb-3">
    <label for="photo" class="form-label">Profile Photo</label>
    <input type="file" class="form-control" id="photo" name="photo">
    <?php if($user['photo']): ?>
      <img src="../uploads/<?php echo htmlspecialchars($user['photo']); ?>" 
           class="img-fluid mt-2" style="max-width:150px;">
    <?php endif; ?>
  </div>
  <div class="mb-3">
    <label for="theme_preference" class="form-label">Theme Preference</label>
    <select class="form-select" id="theme_preference" name="theme_preference">
      <option value="light" <?php if($user['theme_preference']==='light') echo 'selected'; ?>>Light</option>
      <option value="dark" <?php if($user['theme_preference']==='dark') echo 'selected'; ?>>Dark</option>
    </select>
  </div>
  <button type="submit" class="btn btn-primary w-100">Save Changes</button>
</form>
<hr class="my-4">
<h3 class="mb-3">Change Password</h3>
<form action="../actions/change_password.php" method="POST">
  <div class="mb-3">
    <label for="current_password" class="form-label">Current Password</label>
    <input type="password" class="form-control" id="current_password" name="current_password" required>
  </div>
  <div class="mb-3">
    <label for="new_password" class="form-label">New Password</label>
    <input type="password" class="form-control" id="new_password" name="new_password" required>
  </div>
  <div class="mb-3">
    <label for="confirm_password" class="form-label">Confirm New Password</label>
    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
  </div>
  <button type="submit" class="btn btn-warning w-100">Update Password</button>
</form>


<?php include "../includes/footer.php"; ?>
