<?php
// public/my_jobs.php - Client's Posted Jobs
//session_start();
require_once("../config/init.php");
include_once("../includes/header.php");
// Only clients can view this page
requireLogin('client');

$client_id = $_SESSION['user_id'];

// Handle job deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_job'])) {
    $job_id = intval($_POST['job_id']);

    $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ? AND client_id = ?");
    $stmt->execute([$job_id, $client_id]);

    // Audit log
    $log = $pdo->prepare("INSERT INTO audit_logs (action, target_user_id, job_id, created_at) 
                          VALUES (?, ?, ?, NOW())");
    $log->execute(["Job deleted", $client_id, $job_id]);

    header("Location: my_jobs.php?success=job_deleted");
    exit;
}

// Fetch jobs posted by this client
$stmt = $pdo->prepare("SELECT jobs.*, COUNT(applications.id) AS application_count
                       FROM jobs
                       LEFT JOIN applications ON jobs.id = applications.job_id
                       WHERE jobs.client_id = ?
                       GROUP BY jobs.id
                       ORDER BY jobs.created_at DESC");
$stmt->execute([$client_id]);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
$totalJobs = count($jobs);
$postedJobs = 0;
$filledJobs = 0;
$closedJobs = 0;

foreach ($jobs as $job) {
    if ($job['status'] === 'posted') $postedJobs++;
    if ($job['status'] === 'filled') $filledJobs++;
    if ($job['status'] === 'closed') $closedJobs++;
}
?>
<main class="container py-5">
    <h2 class="text-center mb-4">My Posted Jobs</h2>

    <?php if (empty($jobs)): ?>
        <div class="alert alert-info">You have not posted any jobs yet.</div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($jobs as $job): ?>
            <div class="col-md-6 mb-4">
                <div class="card shadow h-100">
                    <?php if (!empty($job['image'])): ?>
                        <img src="../uploads/jobs/<?= htmlspecialchars($job['image']) ?>" 
                             class="card-img-top" alt="Job Image" 
                             style="max-height:200px;object-fit:cover;">
                    <?php endif; ?>

                    <div class="card-body">
                        <h5 class="card-title d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($job['title']) ?>
                            <!-- Status Badge -->
                            <?php if ($job['status'] === 'posted'): ?>
                                <span class="badge bg-success">Posted</span>
                            <?php elseif ($job['status'] === 'filled'): ?>
                                <span class="badge bg-warning text-dark">Filled</span>
                            <?php elseif ($job['status'] === 'closed'): ?>
                                <span class="badge bg-secondary">Closed</span>
                            <?php endif; ?>
                        </h5>

                        <p class="card-text"><?= htmlspecialchars($job['description']) ?></p>

                        <ul class="list-unstyled small mb-3">
                            <li><strong>Posted on:</strong> <?= htmlspecialchars($job['created_at']) ?></li>
							<li><strong>Payment:</strong> $<?= number_format($job['payment_amount'], 2) ?></li>

                            <li><strong>Applications:</strong> <?= htmlspecialchars($job['application_count']) ?></li>
                            <li><strong>Location:</strong> 
                                <a href="https://www.google.com/maps?q=<?= htmlspecialchars($job['location_lat']) ?>,<?= htmlspecialchars($job['location_lng']) ?>" target="_blank">
                                    View on Google Maps
                                </a>
                            </li>
                        </ul>

                        <!-- Inline Map -->
                        <div id="map<?= $job['id'] ?>" style="height:180px;" class="mb-3"></div>
                        <script>
                          const map<?= $job['id'] ?> = initMap('map<?= $job['id'] ?>', {
                              lat: <?= $job['location_lat'] ?>,
                              lng: <?= $job['location_lng'] ?>,
                              zoom: 13
                          });
                          addMarker(map<?= $job['id'] ?>, <?= $job['location_lat'] ?>, <?= $job['location_lng'] ?>, "<?= htmlspecialchars($job['title']) ?>");
                        </script>

                        <!-- Actions -->
                        <div class="d-flex flex-wrap gap-2">
                            <a href="edit_job.php?id=<?= htmlspecialchars($job['id']) ?>" class="btn btn-warning btn-sm">Edit</a>

                            <form method="POST" action="my_jobs.php" class="d-inline">
                                <input type="hidden" name="job_id" value="<?= htmlspecialchars($job['id']) ?>">
                                <button type="submit" name="delete_job" class="btn btn-danger btn-sm" onclick="return confirm('Delete this job?');">Delete</button>
                            </form>

                            <form method="POST" action="update_job_status.php" class="d-inline">
                                <input type="hidden" name="job_id" value="<?= htmlspecialchars($job['id']) ?>">
                                <select name="status" class="form-select form-select-sm d-inline w-auto">
                                    <option value="posted" <?= $job['status'] === 'posted' ? 'selected' : '' ?>>Posted</option>
                                    <option value="filled" <?= $job['status'] === 'filled' ? 'selected' : '' ?>>Filled</option>
                                    <option value="closed" <?= $job['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                                </select>
                                <button type="submit" class="btn btn-info btn-sm">Update</button>
                            </form>

                            <?php if ($job['application_count'] > 0): ?>
                              <a href="../public/manage_applications.php?job_id=<?= htmlspecialchars($job['id']) ?>" class="btn btn-primary btn-sm">View Applications</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>



<?php include_once("../includes/footer.php"); ?>