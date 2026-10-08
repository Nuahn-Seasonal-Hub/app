<?php
require_once("../config/init.php");
// Only superadmins can create staff accounts (others are sent to their own home).
requireLogin('superadmin');
include_once("../includes/header.php");


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

 $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, 'superadmin', NOW())");

    $stmt->execute([$name, $email, $password]);

    flashSuccess("Super Admin account created successfully. Please log in.");
    header("Location: login.php");
    exit;
}
?>

<div class="auth">
  <?php nu_auth_aside(); ?>
  <section class="auth__main">
    <div class="auth-card">
      <?php nu_auth_head('Add a superadmin', 'Create a new superadmin account. Superadmin only.', 'shield-check', true); ?>
      <?php nu_register_form(); ?>
      
    </div>
  </section>
</div>

<?php include_once("../includes/footer.php"); ?>
