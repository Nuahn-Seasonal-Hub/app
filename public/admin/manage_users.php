<?php
require_once "../../config/db.php";
//session_start();

// Protect page: only allow admins/superadmins
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','superadmin'])) {
    header("Location: ../login.php?error=unauthorized");
    exit;
}

include "../../includes/header.php";

// Fetch all users
$stmt = $pdo->query("SELECT id, name, email, role, status, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>

<div class="container mt-4">
  <h2 class="mb-4">Manage Users</h2>

  <!-- Search bar -->
  <div class="mb-3">
    <input type="text" id="searchInput" class="form-control" placeholder="Search by name or email...">
  </div>

  <!-- Users table -->
  <table class="table table-striped table-hover" id="usersTable">
    <thead>
      <tr>
        <th scope="col">Name</th>
        <th scope="col">Email</th>
        <th scope="col">Role</th>
        <th scope="col">Status</th>
        <th scope="col">Joined</th>
        <th scope="col">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $user): ?>
        <tr>
          <td><?php echo htmlspecialchars($user['name']); ?></td>
          <td><?php echo htmlspecialchars($user['email']); ?></td>
          <td><?php echo htmlspecialchars($user['role']); ?></td>
          <td>
            <?php if ($user['status'] === 'active'): ?>
              <span class="badge bg-success">Active</span>
            <?php elseif ($user['status'] === 'suspended'): ?>
              <span class="badge bg-danger">Suspended</span>
            <?php else: ?>
              <span class="badge bg-secondary"><?php echo htmlspecialchars($user['status']); ?></span>
            <?php endif; ?>
          </td>
          <td><?php echo htmlspecialchars($user['created_at']); ?></td>
          <td>
            <a href="suspend_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning">Suspend</a>
            <a href="promote_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info">Promote</a>
            <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" 
               onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include "../../includes/footer.php"; ?>

<!-- Client-side search -->
<script>
  const searchInput = document.getElementById('searchInput');
  const tableRows = document.querySelectorAll('#usersTable tbody tr');

  searchInput.addEventListener('keyup', function() {
    const query = this.value.toLowerCase();
    tableRows.forEach(row => {
      const name = row.cells[0].textContent.toLowerCase();
      const email = row.cells[1].textContent.toLowerCase();
      row.style.display = (name.includes(query) || email.includes(query)) ? '' : 'none';
    });
  });
</script>