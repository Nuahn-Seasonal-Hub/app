<?php
require_once "../../config/init.php";
// Only superadmins manage staff. users.role has no 'admin' value or is_superadmin column,
// so "staff" here means managers and superadmins: promote = manager -> superadmin,
// demote = superadmin -> manager.
requireLogin('superadmin');

$stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users WHERE role IN ('superadmin','manager') ORDER BY role = 'superadmin' DESC, name");
$admins = $stmt->fetchAll();

include "../../includes/header.php";

nu_band('shield-check', 'Superadmin', 'Manage staff', 'Managers run day-to-day operations; superadmins can also manage staff.',
    '<a class="btn btn-glass" href="../register_manager.php">' . nu_icon('plus-lg') . ' Add manager</a><a class="btn btn-light" href="../register_superadmin.php">' . nu_icon('plus-lg') . ' Add superadmin</a>');
?>

<main class="wrap page-body">
<?php if (empty($admins)): ?>
  <?php nu_empty('shield-check', 'No staff accounts.', 'Add a manager to get started.'); ?>
<?php else: ?>
  <div class="table-wrap" data-reveal>
    <table class="table table--stack">
      <thead><tr><th>Name</th><th>Email</th><th>Role</th><th style="text-align:right">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($admins as $admin): $isSuper = $admin['role'] === 'superadmin'; ?>
          <tr>
            <td data-label="Name"><strong><?= htmlspecialchars($admin['name']) ?></strong></td>
            <td data-label="Email"><?= htmlspecialchars($admin['email']) ?></td>
            <td data-label="Role"><span class="chip <?= $isSuper ? 'chip--navy' : 'chip--blue' ?>"><?= htmlspecialchars($admin['role']) ?></span></td>
            <td style="text-align:right" data-label="Actions">
              <?php if ((int) $admin['id'] === (int) $_SESSION['user_id']): ?>
                <span class="muted">You</span>
              <?php elseif ($isSuper): ?>
                <a href="../../actions/demote_admin.php?id=<?= (int) $admin['id'] ?>" class="btn btn-danger-soft btn-sm" onclick="return confirm('Demote this superadmin to manager?');">Demote</a>
              <?php else: ?>
                <a href="../../actions/promote_admin.php?id=<?= (int) $admin['id'] ?>" class="btn btn-success-soft btn-sm" onclick="return confirm('Promote this manager to superadmin?');">Promote</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
</main>

<?php include "../../includes/footer.php"; ?>
