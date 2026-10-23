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
    <div class="row-flex enter" style="--i:1">
      <a class="btn btn-light" href="post_task.php"><?= nu_icon('plus-lg') ?> Post a task</a>
      <a class="btn btn-glass" href="mechanics.php"><?= nu_icon('wrench-adjustable') ?> Get a mechanic</a>
    </div>
  </div>
</section>

<main class="wrap page-body">
    <!-- Quick Alerts -->
    <?php if(isset($_GET['success']) && $_GET['success'] === 'profile_updated'): ?>
        <div class="alert alert-success"><?= nu_icon('check-circle-fill') ?> Your profile has been updated successfully.</div>
    <?php endif; ?>

<?php
    $svcs = nu_fixture('services'); $wx = nu_fixture('weather'); $nb = null;
    foreach (nu_fixture('bookings') as $b) { if ($b['id'] === 'NSH-1042') { $nb = $b; break; } }
    $rem = nu_fixture('care_plans')['reminders'][0] ?? null; // Premium panel only for enrolled customers (doc2 p1)
    $notes = array_slice(nu_fixture('notifications'), 0, 3);
?>
    <section class="mk-action" data-reveal aria-label="Needs your attention">
      <a class="mk-action__row" href="approve.php?id=SNOW-1035"><span class="mk-action__ic"><?= nu_icon('check2-all') ?></span><span><b>Approve the work to release payment</b><small>Priya Nair finished SNOW-1035. Before and after photos are ready. <?= nu_sample_tag('Sample') ?></small></span><?= nu_icon('chevron-right') ?></a>
      <a class="mk-action__row" href="offers.php?state=many"><span class="mk-action__ic"><?= nu_icon('chat-square-text') ?></span><span><b>4 offers on your task</b><small>TASK-2051 · from $39 · fastest about 40 min</small></span><?= nu_icon('chevron-right') ?></a>
      <a class="mk-action__row" href="post_task.php" data-draft hidden><span class="mk-action__ic"><?= nu_icon('pencil-square') ?></span><span><b>Continue your task draft</b><small data-draft-t>You started a snow task. Pick up where you left off.</small></span><?= nu_icon('chevron-right') ?></a>
    </section>
    <script>try{var d=JSON.parse(sessionStorage.getItem('nuahn.taskDraft')||'null');if(d){var a=document.querySelector('[data-draft]');a.hidden=false;if(d.addr)document.querySelector('[data-draft-t]').textContent=d.addr+' · budget $'+(d.budget||'')}}catch(e){}</script>
    <div class="grid grid-2" style="margin-bottom:var(--s5)">
<?php if ($nb): $nsv = $svcs[$nb['service']]; ?>
      <section class="card nb" data-reveal>
        <div class="wx__top"><h2 class="lside__h">Your next booking</h2><?= nu_booking_chip($nb['status']) ?></div>
        <div class="nb__row"><span class="nb__icon"><?= nu_icon($nsv['icon']) ?></span><div><div class="nb__title"><?= nu_e($nsv['name']) ?></div><div class="nb__when"><?= nu_e($nb['when']) ?> · <?= nu_e($nb['window']) ?></div><div class="nb__team"><?= nu_e($nb['team']) ?> · <?= nu_sample_tag('Sample') ?></div></div></div>
        <div class="nb__actions"><a class="btn btn-primary btn-sm" href="track.php?id=<?= nu_e(rawurlencode($nb['id'])) ?>">Track</a><a class="btn btn-outline btn-sm" href="chat.php?thread=t1"><?= nu_icon('chat-dots') ?> Message contractor</a></div>
      </section>
<?php endif; ?>
      <section class="card nb" data-reveal style="--i:1">
        <div class="wx__top"><h2 class="lside__h">Today in <?= nu_e($wx['city']) ?></h2><span class="wx__temp" style="font-size:1.3rem"><?= (int) $wx['now']['temp'] ?>°C</span></div>
