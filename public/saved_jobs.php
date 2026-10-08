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

<section class="band">
  <div class="wrap band__row">
    <div>
      <span class="eyebrow"><?= nu_icon('bookmark-fill') ?> Provider</span>
      <h1>My Saved Jobs</h1>
      <p>Jobs you bookmarked. Apply when you’re ready.</p>
    </div>
    <a class="btn btn-light" href="jobs.php"><?= nu_icon('search') ?> Find more</a>
  </div>
</section>

<main class="wrap page-body">
    <?php include_once("../includes/flash.php"); ?>

    <?php if (empty($saved_jobs)): ?>
        <?php nu_empty('bookmark', 'You have not saved any jobs yet.', 'Tap Save on any job to keep it here for later.', '<a class="btn btn-primary" href="jobs.php">Browse jobs</a>'); ?>
    <?php else: ?>
        <div class="grid grid-2 grid-jobs">
            <?php foreach ($saved_jobs as $n => $job):
                $foot = '<form method="POST" action="../actions/remove_saved_job.php" class="inline-form">'
                      . '<input type="hidden" name="job_id" value="' . htmlspecialchars($job['id']) . '">'
                      . '<button type="submit" class="btn btn-danger-soft btn-sm">' . nu_icon('trash3') . ' Remove</button></form>'
                      . '<form method="POST" action="../actions/accept_job.php" class="inline-form">'
                      . '<input type="hidden" name="job_id" value="' . htmlspecialchars($job['id']) . '">'
                      . '<button type="submit" class="btn btn-primary btn-sm">Apply ' . nu_icon('arrow-right') . '</button></form>';
                nu_job_card($job, ['map' => true, 'footer' => $foot, 'showStatus' => true, 'i' => $n]);
            endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include_once("../includes/footer.php"); ?>
