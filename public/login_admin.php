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

<main class="container py-5" style="max-width: 500px;">
  <h2 class="mb-4 text-center">Management Login</h2>
  <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid'): ?>
    <div class="alert alert-danger text-center">Invalid email or password.</div>
  <?php endif; ?>
  <form method="POST" action="login_admin.php">
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" required autofocus>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-danger w-100">Login</button>
  </form>
</main>

<?php include_once("../includes/footer.php"); ?>
