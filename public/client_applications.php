<?php
// public/client_applications.php - Applications for Client's Jobs
require_once("../config/init.php");
include_once("../includes/header.php");

// Only clients can view this page
requireLogin('client');

$client_id = $_SESSION['user_id'];

// Fetch applications for jobs posted by this client
$stmt = $pdo->prepare("
    SELECT applications.*, jobs.title, jobs.description, users.name AS provider_name
    FROM applications
    JOIN jobs ON applications.job_id = jobs.id
    JOIN users ON applications.provider_id = users.id
    WHERE jobs.client_id = ?
    ORDER BY applications.created_at DESC
");
$stmt->execute([$client_id]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="band">
  <div class="wrap band__row">
    <div>
      <span class="eyebrow"><?= nu_icon('people-fill') ?> Client</span>
      <h1>Applications for My Jobs</h1>
      <p>Review the providers who applied and approve the right fit.</p>
    </div>
  </div>
</section>

<main class="wrap page-body">
    <?php include_once("../includes/flash.php"); ?>

    <?php if (empty($applications)): ?>
        <?php nu_empty('people', 'No providers have applied to your jobs yet.', 'Applications will show up here as soon as providers apply.', '<a class="btn btn-primary" href="jobs.php#post">Post another job</a>'); ?>
    <?php else: ?>
        <div class="list">
            <?php foreach ($applications as $n => $app): ?>
            <div class="list-row" data-reveal style="--i:<?= $n % 6 ?>">
                <span class="list-row__icon"><?= nu_e(nu_initials($app['provider_name'])) ?></span>
                <div class="list-row__main">
                    <p class="list-row__title"><?= htmlspecialchars($app['provider_name']) ?></p>
                    <p class="list-row__sub">Applied for <strong><?= htmlspecialchars($app['title']) ?></strong> · <?= nu_e(nu_date($app['created_at'])) ?></p>
                </div>
                <div class="list-row__side">
                    <?= nu_status_chip($app['status']) ?>
                    <!-- Action buttons for client -->
                    <form method="POST" action="../actions/update_application.php" class="inline-form" style="gap:6px">
                        <input type="hidden" name="application_id" value="<?= htmlspecialchars($app['id']) ?>">
                        <button type="submit" name="approve" class="btn btn-success-soft btn-sm"><?= nu_icon('check2') ?> Approve</button>
                        <button type="submit" name="reject" class="btn btn-danger-soft btn-sm"><?= nu_icon('x-lg') ?> Reject</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include_once("../includes/footer.php"); ?>
