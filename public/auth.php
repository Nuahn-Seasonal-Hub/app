<?php
// public/auth.php
require_once("../config/init.php");
include_once("../includes/header.php");
?>

<section class="band band--sm">
  <div class="wrap text-center">
    <span class="eyebrow"><?= nu_icon('stars') ?> Welcome to Nuahn Seasonal Hub</span>
    <h1>How would you like to continue?</h1>
    <p style="margin-inline:auto">Choose your role to sign in or create an account.</p>
  </div>
</section>

<main class="wrap page-body">
  <?php if (isset($_GET['message']) && $_GET['message'] === 'loggedout'): ?>
    <div class="alert alert-success"><?= nu_icon('check-circle-fill') ?> You’ve been logged out successfully.</div>
  <?php endif; ?>
  <?php if (isset($_GET['message']) && $_GET['message'] === 'timeout'): ?>
    <div class="alert alert-info"><?= nu_icon('clock') ?> Your session timed out. Please sign in again.</div>
  <?php endif; ?>
  <?php include_once("../includes/flash.php"); ?>

  <div class="grid grid-3">
    <!-- Client -->
    <div class="card role-card" data-reveal style="--i:0">
      <div class="tile__icon"><?= nu_icon('briefcase-fill') ?></div>
      <h3>Client</h3>
      <p>Post and manage seasonal jobs, then review and approve applicants.</p>
      <a href="login_client.php" class="btn btn-primary btn-block">Sign in as client</a>
      <a href="register_client.php" class="btn btn-outline btn-block">Register as client</a>
    </div>

    <!-- Provider -->
    <div class="card role-card" data-reveal style="--i:1">
      <div class="tile__icon"><?= nu_icon('person-badge') ?></div>
      <h3>Provider</h3>
      <p>Browse jobs near you, save the ones you like and apply in a tap.</p>
      <a href="login_provider.php" class="btn btn-primary btn-block">Sign in as provider</a>
      <a href="register_provider.php" class="btn btn-outline btn-block">Register as provider</a>
    </div>

    <!-- Manager/Admin/Superadmin -->
    <div class="card role-card" data-reveal style="--i:2">
      <div class="tile__icon tile__icon--navy"><?= nu_icon('shield-check') ?></div>
      <h3>Management</h3>
      <p>Restricted access for managers, admins, and superadmins.</p>
      <a href="login_admin.php" class="btn btn-navy btn-block">Team sign in</a>
      <?php if (isset($_SESSION['role']) === 'superadmin'): ?>
        <a href="register_admin.php" class="btn btn-outline btn-block">Register Admin/Manager</a>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php include_once("../includes/footer.php"); ?>
