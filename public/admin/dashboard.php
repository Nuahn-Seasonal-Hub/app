<?php
require_once "../../config/init.php";
// Staff only: superadmin, manager (and legacy admin).
requireLogin(STAFF_ROLES);

$totalUsers     = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalProviders = $pdo->query("SELECT COUNT(*) FROM users WHERE role='provider'")->fetchColumn();
$totalJobs      = $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
$suspended      = $pdo->query("SELECT COUNT(*) FROM users WHERE status='suspended'")->fetchColumn();

// Recent activity: staff actions (activity_logs) plus the marketplace audit trail (audit_logs).
$recent = $pdo->query("
    SELECT u.name, a.action, a.created_at
    FROM (
        SELECT user_id, action, created_at FROM activity_logs
        UNION ALL
        SELECT target_user_id AS user_id, action, `timestamp` AS created_at FROM audit_logs WHERE target_user_id IS NOT NULL
    ) a
    JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC LIMIT 10
")->fetchAll();

include "../../includes/header.php";

nu_band('speedometer2', ucfirst($_SESSION['role']) . ' tools', 'Admin overview', 'Accounts, jobs and the latest activity across the platform.',
    '<a class="btn btn-glass" href="manage_users.php">' . nu_icon('people') . ' Users</a><a class="btn btn-light" href="../dashboard.php">' . nu_icon('bar-chart-line') . ' Dashboard</a>');
?>

<main class="wrap page-body">
  <div class="grid grid-stats">
    <div class="stat" data-reveal style="--i:0"><span class="stat__icon stat__icon--navy"><?= nu_icon('people-fill') ?></span><div><div class="stat__label">Users</div><div class="stat__value" data-count="<?= (int) $totalUsers ?>"><?= (int) $totalUsers ?></div></div></div>
    <div class="stat" data-reveal style="--i:1"><span class="stat__icon stat__icon--success"><?= nu_icon('person-badge') ?></span><div><div class="stat__label">Providers</div><div class="stat__value" data-count="<?= (int) $totalProviders ?>"><?= (int) $totalProviders ?></div></div></div>
    <div class="stat" data-reveal style="--i:2"><span class="stat__icon stat__icon--warning"><?= nu_icon('briefcase-fill') ?></span><div><div class="stat__label">Jobs</div><div class="stat__value" data-count="<?= (int) $totalJobs ?>"><?= (int) $totalJobs ?></div></div></div>
    <div class="stat" data-reveal style="--i:3"><span class="stat__icon stat__icon--danger"><?= nu_icon('x-circle') ?></span><div><div class="stat__label">Suspended accounts</div><div class="stat__value" data-count="<?= (int) $suspended ?>"><?= (int) $suspended ?></div></div></div>
  </div>

  <div class="section-title"><h2>Admin tools</h2></div>
  <div class="grid grid-3">
    <a class="tile" href="manage_users.php" data-reveal style="--i:0">
      <span class="tile__icon tile__icon--navy"><?= nu_icon('people') ?></span>
      <span><span class="tile__title" style="display:block">Manage users</span><span class="tile__text">Search everyone by name, email, role or status.</span></span>
      <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
    </a>
    <a class="tile" href="../manage_applications.php" data-reveal style="--i:1">
      <span class="tile__icon"><?= nu_icon('clipboard-check') ?></span>
      <span><span class="tile__title" style="display:block">Applications</span><span class="tile__text">Approve or reject provider applications.</span></span>
      <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
    </a>
    <a class="tile" href="analytics_dashboard.php" data-reveal style="--i:2">
      <span class="tile__icon tile__icon--soft"><?= nu_icon('graph-up-arrow') ?></span>
      <span><span class="tile__title" style="display:block">Analytics</span><span class="tile__text">Notifications, subscriptions and activity.</span></span>
      <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
    </a>
    <a class="tile" href="subscriptions.php" data-reveal style="--i:3">
      <span class="tile__icon tile__icon--soft"><?= nu_icon('award') ?></span>
      <span><span class="tile__title" style="display:block">Subscriptions</span><span class="tile__text">Plans and client subscriptions.</span></span>
      <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
    </a>
    <a class="tile" href="notifications.php" data-reveal style="--i:4">
      <span class="tile__icon"><?= nu_icon('bell') ?></span>
      <span><span class="tile__title" style="display:block">Notifications</span><span class="tile__text">Send a notice to a group of users.</span></span>
      <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
    </a>
<?php if (isRole('superadmin')): ?>
    <a class="tile" href="manage_admins.php" data-reveal style="--i:5">
      <span class="tile__icon tile__icon--navy"><?= nu_icon('shield-check') ?></span>
      <span><span class="tile__title" style="display:block">Manage staff</span><span class="tile__text">Promote managers and add team accounts.</span></span>
      <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
    </a>
<?php endif; ?>
  </div>

  <div class="section-title"><h2>Recent activity</h2></div>
<?php if (empty($recent)): ?>
  <?php nu_empty('clock', 'No activity yet.', 'Actions such as posting, applying and approving will show up here.'); ?>
<?php else: ?>
  <div class="table-wrap" data-reveal>
    <table class="table table--stack">
      <thead><tr><th>User</th><th>Action</th><th>Date</th></tr></thead>
      <tbody>
      <?php foreach ($recent as $row): ?>
        <tr>
          <td data-label="User"><strong><?= htmlspecialchars($row['name']) ?></strong></td>
          <td data-label="Action"><?= htmlspecialchars($row['action']) ?></td>
          <td class="muted" data-label="Date"><?= nu_e(nu_date($row['created_at'], 'M j, Y · H:i')) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
</main>

<?php include "../../includes/footer.php"; ?>
