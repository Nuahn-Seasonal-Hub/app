<?php
// public/auth.php
require_once("../config/init.php");
include_once("../includes/header.php");
?>

<main class="container py-5">
  <?php if (isset($_GET['message']) && $_GET['message'] === 'loggedout'): ?>
    <div class="alert alert-success text-center">
      You’ve been logged out successfully.
    </div>
  <?php endif; ?>

  <h2 class="mb-4 text-center">Welcome to Nuahn Seasonal Hub</h2>
  <p class="text-center">Choose your role to log in or register.</p>

  <div class="row justify-content-center g-4">
    <!-- Client -->
    <div class="col-md-4">
      <div class="card shadow-sm h-100 text-center">
        <div class="card-body">
          <h5 class="card-title">Client</h5>
          <p class="card-text">Post and manage seasonal jobs.</p>
          <a href="login_client.php" class="btn btn-primary w-100 mb-2">Login as Client</a>
          <a href="register_client.php" class="btn btn-outline-primary w-100">Register as Client</a>
        </div>
      </div>
    </div>

    <!-- Provider -->
    <div class="col-md-4">
      <div class="card shadow-sm h-100 text-center">
        <div class="card-body">
          <h5 class="card-title">Provider</h5>
          <p class="card-text">Browse jobs and apply for opportunities.</p>
          <a href="login_provider.php" class="btn btn-success w-100 mb-2">Login as Provider</a>
          <a href="register_provider.php" class="btn btn-outline-success w-100">Register as Provider</a>
        </div>
      </div>
    </div>

    <!-- Manager/Admin/Superadmin -->
    <div class="col-md-4">
      <div class="card shadow-sm h-100 text-center">
        <div class="card-body">
          <h5 class="card-title">Management</h5>
          <p class="card-text">Restricted access for managers, admins, and superadmins.</p>
          <a href="login_admin.php" class="btn btn-danger w-100 mb-2">Login as Admin</a>
          <?php if (isset($_SESSION['role']) === 'superadmin'): ?>
            <a href="register_admin.php" class="btn btn-outline-danger w-100">Register Admin/Manager</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</main>


<?php include_once("../includes/footer.php"); ?>
