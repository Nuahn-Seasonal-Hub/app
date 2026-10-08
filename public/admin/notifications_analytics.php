<?php
require_once "../../config/db.php";
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit;
}

// Fetch counts by group
$groupCounts = $pdo->query("SELECT recipient_group, COUNT(*) as count 
                            FROM notifications_log 
                            GROUP BY recipient_group")->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch override usage
$overrideCounts = $pdo->query("SELECT override, COUNT(*) as count 
                               FROM notifications_log 
                               GROUP BY override")->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch time trend (last 30 days)
$trendStmt = $pdo->query("SELECT DATE(sent_at) as date, COUNT(*) as count 
                          FROM notifications_log 
                          WHERE sent_at >= CURDATE() - INTERVAL 30 DAY 
                          GROUP BY DATE(sent_at) 
                          ORDER BY date ASC");
$trendData = $trendStmt->fetchAll();
$dates = array_column($trendData, 'date');
$counts = array_column($trendData, 'count');

include "../../includes/header.php";
?>

<div class="container mt-4">
  <h2 class="mb-4">Notification Analytics</h2>

  <div class="row">
    <div class="col-md-6">
      <canvas id="groupChart"></canvas>
    </div>
    <div class="col-md-6">
      <canvas id="overrideChart"></canvas>
    </div>
  </div>

  <div class="row mt-4">
    <div class="col-12">
      <canvas id="trendChart"></canvas>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Group chart
  new Chart(document.getElementById('groupChart'), {
    type: 'pie',
    data: {
      labels: <?php echo json_encode(array_keys($groupCounts)); ?>,
      datasets: [{
        data: <?php echo json_encode(array_values($groupCounts)); ?>,
        backgroundColor: ['#007bff','#28a745','#ffc107','#6c757d']
      }]
    }
  });

  // Override chart
  new Chart(document.getElementById('overrideChart'), {
    type: 'doughnut',
    data: {
      labels: ['No Override','Override'],
      datasets: [{
        data: [<?php echo $overrideCounts[0] ?? 0; ?>, <?php echo $overrideCounts[1] ?? 0; ?>],
        backgroundColor: ['#17a2b8','#dc3545']
      }]
    }
  });

  // Trend chart
  new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
      labels: <?php echo json_encode($dates); ?>,
      datasets: [{
        label: 'Notifications per day',
        data: <?php echo json_encode($counts); ?>,
        borderColor: '#007bff',
        fill: false
      }]
    }
  });
</script>

<?php include "../../includes/footer.php"; ?>
