<?php
// public/login_admin.php
require_once("../config/init.php");
include_once("../includes/header.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && in_array($user['role'], ['manager','admin','superadmin']) && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role']      = $user['role'];
        header("Location: dashboard.php");
        exit;
    } else {
        flashError("Invalid management credentials.");
        header("Location: login_admin.php?error=invalid");
        exit;
    }
}
?>

<div class="auth">
  <?php nu_auth_aside(); ?>
  <section class="auth__main">
    <div class="auth-card">
      <?php nu_auth_head('Team sign in', 'Restricted access for managers, admins and superadmins.', 'shield-check', true); ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid' && empty($nuFlashes)): ?>
      <div class="alert alert-danger"><?= nu_icon('x-circle') ?> Invalid email or password.</div>
    <?php endif; ?>
      <?php nu_login_form('login_admin.php'); ?>
      <div class="divider">Other sign-in options</div>
      <div class="role-links"><a class="btn btn-outline btn-sm" href="login_client.php">Client</a><a class="btn btn-outline btn-sm" href="login_provider.php">Provider</a></div>
      
    </div>
  </section>
</div>

<?php include_once("../includes/footer.php"); ?>
