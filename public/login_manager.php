<?php
require_once("../config/init.php");
include_once("../includes/header.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'manager'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role']      = $user['role'];
        header("Location: dashboard.php");
        exit;
    } else {
        flashError("Invalid manager credentials.");
        header("Location: login_manager.php");
        exit;
    }
}
?>

<div class="auth">
  <?php nu_auth_aside(); ?>
  <section class="auth__main">
    <div class="auth-card">
      <?php nu_auth_head('Manager sign in', 'Restricted access for managers.', 'shield-check', true); ?>
      <?php nu_login_form(null); ?>
      <div class="divider">Other sign-in options</div>
      <div class="role-links"><a class="btn btn-outline btn-sm" href="login_admin.php">Team</a><a class="btn btn-outline btn-sm" href="login.php">All roles</a></div>
      
    </div>
  </section>
</div>

<?php include_once("../includes/footer.php"); ?>
