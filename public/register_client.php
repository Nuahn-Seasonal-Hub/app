<?php
require_once("../config/init.php");
include_once("../includes/header.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, 'client', NOW())");
    $stmt->execute([$name, $email, $password]);

    flashSuccess("Client account created successfully. Please log in.");
    header("Location: login.php");
    exit;
}
?>

<div class="auth">
  <?php nu_auth_aside(); ?>
  <section class="auth__main">
    <div class="auth-card">
      <?php nu_auth_head('Join as a client', 'Post seasonal jobs and hire trusted providers.', 'briefcase-fill', false); ?>
      <?php nu_register_form(); ?>
      <p class="auth-alt">Looking for work instead? <a href="register_provider.php">Join as a provider</a> · <a href="login_client.php">Sign in</a></p>
    </div>
  </section>
</div>

<?php include_once("../includes/footer.php"); ?>
