<?php
// public/applications.php - Provider Applications Page
require_once("../config/init.php");
include_once("../includes/header.php");

// Only providers can view this page
requireLogin('provider');

$provider_id = $_SESSION['user_id'];

// Fetch applications for this provider
$stmt = $pdo->prepare("
    SELECT applications.*, jobs.title, jobs.description, users.name AS client_name
    FROM applications
    JOIN jobs ON applications.job_id = jobs.id
    JOIN users ON jobs.client_id = users.id
    WHERE applications.provider_id = ?
    ORDER BY applications.created_at DESC
");
$stmt->execute([$provider_id]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="band">
  <div class="wrap band__row">
    <div>
      <span class="eyebrow"><?= nu_icon('clipboard-check-fill') ?> Provider</span>
      <h1>My Applications</h1>
      <p>Every job you’ve applied for, and where it stands.</p>
    </div>
    <a class="btn btn-glass" href="my_activity.php"><?= nu_icon('collection') ?> Activity</a>
  </div>
</section>

<main class="wrap page-body">
    <?php include_once("../includes/flash.php"); ?>

    <?php if (empty($applications)): ?>
        <?php nu_empty('clipboard-check', 'You have not applied to any jobs yet.', 'Browse open seasonal jobs and apply in a tap.', '<a class="btn btn-primary" href="jobs.php">Find jobs</a>'); ?>
    <?php else: ?>
        <div class="list">
            <?php foreach ($applications as $n => $app): ?>
            <div class="list-row" data-reveal style="--i:<?= $n % 6 ?>">
                <span class="list-row__icon"><?= nu_icon('briefcase-fill') ?></span>
                <div class="list-row__main">
                    <p class="list-row__title"><?= htmlspecialchars($app['title']) ?></p>
                    <p class="list-row__sub"><?= htmlspecialchars($app['client_name']) ?> · Applied <?= nu_e(nu_date($app['created_at'])) ?></p>
                </div>
                <div class="list-row__side"><?= nu_status_chip($app['status']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include_once("../includes/footer.php"); ?>
