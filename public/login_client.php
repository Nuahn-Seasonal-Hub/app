<?php
// public/login_client.php
require_once("../config/init.php");
include_once("../includes/header.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'client'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role']      = $user['role'];
        header("Location: client_home.php");
        exit;
    } else {
        flashError("Invalid client credentials.");
        header("Location: login_client.php?error=invalid");
        exit;
    }
}
?>

<div class="auth">
  <?php nu_auth_aside(); ?>
  <section class="auth__main">
    <div class="auth-card">
      <?php nu_auth_head('Client sign in', 'Welcome back. Manage your seasonal jobs and applicants.', 'briefcase-fill', false); ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid' && empty($nuFlashes)): ?>
      <div class="alert alert-danger"><?= nu_icon('x-circle') ?> Invalid email or password.</div>
    <?php endif; ?>
      <?php nu_login_form('login_client.php'); ?>
      <div class="divider">Other sign-in options</div>
      <div class="role-links"><a class="btn btn-outline btn-sm" href="login_provider.php">Provider</a><a class="btn btn-outline btn-sm" href="login_admin.php">Team</a></div>
      <p class="auth-alt">New to Nuahn? <a href="register_client.php">Create a client account</a></p>
    </div>
  </section>
</div>

<?php include_once("../includes/footer.php"); ?>
