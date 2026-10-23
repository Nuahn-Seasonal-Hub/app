<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/mechanics.php: Mobile Mechanics, two pathways. Pathway A (roadside, urgent): six categories, the request is broadcast to nearby
// approved technicians, no scheduling. Pathway B (scheduled repair): six services, scoped and quoted, approved before work.
// Selecting a service opens its own form (mechanic_request.php). Manual address works without device location permission.
// Live: the category menu is static config (fixtures/mechanic.php); the technician count comes from approved contractor profiles.
// The service location is captured in the request form itself (map picker); a geocoding key is pending (docs/BOOKING-BACKEND.md).
require_once("../config/init.php");
require_once("../includes/marketplace/view.php");

$mech = nu_fixture('mechanic');
$req = nu_url('public/mechanic_request.php');
$techCount = 0;
if (mk_ready()) {
    try { $techCount = (int) mk_db()->query("SELECT COUNT(*) FROM contractor_profiles WHERE vetting_status = 'approved' AND FIND_IN_SET('mobile-mechanics', trades)")->fetchColumn(); } catch (Throwable $e) { $techCount = 0; }
}
include_once("../includes/header.php");
?>
<main class="mx" id="mechanics">
  <div class="mx-app">
    <h1 class="mx-title">Mobile Mechanics</h1>
    <nav class="mx-paths" aria-label="Choose a pathway">
      <a class="mx-path mx-path--a" href="#roadside"><span class="mx-path__ic"><?= nu_icon('broadcast') ?></span><span><b>Roadside help now</b><small>Broadcast to nearby approved technicians. No scheduling.</small></span><?= nu_icon('chevron-right') ?></a>
      <a class="mx-path mx-path--b" href="#services"><span class="mx-path__ic"><?= nu_icon('calendar2-check') ?></span><span><b>Scheduled repair</b><small>Describe the job, get an itemised quote, approve, book.</small></span><?= nu_icon('chevron-right') ?></a>
    </nav>
    <p class="mx-status" role="status"><?= nu_icon('check-circle-fill') ?> <?= $techCount ? '<b>' . $techCount . '</b> approved technician' . ($techCount === 1 ? '' : 's') . ' on Nuahn. ' : '' ?>Pick a service below, then confirm your address on the request form.</p>

    <section class="mx-sec" id="roadside">
      <h2><span class="mx-pill mx-pill--a">A</span> Need roadside help?</h2>
      <div class="mx-road">
<?php foreach ($mech['roadside'] as $k => $r): ?>
        <a class="mx-road__t" href="<?= nu_e($req . '?path=roadside&svc=' . $k) ?>"><span class="mx-road__ic mx-tone--<?= nu_e($r['tone']) ?>"><?= nu_icon($r['icon']) ?></span><span><?= nu_e($r['name']) ?><small><?= nu_e($mech['roadside_est'][$k]) ?></small></span></a>
<?php endforeach; ?>
      </div>
      <p class="mx-hint"><?= nu_icon('info-circle') ?> Your request goes out to approved technicians near you at once. The first to accept confirms a price and an ETA; funds are held until the job is done.</p>
    </section>

    <section class="mx-sec" id="services">
      <h2><span class="mx-pill">B</span> Schedule a repair</h2>
      <div class="mx-svc">
<?php foreach ($mech['maintenance'] as $k => $s): ?>
        <a class="mx-svc__t<?= !empty($s['selected']) ? ' is-sel' : '' ?>" href="<?= nu_e($req . '?path=repair&svc=' . $k) ?>"><span class="mx-svc__ic"><?= nu_icon($s['icon']) ?></span><span><?= nu_e($s['name']) ?></span><?= !empty($s['selected']) ? '<em>Selected</em>' : '' ?></a>
<?php endforeach; ?>
      </div>
      <p class="mx-hint"><?= nu_icon('file-text') ?> Repairs are quoted first: parts, labour, call-out fee and tax are itemised and you approve before any work or payment hold.</p>
    </section>
  </div>
  <aside class="cp-rules mx-rules">
    <h3>How roadside works</h3><p>Pick the problem, confirm where you are and send. Nearby approved technicians see it instantly; the first to accept shows their price and ETA. No appointment needed.</p>
    <h3>How scheduled repair works</h3><p>Describe the vehicle and symptoms, add photos if you like, and mechanics send itemised quotes. Approve one, choose a window and funds are held.</p>
    <h3>Extra work on site</h3><p>If the mechanic finds more work, they pause and send a revised quote. Nothing extra happens until you approve it.</p>
    <h3>Safe payment</h3><p>Funds are held securely until you approve the finished work. Card details never touch Nuahn.</p>
  </aside>
</main>
<?php include_once("../includes/footer.php"); ?>
