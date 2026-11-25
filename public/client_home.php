<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/client_home.php: client home on live data (the signed-in client's own tasks, offers and updates).
require_once("../config/init.php"); // handles session, db, auth, flash helpers

// Only clients can view this page
requireLogin('client');

require_once("../includes/marketplace/view.php");
require_once("../includes/onboarding.php");
ob_ensure_users_schema();
$mkReady = mk_ready();

$uid = mk_uid();
$me = ob_user_get($uid);
$ver = ob_client_verification_status($me);
$active = $mkReady ? mk_client_tasks($uid, false) : [];
$allCount = $mkReady ? (count($active) + count(mk_client_tasks($uid, true))) : 0;
$myOpen = 0; $received = 0; $attn = [];
foreach ($active as $t) {
    if (in_array($t['status'], ['open_for_bids', 'quote_pending', 'searching'], true)) $myOpen++;
    $received += (int) $t['offer_count'];
    if ($t['status'] === 'pending_approval') $attn[] = ['check2-all', 'Approve the work to release payment', ($t['contractor_name'] ?: 'Your contractor') . ' finished ' . $t['title'] . '. Review the before and after photos.', nu_booking_href($t)];
    elseif ($t['status'] === 'open_for_bids' && $t['offer_count'] > 0) $attn[] = ['chat-square-text', $t['offer_count'] . ' offer' . ($t['offer_count'] > 1 ? 's' : '') . ' on your task', $t['title'], nu_booking_href($t)];
    elseif ($t['status'] === 'quote_pending') $attn[] = ['receipt', 'Review mechanic quotes', $t['title'], nu_booking_href($t)];
    elseif ($t['status'] === 'awaiting_payment') $attn[] = ['credit-card', 'Finish the payment hold', $t['title'], nu_booking_href($t)];
    elseif ($t['status'] === 'paused') $attn[] = ['exclamation-triangle', 'Extra work needs your approval', $t['title'], nu_url('public/track.php') . '?id=' . (int) $t['id'] . '#revision'];
}
$next = null; foreach ($active as $t) { if (in_array($t['status'], ['confirmed', 'on_the_way', 'arrived', 'in_progress'], true)) { $next = $t; break; } }
$notes = [];
if ($mkReady) {
    try {
        $nst = mk_db()->prepare('SELECT title, type FROM job_notifications WHERE user_id = ? ORDER BY id DESC LIMIT 3');
        $nst->execute([$uid]);
        $notes = $nst->fetchAll();
    } catch (Throwable $e) {
        error_log('client_home notifications: ' . $e->getMessage());
    }
}
$hour = (int) date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
include "../includes/header.php";
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

<?php if ($ver['pending_count'] > 0): ?>
    <section class="card cl-verify" data-reveal aria-label="Account verification">
      <div class="cl-verify__top">
        <div>
          <h2 class="cl-verify__h"><?= nu_icon('shield-check') ?> <?= nu_e($ver['label']) ?></h2>
          <p class="mk-sub">You can post tasks right away. Confirming your contact details speeds up contractor responses and unlocks SMS offer alerts.</p>
        </div>
        <div class="cl-verify__chips">
          <span class="chip <?= $ver['email_ok'] ? 'chip--success' : 'chip--warning' ?>"><?= $ver['email_ok'] ? 'Email verified' : ($ver['has_real_email'] ? 'Email pending' : 'No email yet') ?></span>
          <span class="chip <?= $ver['phone_ok'] ? 'chip--success' : 'chip--soft' ?>"><?= $ver['phone_ok'] ? 'Phone verified' : 'Phone optional' ?></span>
        </div>
      </div>
      <div class="cl-verify__acts">
<?php if (!$ver['email_ok'] && $ver['has_real_email']): ?>
        <form action="<?= nu_e(nu_url('actions/onboarding.php')) ?>" method="POST" class="d-inline">
          <?= nu_csrf_field() ?>
          <input type="hidden" name="do" value="resend_email">
          <input type="hidden" name="back" value="public/client_home.php">
          <button type="submit" class="btn btn-outline btn-sm"><?= nu_icon('envelope') ?> Resend verification email</button>
        </form>
<?php elseif (!$ver['has_real_email']): ?>
        <a class="btn btn-outline btn-sm" href="profile.php#verification"><?= nu_icon('envelope') ?> Add email address</a>
<?php endif; ?>
<?php if (!$ver['phone_ok']): ?>
        <a class="btn btn-soft btn-sm" href="profile.php#verification"><?= nu_icon('phone') ?> Verify phone (optional)</a>
<?php endif; ?>
<?php if (!$ver['email_ok'] && !empty($_SESSION['ob_last_verify_link'])): ?>
        <a class="btn btn-primary btn-sm" href="<?= nu_e($_SESSION['ob_last_verify_link']) ?>"><?= nu_icon('check-circle-fill') ?> Beta: verify email now</a>
<?php endif; ?>
      </div>
    </section>
<?php endif; ?>

<?php $svcs = nu_fixture('services'); ?>
<?php if ($attn): ?>
    <section class="mk-action" data-reveal aria-label="Needs your attention">
<?php foreach (array_slice($attn, 0, 4) as $x): ?>
      <a class="mk-action__row" href="<?= nu_e($x[3]) ?>"><span class="mk-action__ic"><?= nu_icon($x[0]) ?></span><span><b><?= nu_e($x[1]) ?></b><small><?= nu_e($x[2]) ?></small></span><?= nu_icon('chevron-right') ?></a>
<?php endforeach; ?>
    </section>
<?php endif; ?>
    <div class="grid grid-2" style="margin-bottom:var(--s5)">
<?php if ($next): $nsv = $svcs[$next['service']] ?? ['name' => $next['title'], 'icon' => 'briefcase']; ?>
      <section class="card nb" data-reveal>
        <div class="wx__top"><h2 class="lside__h">Your next booking</h2><?= nu_booking_chip($next['status']) ?></div>
        <div class="nb__row"><span class="nb__icon"><?= nu_icon($nsv['icon']) ?></span><div><div class="nb__title"><?= nu_e($next['title']) ?></div><div class="nb__when"><?= nu_e(mk_when($next)) ?></div><div class="nb__team"><?= nu_e($next['contractor_name'] ?: 'Contractor to be confirmed') ?> · <?= nu_e(mk_area($next['address'])) ?></div></div></div>
        <div class="nb__actions"><a class="btn btn-primary btn-sm" href="track.php?id=<?= (int) $next['id'] ?>">Track</a><a class="btn btn-outline btn-sm" href="track.php?id=<?= (int) $next['id'] ?>#messages"><?= nu_icon('chat-dots') ?> Message</a></div>
      </section>
<?php endif; ?>
      <section class="card nb" data-reveal style="--i:1">
        <div class="wx__top"><h2 class="lside__h">Latest updates</h2><a class="btn btn-ghost btn-sm" href="updates.php">All</a></div>
<?php foreach ($notes as $n): ?>
        <div class="notif" style="padding:8px 0"><span class="notif__ic"><?= nu_icon('bell') ?></span><div><p class="notif__t" style="font-size:.92rem"><?= nu_e($n['title']) ?></p></div></div>
<?php endforeach; ?>
<?php if (!$notes): ?><p class="muted">Offers, payments and job progress show up here.</p><?php endif; ?>
        <a class="btn btn-soft btn-sm btn-block mt-2" href="bookings.php">My bookings <?= nu_icon('arrow-right') ?></a>
      </section>
    </div>

    <div class="grid grid-stats">
        <div class="stat" data-reveal style="--i:0"><span class="stat__icon"><?= nu_icon('briefcase-fill') ?></span><div><div class="stat__label">Tasks posted</div><div class="stat__value" data-count="<?= $allCount ?>"><?= $allCount ?></div></div></div>
        <div class="stat" data-reveal style="--i:1"><span class="stat__icon stat__icon--success"><?= nu_icon('lightning-charge-fill') ?></span><div><div class="stat__label">Open now</div><div class="stat__value" data-count="<?= $myOpen ?>"><?= $myOpen ?></div></div></div>
        <div class="stat" data-reveal style="--i:2"><span class="stat__icon stat__icon--navy"><?= nu_icon('people-fill') ?></span><div><div class="stat__label">Offers received</div><div class="stat__value" data-count="<?= $received ?>"><?= $received ?></div></div></div>
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
