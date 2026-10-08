<?php
// public/provider_home.php
require_once("../config/init.php");
include_once("../includes/header.php");

// Only providers can view this page
requireLogin('provider');

// Read-only figures for the dashboard (display only)
$uid = $_SESSION['user_id'] ?? 0;
$appCount   = nu_count($pdo, "SELECT COUNT(*) FROM applications WHERE provider_id = ?", [$uid]);
$savedCount = nu_count($pdo, "SELECT COUNT(*) FROM saved_jobs WHERE provider_id = ?", [$uid]);
$openCount  = nu_count($pdo, "SELECT COUNT(*) FROM jobs WHERE status = 'posted'");
$fresh = [];
try {
    $st = $pdo->query("SELECT jobs.*, users.name AS client_name FROM jobs JOIN users ON jobs.client_id = users.id WHERE jobs.status = 'posted' ORDER BY jobs.created_at DESC LIMIT 4");
    $fresh = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $fresh = []; }
$hour = (int) date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
?>

<section class="band">
  <div class="wrap band__row">
    <div class="welcome enter">
      <span class="avatar"><?= nu_e(nu_initials($_SESSION['user_name'] ?? 'P')) ?></span>
      <div>
        <p class="welcome__hi"><?= $greet ?>,</p>
        <h1 style="margin:0">Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Provider'); ?>!</h1>
      </div>
    </div>
    <a class="btn btn-light enter" style="--i:1" href="jobs.php"><?= nu_icon('search') ?> Find jobs</a>
  </div>
</section>

<main class="wrap page-body">
    <?php include_once("../includes/flash.php"); ?>

    <div class="grid grid-stats">
        <div class="stat" data-reveal style="--i:0"><span class="stat__icon"><?= nu_icon('briefcase-fill') ?></span><div><div class="stat__label">Open jobs</div><div class="stat__value" data-count="<?= $openCount ?>"><?= $openCount ?></div></div></div>
        <div class="stat" data-reveal style="--i:1"><span class="stat__icon stat__icon--success"><?= nu_icon('clipboard-check-fill') ?></span><div><div class="stat__label">Applications</div><div class="stat__value" data-count="<?= $appCount ?>"><?= $appCount ?></div></div></div>
        <div class="stat" data-reveal style="--i:2"><span class="stat__icon stat__icon--navy"><?= nu_icon('bookmark-fill') ?></span><div><div class="stat__label">Saved</div><div class="stat__value" data-count="<?= $savedCount ?>"><?= $savedCount ?></div></div></div>
    </div>

    <div class="section-title"><h2>Quick actions</h2></div>
    <div class="grid grid-2">
        <a class="tile" href="jobs.php" data-reveal style="--i:0">
            <span class="tile__icon"><?= nu_icon('search') ?></span>
            <span><span class="tile__title" style="display:block">Browse Seasonal Jobs</span><span class="tile__text">Find new job listings and apply to opportunities that match your skills.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
        <a class="tile" href="applications.php" data-reveal style="--i:1">
            <span class="tile__icon"><?= nu_icon('clipboard-check') ?></span>
            <span><span class="tile__title" style="display:block">My Applications</span><span class="tile__text">Track the status of jobs you’ve applied for.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
        <a class="tile" href="saved_jobs.php" data-reveal style="--i:2">
            <span class="tile__icon tile__icon--navy"><?= nu_icon('bookmark') ?></span>
            <span><span class="tile__title" style="display:block">Saved Jobs</span><span class="tile__text">Review jobs you’ve bookmarked for later.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
        <a class="tile" href="profile.php" data-reveal style="--i:3">
            <span class="tile__icon tile__icon--soft"><?= nu_icon('gear') ?></span>
            <span><span class="tile__title" style="display:block">Profile Settings</span><span class="tile__text">Update your personal details and preferences.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
    </div>

    <div class="section-title"><h2>Fresh opportunities</h2><a class="btn btn-ghost btn-sm" href="jobs.php">See all <?= nu_icon('arrow-right') ?></a></div>
    <?php if (empty($fresh)): ?>
        <?php nu_empty('inbox', 'No open jobs right now', 'New seasonal jobs are posted all the time. Check back soon.'); ?>
    <?php else: ?>
    <div class="list">
        <?php foreach ($fresh as $n => $job): ?>
        <a class="list-row" href="jobs.php#job<?= (int) $job['id'] ?>" data-reveal style="--i:<?= $n ?>">
            <span class="list-row__icon"><?= nu_e(nu_initials($job['client_name'])) ?></span>
            <span class="list-row__main">
                <span class="list-row__title" style="display:block;color:var(--ink)"><?= nu_e($job['title']) ?></span>
                <span class="list-row__sub" style="display:block"><?= nu_e($job['client_name']) ?> · <?= nu_e(nu_place($job)) ?></span>
            </span>
            <span class="list-row__side"><strong style="color:var(--ink)"><?= nu_money($job['payment_amount']) ?></strong><?= nu_icon('chevron-right', 'muted') ?></span>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>

<?php include "../includes/footer.php"; ?>
