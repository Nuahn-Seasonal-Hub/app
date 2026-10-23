<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/open_jobs.php: contractor-side "Open jobs near you" board. Accept at the client's price, or counter-bid with your own.
// Roadside requests (mobile mechanics Pathway A) are broadcast to nearby approved technicians: the first to accept wins.
// SAMPLE DATA: fixtures/marketplace.php ['open_jobs']. Cards and behaviour: nu_open_job_card() and public/assets/jobs.js.
// Sir Paul: GET /api/contractors/me/open-jobs?radius=&service= (filter by approval status, service, distance; live updates by poll or push),
//           POST /api/jobs/{id}/accept, POST /api/tasks/{id}/offers {price, eta, note}. Show only to contractors approved in the vetting queue.
require_once("../config/init.php");
requireLogin('provider');
$m = nu_fixture('marketplace');
$jobs = $m['open_jobs'];
$f = in_array($_GET['f'] ?? 'all', ['all', 'snow-removal', 'mobile-mechanics', 'roadside'], true) ? ($_GET['f'] ?? 'all') : 'all';
$filters = ['all' => 'All', 'snow-removal' => 'Snow removal', 'mobile-mechanics' => 'Mechanics', 'roadside' => 'Roadside now'];
$show = array_values(array_filter($jobs, function ($j) use ($f) { return $f === 'all' || ($f === 'roadside' ? !empty($j['roadside']) : $j['svc'] === $f); }));
$ctr = nu_contractor('c-1001');
include_once("../includes/header.php");
?>
<div class="console">
  <?= nu_console_nav(nu_provider_nav(), 'open', 'Contractor workspace', 'Choose the jobs<br>that suit<br>your day.') ?>
  <main class="console__main oj-page" id="open-jobs">
    <header class="pd-head">
      <div><h1>Open jobs near you</h1><p>Accept at the client’s price or send your own. Verified clients, funds held before you start.</p></div>
      <?= nu_sample_tag() ?>
    </header>

    <div class="oj-bar">
      <nav class="oj-filters" aria-label="Filter jobs"><?php foreach ($filters as $k => $l): ?><a href="?f=<?= nu_e($k) ?>"<?= $k === $f ? ' aria-current="true"' : '' ?>><?= nu_e($l) ?></a><?php endforeach; ?></nav>
      <label class="oj-rad"><span class="sr-only">Distance</span><select class="input" aria-label="Distance"><option>Within 10 km</option><option>Within 25 km</option><option>Within 50 km</option></select></label>
      <label class="switch oj-on"><input type="checkbox" checked data-online><span class="switch__t"></span><span data-online-l>Online</span></label>
    </div>

    <p class="oj-info"><?= nu_icon('shield-check') ?><span>You are approved for <b>snow removal</b> and <b>mobile mechanics</b>. Payments are held by a secure processor and paid out after the client approves your photos; Nuahn keeps a <b><?= (int) $m['commission_pct'] ?>%</b> commission <?= nu_sample_tag('Sample rate') ?></span></p>

    <div class="oj-list" id="oj-list">
<?php foreach ($show as $j) echo nu_open_job_card($j); ?>
    </div>
    <div class="oj-empty" id="oj-empty" <?= $show ? 'hidden' : '' ?>>
      <?php nu_empty('megaphone', 'No jobs match this filter', 'New tasks appear here as clients post them. Try another filter or a wider distance.', '<a class="btn btn-primary" href="?f=all">Show all jobs</a>'); ?>
    </div>
    <p class="oj-foot"><a href="<?= nu_e(nu_url('public/provider_dashboard.php')) ?>#earnings"><?= nu_icon('wallet2') ?> See what you would earn</a></p>
  </main>
</div>
<script src="<?= nu_e(nu_asset('public/assets/jobs.js')) ?>" defer></script>
<script>
document.addEventListener('oj:skip', function () { var l = document.getElementById('oj-list'); if (!l.querySelector('[data-oj]:not([hidden])')) document.getElementById('oj-empty').hidden = false; });
(function () { var s = document.querySelector('[data-online]'), l = document.querySelector('[data-online-l]'), list = document.getElementById('oj-list');
  s.addEventListener('change', function () { l.textContent = s.checked ? 'Online' : 'Offline · not receiving jobs'; list.classList.toggle('is-off', !s.checked); }); })();
</script>
<?php include_once("../includes/footer.php"); ?>
