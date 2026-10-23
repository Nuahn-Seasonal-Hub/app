<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/provider_dashboard.php: contractor workspace ("Today") on live data.
// Open jobs near you (mk_board), your jobs (tasks you hold), offers waiting on the client, earnings with payout and commission breakdown,
// seasonal-plan visits and the online toggle. Job actions (on the way, photos, completion, extra-work quote) live on track.php.
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
$pending = mk_contractor_pending($uid);
$earn = mk_contractor_earnings($uid);
$pct = (int) MK_COMMISSION_PCT;
$plans = array_values(array_filter($mine, function ($j) { return $j['mode'] === 'seasonal'; }));
$u = function ($p) { return nu_url('public/' . $p); };
$hour = (int) date('G'); $greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$vet = $prof['vetting_status'] ?? 'draft';
include_once("../includes/header.php");
?>
<div class="console">
  <?= nu_console_nav(nu_provider_nav(), 'today', 'Provider workspace', 'Seasonal care<br>for a brighter<br>tomorrow.') ?>
  <main class="console__main pd" id="today">
    <header class="pd-head">
      <div><h1><?= $greet ?>, <?= nu_e($first) ?></h1><p>Open jobs near you, your accepted jobs and your earnings.</p>
        <div class="pd-me"><?= $ctr ? nu_badges($ctr) : '' ?><span><?= nu_icon('star-fill') ?> <?= $ctr && $ctr['reviews'] ? nu_e(number_format($ctr['rating'], 1)) : 'New' ?> · <?= (int) ($ctr['jobs'] ?? 0) ?> jobs</span></div></div>
      <div class="pd-clock"><span><?= nu_e(date('D, M j')) ?><b><?= nu_e(date('g:i A')) ?></b></span><?= nu_icon('brightness-high') ?></div>
    </header>
    <?= mk_test_note() ?>
<?php if (!$approved): ?>
    <p class="mk-note mk-note--warn"><?= nu_icon('exclamation-circle') ?> <span>Your application is <b><?= nu_e(str_replace('_', ' ', $vet)) ?></b>. Only approved contractors can see open jobs and send offers. <a href="<?= nu_e($u('contractor_setup.php')) ?>">Open your application</a>.</span></p>
<?php else: ?>
    <?= mk_form('online_toggle', ['online' => !empty($prof['is_online']) ? 0 : 1], 'class="mk-inline"', 'provider_dashboard.php') ?>
      <span class="chip chip--<?= !empty($prof['is_online']) ? 'success' : '' ?> chip--dot"><?= !empty($prof['is_online']) ? 'Online · receiving roadside alerts' : 'Offline' ?></span>
      <button class="btn btn-soft btn-sm" type="submit"><?= !empty($prof['is_online']) ? 'Go offline' : 'Go online' ?></button>
    </form>
<?php endif; ?>
    <div class="pd-stats">
      <a class="pd-stat" href="<?= nu_e($u('open_jobs.php')) ?>"><span class="pd-stat__ic"><?= nu_icon('megaphone') ?></span><span><b><?= count($board) ?></b> <strong>Open jobs</strong><small>near you</small></span><?= nu_icon('chevron-right') ?></a>
      <a class="pd-stat" href="#today-list"><span class="pd-stat__ic"><?= nu_icon('clipboard-check') ?></span><span><b><?= count($mine) ?></b> <strong>Your jobs</strong><small>active</small></span><?= nu_icon('chevron-right') ?></a>
      <a class="pd-stat" href="#earnings"><span class="pd-stat__ic"><?= nu_icon('wallet2') ?></span><span><b>$<?= number_format((float) $earn['sum']['held'] + (float) $earn['sum']['pending'], 0) ?></b> <strong>Coming to you</strong><small>held or awaiting payout</small></span><?= nu_icon('chevron-right') ?></a>
    </div>

    <section class="pd-card pd-open" id="open-jobs" aria-label="Open jobs near you">
      <h2 class="pd-h2"><?= nu_icon('megaphone') ?> Open jobs near you <small>Accept at the client’s price or counter-bid</small></h2>
<?php if ($board): ?>
      <div class="oj-list"><?php foreach (array_slice($board, 0, 3) as $oj) echo nu_open_job_card($oj); ?></div>
      <a class="btn btn-soft btn-sm pd-open__all" href="<?= nu_e($u('open_jobs.php')) ?>">See all <?= count($board) ?> open jobs <?= nu_icon('chevron-right') ?></a>
<?php else: nu_empty('megaphone', 'No open jobs right now', $approved ? 'New tasks appear here as clients post them. Keep your service area and radius up to date.' : 'Open jobs appear here once your application is approved.'); endif; ?>
    </section>

    <div class="pd-grid">
      <section class="pd-card pd-list" id="today-list" aria-label="Your jobs">
        <h2 class="pd-h2">Your jobs <small>Open one to update progress</small></h2>
<?php if ($mine): ?>
        <ul>
