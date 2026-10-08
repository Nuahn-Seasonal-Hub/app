<?php
// public/dashboard.php - Manager/Admin Dashboard Summary
require_once("../config/init.php"); // handles session, db, auth, flash helpers

// Only managers and superadmins (and legacy admins) can view this page
requireLogin(STAFF_ROLES);
include_once("../includes/header.php");


// Fetch summary counts
$totalJobs     = $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
$postedJobs    = $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'posted'")->fetchColumn();
$pendingApps   = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'")->fetchColumn();
$approvedApps  = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'approved'")->fetchColumn();
$rejectedApps  = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'rejected'")->fetchColumn();
$totalClients  = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'rejeckted'")->fetchColumn();
$totalProviders  = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'rejected'")->fetchColumn();


$totalUsers     = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalClients   = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
$totalProviders = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'provider'")->fetchColumn();
$totalApps      = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$jobsPerClient  = $pdo->query("SELECT client_id, COUNT(*) AS job_count FROM jobs GROUP BY client_id")->fetchAll(PDO::FETCH_ASSOC);


// User growth per month (last 6 months)
$userGrowthStmt = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS month,
           SUM(CASE WHEN role = 'client' THEN 1 ELSE 0 END) AS clients,
           SUM(CASE WHEN role = 'provider' THEN 1 ELSE 0 END) AS providers
    FROM users
    GROUP BY month
    ORDER BY month DESC
    LIMIT 6
");
$userGrowth = $userGrowthStmt->fetchAll(PDO::FETCH_ASSOC);

// Jobs per month (last 6 months)
$jobsPerMonthStmt = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS count
    FROM jobs
    GROUP BY month
    ORDER BY month DESC
    LIMIT 6
");
$jobsPerMonth = $jobsPerMonthStmt->fetchAll(PDO::FETCH_ASSOC);

