<?php
// Launch gate: public domain shows only the Coming Soon page until launch (includes/gate.php).
require_once __DIR__ . '/../includes/gate.php';
require_once "../config/init.php";
require_once "../includes/onboarding.php";
ob_ensure_users_schema();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$user = ob_user_get($user_id);
$pd = ob_prof_data($user);
$ver = ob_client_verification_status($user);
$cities = ob_canadian_cities();
$displayEmail = $ver['has_real_email'] ? (string) ($user['email'] ?? '') : '';
$phoneDigits = preg_replace('/^\+?1/', '', preg_replace('/[^\d+]/', '', (string) ($user['phone'] ?? '')));

include "../includes/header.php";
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

<!-- Contact Verification (Email + Optional SMS OTP Phone Verification) -->
<div class="card card-pad" id="verification" data-reveal>
  <div class="card-head">
    <span class="stat__icon stat__icon--success"><?= nu_icon('shield-check') ?></span>
    <div>
      <h2>Contact Verification</h2>
      <p class="muted mb-0" style="font-size:var(--fs-sm)">Verified contact details keep bookings, offers, and receipts reliable.</p>
    </div>
  </div>

  <!-- Email Verification Row -->
  <div class="cl-vrow">
    <div class="cl-vrow__top">
      <div>
        <b>Email address</b>
        <small class="muted d-block"><?= $displayEmail !== '' ? nu_e($displayEmail) : 'No email address added yet (signed up via SMS OTP)' ?></small>
      </div>
      <span class="chip <?= $ver['email_ok'] ? 'chip--success' : 'chip--warning' ?>"><?= $ver['email_ok'] ? 'Verified' : ($ver['has_real_email'] ? 'Pending' : 'Not set') ?></span>
    </div>
<?php if (!$ver['has_real_email']): ?>
    <form action="<?= nu_e(nu_url('actions/onboarding.php')) ?>" method="POST" class="ob-otp-row mt-2">
      <?= nu_csrf_field() ?>
      <input type="hidden" name="do" value="update_client_email">
      <input type="hidden" name="back" value="public/profile.php#verification">
      <input type="email" class="input" name="email" placeholder="you@example.com" required>
      <button type="submit" class="btn btn-primary">Save &amp; verify email</button>
    </form>
<?php elseif (!$ver['email_ok']): ?>
    <div class="cl-verify__acts mt-2">
      <form action="<?= nu_e(nu_url('actions/onboarding.php')) ?>" method="POST" class="d-inline">
        <?= nu_csrf_field() ?>
        <input type="hidden" name="do" value="resend_email">
        <input type="hidden" name="back" value="public/profile.php#verification">
        <button type="submit" class="btn btn-outline btn-sm"><?= nu_icon('envelope') ?> Resend verification email</button>
      </form>
<?php if (!empty($_SESSION['ob_last_verify_link'])): ?>
      <a class="btn btn-primary btn-sm" href="<?= nu_e($_SESSION['ob_last_verify_link']) ?>"><?= nu_icon('check-circle-fill') ?> Beta: verify email now</a>
<?php endif; ?>
    </div>
<?php endif; ?>
  </div>

  <!-- Phone Verification Row -->
  <div class="cl-vrow">
    <div class="cl-vrow__top">
      <div>
        <b>Mobile phone</b>
        <small class="muted d-block"><?= !empty($user['phone']) ? nu_e($user['phone']) : 'Optional — verify for SMS alerts &amp; instant OTP sign-in' ?></small>
      </div>
      <span class="chip <?= $ver['phone_ok'] ? 'chip--success' : 'chip--soft' ?>"><?= $ver['phone_ok'] ? 'Verified' : 'Optional · Not verified' ?></span>
    </div>
<?php if (!$ver['phone_ok']): ?>
    <form action="<?= nu_e(nu_url('actions/onboarding.php')) ?>" method="POST" class="mt-2" id="profPhoneForm">
      <?= nu_csrf_field() ?>
      <input type="hidden" name="do" value="verify_phone">
      <input type="hidden" name="back" value="public/profile.php#verification">
      <div class="ob-phone-row">
        <select class="input ob-cc" name="phone_code" id="pr-cc" aria-label="Country code">
          <option value="+1" selected>+1</option>
        </select>
        <input type="tel" class="input" id="pr-phone" name="phone" value="<?= nu_e($phoneDigits) ?>" placeholder="780 555 1234" maxlength="16" autocomplete="tel-national" required>
      </div>
      <div class="ob-otp-row">
        <input type="text" class="input" id="pr-otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="Enter 6-digit SMS code" required>
        <button type="button" class="btn btn-outline" id="pr-get-code">Get Code</button>
        <button type="submit" class="btn btn-primary">Verify phone</button>
      </div>
      <p class="hint" id="pr-otp-status" aria-live="polite" style="min-height:1.2em;margin-top:6px"></p>
    </form>
<?php endif; ?>
  </div>
</div>