<?php foreach ($mine as $job): ?>
          <li><a class="pd-job" href="<?= nu_e($u('track.php')) ?>?id=<?= (int) $job['id'] ?>">
            <span class="pd-job__top"><span><?= nu_e(mk_ref($job)) ?><b><?= nu_e($job['title']) ?></b></span><?= nu_booking_chip($job['status']) ?></span>
            <span class="pd-job__m"><?= nu_icon('clock') ?> <?= nu_e(mk_when($job)) ?></span><span class="pd-job__m"><?= nu_icon('geo-alt') ?> <?= nu_e(mk_area($job['address'])) ?></span><span class="pd-job__m"><?= nu_icon('lock') ?> <?= nu_payment_chip($job['payment_status']) ?></span></a></li>
<?php endforeach; ?>
        </ul>
<?php else: nu_empty('clipboard-check', 'No active jobs', 'Jobs you win appear here, with the client’s funds already held.'); endif; ?>
<?php if ($pending): ?>
        <h3 class="pd-h3">Waiting on the client</h3>
        <ul class="pd-pay">
<?php foreach ($pending as $p): ?>
          <li><span><b><?= nu_e($p['title']) ?></b><small><?= $p['kind'] === 'quote' ? 'Quote' : ($p['kind'] === 'counter' ? 'Counter-offer' : 'Offer') ?> sent</small></span><span class="pd-pay__a"><b>$<?= number_format((float) $p['amount'], 2) ?></b></span></li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
      </section>
      <section class="pd-card" aria-label="How a job runs">
        <h2 class="pd-h2"><?= nu_icon('list-check') ?> How a job runs</h2>
        <ol class="mk-steps">
          <li>Offer or quote. Only approved contractors can respond.</li>
          <li>The client accepts and their payment is held in escrow.</li>
          <li>Mark on the way, then start. Upload at least one <b>before</b> and one <b>after</b> photo.</li>
          <li>Submit the completion report. The client approves, or opens a dispute.</li>
          <li>Approved jobs pay out your share after the <?= $pct ?>% Nuahn commission. If the client does not respond, the job is released automatically after <?= (int) MK_AUTO_RELEASE_HOURS ?> hours.</li>
        </ol>
      </section>
    </div>

    <section class="pd-card pd-earn" id="earnings" aria-label="Earnings">
      <h2 class="pd-h2"><?= nu_icon('wallet2') ?> Earnings <small>Payout and commission breakdown</small></h2>
      <div class="pd-stats">
        <div class="pd-stat"><span><b>$<?= number_format((float) $earn['sum']['held'], 2) ?></b> <strong>Held in escrow</strong><small>awaiting client approval</small></span></div>
        <div class="pd-stat"><span><b>$<?= number_format((float) $earn['sum']['pending'], 2) ?></b> <strong>Approved</strong><small>payout pending</small></span></div>
        <div class="pd-stat"><span><b>$<?= number_format((float) $earn['sum']['paid'], 2) ?></b> <strong>Paid out</strong><small>to date</small></span></div>
      </div>
<?php if ($earn['rows']): ?>
      <table class="pd-tbl mk-tbl"><thead><tr><th>Job</th><th>Job total</th><th>Commission (<?= $pct ?>%)</th><th>Your payout</th><th>Status</th></tr></thead><tbody>
<?php foreach ($earn['rows'] as $r): ?>
        <tr><td><a href="<?= nu_e($u('track.php')) ?>?id=<?= (int) $r['task_id'] ?>"><?= nu_e($r['title']) ?></a></td><td>$<?= number_format((float) $r['subtotal'], 2) ?></td><td>−$<?= number_format((float) $r['commission'], 2) ?></td><td><b>$<?= number_format((float) $r['payout_amount'], 2) ?></b></td><td><?= nu_payment_chip($r['state']) ?><?= $r['payout_status'] === 'paid' ? ' <span class="chip chip--success">Paid</span>' : '' ?></td></tr>
<?php endforeach; ?>
      </tbody></table>
<?php else: nu_empty('wallet2', 'No earnings yet', 'Your first approved job will show here.'); endif; ?>
      <p class="pd-hint"><?= nu_icon('info-circle') ?> Commission and payout are calculated on the pre-tax job amount. Sales tax collected from the client is not part of your payout.</p>
    </section>

<?php if ($plans): ?>
    <section class="pd-card">
      <h2 class="pd-h2"><?= nu_icon('snow') ?> Seasonal-plan visits <small>Clients who chose a snowfall trigger</small></h2>
      <ul class="pd-pay"><?php foreach ($plans as $r): ?><li><span><b><?= nu_e($r['title']) ?></b><small><?= nu_e(mk_area($r['address'])) ?></small></span><span class="pd-pay__a"><?= nu_booking_chip($r['status']) ?></span></li><?php endforeach; ?></ul>
      <p class="pd-hint"><?= nu_icon('shield-check') ?> One visit per snow event; under <?= (int) MK_SEASONAL_TRIGGER_CM ?> cm the visit is skipped.</p>
    </section>
<?php endif; ?>
  </main>
</div>
<?php include_once("../includes/footer.php"); ?>
