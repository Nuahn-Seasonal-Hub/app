<?php
// public/my_activity.php - Provider Activity Dashboard
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");
include_once("../includes/header.php");

requireLogin('provider');
$provider_id = $_SESSION['user_id'];

// Fetch applications
$appStmt = $pdo->prepare("SELECT applications.*, jobs.title, jobs.description, jobs.image, jobs.payment_amount, 
                                 jobs.location_lat, jobs.location_lng, users.name AS client_name
                          FROM applications
                          JOIN jobs ON applications.job_id = jobs.id
                          JOIN users ON jobs.client_id = users.id
                          WHERE applications.provider_id = ?
                          ORDER BY applications.created_at DESC");

$appStmt->execute([$provider_id]);
$applications = $appStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch saved jobs
$savedStmt = $pdo->prepare("SELECT jobs.*, users.name AS client_name, saved_jobs.created_at AS saved_at
                            FROM saved_jobs
                            JOIN jobs ON saved_jobs.job_id = jobs.id
                            JOIN users ON jobs.client_id = users.id
                            WHERE saved_jobs.provider_id = ?
                            ORDER BY saved_jobs.created_at DESC");

$savedStmt->execute([$provider_id]);
$savedJobs = $savedStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container py-5">
    <h2 class="text-center mb-4">My Activity</h2>

    <!-- Applications Section -->
    <h3 class="mb-3">My Applications</h3>
    <?php if (empty($applications)): ?>
        <div class="alert alert-info">You have not applied to any jobs yet.</div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($applications as $app): ?>
            <div class="col-md-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($app['title']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars($app['description']) ?></p>
                        <p><strong>Client:</strong> <?= htmlspecialchars($app['client_name']) ?></p>
                        <p><strong>Status:</strong> <?= htmlspecialchars($app['status']) ?></p>
                        <p><strong>Applied on:</strong> <?= htmlspecialchars($app['created_at']) ?></p>
						<?php if (!empty($app['image'])): ?>
  <img src="../uploads/jobs/<?= htmlspecialchars($app['image']) ?>" 
       class="card-img-top mb-2" alt="Job Image"
       style="max-height:150px;object-fit:cover;">
<?php endif; ?>

<p><strong>Payment:</strong> $<?= number_format($app['payment_amount'], 2) ?></p>

<div id="map<?= $app['id'] ?>" style="height:140px;" class="mb-2"></div>
<script>
  const map<?= $app['id'] ?> = initMap('map<?= $app['id'] ?>', {
      lat: <?= $app['location_lat'] ?>,
      lng: <?= $app['location_lng'] ?>,
      zoom: 13
  });
  addMarker(map<?= $app['id'] ?>, <?= $app['location_lat'] ?>, <?= $app['location_lng'] ?>, "<?= htmlspecialchars($app['title']) ?>");
</script>

                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Saved Jobs Section -->
    <h3 class="mt-5 mb-3">My Saved Jobs</h3>
    <?php if (empty($savedJobs)): ?>
        <div class="alert alert-info">You have not saved any jobs yet.</div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($savedJobs as $job): ?>
            <div class="col-md-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($job['title']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars($job['description']) ?></p>
                        <p><strong>Client:</strong> <?= htmlspecialchars($job['client_name']) ?></p>
                        <p><strong>Status:</strong> <?= htmlspecialchars($job['status']) ?></p>
                        <p><strong>Saved on:</strong> <?= htmlspecialchars($job['saved_at']) ?></p>
						<?php if (!empty($job['image'])): ?>
  <img src="../uploads/jobs/<?= htmlspecialchars($job['image']) ?>" 
       class="card-img-top mb-2" alt="Job Image"
       style="max-height:150px;object-fit:cover;">
<?php endif; ?>

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

                        <form method="POST" action="../actions/remove_saved_job.php" class="d-inline">
                            <input type="hidden" name="job_id" value="<?= htmlspecialchars($job['id']) ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                        </form>
                        <form method="POST" action="../actions/accept_job.php" class="d-inline">
                            <input type="hidden" name="job_id" value="<?= htmlspecialchars($job['id']) ?>">
                            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include_once("../includes/footer.php"); ?>