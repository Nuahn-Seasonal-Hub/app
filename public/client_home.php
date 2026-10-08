<?php
// public/client_home.php
require_once("../config/init.php"); // handles session, db, auth, flash helpers

// Only clients can view this page
requireLogin('client');

include "../includes/header.php";

// Read-only figures for the dashboard (display only)
$uid = $_SESSION['user_id'] ?? 0;
$myJobs   = nu_count($pdo, "SELECT COUNT(*) FROM jobs WHERE client_id = ?", [$uid]);
$myOpen   = nu_count($pdo, "SELECT COUNT(*) FROM jobs WHERE client_id = ? AND status = 'posted'", [$uid]);
$received = nu_count($pdo, "SELECT COUNT(*) FROM applications JOIN jobs ON applications.job_id = jobs.id WHERE jobs.client_id = ?", [$uid]);
$hour = (int) date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
?>

<section class="band">
  <div class="wrap band__row">
    <div class="welcome enter">
      <span class="avatar"><?= nu_e(nu_initials($_SESSION['user_name'] ?? 'C')) ?></span>
      <div>
        <p class="welcome__hi"><?= $greet ?>,</p>
        <h1 style="margin:0">Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Client'); ?>!</h1>
      </div>
    </div>
    <a class="btn btn-light enter" style="--i:1" href="jobs.php#post"><?= nu_icon('plus-lg') ?> Post a job</a>
  </div>
</section>

<main class="wrap page-body">
    <!-- Quick Alerts -->
    <?php if(isset($_GET['success']) && $_GET['success'] === 'profile_updated'): ?>
        <div class="alert alert-success"><?= nu_icon('check-circle-fill') ?> Your profile has been updated successfully.</div>
    <?php endif; ?>

    <div class="grid grid-stats">
        <div class="stat" data-reveal style="--i:0"><span class="stat__icon"><?= nu_icon('briefcase-fill') ?></span><div><div class="stat__label">Jobs posted</div><div class="stat__value" data-count="<?= $myJobs ?>"><?= $myJobs ?></div></div></div>
        <div class="stat" data-reveal style="--i:1"><span class="stat__icon stat__icon--success"><?= nu_icon('lightning-charge-fill') ?></span><div><div class="stat__label">Open now</div><div class="stat__value" data-count="<?= $myOpen ?>"><?= $myOpen ?></div></div></div>
        <div class="stat" data-reveal style="--i:2"><span class="stat__icon stat__icon--navy"><?= nu_icon('people-fill') ?></span><div><div class="stat__label">Applicants</div><div class="stat__value" data-count="<?= $received ?>"><?= $received ?></div></div></div>
    </div>

    <div class="section-title"><h2>Quick actions</h2></div>
    <div class="grid grid-2">
        <a class="tile" href="jobs.php#post" data-reveal style="--i:0">
            <span class="tile__icon"><?= nu_icon('plus-lg') ?></span>
            <span><span class="tile__title" style="display:block">Post a seasonal job</span><span class="tile__text">Describe the work, drop a pin and set the pay.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
        <a class="tile" href="my_jobs.php" data-reveal style="--i:1">
            <span class="tile__icon tile__icon--navy"><?= nu_icon('briefcase') ?></span>
            <span><span class="tile__title" style="display:block">My posted jobs</span><span class="tile__text">Edit, update status or remove your listings.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
        <a class="tile" href="client_applications.php" data-reveal style="--i:2">
            <span class="tile__icon"><?= nu_icon('people') ?></span>
            <span><span class="tile__title" style="display:block">My Applications</span><span class="tile__text">Review providers who applied and approve the right fit.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
        <a class="tile" href="jobs.php" data-reveal style="--i:3">
            <span class="tile__icon tile__icon--soft"><?= nu_icon('search') ?></span>
            <span><span class="tile__title" style="display:block">Browse Seasonal Jobs</span><span class="tile__text">See what’s open on the marketplace right now.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
        <a class="tile" href="profile.php" data-reveal style="--i:4">
            <span class="tile__icon tile__icon--soft"><?= nu_icon('gear') ?></span>
            <span><span class="tile__title" style="display:block">Profile Settings</span><span class="tile__text">Update your personal details and preferences.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
        <a class="tile" href="support.php" data-reveal style="--i:5">
            <span class="tile__icon tile__icon--soft"><?= nu_icon('envelope') ?></span>
            <span><span class="tile__title" style="display:block">Support</span><span class="tile__text">Need help? Contact our support team for assistance.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
    </div>
</main>

<?php include "../includes/footer.php"; ?>
