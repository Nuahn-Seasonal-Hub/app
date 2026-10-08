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

 $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, 'admin', NOW())");

    $stmt->execute([$name, $email, $password]);

    flashSuccess("Admin account created successfully. Please log in.");
    header("Location: login.php");
    exit;
}
?>

<main class="container py-5">
  <h2 class="mb-4">Register as Admin</h2>
  <form method="POST">
    <div class="mb-3">
      <label class="form-label">Name</label>
      <input type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Register</button>
  </form>
</main>

<?php include_once("../includes/footer.php"); ?>
