<?php
require_once "../../config/db.php";
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit;
}

// Notifications data
$notifGroups = $pdo->query("SELECT recipient_group, COUNT(*) as count 
                            FROM notifications_log GROUP BY recipient_group")->fetchAll(PDO::FETCH_KEY_PAIR);
$notifOverride = $pdo->query("SELECT override, COUNT(*) as count 
                              FROM notifications_log GROUP BY override")->fetchAll(PDO::FETCH_KEY_PAIR);

// Subscriptions data
$subStatus = $pdo->query("SELECT status, COUNT(*) as count 
                          FROM subscriptions GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$planDist = $pdo->query("SELECT p.name, COUNT(*) as count 
                         FROM subscriptions s JOIN plans p ON s.plan_id=p.id 
                         GROUP BY p.name")->fetchAll(PDO::FETCH_KEY_PAIR);

// User activity data (last 30 days)
$activityTrend = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as count 
                              FROM activity_logs 
                              WHERE created_at >= CURDATE() - INTERVAL 30 DAY 
                              GROUP BY DATE(created_at) ORDER BY date ASC")->fetchAll();
$actDates = array_column($activityTrend, 'date');
$actCounts = array_column($activityTrend, 'count');

include "../../includes/header.php";
?>

<div class="container mt-4">
  <h2 class="mb-4">Combined Analytics Dashboard</h2>

  <div class="row">
    <div class="col-md-4">
      <canvas id="notifGroupChart"></canvas>
    </div>
    <div class="col-md-4">
      <canvas id="subStatusChart"></canvas>
    </div>
    <div class="col-md-4">
      <canvas id="planDistChart"></canvas>
    </div>
  </div>

  <div class="row mt-4">
    <div class="col-md-6">
      <canvas id="notifOverrideChart"></canvas>
    </div>
    <div class="col-md-6">
      <canvas id="activityTrendChart"></canvas>
    </div>
  </div>
</div>
<!-- Drill-down Modal -->
<div class="modal fade" id="drillModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Drill-Down Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="drillContent">
        Loading...
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Notifications by group
  new Chart(document.getElementById('notifGroupChart'), {
    type: 'pie',
    data: {
      labels: <?php echo json_encode(array_keys($notifGroups)); ?>,
      datasets: [{ data: <?php echo json_encode(array_values($notifGroups)); ?> }]
    }
  });

  // Subscriptions by status
  new Chart(document.getElementById('subStatusChart'), {
    type: 'doughnut',
    data: {
      labels: <?php echo json_encode(array_keys($subStatus)); ?>,
      datasets: [{ data: <?php echo json_encode(array_values($subStatus)); ?> }]
    }
  });

  // Plan distribution
  new Chart(document.getElementById('planDistChart'), {
    type: 'bar',
    data: {
      labels: <?php echo json_encode(array_keys($planDist)); ?>,
      datasets: [{ label: 'Subscribers', data: <?php echo json_encode(array_values($planDist)); ?> }]
    }
  });

  // Override usage
  new Chart(document.getElementById('notifOverrideChart'), {
    type: 'doughnut',
    data: {
      labels: ['No Override','Override'],
      datasets: [{ data: [<?php echo $notifOverride[0] ?? 0; ?>, <?php echo $notifOverride[1] ?? 0; ?>] }]
    }
  });

  // User activity trend
  new Chart(document.getElementById('activityTrendChart'), {
    type: 'line',
    data: {
      labels: <?php echo json_encode($actDates); ?>,
      datasets: [{ label: 'User Actions', data: <?php echo json_encode($actCounts); ?>, borderColor: '#007bff', fill: false }]
    }
  });
  
  
  function drillDown(type, filter) {
  fetch('../../actions/drilldown.php?type=' + type + '&filter=' + encodeURIComponent(filter))
    .then(response => response.text())
    .then(html => {
      document.getElementById('drillContent').innerHTML = html;
      new bootstrap.Modal(document.getElementById('drillModal')).show();
    });
}

// Example: Notifications by group chart
new Chart(document.getElementById('notifGroupChart'), {
  type: 'pie',
  data: {
    labels: <?php echo json_encode(array_keys($notifGroups)); ?>,
    datasets: [{ data: <?php echo json_encode(array_values($notifGroups)); ?> }]
  },
  options: {
    onClick: (evt, elements) => {
      if (elements.length > 0) {
        const index = elements[0].index;
        const label = <?php echo json_encode(array_keys($notifGroups)); ?>[index];
        drillDown('notifications', label);
      }
    }
  }
});

</script>

<?php include "../../includes/footer.php"; ?>
