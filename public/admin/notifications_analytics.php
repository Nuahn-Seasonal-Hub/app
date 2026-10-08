<?php
require_once "../../config/init.php";
// Staff only: superadmin, manager (and legacy admin).
requireLogin(STAFF_ROLES);

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
$counts = array_map('intval', array_column($trendData, 'count'));

$total = array_sum($groupCounts);

include "../../includes/header.php";
nu_band('bar-chart-line', 'Messaging', 'Notification Analytics', 'Who receives notices, how often, and how many override preferences.',
    '<a class="btn btn-light" href="notifications_history.php">' . nu_icon('clock') . ' History</a>');
?>

<main class="wrap page-body">
<?php if ($total === 0): ?>
  <?php nu_empty('bar-chart-line', 'No notifications sent yet.', 'Charts appear once notices have been sent from the control panel.', '<a class="btn btn-primary" href="notifications.php">' . nu_icon('send') . ' Send a notification</a>'); ?>
<?php else: ?>
  <div class="grid grid-2">
    <div class="card chart-card" data-reveal style="--i:0"><h3><?= nu_icon('people') ?> By recipient group</h3><div class="chart-box"><canvas id="groupChart"></canvas></div></div>
    <div class="card chart-card" data-reveal style="--i:1"><h3><?= nu_icon('lightning-charge-fill') ?> Override usage</h3><div class="chart-box"><canvas id="overrideChart"></canvas></div></div>
  </div>
  <div class="card chart-card mt-6" data-reveal><h3><?= nu_icon('graph-up-arrow') ?> Notifications per day (30 days)</h3><div class="chart-box"><canvas id="trendChart"></canvas></div></div>
<?php endif; ?>
</main>

<?php if ($total > 0): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (typeof Chart === 'undefined') return;
  <?= nu_chart_defaults_js() ?>
  var charts = [];
  // Group chart
  charts.push(new Chart(document.getElementById('groupChart'), {
    type: 'doughnut',
    data: { labels: <?= json_encode(array_keys($groupCounts)) ?>, datasets: [{ data: <?= json_encode(array_map('intval', array_values($groupCounts))) ?>, backgroundColor: PALETTE, borderWidth: 0 }] },
    options: { cutout: '64%', plugins: { legend: { position: 'bottom' } } }
  }));
  // Override chart
  charts.push(new Chart(document.getElementById('overrideChart'), {
    type: 'doughnut',
    data: { labels: ['No Override','Override'], datasets: [{ data: [<?= (int) ($overrideCounts[0] ?? 0) ?>, <?= (int) ($overrideCounts[1] ?? 0) ?>], backgroundColor: ['#1B5BEA', '#DC3248'], borderWidth: 0 }] },
    options: { cutout: '64%', plugins: { legend: { position: 'bottom' } } }
  }));
  // Trend chart
  charts.push(new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: { labels: <?= json_encode($dates) ?>, datasets: [{ label: 'Notifications per day', data: <?= json_encode($counts) ?>, borderColor: '#1B5BEA', backgroundColor: 'rgba(27,91,234,.12)', fill: true, tension: .35 }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } } }
  }));
  document.addEventListener('nuahn:theme', function () { tone(); charts.forEach(function (c) { c.update('none'); }); });
});
</script>
<?php endif; ?>

<?php include "../../includes/footer.php"; ?>
