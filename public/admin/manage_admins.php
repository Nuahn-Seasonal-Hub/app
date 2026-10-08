<?php
require_once "../../config/db.php";
//session_start();
require_once("../includes/auth.php");
requireAdmin();

// Now safe to show admin dashboard
// Only super-admins can access
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== true) {
    header("Location: ../dashboard.php?error=unauthorized");
    exit;
}

$stmt = $pdo->query("SELECT id, name, email, is_superadmin FROM users WHERE role='admin'");
$admins = $stmt->fetchAll();

include "../../includes/header.php";
?>

<div class="container mt-4">
  <h2 class="mb-4">Manage Admins</h2>
  <table class="table table-striped">
    <thead>
      <tr>
        <th>Name</th>
        <th>Email</th>
        <th>Super-Admin</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($admins as $admin): ?>
        <tr>
          <td><?php echo $admin['name']; ?></td>
          <td><?php echo $admin['email']; ?></td>
          <td><?php echo $admin['is_superadmin'] ? "Yes" : "No"; ?></td>
          <td>
            <?php if ($admin['is_superadmin']): ?>
              <a href="../../actions/demote_admin.php?id=<?php echo $admin['id']; ?>" class="btn btn-warning btn-sm">Demote</a>
            <?php else: ?>
              <a href="../../actions/promote_admin.php?id=<?php echo $admin['id']; ?>" class="btn btn-success btn-sm">Promote</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include "../../includes/footer.php"; ?>
