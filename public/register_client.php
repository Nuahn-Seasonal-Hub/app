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

<main class="container py-5">
  <h2 class="mb-4">Register as Client</h2>
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
