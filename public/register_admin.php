<?php
require_once("../config/init.php");
require_once __DIR__ . '/../includes/marketplace/core.php';
// Only superadmins can create staff accounts (others are sent to their own home).
requireLogin('superadmin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!mk_csrf_ok()) {
        flashError("Your session expired. Please refresh and try again.");
        header("Location: register_admin.php");
        exit;
    }
    $name  = trim((string) ($_POST['name'] ?? ''));
    $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    $rawPw = (string) ($_POST['password'] ?? '');

    if (mb_strlen($name) < 2) {
        flashError("Enter the staff member's full name.");
        header("Location: register_admin.php");
        exit;
    }
    if (!$email) {
        flashError("Enter a valid email address.");
        header("Location: register_admin.php");
        exit;
    }
    if (strlen($rawPw) < 8) {
        flashError("Password must be at least 8 characters.");
        header("Location: register_admin.php");
        exit;
    }

    $chk = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $chk->execute([$email]);
    if ($chk->fetch()) {
        flashError("An account with that email address already exists.");
        header("Location: register_admin.php");
        exit;
    }

    $parts = preg_split('/\s+/', $name, 2);
    $first = $parts[0] ?? $name;
    $last  = $parts[1] ?? null;
    $password = password_hash($rawPw, PASSWORD_DEFAULT);

    // users.role has no 'admin' value (superadmin, manager, client, provider), so admin sign-ups are created as managers.
    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, firstname, lastname, created_at) VALUES (?, ?, ?, 'manager', ?, ?, NOW())");
        $stmt->execute([$name, $email, $password, $first, $last]);
    } catch (Throwable $e) {
        flashError("Could not create admin account (email may already be in use).");
        header("Location: register_admin.php");
        exit;
    }

    flashSuccess("Admin account created for {$name} ({$email}).");
    header("Location: admin/manage_admins.php");
    exit;
}

include_once("../includes/header.php");
?>

<div class="auth">
  <?php nu_auth_aside(); ?>
  <section class="auth__main">
    <div class="auth-card">
      <?php nu_auth_head('Add an admin', 'Create a new admin account. Superadmin only.', 'shield-check', true); ?>
      <?php nu_register_form('Create admin account'); ?>
      <p class="auth-alt"><a href="admin/manage_admins.php">Back to Manage staff</a></p>
    </div>
  </section>
</div>

<?php include_once("../includes/footer.php"); ?>