<!-- Personal Details & Saved Service Area -->
<div class="card card-pad mt-6" data-reveal>
  <div class="card-head"><span class="stat__icon"><?= nu_icon('person') ?></span><div><h2>Personal details &amp; area</h2><p class="muted mb-0" style="font-size:var(--fs-sm)"><?= $displayEmail !== '' ? nu_e($displayEmail) : nu_e($user['phone'] ?? '') ?></p></div></div>
  <form action="../actions/update_profile.php" method="POST" enctype="multipart/form-data">
    <?= nu_csrf_field() ?>
    <div class="form-grid">
      <label class="field" for="firstname"><span class="label">First Name</span>
        <input type="text" class="input" id="firstname" name="firstname" value="<?= nu_e($user['firstname'] ?? '') ?>">
      </label>
      <label class="field" for="lastname"><span class="label">Last Name</span>
        <input type="text" class="input" id="lastname" name="lastname" value="<?= nu_e($user['lastname'] ?? '') ?>">
      </label>
      <label class="field" for="city"><span class="label">Service City</span>
        <input type="text" class="input" id="city" name="city" list="nu-prof-cities" value="<?= nu_e($pd['city'] ?? '') ?>" placeholder="e.g. Edmonton, AB">
        <datalist id="nu-prof-cities">
<?php foreach ($cities as $c): ?>
          <option value="<?= nu_e($c) ?>">
<?php endforeach; ?>
        </datalist>
      </label>
      <label class="field" for="postal_code"><span class="label">Postal Code</span>
        <input type="text" class="input" id="postal_code" name="postal_code" value="<?= nu_e($pd['postal_code'] ?? '') ?>" placeholder="T5J 0N3" maxlength="7" style="text-transform:uppercase">
      </label>
      <label class="field span-2" for="contact_info"><span class="label">Contact Info</span>
        <input type="text" class="input" id="contact_info" name="contact_info" value="<?= nu_e($user['contact_info'] ?? '') ?>">
      </label>
      <label class="field" for="photo"><span class="label">Profile Photo</span>
        <input type="file" class="input" id="photo" name="photo">
        <?php if (!empty($user['photo']) && is_file(__DIR__ . '/../uploads/' . basename($user['photo']))): ?>
          <img src="../uploads/<?= nu_e($user['photo']) ?>" alt="Profile photo" class="preview-img" style="max-width:150px;" loading="lazy">
        <?php endif; ?>
      </label>
      <label class="field" for="theme_preference"><span class="label">Theme Preference</span>
        <select class="input" id="theme_preference" name="theme_preference">
          <option value="light" <?= ($user['theme_preference'] ?? '') === 'light' ? 'selected' : '' ?>>Light</option>
          <option value="dark" <?= ($user['theme_preference'] ?? '') === 'dark' ? 'selected' : '' ?>>Dark</option>
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

<script>
(function () {
  var btn = document.getElementById('pr-get-code');
  if (!btn) return;
  var form = document.getElementById('profPhoneForm');
  var phoneIn = document.getElementById('pr-phone');
  var otpIn = document.getElementById('pr-otp');
  var status = document.getElementById('pr-otp-status');

  btn.addEventListener('click', function () {
    var digits = phoneIn.value.replace(/\D+/g, '');
    if (digits.length === 11 && digits[0] === '1') digits = digits.slice(1);
    if (digits.length !== 10) {
      status.style.color = 'var(--danger)';
      status.textContent = 'Enter a valid 10-digit phone number first.';
      phoneIn.focus();
      return;
    }
    btn.disabled = true;
    status.style.color = 'var(--muted)';
    status.textContent = 'Sending verification code…';
    var csrfEl = form.querySelector('input[name="_csrf"]');
    var fd = new FormData();
    fd.append('_csrf', csrfEl ? csrfEl.value : '');
    fd.append('do', 'otp_send');
    fd.append('purpose', 'verify');
    fd.append('phone_code', document.getElementById('pr-cc').value || '+1');
    fd.append('phone', phoneIn.value);
    fetch(form.action, { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res.ok) throw new Error(res.error || 'Could not send code.');
        status.style.color = 'var(--success)';
        status.textContent = res.sandbox
          ? 'Beta sandbox code: ' + res.sandbox_code + ' (live SMS activates once Twilio is configured).'
          : 'Verification code sent to ' + res.phone + '.';
        if (res.sandbox && res.sandbox_code && !otpIn.value) otpIn.value = res.sandbox_code;
        var wait = 30;
        var t = setInterval(function () {
          wait--;
          btn.textContent = wait > 0 ? 'Resend (' + wait + 's)' : 'Get Code';
          if (wait <= 0) { clearInterval(t); btn.disabled = false; }
        }, 1000);
      })
      .catch(function (err) {
        status.style.color = 'var(--danger)';
        status.textContent = err.message;
        btn.disabled = false;
      });
  });
})();
</script>

<?php include "../includes/footer.php"; ?>
