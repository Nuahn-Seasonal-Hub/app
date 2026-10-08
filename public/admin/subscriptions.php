<?php
require_once "../../config/db.php";
session_start();

// Ensure only admins can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit;
}

include "../../includes/header.php";
?>

<div class="container mt-4">
  <h2 class="mb-4">Subscription Management</h2>

  <a href="create_plan.php" class="btn btn-success mb-3">Create New Plan</a>

  <table class="table table-striped">
    <thead>
      <tr>
        <th>User</th>
        <th>Plan</th>
        <th>Start Date</th>
        <th>Expiry Date</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $stmt = $pdo->query("SELECT s.id, u.name, p.name AS plan_name, s.start_date, s.expiry_date, s.status
                           FROM subscriptions s
                           JOIN users u ON s.user_id = u.id
                           JOIN plans p ON s.plan_id = p.id
                           ORDER BY s.start_date DESC");
      foreach ($stmt as $row) {
          echo "<tr>
                  <td>{$row['name']}</td>
                  <td>{$row['plan_name']}</td>
                  <td>{$row['start_date']}</td>
                  <td>{$row['expiry_date']}</td>
                  <td>{$row['status']}</td>
                  <td>
                    <a href='edit_subscription.php?id={$row['id']}' class='btn btn-primary btn-sm'>Edit</a>
                    <a href='../actions/delete_subscription.php?id={$row['id']}' class='btn btn-danger btn-sm'>Delete</a>
                  </td>
                </tr>";
      }
      ?>
    </tbody>
  </table>
</div>

<?php include "../../includes/footer.php"; ?>