<?php foreach ($notes as $n): ?>
        <div class="notif notif--<?= nu_e($n['type']) ?>" style="padding:8px 0"><span class="notif__ic"><?= nu_icon($n['icon']) ?></span><div><p class="notif__t" style="font-size:.92rem"><?= nu_e($n['title']) ?></p></div></div>
<?php endforeach; ?>
        <a class="btn btn-soft btn-sm btn-block mt-2" href="calendar.php">Open calendar <?= nu_icon('arrow-right') ?></a>
      </section>
    </div>

<?php if ($rem): ?>
    <section class="pl-rem ch-home-rem" data-reveal aria-label="Premium reminder">
      <span class="pl-ic"><?= nu_icon($svcs[$rem['service']]['icon']) ?></span>
      <div class="pl-rem__t"><b><?= nu_e($rem['title']) ?></b><span><?= nu_e($rem['provider']) ?>: <?= nu_e($rem['text']) ?></span></div>
      <div class="pl-rem__a"><a class="btn btn-primary btn-sm" href="book.php?service=<?= nu_e($rem['service']) ?>&amp;from=reminder">Confirm a visit</a><a class="btn btn-outline btn-sm" href="change_appointment.php?id=MECH-1042&amp;reminder=1">Change date</a><a class="btn btn-outline btn-sm" href="care_plans.php">Skip this month</a></div>
    </section>
    <a class="se-wx ch-home-snow" href="snow_event.php" data-reveal><?= nu_icon('cloud-snow') ?><span><b>Snow event confirmed for your area</b> Jordan Mercer is booked for #SNOW-1045. Busy? Ask them to come later. <?= nu_icon('arrow-right') ?></span></a>
<?php endif; ?>
    <div class="grid grid-stats">
        <div class="stat" data-reveal style="--i:0"><span class="stat__icon"><?= nu_icon('briefcase-fill') ?></span><div><div class="stat__label">Jobs posted</div><div class="stat__value" data-count="<?= $myJobs ?>"><?= $myJobs ?></div></div></div>
        <div class="stat" data-reveal style="--i:1"><span class="stat__icon stat__icon--success"><?= nu_icon('lightning-charge-fill') ?></span><div><div class="stat__label">Open now</div><div class="stat__value" data-count="<?= $myOpen ?>"><?= $myOpen ?></div></div></div>
        <div class="stat" data-reveal style="--i:2"><span class="stat__icon stat__icon--navy"><?= nu_icon('people-fill') ?></span><div><div class="stat__label">Applicants</div><div class="stat__value" data-count="<?= $received ?>"><?= $received ?></div></div></div>
    </div>

    <div class="section-title"><h2>Start a task</h2></div>
    <div class="grid grid-2 ch-svcs">
<?php $ti = 0; foreach (nu_services_by_status('live') as $sl => $sv): ?>
        <a class="tile" href="<?= nu_e(nu_book_url($sl)) ?>" data-reveal style="--i:<?= $ti++ ?>">
            <span class="tile__icon"><?= nu_icon($sv['icon']) ?></span>
            <span><span class="tile__title" style="display:block"><?= nu_e($sv['name']) ?></span><span class="tile__text"><?= nu_e($sv['blurb']) ?></span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
<?php endforeach; foreach (nu_services_by_status('coming_soon') as $sl => $sv): ?>
        <a class="tile tile--soon" href="<?= nu_e(nu_book_url($sl)) ?>" data-reveal style="--i:<?= $ti++ ?>">
            <span class="tile__icon tile__icon--soft"><?= nu_icon($sv['icon']) ?></span>
            <span><span class="tile__title" style="display:block"><?= nu_e($sv['name']) ?> <?= nu_soon_chip() ?></span><span class="tile__text">Not bookable yet. Get notified when it launches.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
<?php endforeach; ?>
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
        <a class="tile" href="chat.php?thread=support" data-reveal style="--i:5">
            <span class="tile__icon tile__icon--soft"><?= nu_icon('envelope') ?></span>
            <span><span class="tile__title" style="display:block">Support</span><span class="tile__text">Need help? Contact our support team for assistance.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
    </div>
</main>

<?php include "../includes/footer.php"; ?>
