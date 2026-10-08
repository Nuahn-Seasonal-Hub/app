<?php
require_once "../../config/db.php";
//session_start();
require_once("../includes/auth.php");
requireLogin('admin');

// Admin-only content

// Ensure only admins/superadmins can access
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','superadmin'])) {
    header("Location: ../login.php?error=unauthorized");
    exit;
}

include "../../includes/header.php";
?>

<div class="container mt-4">
  <h2 class="mb-4">Admin Dashboard</h2>

  <!-- Metrics Cards -->
  <div class="row g-4">
    <div class="col-md-3">
      <div class="card text-bg-primary h-100 shadow-sm">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-people"></i> Users</h5>
          <p class="card-text">Total: <?php echo $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(); ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-bg-success h-100 shadow-sm">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-briefcase"></i> Providers</h5>
          <p class="card-text">Total: <?php echo $pdo->query("SELECT COUNT(*) FROM users WHERE role='provider'")->fetchColumn(); ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-bg-warning h-100 shadow-sm">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-card-list"></i> Jobs</h5>
          <p class="card-text">Total: <?php echo $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn(); ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-bg-danger h-100 shadow-sm">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-person-x"></i> Suspended Accounts</h5>
          <p class="card-text">Total: <?php echo $pdo->query("SELECT COUNT(*) FROM users WHERE status='suspended'")->fetchColumn(); ?></p>
        </div>
      </div>
    </div>
  </div>

  <hr class="my-4">

  <!-- Admin Tools Section -->
  <h4>Admin Tools</h4>
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <a href="manage_users.php" class="btn btn-outline-primary w-100">
        <i class="bi bi-person-gear"></i> Manage Users
      </a>
    </div>
    <div class="col-md-3">
      <a href="manage_jobs.php" class="btn btn-outline-success w-100">
        <i class="bi bi-briefcase-fill"></i> Manage Jobs
      </a>
    </div>
    <div class="col-md-3">
      <a href="system_settings.php" class="btn btn-outline-warning w-100">
        <i class="bi bi-gear"></i> System Settings
      </a>
    </div>
    <div class="col-md-3">
      <a href="reports.php" class="btn btn-outline-danger w-100">
        <i class="bi bi-bar-chart"></i> Reports
      </a>
    </div>
  </div>

  <hr class="my-4">

  <!-- Recent Activity -->
  <h4>Recent Activity</h4>
  <table class="table table-striped table-hover">
    <thead>
      <tr>
        <th>User</th>
        <th>Action</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $stmt = $pdo->query("SELECT u.name, a.action, a.created_at 
                           FROM activity_logs a 
                           JOIN users u ON a.user_id = u.id 
                           ORDER BY a.created_at DESC LIMIT 10");
      foreach ($stmt as $row) {
          echo "<tr>
                  <td>".htmlspecialchars($row['name'])."</td>
                  <td>".htmlspecialchars($row['action'])."</td>
                  <td>".htmlspecialchars($row['created_at'])."</td>
                </tr>";
      }
      ?>
    </tbody>
  </table>
  <a href='activity_logs.php' class='btn btn-outline-secondary'>View All Activity</a>
</div>

<?php include "../../includes/footer.php"; ?>