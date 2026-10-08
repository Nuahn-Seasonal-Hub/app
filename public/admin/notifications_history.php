<?php
require_once "../../config/init.php";
// Staff only: superadmin, manager (and legacy admin).
requireLogin(STAFF_ROLES);

// Handle filters
$group = $_GET['group'] ?? '';
$keyword = $_GET['keyword'] ?? '';
$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';

$query = "SELECT subject, recipient_group, override, sent_at 
          FROM notifications_log WHERE 1=1";
$params = [];

if ($group) {
    $query .= " AND recipient_group = ?";
    $params[] = $group;
}
if ($keyword) {
    $query .= " AND subject LIKE ?";
    $params[] = "%$keyword%";
}
if ($start && $end) {
    $query .= " AND sent_at BETWEEN ? AND ?";
    $params[] = $start;
    $params[] = $end;
}

$query .= " ORDER BY sent_at DESC LIMIT 50";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();

include "../../includes/header.php";
$export = "../../actions/export_notifications.php?group=" . urlencode($group) . "&keyword=" . urlencode($keyword) . "&start=" . urlencode($start) . "&end=" . urlencode($end);
nu_band('clock', 'Messaging', 'Notification History', 'The last 50 notices sent, newest first.',
    '<a class="btn btn-light" href="' . nu_e($export) . '">' . nu_icon('file-earmark-text') . ' Export to CSV</a>');
?>

<main class="wrap page-body">
  <form method="GET" class="card card-pad" data-reveal>
    <div class="form-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));align-items:end">
      <label class="field"><span class="label">Group</span>
        <select class="input" name="group">
          <option value="">All Groups</option>
          <option value="all" <?php if($group=="all") echo "selected"; ?>>Everyone</option>
          <option value="manager" <?php if($group=="manager") echo "selected"; ?>>Managers</option>
          <option value="provider" <?php if($group=="provider") echo "selected"; ?>>Providers</option>
          <option value="client" <?php if($group=="client") echo "selected"; ?>>Clients</option>
        </select></label>
      <label class="field"><span class="label">Subject</span>
        <input type="text" class="input" name="keyword" placeholder="Search subject..." value="<?= htmlspecialchars($keyword) ?>"></label>
      <label class="field"><span class="label">From</span>
        <input type="date" class="input" name="start" value="<?= htmlspecialchars($start) ?>"></label>
      <label class="field"><span class="label">To</span>
        <input type="date" class="input" name="end" value="<?= htmlspecialchars($end) ?>"></label>
      <button type="submit" class="btn btn-primary"><?= nu_icon('funnel') ?> Filter</button>
    </div>
  </form>

<?php if (empty($logs)): ?>
  <div class="mt-6"><?php nu_empty('bell', 'No notifications found.', 'Notices you send from the control panel are listed here.', '<a class="btn btn-primary" href="notifications.php">' . nu_icon('send') . ' Send a notification</a>'); ?></div>
<?php else: ?>
  <div class="table-wrap mt-6" data-reveal>
    <table class="table table--stack">
      <thead><tr><th>Subject</th><th>Recipients</th><th>Override</th><th>Sent At</th></tr></thead>
      <tbody>
        <?php foreach ($logs as $row): ?>
          <tr>
            <td data-label="Subject"><strong><?= htmlspecialchars($row['subject']) ?></strong></td>
            <td data-label="Recipients"><span class="chip chip--blue"><?= htmlspecialchars($row['recipient_group']) ?></span></td>
            <td data-label="Override"><?= $row['override'] ? '<span class="chip chip--danger">Yes</span>' : 'No' ?></td>
            <td class="muted" data-label="Sent"><?= nu_e(nu_date($row['sent_at'], 'M j, Y · H:i')) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
</main>

<?php include "../../includes/footer.php"; ?>
