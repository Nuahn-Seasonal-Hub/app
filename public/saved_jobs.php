<?php
// public/saved_jobs.php
require_once("../config/init.php");
include_once("../includes/header.php");
requireLogin('provider');

$provider_id = $_SESSION['user_id'];

// Fetch saved jobs
$stmt = $pdo->prepare("
    SELECT jobs.*, users.name AS client_name
    FROM saved_jobs
    JOIN jobs ON saved_jobs.job_id = jobs.id
    JOIN users ON jobs.client_id = users.id
    WHERE saved_jobs.provider_id = ?
    ORDER BY saved_jobs.id DESC
");
$stmt->execute([$provider_id]);
$saved_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container py-5">
    <h2 class="mb-4 text-center">My Saved Jobs</h2>

    <?php include_once("../includes/flash.php"); ?>

    <?php if (empty($saved_jobs)): ?>
        <div class="alert alert-info">You have not saved any jobs yet.</div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($saved_jobs as $job): ?>
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
			<p><strong>Payment:</strong> $<?= number_format($job['payment_amount'], 2) ?></p>
            <p><strong>Posted by:</strong> <?= htmlspecialchars($job['client_name']) ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($job['status']) ?></p>

            <!-- Compact Map -->
            <div id="map<?= $job['id'] ?>" style="height:140px;" class="mb-2"></div>
            <script>
              const map<?= $job['id'] ?> = initMap('map<?= $job['id'] ?>', {
                  lat: <?= $job['location_lat'] ?>,
                  lng: <?= $job['location_lng'] ?>,
                  zoom: 13
              });
              addMarker(map<?= $job['id'] ?>, <?= $job['location_lat'] ?>, <?= $job['location_lng'] ?>, "<?= htmlspecialchars($job['title']) ?>");
            </script>

            <!-- Actions -->
            <form method="POST" action="../actions/accept_job.php" class="d-inline">
                <input type="hidden" name="job_id" value="<?= htmlspecialchars($job['id']) ?>">
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            </form>

            <form method="POST" action="../actions/remove_saved_job.php" class="d-inline">
                <input type="hidden" name="job_id" value="<?= htmlspecialchars($job['id']) ?>">
                <button type="submit" class="btn btn-danger btn-sm">Remove</button>
            </form>
        </div>
    </div>
</div>

            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include_once("../includes/footer.php"); ?>
