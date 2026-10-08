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

<section class="band">
  <div class="wrap band__row">
    <div>
      <span class="eyebrow"><?= nu_icon('collection') ?> Provider</span>
      <h1>My Activity</h1>
      <p>Your applications and saved jobs in one place.</p>
    </div>
  </div>
</section>

<main class="wrap page-body">
    <!-- Applications Section -->
    <div class="section-title" style="margin-top:0"><h2>My Applications</h2><span class="chip chip--blue chip--plain"><?= count($applications) ?></span></div>
    <?php if (empty($applications)): ?>
        <?php nu_empty('clipboard-check', 'You have not applied to any jobs yet.', 'Browse open seasonal jobs and apply in a tap.', '<a class="btn btn-primary" href="jobs.php">Find jobs</a>'); ?>
    <?php else: ?>
        <div class="grid grid-2 grid-jobs">
            <?php foreach ($applications as $n => $app):
                $card = $app;
                $meta = '<span>' . nu_icon('calendar3') . 'Applied ' . nu_e(nu_date($app['created_at'])) . '</span>' . nu_status_chip($app['status']);
                nu_job_card($card, ['map' => true, 'meta' => $meta, 'i' => $n, 'uid' => 'a' . $app['id']]);
            endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Saved Jobs Section -->
    <div class="section-title"><h2>My Saved Jobs</h2><span class="chip chip--blue chip--plain"><?= count($savedJobs) ?></span></div>
    <?php if (empty($savedJobs)): ?>
        <?php nu_empty('bookmark', 'You have not saved any jobs yet.', 'Tap Save on any job to keep it here for later.'); ?>
    <?php else: ?>
        <div class="grid grid-2 grid-jobs">
            <?php foreach ($savedJobs as $n => $job):
                $foot = '<form method="POST" action="../actions/remove_saved_job.php" class="inline-form">'
                      . '<input type="hidden" name="job_id" value="' . htmlspecialchars($job['id']) . '">'
                      . '<button type="submit" class="btn btn-danger-soft btn-sm">' . nu_icon('trash3') . ' Remove</button></form>'
                      . '<form method="POST" action="../actions/accept_job.php" class="inline-form">'
                      . '<input type="hidden" name="job_id" value="' . htmlspecialchars($job['id']) . '">'
                      . '<button type="submit" class="btn btn-primary btn-sm">Apply ' . nu_icon('arrow-right') . '</button></form>';
                $job['created_at'] = $job['saved_at'] ?? ($job['created_at'] ?? null);
                nu_job_card($job, ['map' => true, 'footer' => $foot, 'showStatus' => true, 'i' => $n, 'uid' => 's' . $job['id']]);
            endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include_once("../includes/footer.php"); ?>
