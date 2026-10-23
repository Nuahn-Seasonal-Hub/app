<?php
// public/provider_home.php: contractor "My jobs" home (sample data from fixtures/provider_day.php and fixtures/marketplace.php)
require_once("../config/init.php");

// Only providers can view this page
requireLogin('provider');
include_once("../includes/header.php");

$d = nu_fixture('provider_day'); $mk = nu_fixture('marketplace'); $ctr = nu_contractor($d['contractor']); $first = explode(' ', $ctr['name'])[0];
$hour = (int) date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
?>

<section class="band">
  <div class="wrap band__row">
    <div class="welcome enter">
      <span class="avatar"><?= nu_e(nu_initials($ctr['name'])) ?></span>
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
<?php $rq = nu_fixture('queue'); $nextStop = null; foreach ($rq['stops'] as $st) { if ($st['status'] !== 'completed') { $nextStop = $st; break; } } ?>
    <a class="card nb" href="provider_route.php" style="display:flex;gap:var(--s4);align-items:center;margin-bottom:var(--s5);color:var(--ink)" data-reveal>
      <span class="nb__icon"><?= nu_icon('signpost-split') ?></span>
      <span style="flex:1;min-width:0"><span class="nb__title" style="display:block">Today’s route · <?= count($rq['stops']) ?> stops</span>
      <span class="nb__when" style="display:block"><?= $nextStop ? 'Now: ' . nu_e($nextStop['address']) . ' · ' . nu_e($nextStop['window']) : 'All done' ?></span>
      <span class="nb__team" style="display:block"><?= nu_e($rq['date']) ?> · <?= nu_sample_tag('Sample') ?></span></span>
      <span class="btn btn-primary btn-sm">Open route</span>
    </a>

    <div class="grid grid-stats">
        <?php $sh = ['open_jobs.php', 'provider_dashboard.php#today-list', 'provider_dashboard.php#job']; $si = ['megaphone', 'clipboard-check-fill', 'hourglass-split']; foreach ($d['stats'] as $i => $st): ?>
        <a class="stat" href="<?= nu_e($sh[$i]) ?>" data-reveal style="--i:<?= $i ?>;color:inherit"><span class="stat__icon<?= $i === 1 ? ' stat__icon--success' : ($i === 2 ? ' stat__icon--navy' : '') ?>"><?= nu_icon($si[$i]) ?></span><div><div class="stat__label"><?= nu_e($st[2]) ?></div><div class="stat__value"><?= (int) $st[1] ?></div></div></a>
        <?php endforeach; ?>
    </div>

    <div class="section-title"><h2>Quick actions</h2></div>
    <div class="grid grid-2">
        <a class="tile" href="open_jobs.php" data-reveal style="--i:0">
            <span class="tile__icon"><?= nu_icon('megaphone') ?></span>
            <span><span class="tile__title" style="display:block">Open jobs near you</span><span class="tile__text">Accept at the client’s price or send a counter-bid.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
        <a class="tile" href="provider_dashboard.php#job" data-reveal style="--i:1">
            <span class="tile__icon"><?= nu_icon('camera') ?></span>
            <span><span class="tile__title" style="display:block">Submit a completion report</span><span class="tile__text">Add before and after photos so the client can approve and release payment.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
        <a class="tile" href="provider_dashboard.php#earnings" data-reveal style="--i:2">
            <span class="tile__icon tile__icon--navy"><?= nu_icon('wallet2') ?></span>
            <span><span class="tile__title" style="display:block">Earnings and payouts</span><span class="tile__text">See your payout after Nuahn’s commission on each job.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
        <a class="tile" href="contractor.php?id=<?= nu_e($ctr['id']) ?>" data-reveal style="--i:3">
            <span class="tile__icon tile__icon--soft"><?= nu_icon('person-vcard') ?></span>
            <span><span class="tile__title" style="display:block">Your public profile</span><span class="tile__text">See what clients see: badges, rating and response time.</span></span>
            <span class="tile__chev"><?= nu_icon('chevron-right') ?></span>
        </a>
    </div>

    <div class="section-title"><h2>Open jobs near you</h2><a class="btn btn-ghost btn-sm" href="open_jobs.php">See all <?= nu_icon('arrow-right') ?></a></div>
    <div class="list">
        <?php foreach (array_slice($mk['open_jobs'], 0, 3) as $n => $job): ?>
        <a class="list-row" href="open_jobs.php" data-reveal style="--i:<?= $n ?>">
            <span class="list-row__icon"><?= nu_icon($job['svc'] === 'snow-removal' ? 'snow' : 'wrench-adjustable') ?></span>
            <span class="list-row__main">
                <span class="list-row__title" style="display:block;color:var(--ink)"><?= nu_e($job['title']) ?></span>
                <span class="list-row__sub" style="display:block"><?= nu_e($job['area']) ?> · <?= nu_e($job['dist']) ?> · <?= nu_e($job['when']) ?></span>
            </span>
            <span class="list-row__side"><strong style="color:var(--ink)"><?= $job['budget'] ? '$' . (int) $job['budget'] : 'Quote' ?></strong><?= nu_icon('chevron-right', 'muted') ?></span>
        </a>
        <?php endforeach; ?>
    </div>
    <p class="section-note"><?= nu_sample_tag() ?></p>
</main>

<?php include "../includes/footer.php"; ?>
