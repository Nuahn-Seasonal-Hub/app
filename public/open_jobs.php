<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/open_jobs.php: contractor-side "Open jobs near you" board on live data (includes/marketplace/jobs.php mk_board).
// Only contractors approved in the vetting queue see jobs. Snow jobs: accept at the posted price or counter-bid.
// Roadside requests are broadcast by distance; the first technician to accept wins. Scheduled repairs open the quote builder.
require_once("../config/init.php");
requireLogin('provider');
require_once("../includes/marketplace/view.php");
$uid = mk_uid();
$prof = mk_profile($uid);
$approved = $prof && $prof['vetting_status'] === 'approved';
$f = in_array($_GET['f'] ?? 'all', ['all', 'snow-removal', 'mobile-mechanics', 'roadside'], true) ? ($_GET['f'] ?? 'all') : 'all';
$filters = ['all' => 'All', 'snow-removal' => 'Snow removal', 'mobile-mechanics' => 'Mechanics', 'roadside' => 'Roadside now'];
$jobs = $approved ? mk_board($uid) : [];
$show = array_values(array_filter($jobs, function ($j) use ($f) { return $f === 'all' || ($f === 'roadside' ? $j['mode'] === 'roadside' : $j['service'] === $f); }));
$trades = $prof ? mk_trades($prof) : [];
$tradeNames = array_map(function ($t) { return $t === 'snow-removal' ? 'snow removal' : 'mobile mechanics'; }, $trades);
include_once("../includes/header.php");
?>
<div class="console">
  <?= nu_console_nav(nu_provider_nav(), 'open', 'Contractor workspace', 'Choose the jobs<br>that suit<br>your day.') ?>
  <main class="console__main oj-page" id="open-jobs">
    <header class="pd-head">
      <div><h1>Open jobs near you</h1><p>Accept at the client’s price or send your own. Verified clients, funds held before you start.</p></div>
      <?= mk_test_note() ?>
    </header>

<?php if (!$approved): ?>
    <div class="mk-note mk-note--warn" role="status">
      <?= nu_icon('shield-exclamation') ?>
      <div><b><?= $prof && $prof['vetting_status'] === 'pending' || $prof && $prof['vetting_status'] === 'in_review' ? 'Your application is being reviewed.' : 'Finish your contractor profile to see jobs.' ?></b>
        <p>Only contractors approved by Nuahn can see open jobs, send offers and quotes. <?= $prof ? 'Status: <b>' . nu_e(str_replace('_', ' ', $prof['vetting_status'])) . '</b>.' : '' ?></p>
        <a class="btn btn-primary btn-sm" href="<?= nu_e(nu_url('public/contractor_setup.php')) ?>">Open my application</a></div>
    </div>
<?php else: ?>
    <div class="oj-bar">
      <nav class="oj-filters" aria-label="Filter jobs"><?php foreach ($filters as $k => $l): ?><a href="?f=<?= nu_e($k) ?>"<?= $k === $f ? ' aria-current="true"' : '' ?>><?= nu_e($l) ?></a><?php endforeach; ?></nav>
      <?= mk_form('online_toggle', ['online' => $prof['is_online'] ? '0' : '1'], 'class="oj-on"', 'provider_home.php') ?>
        <button type="submit" class="btn btn-ghost btn-sm"><?= $prof['is_online'] ? nu_icon('check-circle-fill') . ' Online · go offline' : nu_icon('pause-circle') . ' Offline · go online' ?></button></form>
    </div>

    <p class="oj-info"><?= nu_icon('shield-check') ?><span>You are approved for <b><?= nu_e(implode(' and ', $tradeNames)) ?></b> within <b><?= (int) $prof['radius_km'] ?> km</b> of your base. Payments are held by a secure processor and paid out after the client approves your photos; Nuahn keeps a <b><?= (int) MK_COMMISSION_PCT ?>%</b> commission.</span></p>

    <div class="oj-list" id="oj-list">
<?php foreach ($show as $j) echo nu_open_job_card($j); ?>
    </div>
    <div class="oj-empty" id="oj-empty" <?= $show ? 'hidden' : '' ?>>
      <?php nu_empty('megaphone', 'No jobs match this filter', 'New tasks appear here as clients post them. Try another filter or widen your service radius in your profile.', '<a class="btn btn-primary" href="?f=all">Show all jobs</a>'); ?>
    </div>
<?php endif; ?>
    <p class="oj-foot"><a href="<?= nu_e(nu_url('public/provider_dashboard.php')) ?>#earnings"><?= nu_icon('wallet2') ?> See what you have earned</a></p>
  </main>
</div>
<script src="<?= nu_e(nu_asset('public/assets/jobs.js')) ?>" defer></script>
<script>
document.addEventListener('oj:skip', function () { var l = document.getElementById('oj-list'); if (l && !l.querySelector('[data-oj]:not([hidden])')) document.getElementById('oj-empty').hidden = false; });
</script>
<?php include_once("../includes/footer.php"); ?>
