<?php
// public/login.php
require_once("../config/init.php");
require_once __DIR__ . '/../includes/marketplace/core.php';

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    redirectByRole($_SESSION['role']);
    exit;
}
$mode = ($_GET['mode'] ?? '') === 'otp' ? 'otp' : 'password';
$next = mk_safe_next($_GET['next'] ?? '');
include_once("../includes/header.php");
?>

<div class="auth">
  <?php nu_auth_aside('Welcome back to Nuahn.', 'Sign in to post jobs, track applications and find seasonal work near you.'); ?>
  <section class="auth__main">
    <div class="auth-card">
      <?php nu_auth_head('Sign in', 'Sign in with your email and password, or via SMS verification code.', 'person-circle'); ?>

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

      <!-- Sign-in method tabs (Nuahn native styling) -->
      <div class="ob-mode-tabs" role="tablist" aria-label="Sign-in method">
        <a href="login.php<?= $next !== '' ? '?next=' . rawurlencode($next) : '' ?>" class="ob-mode-tab<?= $mode === 'password' ? ' is-active' : '' ?>" role="tab" aria-selected="<?= $mode === 'password' ? 'true' : 'false' ?>"><?= nu_icon('envelope') ?> Email &amp; Password</a>
        <a href="login.php?mode=otp<?= $next !== '' ? '&next=' . rawurlencode($next) : '' ?>" class="ob-mode-tab<?= $mode === 'otp' ? ' is-active' : '' ?>" role="tab" aria-selected="<?= $mode === 'otp' ? 'true' : 'false' ?>"><?= nu_icon('phone') ?> Sign in via OTP</a>
      </div>

<?php if ($mode === 'otp'): ?>
      <!-- Passwordless SMS OTP Login -->
      <form method="POST" action="../actions/onboarding.php" class="auth-form" id="otpLoginForm">
        <?= nu_csrf_field() ?>
        <input type="hidden" name="do" value="otp_login">
        <input type="hidden" name="back" value="public/login.php?mode=otp">
        <?php if ($next !== ''): ?><input type="hidden" name="next" value="<?= nu_e($next) ?>"><?php endif; ?>

        <div class="field">
          <span class="label">Registered phone number</span>
          <div class="ob-phone-row">
            <select class="input ob-cc" name="phone_code" id="l-cc" aria-label="Country code"><option value="+1" selected>+1</option></select>
            <input type="tel" name="phone" id="l-phone" class="input" placeholder="780 555 1234" autocomplete="tel-national" required autofocus>
          </div>
        </div>

        <div class="field">
          <span class="label">Verification code</span>
          <div class="ob-otp-row">
            <input type="text" name="otp" id="l-otp" class="input" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="123456" required>
            <button type="button" class="btn btn-outline" id="l-get-code">Get Code</button>
          </div>
          <p class="hint" id="l-otp-status" aria-live="polite"></p>
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block">Sign in with code <?= nu_icon('arrow-right') ?></button>
      </form>
<?php else: ?>
      <!-- Email & Password Login Form -->
      <form method="POST" action="../actions/login_action.php" class="auth-form">
        <?= nu_csrf_field() ?>
        <?php if ($next !== ''): ?><input type="hidden" name="next" value="<?= nu_e($next) ?>"><?php endif; ?>
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
<?php endif; ?>

      <p class="auth-alt">
        Don’t have an account?
        <a href="register.php<?= $next !== '' ? '?next=' . rawurlencode($next) : '' ?>">Join as a client</a>
        · <a href="provider_onboarding.php">Become a provider</a>
      </p>
    </div>
  </section>
</div>

<?php if ($mode === 'otp'): ?>
<script>
(function () {
  var btn = document.getElementById('l-get-code');
  var form = document.getElementById('otpLoginForm');
  if (!btn || !form) return;
  var status = document.getElementById('l-otp-status');
  var csrf = form.querySelector('input[name="_csrf"]');
  btn.addEventListener('click', function () {
    btn.disabled = true;
    status.textContent = 'Sending code...';
    status.style.color = 'var(--muted)';
    var fd = new FormData();
    fd.append('_csrf', csrf ? csrf.value : '');
    fd.append('do', 'otp_send');
    fd.append('purpose', 'login');
    fd.append('phone_code', document.getElementById('l-cc').value);
    fd.append('phone', document.getElementById('l-phone').value.trim());
    fetch('<?= nu_e(nu_url('actions/onboarding.php')) ?>', { method: 'POST', headers: { 'Accept': 'application/json' }, body: fd })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d.ok) { status.textContent = d.error || 'Could not send code.'; status.style.color = 'var(--danger)'; btn.disabled = false; return; }
        status.textContent = (d.sandbox && d.sandbox_code) ? ('Beta mode — your login code is: ' + d.sandbox_code) : ('Verification code sent to ' + d.phone);
        status.style.color = 'var(--pine)';
        setTimeout(function () { btn.disabled = false; }, 30000);
      })
      .catch(function () { status.textContent = 'Network error.'; status.style.color = 'var(--danger)'; btn.disabled = false; });
  });
})();
</script>
<?php endif; ?>

<?php include_once("../includes/footer.php"); ?>
