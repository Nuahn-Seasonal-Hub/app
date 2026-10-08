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

<main class="container py-5" style="max-width: 500px;">
  <h2 class="mb-4 text-center">Client Login</h2>
  <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid'): ?>
    <div class="alert alert-danger text-center">Invalid email or password.</div>
  <?php endif; ?>
  <form method="POST" action="login_client.php">
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" required autofocus>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Login</button>
  </form>
</main>

<?php include_once("../includes/footer.php"); ?>
