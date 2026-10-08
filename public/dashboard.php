<?php
// public/dashboard.php - Manager/Admin Dashboard Summary
require_once("../config/init.php"); // handles session, db, auth, flash helpers
include_once("../includes/header.php");
include_once("../includes/flash.php");


// Only managers, admins, or superadmins can view this page
/*
if (!isRole('manager') && !in_array($_SESSION['role'], ['admin','superadmin'])) {
    addFlash('danger', 'Unauthorized access attempt');
    header("Location: /Nuahn/public/login.php?error=unauthorized");
    exit;
}
*/
requireLogin();
if ($_SESSION['role'] !== 'superadmin') {
    flashError("Unauthorized access.");
    header("Location: /Nuahn/public/login.php?error=unauthorized");
    exit;
}


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

?>

<main class="container py-5">
    <h2 class="text-center mb-4">Dashboard Summary</h2>

    <?php include_once("../includes/flash.php"); ?>

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card shadow h-100 text-center">
                <div class="card-body">
                    <h5 class="card-title">Total Jobs</h5>
                    <p class="display-6"><?= htmlspecialchars($totalJobs) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card shadow h-100 text-center">
                <div class="card-body">
                    <h5 class="card-title">Posted Jobs</h5>
                    <p class="display-6"><?= htmlspecialchars($postedJobs) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-4">
            <div class="card shadow h-100 text-center">
                <div class="card-body">
                    <h5 class="card-title">Pending Apps</h5>
                    <p class="display-6"><?= htmlspecialchars($pendingApps) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-4">
            <div class="card shadow h-100 text-center">
                <div class="card-body">
                    <h5 class="card-title">Approved Apps</h5>
                    <p class="display-6 text-success"><?= htmlspecialchars($approvedApps) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-4">
            <div class="card shadow h-100 text-center">
                <div class="card-body">
                    <h5 class="card-title">Rejected Apps</h5>
                    <p class="display-6 text-danger"><?= htmlspecialchars($rejectedApps) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Extra metrics -->
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card shadow h-100 text-center">
                <div class="card-body">
                    <h5 class="card-title">Total Providers</h5>
                    <p class="display-6"><?= htmlspecialchars($totalProviders) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card shadow h-100 text-center">
                <div class="card-body">
                    <h5 class="card-title">Total Clients</h5>
                    <p class="display-6"><?= htmlspecialchars($totalClients) ?></p>
                </div>
            </div>
        </div>
    </div>
	
	<div class="row mt-5">
	<h3 class="mb-3">Pending Job Approvals</h3>
<?php if (empty($pendingJobs)): ?>
  <div class="alert alert-info">No jobs awaiting approval.</div>
<?php else: ?>
  <div class="row">
    <?php foreach ($pendingJobs as $job): ?>
      <div class="col-md-6 mb-4">
        <div class="card shadow h-100">
          <?php if (!empty($job['image'])): ?>
            <img src="../uploads/jobs/<?= htmlspecialchars($job['image']) ?>" 
                 class="card-img-top" alt="Job Image"
                 style="max-height:150px;object-fit:cover;">
          <?php endif; ?>
          <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($job['title']) ?></h5>
            <p class="card-text"><?= htmlspecialchars($job['description']) ?></p>
            <p><strong>Client:</strong> <?= htmlspecialchars($job['client_name']) ?></p>
            <p><strong>Payment:</strong> $<?= number_format($job['payment_amount'], 2) ?></p>
            <div id="map<?= $job['id'] ?>" style="height:140px;" class="mb-2"></div>
            <script>
              const map<?= $job['id'] ?> = initMap('map<?= $job['id'] ?>', {
                  lat: <?= $job['location_lat'] ?>,
                  lng: <?= $job['location_lng'] ?>,
                  zoom: 13
              });
              addMarker(map<?= $job['id'] ?>, <?= $job['location_lat'] ?>, <?= $job['location_lng'] ?>, "<?= htmlspecialchars($job['title']) ?>");
            </script>

            <!-- Approve / Reject -->
            <form method="POST" action="../actions/approve_job.php" class="d-inline">
              <input type="hidden" name="job_id" value="<?= htmlspecialchars($job['id']) ?>">
              <button type="submit" class="btn btn-success btn-sm">Approve</button>
            </form>
            <form method="POST" action="../actions/reject_job.php" class="d-inline">
              <input type="hidden" name="job_id" value="<?= htmlspecialchars($job['id']) ?>">
              <button type="submit" class="btn btn-danger btn-sm">Reject</button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

	</div>
	<div class="row mt-5">
    <div class="col-md-6">
        <div class="card shadow h-100">
            <div class="card-body">
                <h5 class="card-title text-center">Jobs Posted Per Month</h5>
                <canvas id="jobsChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow h-100">
            <div class="card-body">
                <h5 class="card-title text-center">Applications by Status</h5>
                <canvas id="appsChart"></canvas>
            </div>
        </div>
    </div>
