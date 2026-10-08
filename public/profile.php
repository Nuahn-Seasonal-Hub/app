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

<section class="band band--sm">
  <div class="wrap band__row">
    <div class="welcome">
      <span class="avatar"><?= nu_e(nu_initials($user['name'] ?? ($_SESSION['user_name'] ?? 'U'))) ?></span>
      <div>
        <p class="welcome__hi"><?= nu_e(ucfirst($user['role'] ?? '')) ?> account</p>
        <h1 style="margin:0">Profile Settings</h1>
      </div>
    </div>
  </div>
</section>

<main class="wrap wrap-sm page-body">
<?php if (isset($_GET['success'])): ?>
  <div class="alert alert-success"><?= nu_icon('check-circle-fill') ?> Changes saved.</div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
  <div class="alert alert-danger"><?= nu_icon('x-circle') ?> Something went wrong (<?= nu_e($_GET['error']) ?>).</div>
<?php endif; ?>

<div class="card card-pad" data-reveal>
  <div class="card-head"><span class="stat__icon"><?= nu_icon('person') ?></span><div><h2>Personal details</h2><p class="muted mb-0" style="font-size:var(--fs-sm)"><?= nu_e($user['email'] ?? '') ?></p></div></div>
  <form action="../actions/update_profile.php" method="POST" enctype="multipart/form-data">
    <div class="form-grid">
      <label class="field" for="firstname"><span class="label">First Name</span>
        <input type="text" class="input" id="firstname" name="firstname" 
               value="<?php echo htmlspecialchars($user['firstname'] ?? ''); ?>">
      </label>
      <label class="field" for="lastname"><span class="label">Last Name</span>
        <input type="text" class="input" id="lastname" name="lastname" 
               value="<?php echo htmlspecialchars($user['lastname'] ?? ''); ?>">
      </label>
      <label class="field span-2" for="contact_info"><span class="label">Contact Info</span>
        <input type="text" class="input" id="contact_info" name="contact_info" 
               value="<?php echo htmlspecialchars($user['contact_info'] ?? ''); ?>">
      </label>
      <label class="field" for="photo"><span class="label">Profile Photo</span>
        <input type="file" class="input" id="photo" name="photo">
        <?php if(!empty($user['photo']) && is_file(__DIR__ . '/../uploads/' . basename($user['photo']))): ?>
          <img src="../uploads/<?php echo htmlspecialchars($user['photo']); ?>" alt="Profile photo" class="preview-img" style="max-width:150px;" loading="lazy">
        <?php endif; ?>
      </label>
      <label class="field" for="theme_preference"><span class="label">Theme Preference</span>
        <select class="input" id="theme_preference" name="theme_preference">
          <option value="light" <?php if(($user['theme_preference'] ?? '')==='light') echo 'selected'; ?>>Light</option>
          <option value="dark" <?php if(($user['theme_preference'] ?? '')==='dark') echo 'selected'; ?>>Dark</option>
        </select>
      </label>
    </div>
    <button type="submit" class="btn btn-primary btn-lg btn-block">Save Changes</button>
  </form>
</div>

<div class="card card-pad mt-6" data-reveal>
  <div class="card-head"><span class="stat__icon stat__icon--navy"><?= nu_icon('lock') ?></span><h2>Change Password</h2></div>
  <form action="../actions/change_password.php" method="POST">
    <?php nu_password_field('current_password', 'current_password', 'Current Password'); ?>
    <div class="form-grid">
      <?php nu_password_field('new_password', 'new_password', 'New Password', 'new-password'); ?>
      <?php nu_password_field('confirm_password', 'confirm_password', 'Confirm New Password', 'new-password'); ?>
    </div>
    <button type="submit" class="btn btn-navy btn-lg btn-block">Update Password</button>
  </form>
</div>

<a class="btn btn-danger-soft btn-lg btn-block mt-6" href="logout.php" data-no-prefetch><?= nu_icon('box-arrow-right') ?> Sign out</a>
</main>

<?php include "../includes/footer.php"; ?>