// Applications by status
$appStatusStmt = $pdo->query("
    SELECT status, COUNT(*) AS count
    FROM applications
    GROUP BY status
");
$appStatus = $appStatusStmt->fetchAll(PDO::FETCH_ASSOC);

// Jobs waiting for staff approval (new jobs are inserted with status 'pending' and only
// 'posted' jobs are listed publicly). Approve -> 'posted', reject -> 'closed'.
$pendingJobs = $pdo->query("
    SELECT jobs.*, users.name AS client_name
    FROM jobs
    JOIN users ON jobs.client_id = users.id
    WHERE jobs.status = 'pending'
    ORDER BY jobs.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$approvalRate = $totalApps > 0 ? round(($approvedApps / $totalApps) * 100) : 0;
?>

<section class="band">
  <div class="wrap band__row">
    <div>
      <span class="eyebrow"><?= nu_icon('speedometer2') ?> <?= nu_e(ucfirst($_SESSION['role'] ?? 'Admin')) ?> console</span>
      <h1>Dashboard Summary</h1>
      <p>Marketplace health at a glance: jobs, applications and people.</p>
    </div>
    <div class="row-flex">
      <a class="btn btn-glass" href="manage_applications.php"><?= nu_icon('clipboard-check') ?> Applications</a>
      <a class="btn btn-light" href="jobs.php"><?= nu_icon('briefcase') ?> Jobs</a>
    </div>
  </div>
</section>

<main class="wrap page-body">
    <?php include_once("../includes/flash.php"); ?>

    <div class="grid grid-stats">
        <div class="stat" data-reveal style="--i:0"><span class="stat__icon stat__icon--navy"><?= nu_icon('briefcase-fill') ?></span><div><div class="stat__label">Total Jobs</div><div class="stat__value" data-count="<?= (int) $totalJobs ?>"><?= htmlspecialchars($totalJobs) ?></div></div></div>
        <div class="stat" data-reveal style="--i:1"><span class="stat__icon"><?= nu_icon('lightning-charge-fill') ?></span><div><div class="stat__label">Posted Jobs</div><div class="stat__value" data-count="<?= (int) $postedJobs ?>"><?= htmlspecialchars($postedJobs) ?></div></div></div>
        <div class="stat" data-reveal style="--i:2"><span class="stat__icon stat__icon--warning"><?= nu_icon('hourglass-split') ?></span><div><div class="stat__label">Pending Apps</div><div class="stat__value" data-count="<?= (int) $pendingApps ?>"><?= htmlspecialchars($pendingApps) ?></div></div></div>
        <div class="stat" data-reveal style="--i:3"><span class="stat__icon stat__icon--success"><?= nu_icon('check-circle-fill') ?></span><div><div class="stat__label">Approved Apps</div><div class="stat__value" data-count="<?= (int) $approvedApps ?>"><?= htmlspecialchars($approvedApps) ?></div></div></div>
        <div class="stat" data-reveal style="--i:4"><span class="stat__icon stat__icon--danger"><?= nu_icon('x-circle') ?></span><div><div class="stat__label">Rejected Apps</div><div class="stat__value" data-count="<?= (int) $rejectedApps ?>"><?= htmlspecialchars($rejectedApps) ?></div></div></div>
    </div>

    <div class="grid grid-stats mt-4">
        <div class="stat" data-reveal style="--i:0"><span class="stat__icon stat__icon--navy"><?= nu_icon('people-fill') ?></span><div><div class="stat__label">Total Users</div><div class="stat__value" data-count="<?= (int) $totalUsers ?>"><?= htmlspecialchars($totalUsers) ?></div></div></div>
        <div class="stat" data-reveal style="--i:1"><span class="stat__icon"><?= nu_icon('briefcase') ?></span><div><div class="stat__label">Clients</div><div class="stat__value" data-count="<?= (int) $totalClients ?>"><?= htmlspecialchars($totalClients) ?></div></div></div>
        <div class="stat" data-reveal style="--i:2"><span class="stat__icon"><?= nu_icon('person-badge') ?></span><div><div class="stat__label">Providers</div><div class="stat__value" data-count="<?= (int) $totalProviders ?>"><?= htmlspecialchars($totalProviders) ?></div></div></div>
        <div class="stat" data-reveal style="--i:3"><span class="stat__icon stat__icon--success"><?= nu_icon('clipboard-check-fill') ?></span><div><div class="stat__label">Total Applications</div><div class="stat__value" data-count="<?= (int) $totalApps ?>"><?= htmlspecialchars($totalApps) ?></div></div></div>
        <div class="stat" data-reveal style="--i:4"><span class="stat__icon stat__icon--navy"><?= nu_icon('graph-up-arrow') ?></span><div><div class="stat__label">Approval rate</div><div class="stat__value"><?= $approvalRate ?>%</div></div></div>
    </div>

    <div class="grid grid-2 mt-6">
        <div class="card chart-card" data-reveal style="--i:0">
            <h3><?= nu_icon('bar-chart-line') ?> Jobs Posted Per Month</h3>
            <div class="chart-box"><canvas id="jobsChart" aria-label="Jobs posted per month"></canvas></div>
        </div>
        <div class="card chart-card" data-reveal style="--i:1">
            <h3><?= nu_icon('clipboard-check') ?> Applications by Status</h3>
            <div class="chart-box"><canvas id="appsChart" aria-label="Applications by status"></canvas></div>
        </div>
    </div>
    <div class="grid grid-2 mt-6">
        <div class="card chart-card" data-reveal style="--i:0">
            <h3><?= nu_icon('graph-up-arrow') ?> User Growth (Clients vs Providers)</h3>
            <div class="chart-box"><canvas id="userGrowthChart" aria-label="User growth"></canvas></div>
        </div>
        <div class="card chart-card" data-reveal style="--i:1">
            <h3><?= nu_icon('people') ?> Jobs Per Client</h3>
            <div class="chart-box"><canvas id="jobsPerClientChart" aria-label="Jobs per client"></canvas></div>
        </div>
    </div>

    <div class="section-title"><h2>Admin tools</h2></div>
    <div class="grid grid-3">
        <a class="tile" href="admin/manage_users.php" data-reveal style="--i:0">
            <span class="tile__icon tile__icon--navy"><?= nu_icon('people') ?></span>
            <span><span class="tile__title" style="display:block">Manage users</span><span class="tile__text">Everyone on the platform, by role and status.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
        <a class="tile" href="admin/analytics_dashboard.php" data-reveal style="--i:1">
            <span class="tile__icon"><?= nu_icon('graph-up-arrow') ?></span>
            <span><span class="tile__title" style="display:block">Analytics</span><span class="tile__text">Notifications, subscriptions and activity trends.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
        <a class="tile" href="admin/subscriptions.php" data-reveal style="--i:2">
            <span class="tile__icon tile__icon--soft"><?= nu_icon('award') ?></span>
            <span><span class="tile__title" style="display:block">Subscriptions</span><span class="tile__text">Plans and client subscriptions.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
        <a class="tile" href="admin/notifications.php" data-reveal style="--i:3">
            <span class="tile__icon tile__icon--soft"><?= nu_icon('bell') ?></span>
            <span><span class="tile__title" style="display:block">Notifications</span><span class="tile__text">Send notices and review the history.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
        <a class="tile" href="admin/dashboard.php" data-reveal style="--i:4">
            <span class="tile__icon tile__icon--navy"><?= nu_icon('speedometer2') ?></span>
            <span><span class="tile__title" style="display:block">Admin overview</span><span class="tile__text">Account totals and recent activity.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
<?php if (isRole('superadmin')): ?>
        <a class="tile" href="admin/manage_admins.php" data-reveal style="--i:5">
            <span class="tile__icon"><?= nu_icon('shield-check') ?></span>
            <span><span class="tile__title" style="display:block">Manage staff</span><span class="tile__text">Promote managers or add team accounts.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
<?php endif; ?>
    </div>

    <div class="section-title"><h2>Pending Job Approvals</h2></div>
<?php if (empty($pendingJobs)): ?>
    <?php nu_empty('check-circle-fill', 'No jobs awaiting approval.', 'You are all caught up. New job submissions will appear here.'); ?>
<?php else: ?>
    <div class="grid grid-2">
    <?php foreach ($pendingJobs as $n => $job):
        $foot = '<form method="POST" action="../actions/approve_job.php" class="inline-form"><input type="hidden" name="job_id" value="' . htmlspecialchars($job['id']) . '"><button type="submit" class="btn btn-success-soft btn-sm">Approve</button></form>'
              . '<form method="POST" action="../actions/reject_job.php" class="inline-form"><input type="hidden" name="job_id" value="' . htmlspecialchars($job['id']) . '"><button type="submit" class="btn btn-danger-soft btn-sm">Reject</button></form>';
        nu_job_card($job, ['footer' => $foot, 'i' => $n]);
    endforeach; ?>
    </div>
<?php endif; ?>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
  if (typeof Chart === 'undefined') return;
  var css = getComputedStyle(document.documentElement);
  function tone() {
    var dark = document.documentElement.getAttribute('data-theme') === 'dark';
    Chart.defaults.color = dark ? '#8E9DC0' : '#64738F';
    Chart.defaults.borderColor = dark ? 'rgba(255,255,255,.08)' : 'rgba(10,26,63,.07)';
  }
  tone();
  Chart.defaults.font.family = css.getPropertyValue('--font');
  Chart.defaults.font.weight = 600;
  Chart.defaults.maintainAspectRatio = false;
  Chart.defaults.plugins.legend.labels.usePointStyle = true;
  Chart.defaults.plugins.tooltip.backgroundColor = '#0A1A3F';
  Chart.defaults.plugins.tooltip.padding = 10;
  Chart.defaults.plugins.tooltip.cornerRadius = 10;
  var blue = '#1B5BEA', sky = '#5C92FF', navy = '#0A1A3F';
  var charts = [];

  // Jobs per month data
  charts.push(new Chart(document.getElementById('jobsChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_reverse(array_column($jobsPerMonth, 'month'))) ?>,
      datasets: [{ label: 'Jobs Posted', data: <?= json_encode(array_map('intval', array_reverse(array_column($jobsPerMonth, 'count')))) ?>, backgroundColor: blue, borderRadius: 8, maxBarThickness: 36 }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } } }
  }));

  // Applications by status data
  charts.push(new Chart(document.getElementById('appsChart'), {
    type: 'doughnut',
    data: {
      labels: <?= json_encode(array_map(function ($s) { return $s === '' || $s === null ? 'pending' : $s; }, array_column($appStatus, 'status'))) ?>,
      datasets: [{ label: 'Applications', data: <?= json_encode(array_map('intval', array_column($appStatus, 'count'))) ?>, backgroundColor: [blue, navy, sky, '#0E9F6E', '#DC3248'], borderWidth: 0, hoverOffset: 6 }]
    },
    options: { cutout: '68%', plugins: { legend: { position: 'bottom' } } }
  }));

  charts.push(new Chart(document.getElementById('userGrowthChart'), {
    type: 'line',
    data: {
      labels: <?= json_encode(array_reverse(array_column($userGrowth, 'month'))) ?>,
      datasets: [
        { label: 'Clients', data: <?= json_encode(array_map('intval', array_reverse(array_column($userGrowth, 'clients')))) ?>, borderColor: blue, backgroundColor: 'rgba(27,91,234,.12)', fill: true, tension: 0.35, pointRadius: 3 },
        { label: 'Providers', data: <?= json_encode(array_map('intval', array_reverse(array_column($userGrowth, 'providers')))) ?>, borderColor: navy, backgroundColor: 'rgba(10,26,63,.06)', fill: true, tension: 0.35, pointRadius: 3 }
      ]
    },
    options: { plugins: { legend: { position: 'top', align: 'end' } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } } }
  }));

  charts.push(new Chart(document.getElementById('jobsPerClientChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_map(function ($id) { return 'Client #' . $id; }, array_column($jobsPerClient, 'client_id'))) ?>,
      datasets: [{ label: 'Jobs Posted', data: <?= json_encode(array_map('intval', array_column($jobsPerClient, 'job_count'))) ?>, backgroundColor: sky, borderRadius: 8, maxBarThickness: 36 }]
    },
    options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } }, y: { grid: { display: false } } } }
  }));

  document.addEventListener('nuahn:theme', function () { tone(); charts.forEach(function (c) { c.update('none'); }); });
});
</script>

<?php include_once("../includes/footer.php"); ?>
