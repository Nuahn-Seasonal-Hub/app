<?php
// public/login.php
require_once("../config/init.php");
include_once("../includes/header.php");

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    redirectByRole($_SESSION['role']);
    exit;
}
?>

<div class="auth">
  <?php nu_auth_aside('Welcome back to Nuahn.', 'Sign in to post jobs, track applications and find seasonal work near you.'); ?>
  <section class="auth__main">
    <div class="auth-card">
      <?php nu_auth_head('Sign in', 'Choose how you use Nuahn, then enter your details.', 'person-circle'); ?>

      <!-- Flash Messages -->
      <?php if (isset($_GET['success'])): ?>
        <?php if ($_GET['success'] === 'registered'): ?>
          <div class="alert alert-success"><?= nu_icon('check-circle-fill') ?> Registration successful! Please log in.</div>
        <?php elseif ($_GET['success'] === 'loggedout' || $_GET['success'] === 'logged_out'): ?>
          <div class="alert alert-info"><?= nu_icon('check-circle-fill') ?> You’ve been logged out successfully.</div>
        <?php endif; ?>
      <?php endif; ?>

      <?php if (isset($_GET['error']) && empty($nuFlashes)): ?>
        <?php if ($_GET['error'] === 'invalid'): ?>
          <div class="alert alert-danger"><?= nu_icon('x-circle') ?> Invalid email or password.</div>
        <?php elseif ($_GET['error'] === 'unauthorized'): ?>
          <div class="alert alert-warning"><?= nu_icon('lock') ?> You must log in to access that page.</div>
        <?php elseif ($_GET['error'] === 'suspended'): ?>
          <div class="alert alert-danger"><?= nu_icon('x-circle') ?> Your account has been suspended. Contact support.</div>
        <?php endif; ?>
      <?php endif; ?>

      <!-- Login Form -->
      <form method="POST" action="../actions/login_action.php" class="auth-form">
        <fieldset class="field" style="border:0;padding:0;margin:0 0 var(--s5)">
          <legend class="label">I’m signing in as</legend>
          <div class="roles">
            <label class="role-opt"><input type="radio" name="role" value="client" required><span><?= nu_icon('briefcase-fill') ?> Client</span></label>
            <label class="role-opt"><input type="radio" name="role" value="provider"><span><?= nu_icon('person-badge') ?> Provider</span></label>
          </div>
          <details class="more">
            <summary><?= nu_icon('chevron-right') ?> Team member?</summary>
            <div class="roles roles--3 roles--sm">
              <label class="role-opt"><input type="radio" name="role" value="manager"><span>Manager</span></label>
              <label class="role-opt"><input type="radio" name="role" value="admin"><span>Admin</span></label>
              <label class="role-opt"><input type="radio" name="role" value="superadmin"><span>Superadmin</span></label>
            </div>
          </details>
        </fieldset>

        <label class="field" for="email"><span class="label">Email address</span>
          <span class="input-icon"><?= nu_icon('envelope') ?><input type="email" name="email" id="email" class="input" placeholder="you@example.com" autocomplete="email" required autofocus></span>
        </label>
        <?php nu_password_field('password', 'password', 'Password'); ?>

        <button type="submit" class="btn btn-primary btn-lg btn-block">Sign in <?= nu_icon('arrow-right') ?></button>
      </form>

      <p class="auth-alt">Don’t have an account? <a href="register.php">Create one free</a></p>
    </div>
  </section>
</div>

<?php include_once("../includes/footer.php"); ?>
