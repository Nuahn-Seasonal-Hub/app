<?php
require_once "../../config/db.php";
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit;
}

include "../../includes/header.php";

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
?>

<div class="container mt-4">
  <h2 class="mb-4">Notification History</h2>

  <form method="GET" class="row g-3 mb-3">
    <div class="col-md-3">
      <select class="form-select" name="group">
        <option value="">All Groups</option>
        <option value="admin" <?php if($group=="admin") echo "selected"; ?>>Admins</option>
        <option value="provider" <?php if($group=="provider") echo "selected"; ?>>Providers</option>
        <option value="client" <?php if($group=="client") echo "selected"; ?>>Clients</option>
      </select>
    </div>
    <div class="col-md-3">
      <input type="text" class="form-control" name="keyword" placeholder="Search subject..." value="<?php echo htmlspecialchars($keyword); ?>">
    </div>
    <div class="col-md-2">
      <input type="date" class="form-control" name="start" value="<?php echo $start; ?>">
    </div>
    <div class="col-md-2">
      <input type="date" class="form-control" name="end" value="<?php echo $end; ?>">
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-primary w-100">Filter</button>
    </div>
  </form>
<a href="../../actions/export_notifications.php?group=<?php echo urlencode($group); ?>&keyword=<?php echo urlencode($keyword); ?>&start=<?php echo urlencode($start); ?>&end=<?php echo urlencode($end); ?>" 
   class="btn btn-secondary mb-3">Export to CSV</a>

  <table class="table table-striped">
    <thead>
      <tr>
        <th>Subject</th>
        <th>Recipients</th>
        <th>Override</th>
        <th>Sent At</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($logs as $row): ?>
        <tr>
          <td><?php echo htmlspecialchars($row['subject']); ?></td>
          <td><?php echo $row['recipient_group']; ?></td>
          <td><?php echo $row['override'] ? "Yes" : "No"; ?></td>
          <td><?php echo $row['sent_at']; ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include "../../includes/footer.php"; ?>
