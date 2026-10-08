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
<section class="band">
  <div class="wrap band__row">
    <div>
      <span class="eyebrow"><?= nu_icon('briefcase-fill') ?> Client</span>
      <h1>My Posted Jobs</h1>
      <p>Edit listings, update their status and see who applied.</p>
    </div>
    <a class="btn btn-light" href="jobs.php#post"><?= nu_icon('plus-lg') ?> Post a job</a>
  </div>
</section>

<main class="wrap page-body">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?= nu_icon('check-circle-fill') ?> <?= $_GET['success'] === 'job_deleted' ? 'Job deleted.' : ($_GET['success'] === 'status_updated' ? 'Job status updated.' : 'Saved.') ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?= nu_icon('x-circle') ?> Something went wrong (<?= nu_e($_GET['error']) ?>).</div>
    <?php endif; ?>

    <div class="grid grid-stats mb-6">
        <div class="stat" data-reveal style="--i:0"><span class="stat__icon stat__icon--navy"><?= nu_icon('collection') ?></span><div><div class="stat__label">Total</div><div class="stat__value"><?= $totalJobs ?></div></div></div>
        <div class="stat" data-reveal style="--i:1"><span class="stat__icon stat__icon--success"><?= nu_icon('lightning-charge-fill') ?></span><div><div class="stat__label">Posted</div><div class="stat__value"><?= $postedJobs ?></div></div></div>
        <div class="stat" data-reveal style="--i:2"><span class="stat__icon"><?= nu_icon('people-fill') ?></span><div><div class="stat__label">Filled</div><div class="stat__value"><?= $filledJobs ?></div></div></div>
        <div class="stat" data-reveal style="--i:3"><span class="stat__icon stat__icon--warning"><?= nu_icon('check2') ?></span><div><div class="stat__label">Closed</div><div class="stat__value"><?= $closedJobs ?></div></div></div>
    </div>

    <?php if (empty($jobs)): ?>
        <?php nu_empty('briefcase', 'You have not posted any jobs yet.', 'Post your first seasonal job and start receiving applications from nearby providers.', '<a class="btn btn-primary" href="jobs.php#post">Post a job</a>'); ?>
    <?php else: ?>
        <div class="grid grid-2 grid-jobs">
            <?php foreach ($jobs as $n => $job):
                ob_start(); ?>
                <a href="edit_job.php?id=<?= htmlspecialchars($job['id']) ?>" class="btn btn-soft btn-sm"><?= nu_icon('pencil-square') ?> Edit</a>

                <form method="POST" action="my_jobs.php" class="inline-form">
                    <input type="hidden" name="job_id" value="<?= htmlspecialchars($job['id']) ?>">
                    <button type="submit" name="delete_job" class="btn btn-danger-soft btn-sm" onclick="return confirm('Delete this job?');"><?= nu_icon('trash3') ?> Delete</button>
                </form>

                <form method="POST" action="update_job_status.php" class="inline-form" style="gap:6px">
                    <input type="hidden" name="job_id" value="<?= htmlspecialchars($job['id']) ?>">
                    <select name="status" class="input input-sm" style="width:auto" aria-label="Job status">
                        <option value="posted" <?= $job['status'] === 'posted' ? 'selected' : '' ?>>Posted</option>
                        <option value="filled" <?= $job['status'] === 'filled' ? 'selected' : '' ?>>Filled</option>
                        <option value="closed" <?= $job['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                    </select>
                    <button type="submit" class="btn btn-outline btn-sm">Update</button>
                </form>

                <?php if ($job['application_count'] > 0): ?>
                  <a href="../public/manage_applications.php?job_id=<?= htmlspecialchars($job['id']) ?>" class="btn btn-primary btn-sm"><?= nu_icon('people') ?> Applications</a>
                <?php endif; ?>
            <?php
                $foot = ob_get_clean();
                $meta = '<span>' . nu_icon('people') . (int) $job['application_count'] . ' applications</span>'
                      . '<span>' . nu_icon('calendar3') . nu_e(nu_date($job['created_at'])) . '</span>';
                nu_job_card($job, ['map' => true, 'footer' => $foot, 'showStatus' => true, 'meta' => $meta, 'i' => $n]);
            endforeach; ?>
        </div>
    <?php endif; ?>
</main>



<?php include_once("../includes/footer.php"); ?>
