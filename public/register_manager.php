<?php
require_once("../config/init.php");
include_once("../includes/header.php");
requireLogin();
if ($_SESSION['role'] !== 'superadmin') {
    flashError("Unauthorized access.");
    header("Location: /Nuahn/public/login.php?error=unauthorized");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

   $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, 'manager', NOW())");


    $stmt->execute([$name, $email, $password]);

    flashSuccess("Manager account created successfully. Please log in.");
    header("Location: login.php");
    exit;
}
?>

<div class="auth">
  <?php nu_auth_aside(); ?>
  <section class="auth__main">
    <div class="auth-card">
      <?php nu_auth_head('Add a manager', 'Create a new manager account. Superadmin only.', 'shield-check', true); ?>
      <?php nu_register_form(); ?>
      
    </div>
  </section>
</div>

<?php include_once("../includes/footer.php"); ?>
