<?php
// public/login_provider.php
require_once("../config/init.php");
require_once __DIR__ . '/../includes/marketplace/core.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!mk_csrf_ok()) {
        flashError("Your session expired. Please try signing in again.");
        header("Location: login_provider.php?error=invalid");
        exit;
    }
    $email    = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'provider'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if (($user['status'] ?? 'active') === 'suspended') {
            flashError("Your account has been suspended. Please contact support.");
            header("Location: login_provider.php?error=suspended");
            exit;
        }
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['theme']     = $user['theme_preference'] ?? 'light';
        $next = mk_safe_next($_POST['next'] ?? '');
        if ($next !== '') {
            header('Location: ' . nu_url(strpos($next, 'public/') === 0 ? $next : 'public/' . $next));
            exit;
        }
        header("Location: provider_home.php");
        exit;
    } else {
        flashError("Invalid provider credentials.");
        header("Location: login_provider.php?error=invalid");
        exit;
    }
}

include_once("../includes/header.php");
?>

<div class="auth">
  <?php nu_auth_aside(); ?>
  <section class="auth__main">
    <div class="auth-card">
      <?php nu_auth_head('Provider sign in', 'Welcome back. Find and apply to seasonal jobs near you.', 'person-badge', false); ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid' && empty($nuFlashes)): ?>
      <div class="alert alert-danger"><?= nu_icon('x-circle') ?> Invalid email or password.</div>
    <?php elseif (isset($_GET['error']) && $_GET['error'] === 'suspended' && empty($nuFlashes)): ?>
      <div class="alert alert-danger"><?= nu_icon('x-circle') ?> Your account has been suspended. Contact support.</div>
    <?php endif; ?>
      <?php nu_login_form('login_provider.php'); ?>
      <div class="divider">Other sign-in options</div>
      <div class="role-links"><a class="btn btn-outline btn-sm" href="login.php?mode=otp">SMS OTP</a><a class="btn btn-outline btn-sm" href="login.php">Client</a><a class="btn btn-outline btn-sm" href="login_admin.php">Team</a></div>
      <p class="auth-alt">New to Nuahn? <a href="provider_onboarding.php">Create a provider account</a></p>
    </div>
  </section>
</div>

<?php include_once("../includes/footer.php"); ?>
