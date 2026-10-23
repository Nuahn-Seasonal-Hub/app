<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/mechanics.php: Mobile Mechanics, two pathways. Pathway A (roadside, urgent): six categories, the request is broadcast to nearby
// approved technicians, no scheduling. Pathway B (scheduled repair): six services, scoped and quoted, approved before work.
// Selecting a service opens its own form (mechanic_request.php). Manual address works without device location permission.
// UI ONLY: map is an illustrative inline SVG; availability is SAMPLE DATA from fixtures/mechanic.php.
// Sir Paul: GET /api/services/mobile-mechanics/categories?lat=&lng= (availability per location), geocoding for the address box.
require_once("../config/init.php");

$mech = nu_fixture('mechanic');
$req = nu_url('public/mechanic_request.php');
include_once("../includes/header.php");
?>
<main class="mx" id="mechanics">
  <div class="mx-app">
    <h1 class="mx-title">Mobile Mechanics</h1>
    <nav class="mx-paths" aria-label="Choose a pathway">
      <a class="mx-path mx-path--a" href="#roadside"><span class="mx-path__ic"><?= nu_icon('broadcast') ?></span><span><b>Roadside help now</b><small>Broadcast to nearby approved technicians. No scheduling.</small></span><?= nu_icon('chevron-right') ?></a>
      <a class="mx-path mx-path--b" href="#services"><span class="mx-path__ic"><?= nu_icon('calendar2-check') ?></span><span><b>Scheduled repair</b><small>Describe the job, get an itemised quote, approve, book.</small></span><?= nu_icon('chevron-right') ?></a>
    </nav>
    <section class="mx-map">
      <svg class="mx-map__svg" viewBox="0 0 400 210" role="img" aria-label="Map of Edmonton with your service location pinned" preserveAspectRatio="xMidYMid slice">
        <rect width="400" height="210" fill="var(--map-bg)"/>
        <path d="M0 40 C 60 30, 90 70, 140 60 S 220 20, 270 40 S 360 70, 400 50 V0 H0Z" fill="var(--map-park)" opacity=".6"/>
        <path d="M-10 150 C 60 120, 120 170, 190 132 S 300 110, 410 140" fill="none" stroke="var(--map-water)" stroke-width="10" stroke-linecap="round"/>
        <g stroke="var(--map-road)" fill="none" stroke-linecap="round"><path stroke-width="6" d="M0 96 H400 M200 0 V210"/><path stroke-width="3" d="M40 0 C 60 80, 60 140, 40 210 M360 0 C 340 80, 340 140, 360 210 M0 30 H400 M0 180 H400 M110 0 V210 M290 0 V210"/></g>
        <g font-family="system-ui, sans-serif" font-size="9" fill="var(--muted)" font-weight="600" letter-spacing=".08em"><text x="178" y="22">NORTH EDMONTON</text><text x="16" y="120">WEST EDMONTON</text><text x="316" y="122">Sherwood Park</text><text x="170" y="200">SOUTH EDMONTON</text></g>
        <rect x="16" y="20" width="18" height="14" rx="3" fill="#fff" stroke="var(--line)"/><text x="20" y="31" font-size="8" fill="var(--ink-2)">16</text>
        <g transform="translate(200 88)"><circle r="18" fill="var(--teal)" opacity=".15"/><path d="M0 8 C -8 -2, -11 -6, -11 -11 a11 11 0 0 1 22 0 c0 5 -3 9 -11 19z" fill="var(--pine)" transform="translate(0 -6)"/><circle cy="-17" r="4" fill="#fff"/></g>
        <text x="200" y="118" text-anchor="middle" font-family="system-ui, sans-serif" font-size="13" font-weight="700" fill="var(--ink)">Edmonton</text>
      </svg>
      <button class="mx-loc" type="button" data-locate><?= nu_icon('crosshair') ?> Use my location</button>
      <form class="mx-confirm" data-loc-form>
        <span class="mx-confirm__ic"><?= nu_icon('geo-alt-fill') ?></span>
        <label class="sr-only" for="mx-addr">Service location</label>
        <input class="mx-confirm__in" id="mx-addr" value="Service location · <?= nu_e($mech['location']['value']) ?>" autocomplete="street-address">
        <button class="btn btn-primary btn-sm" type="submit" data-loc-btn>Confirm service location</button>
      </form>
    </section>
    <p class="mx-status" data-loc-status role="status" hidden><?= nu_icon('check-circle-fill') ?> Location confirmed. Showing services with mechanics covering this area.</p>

    <section class="mx-sec" id="roadside">
      <h2><span class="mx-pill mx-pill--a">A</span> Need roadside help?</h2>
      <div class="mx-road">
<?php foreach ($mech['roadside'] as $k => $r): ?>
        <a class="mx-road__t" href="<?= nu_e($req . '?path=roadside&svc=' . $k) ?>"><span class="mx-road__ic mx-tone--<?= nu_e($r['tone']) ?>"><?= nu_icon($r['icon']) ?></span><span><?= nu_e($r['name']) ?><small><?= nu_e($mech['roadside_est'][$k]) ?></small></span></a>
<?php endforeach; ?>
      </div>
      <p class="mx-hint"><?= nu_icon('info-circle') ?> Your request goes out to approved technicians near you at once. The first to accept confirms a price and an ETA; funds are held until the job is done. <?= nu_sample_tag('Sample estimates') ?></p>
    </section>

    <section class="mx-sec" id="services">
      <h2><span class="mx-pill">B</span> Schedule a repair</h2>
      <div class="mx-svc">
<?php foreach ($mech['maintenance'] as $k => $s): ?>
        <a class="mx-svc__t<?= !empty($s['selected']) ? ' is-sel' : '' ?>" href="<?= nu_e($req . '?path=repair&svc=' . $k) ?>"><span class="mx-svc__ic"><?= nu_icon($s['icon']) ?></span><span><?= nu_e($s['name']) ?></span><?= !empty($s['selected']) ? '<em>Selected</em>' : '' ?></a>
<?php endforeach; ?>
      </div>
      <p class="mx-hint"><?= nu_icon('file-text') ?> Repairs are quoted first: parts, labour, call-out fee and tax are itemised and you approve before any work or payment hold. <?= nu_sample_tag('Availability varies by location') ?></p>
    </section>
  </div>
  <aside class="cp-rules mx-rules">
    <h3>How roadside works</h3><p>Pick the problem, confirm where you are and send. Nearby approved technicians see it instantly; the first to accept shows their price and ETA. No appointment needed.</p>
    <h3>How scheduled repair works</h3><p>Describe the vehicle and symptoms, add photos if you like, and mechanics send itemised quotes. Approve one, choose a window and funds are held.</p>
    <h3>Extra work on site</h3><p>If the mechanic finds more work, they pause and send a revised quote. Nothing extra happens until you approve it.</p>
    <h3>Safe payment</h3><p>Funds are held securely until you approve the finished work. Card details never touch Nuahn.</p>
  </aside>
</main>
<script>
(function () {
  var f = document.querySelector('[data-loc-form]'); if (!f) return;
  var st = document.querySelector('[data-loc-status]');
  f.addEventListener('submit', function (e) { e.preventDefault(); st.hidden = false; f.querySelector('[data-loc-btn]').textContent = 'Location confirmed'; });
  document.querySelector('[data-locate]').addEventListener('click', function () {
    var i = document.getElementById('mx-addr'); i.focus(); i.select();
    st.hidden = false; st.lastChild.textContent = ' Device location isn’t used in this preview. Type an address or pick a pin instead.';
  });
})();
</script>
<?php include_once("../includes/footer.php"); ?>
