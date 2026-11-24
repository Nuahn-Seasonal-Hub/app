<?php
// public/verify_email.php
// Verifies a user's email address from the token sent during onboarding.
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../includes/onboarding.php';

$token = (string) ($_GET['token'] ?? '');
$verifiedUser = $token !== '' ? ob_email_verify_token($token) : null;

include_once __DIR__ . '/../includes/header.php';
?>
<div class="auth">
  <?php nu_auth_aside('Verified accounts build real local trust.', 'Confirmed email and phone details keep bookings, quotes, and payouts reliable for everyone on Nuahn.'); ?>
  <section class="auth__main">
    <div class="auth-card">
<?php if ($verifiedUser): ?>
      <?php nu_auth_head('Email verified', 'Thanks, ' . $verifiedUser['name'] . '. Your email address is now confirmed.', 'check-circle-fill', false); ?>
      <div class="alert alert-success"><?= nu_icon('check-circle-fill') ?> <?= nu_e($verifiedUser['email']) ?> is verified.</div>
      <?php if (isset($_SESSION['user_id']) && nu_role() === 'provider'): ?>
        <a class="btn btn-primary btn-lg btn-block" href="<?= nu_e(nu_url('public/contractor_setup.php?tab=contact')) ?>">Continue to verification hub <?= nu_icon('arrow-right') ?></a>
      <?php elseif (isset($_SESSION['user_id'])): ?>
        <a class="btn btn-primary btn-lg btn-block" href="<?= nu_e(nu_home_url()) ?>">Go to my dashboard <?= nu_icon('arrow-right') ?></a>
      <?php else: ?>
        <a class="btn btn-primary btn-lg btn-block" href="<?= nu_e(nu_url('public/login.php')) ?>">Sign in to continue <?= nu_icon('arrow-right') ?></a>
      <?php endif; ?>
<?php else: ?>
      <?php nu_auth_head('Verification link expired', 'This verification link is invalid or has already been used.', 'exclamation-circle', true); ?>
      <div class="alert alert-warning"><?= nu_icon('exclamation-circle') ?> Request a fresh verification link from your account settings or verification hub.</div>
      <a class="btn btn-primary btn-lg btn-block" href="<?= nu_e(isset($_SESSION['user_id']) && nu_role() === 'provider' ? nu_url('public/contractor_setup.php?tab=contact') : nu_url('public/login.php')) ?>">Back to Nuahn</a>
<?php endif; ?>
    </div>
  </section>
</div>
<?php include_once __DIR__ . '/../includes/footer.php'; ?>