</div>
<div class="row mt-5">
    <div class="col-md-12">
        <div class="card shadow h-100">
            <div class="card-body">
                <h5 class="card-title text-center">User Growth (Clients vs Providers)</h5>
                <canvas id="userGrowthChart"></canvas>
            </div>
        </div>
    </div>
</div>
<div class="row mt-4">
    <div class="col-md-3 mb-4">
        <div class="card shadow h-100 text-center">
            <div class="card-body">
                <h5 class="card-title">Total Users</h5>
                <p class="display-6"><?= htmlspecialchars($totalUsers) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card shadow h-100 text-center">
            <div class="card-body">
                <h5 class="card-title">Clients</h5>
                <p class="display-6"><?= htmlspecialchars($totalClients) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card shadow h-100 text-center">
            <div class="card-body">
                <h5 class="card-title">Providers</h5>
                <p class="display-6"><?= htmlspecialchars($totalProviders) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card shadow h-100 text-center">
            <div class="card-body">
                <h5 class="card-title">Total Applications</h5>
                <p class="display-6"><?= htmlspecialchars($totalApps) ?></p>
            </div>
        </div>
    </div>
</div>

<?php
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
?>
</main>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Jobs per month data
  const jobsData = {
    labels: <?= json_encode(array_column($jobsPerMonth, 'month')) ?>,
    datasets: [{
      label: 'Jobs Posted',
      data: <?= json_encode(array_column($jobsPerMonth, 'count')) ?>,
      backgroundColor: 'rgba(54, 162, 235, 0.6)'
    }]
  };

  new Chart(document.getElementById('jobsChart'), {
    type: 'bar',
    data: jobsData,
    options: { responsive: true, plugins: { legend: { display: false } } }
  });

  // Applications by status data
  const appsData = {
    labels: <?= json_encode(array_column($appStatus, 'status')) ?>,
    datasets: [{
      label: 'Applications',
      data: <?= json_encode(array_column($appStatus, 'count')) ?>,
      backgroundColor: [
        'rgba(255, 206, 86, 0.6)', // pending
        'rgba(75, 192, 192, 0.6)', // approved
        'rgba(255, 99, 132, 0.6)'  // rejected
      ]
    }]
  };

  new Chart(document.getElementById('appsChart'), {
    type: 'pie',
    data: appsData,
    options: { responsive: true }
  });
</script>

<script>
  const userGrowthLabels = <?= json_encode(array_column($userGrowth, 'month')) ?>;
  const clientData = <?= json_encode(array_column($userGrowth, 'clients')) ?>;
  const providerData = <?= json_encode(array_column($userGrowth, 'providers')) ?>;

  new Chart(document.getElementById('userGrowthChart'), {
    type: 'line',
    data: {
      labels: userGrowthLabels,
      datasets: [
        {
          label: 'Clients',
          data: clientData,
          borderColor: 'rgba(54, 162, 235, 1)',
          backgroundColor: 'rgba(54, 162, 235, 0.2)',
          fill: true,
          tension: 0.3
        },
        {
          label: 'Providers',
          data: providerData,
          borderColor: 'rgba(255, 99, 132, 1)',
          backgroundColor: 'rgba(255, 99, 132, 0.2)',
          fill: true,
          tension: 0.3
        }
      ]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'top' }
      },
      scales: {
        y: { beginAtZero: true }
      }
    }
  });
</script>
<div class="row mt-5">
    <div class="col-md-12">
        <div class="card shadow h-100">
            <div class="card-body">
                <h5 class="card-title text-center">Jobs Per Client</h5>
                <canvas id="jobsPerClientChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
  const jobsPerClientLabels = <?= json_encode(array_column($jobsPerClient, 'client_id')) ?>;
  const jobsPerClientData   = <?= json_encode(array_column($jobsPerClient, 'job_count')) ?>;

  new Chart(document.getElementById('jobsPerClientChart'), {
    type: 'bar',
    data: {
      labels: jobsPerClientLabels,
      datasets: [{
        label: 'Jobs Posted',
        data: jobsPerClientData,
        backgroundColor: 'rgba(153, 102, 255, 0.6)'
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true } }
    }
  });
</script>

<?php include_once("../includes/footer.php"); ?>