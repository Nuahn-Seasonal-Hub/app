<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/provider_home.php: contractor home ("My jobs") on live data.
require_once("../config/init.php");
requireLogin('provider');
require_once("../includes/marketplace/view.php");
$uid = mk_uid();
$prof = mk_profile($uid);
$approved = $prof && $prof['vetting_status'] === 'approved';
$ctr = mk_contractor_card($uid);
$first = explode(' ', (string) ($ctr['name'] ?? ($_SESSION['user_name'] ?? 'there')))[0];
$board = $approved ? mk_board($uid) : [];
$mine = mk_contractor_tasks($uid, false);
$earn = mk_contractor_earnings($uid);
$hour = (int) date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
include_once("../includes/header.php");
?>

<section class="band">
  <div class="wrap band__row">
    <div class="welcome enter">
      <span class="avatar"><?= nu_e(nu_initials($ctr['name'] ?? $first)) ?></span>
      <div>
        <p class="welcome__hi"><?= $greet ?>,</p>
        <h1 style="margin:0">Welcome, <?= nu_e($first) ?>!</h1>
      </div>
    </div>
    <a class="btn btn-light enter" style="--i:1" href="open_jobs.php"><?= nu_icon('megaphone') ?> Open jobs</a>
  </div>
</section>

<main class="wrap page-body">
    <?php include_once("../includes/flash.php"); ?>
<?php if (!$approved): ?>
    <p class="mk-note mk-note--warn"><?= nu_icon('exclamation-circle') ?> <span>Your application is <b><?= nu_e(str_replace('_', ' ', $prof['vetting_status'] ?? 'draft')) ?></b>. You can see and send offers on open jobs once Nuahn approves you. <a href="contractor_setup.php">Open your application</a>.</span></p>
<?php endif; ?>
    <div class="grid grid-stats">
        <a class="stat" href="open_jobs.php" data-reveal style="--i:0;color:inherit"><span class="stat__icon"><?= nu_icon('megaphone') ?></span><span><b class="stat__n"><?= count($board) ?></b><span class="stat__l">Open jobs near you</span></span></a>
        <a class="stat" href="provider_dashboard.php#today-list" data-reveal style="--i:1;color:inherit"><span class="stat__icon stat__icon--success"><?= nu_icon('clipboard-check-fill') ?></span><span><b class="stat__n"><?= count($mine) ?></b><span class="stat__l">Your active jobs</span></span></a>
        <a class="stat" href="provider_dashboard.php#earnings" data-reveal style="--i:2;color:inherit"><span class="stat__icon stat__icon--navy"><?= nu_icon('wallet2') ?></span><span><b class="stat__n">$<?= number_format((float) $earn['sum']['held'] + (float) $earn['sum']['pending'], 0) ?></b><span class="stat__l">Coming to you</span></span></a>
    </div>

    <div class="section-title"><h2>Quick actions</h2></div>
    <div class="grid grid-2">
        <a class="tile" href="open_jobs.php" data-reveal style="--i:0">
            <span class="tile__icon"><?= nu_icon('megaphone') ?></span>
            <span><span class="tile__title" style="display:block">Open jobs near you</span><span class="tile__text">Accept at the client’s price or send a counter-bid.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
        <a class="tile" href="provider_dashboard.php#today-list" data-reveal style="--i:1">
            <span class="tile__icon"><?= nu_icon('camera') ?></span>
            <span><span class="tile__title" style="display:block">Submit a completion report</span><span class="tile__text">Add before and after photos so the client can approve and release payment.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
        <a class="tile" href="provider_dashboard.php#earnings" data-reveal style="--i:2">
            <span class="tile__icon tile__icon--navy"><?= nu_icon('wallet2') ?></span>
            <span><span class="tile__title" style="display:block">Earnings and payouts</span><span class="tile__text">See your payout after Nuahn’s commission on each job.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
        <a class="tile" href="contractor.php?id=<?= (int) $uid ?>" data-reveal style="--i:3">
            <span class="tile__icon tile__icon--soft"><?= nu_icon('person-vcard') ?></span>
            <span><span class="tile__title" style="display:block">Your public profile</span><span class="tile__text">See what clients see: badges, rating and reviews.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
    </div>

    <div class="section-title"><h2>Open jobs near you</h2><a class="btn btn-ghost btn-sm" href="open_jobs.php">See all <?= nu_icon('arrow-right') ?></a></div>
    <div class="list">
        <?php foreach (array_slice($board, 0, 3) as $n => $job): ?>
        <a class="list-row" href="open_jobs.php" data-reveal style="--i:<?= $n ?>">
            <span class="list-row__icon"><?= nu_icon($job['service'] === 'snow-removal' ? 'snow' : 'wrench-adjustable') ?></span>
            <span class="list-row__main">
                <span class="list-row__title" style="display:block;color:var(--ink)"><?= nu_e($job['title']) ?></span>
                <span class="list-row__sub" style="display:block"><?= nu_e($job['area']) ?> · <?= $job['km'] !== null ? nu_e(number_format((float) $job['km'], 1)) . ' km' : 'distance n/a' ?> · <?= nu_e(mk_when($job)) ?></span>
            </span>
            <span class="list-row__side"><strong style="color:var(--ink)"><?= $job['price'] > 0 ? '$' . (int) $job['price'] : 'Quote' ?></strong><?= nu_icon('chevron-right', 'muted') ?></span>
        </a>
        <?php endforeach; ?>
        <?php if (!$board): nu_empty('megaphone', 'No open jobs right now', 'New tasks appear here as clients post them.'); endif; ?>
    </div>
</main>

<?php include "../includes/footer.php"; ?>
