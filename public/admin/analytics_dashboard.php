<?php
require_once "../../config/init.php";
// Staff only: superadmin, manager (and legacy admin).
requireLogin(STAFF_ROLES);

// Notifications data
$notifGroups = $pdo->query("SELECT recipient_group, COUNT(*) as count 
                            FROM notifications_log GROUP BY recipient_group")->fetchAll(PDO::FETCH_KEY_PAIR);
$notifOverride = $pdo->query("SELECT override, COUNT(*) as count 
                              FROM notifications_log GROUP BY override")->fetchAll(PDO::FETCH_KEY_PAIR);

// Subscriptions data (subscriptions.plan holds the plan name; there is no plan_id column)
$subStatus = $pdo->query("SELECT status, COUNT(*) as count 
                          FROM subscriptions GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$planDist = $pdo->query("SELECT COALESCE(plan, 'No plan') AS name, COUNT(*) as count 
                         FROM subscriptions 
                         GROUP BY plan")->fetchAll(PDO::FETCH_KEY_PAIR);

// User activity data (last 30 days): staff actions + marketplace audit trail
$activityTrend = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as count 
                              FROM (
                                  SELECT created_at FROM activity_logs
                                  UNION ALL
                                  SELECT `timestamp` AS created_at FROM audit_logs
                              ) a
                              WHERE created_at >= CURDATE() - INTERVAL 30 DAY 
                              GROUP BY DATE(created_at) ORDER BY date ASC")->fetchAll();
$actDates = array_column($activityTrend, 'date');
$actCounts = array_map('intval', array_column($activityTrend, 'count'));

$cards = [
    ['notifGroupChart', 'bell', 'Notifications by group', array_sum($notifGroups)],
    ['subStatusChart', 'award', 'Subscriptions by status', array_sum($subStatus)],
    ['planDistChart', 'collection', 'Plan distribution', array_sum($planDist)],
    ['notifOverrideChart', 'lightning-charge-fill', 'Override usage', array_sum($notifOverride)],
    ['activityTrendChart', 'graph-up-arrow', 'User activity (30 days)', array_sum($actCounts)],
];

include "../../includes/header.php";
nu_band('graph-up-arrow', 'Insights', 'Combined Analytics Dashboard', 'Notifications, subscriptions and user activity in one place.',
    '<a class="btn btn-glass" href="notifications_analytics.php">' . nu_icon('bell') . ' Notifications</a><a class="btn btn-light" href="subscriptions.php">' . nu_icon('award') . ' Subscriptions</a>');
?>

<main class="wrap page-body">
  <div class="grid grid-3">
  <?php foreach ($cards as $i => [$id, $icon, $label, $n]): if ($i === 3) echo '</div><div class="grid grid-2 mt-6">'; ?>
    <div class="card chart-card" data-reveal style="--i:<?= $i ?>">
      <h3><?= nu_icon($icon) ?> <?= nu_e($label) ?></h3>
      <?php if ($n > 0): ?>
        <div class="chart-box"><canvas id="<?= $id ?>"></canvas></div>
      <?php else: ?>
        <div class="chart-box" style="display:grid;place-items:center;text-align:center"><p class="muted mb-0"><?= nu_icon('inbox', 'ic-lg') ?><br>No data yet</p></div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  </div>
  <p class="muted mt-4" style="font-size:var(--fs-sm)">Tip: click a slice of “Notifications by group” to see the notices behind it.</p>
</main>

<!-- Drill-down dialog -->
<dialog id="drillModal" class="card card-pad" style="max-width:min(760px,92vw);width:100%;border:0">
  <div class="row-flex" style="justify-content:space-between;margin-bottom:12px">
    <h3 class="mb-0">Drill-Down Details</h3>
    <button type="button" class="btn btn-ghost btn-sm" onclick="this.closest('dialog').close()" aria-label="Close"><?= nu_icon('x-lg') ?></button>
  </div>
  <div id="drillContent" class="table-wrap">Loading...</div>
</dialog>

<script>
function drillDown(type, filter) {
  var dlg = document.getElementById('drillModal');
  document.getElementById('drillContent').textContent = 'Loading...';
  if (dlg.showModal) dlg.showModal();
  fetch('../../actions/drilldown.php?type=' + type + '&filter=' + encodeURIComponent(filter))
    .then(function (r) { return r.text(); })
    .then(function (html) { document.getElementById('drillContent').innerHTML = html; });
}
document.addEventListener('DOMContentLoaded', function () {
  if (typeof Chart === 'undefined') return;
  <?= nu_chart_defaults_js() ?>
  var charts = [];
  function make(id, cfg) { var el = document.getElementById(id); if (el) charts.push(new Chart(el, cfg)); }
  var groupLabels = <?= json_encode(array_keys($notifGroups)) ?>;

  // Notifications by group (click a slice to drill down)
  make('notifGroupChart', {
    type: 'pie',
    data: { labels: groupLabels, datasets: [{ data: <?= json_encode(array_map('intval', array_values($notifGroups))) ?>, backgroundColor: PALETTE, borderWidth: 0 }] },
    options: { plugins: { legend: { position: 'bottom' } }, onClick: function (evt, elements) { if (elements.length > 0) drillDown('notifications', groupLabels[elements[0].index]); } }
  });
  // Subscriptions by status
  make('subStatusChart', {
    type: 'doughnut',
    data: { labels: <?= json_encode(array_keys($subStatus)) ?>, datasets: [{ data: <?= json_encode(array_map('intval', array_values($subStatus))) ?>, backgroundColor: PALETTE, borderWidth: 0 }] },
    options: { cutout: '64%', plugins: { legend: { position: 'bottom' } } }
  });
  // Plan distribution
  make('planDistChart', {
    type: 'bar',
    data: { labels: <?= json_encode(array_keys($planDist)) ?>, datasets: [{ label: 'Subscribers', data: <?= json_encode(array_map('intval', array_values($planDist))) ?>, backgroundColor: '#1B5BEA', borderRadius: 8, maxBarThickness: 36 }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } } }
  });
  // Override usage
  make('notifOverrideChart', {
    type: 'doughnut',
    data: { labels: ['No Override','Override'], datasets: [{ data: [<?= (int) ($notifOverride[0] ?? 0) ?>, <?= (int) ($notifOverride[1] ?? 0) ?>], backgroundColor: ['#1B5BEA', '#DC3248'], borderWidth: 0 }] },
    options: { cutout: '64%', plugins: { legend: { position: 'bottom' } } }
  });
  // User activity trend
  make('activityTrendChart', {
    type: 'line',
    data: { labels: <?= json_encode($actDates) ?>, datasets: [{ label: 'User Actions', data: <?= json_encode($actCounts) ?>, borderColor: '#1B5BEA', backgroundColor: 'rgba(27,91,234,.12)', fill: true, tension: .35 }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } } }
  });
  document.addEventListener('nuahn:theme', function () { tone(); charts.forEach(function (c) { c.update('none'); }); });
});
</script>

<?php include "../../includes/footer.php"; ?>
