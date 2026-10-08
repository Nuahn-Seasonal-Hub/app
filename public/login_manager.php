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

<main class="container py-5">
  <h2 class="mb-4 text-center">Manager Login</h2>
  <form method="POST" class="mx-auto" style="max-width: 400px;">
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Login</button>
  </form>
</main>

<?php include_once("../includes/footer.php"); ?>
