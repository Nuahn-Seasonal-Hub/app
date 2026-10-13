<?php
// public/track.php — "Track your service" (client design v2).
// UI ONLY: status, ETA, route, timeline and proof-of-work photos are SAMPLE DATA from
// fixtures/tracking.php and fixtures/proof_of_work.php. The vehicle marker animates along the sample route.
// Sir Paul: GET /api/bookings/{id}/tracking (live), POST /api/bookings/{id}/confirm (escrow release).
require_once("../config/init.php");
requireLogin('client');

$trk = nu_fixture('tracking');
$pow = nu_fixture('proof_of_work');
$sv  = nu_fixture('services')[$trk['service']];
$state = (string) ($_GET['state'] ?? '');
$released = $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === '1';
if ($released) { $state = 'completed'; }
$completed = $state === 'completed';
if ($completed) {
    foreach ($trk['timeline'] as &$t) { $t['done'] = true; $t['current'] = false; }
    unset($t);
    $trk['timeline'][3]['time'] = $pow['before']['time'];
    $trk['timeline'][4]['time'] = $pow['after']['time'];
    $trk['timeline'][4]['current'] = true;
}
$esc = $pow['escrow'];
include_once("../includes/header.php");
?>
<main class="screen screen--track">
  <div class="trk-top">
    <h1 class="serif"><?= nu_e($sv['name']) ?></h1>
    <span class="pill-status<?= $completed ? ' pill-status--arrived' : '' ?>" data-pill><?= $completed ? 'Completed' : 'On the way' ?></span>
  </div>
  <p class="muted" style="margin:-6px 0 var(--s3)">Booking <?= nu_e($trk['booking']) ?> · <?= nu_sample_tag('Sample tracking data') ?></p>

  <div class="trk-grid">
    <div>
      <div class="arrive" data-arrive role="status">
        <?= nu_icon('house-check') ?>
        <div><b>Arrived in your driveway</b><span><?= nu_e($trk['team']['name']) ?> is here and starting your <?= nu_e(strtolower($sv['short'])) ?>.</span></div>
      </div>
<?php if (!$completed): ?>
      <div class="eta" data-eta>
        <span class="eta__ic"><?= nu_icon('clock') ?></span>
        <div><div class="eta__l">Estimated arrival</div><div class="eta__v"><?= nu_e($trk['eta']) ?></div></div>
        <div class="eta__min"><span data-eta-min><?= (int) $trk['eta_min'] ?></span> min</div>
      </div>
<?php endif; ?>
      <div class="trk-map"><div id="trk-map" data-route="<?= nu_e(json_encode($trk['route'])) ?>" data-home="<?= nu_e(json_encode($trk['home'])) ?>"<?= $completed ? ' data-static="1"' : '' ?> role="img" aria-label="Live map showing the team’s vehicle on the way to your home"></div></div>
      <section class="panel2 team">
        <span class="avatar">TA</span>
        <div><div class="team__name"><?= nu_e($trk['team']['name']) ?></div><div class="team__sub"><?= nu_e($trk['team']['members']) ?> · ★ <?= nu_e($trk['team']['rating']) ?><br><?= nu_e($trk['team']['vehicle']) ?></div></div>
        <a class="btn btn-outline btn-sm" href="<?= nu_e(nu_url('public/chat.php')) ?>?thread=t1"><?= nu_icon('chat-dots') ?> Message team</a>
      </section>
    </div>

    <div>
      <section class="panel2">
        <h2 class="panel2__h"><?= nu_icon('list-check') ?> Status</h2>
        <ol class="tl" data-tl>
<?php foreach ($trk['timeline'] as $t): ?>
          <li class="<?= $t['done'] ? 'is-done' : '' ?><?= !empty($t['current']) ? ' is-current' : '' ?>" data-step="<?= nu_e($t['key']) ?>">
            <span class="tl__dot"><?= $t['done'] ? nu_icon('check-lg') : '' ?></span>
            <span class="tl__t"><?= nu_e($t['label']) ?><?php if (!empty($t['note'])): ?><span class="tl__note"><?= nu_e($t['note']) ?></span><?php endif; ?></span>
            <span class="tl__time" data-time><?= nu_e($t['time'] ?: '—') ?></span>
          </li>
<?php endforeach; ?>
        </ol>
      </section>

      <section class="panel2" id="work">
        <h2 class="panel2__h"><?= nu_icon('camera') ?> Completed work</h2>
        <div class="ba">
          <figure><?= nu_photo($pow['before']['photo'], $pow['before']['alt'], ['sizes' => '(max-width: 600px) 45vw, 220px']) ?><figcaption>Before <small><?= nu_e($pow['before']['time']) ?></small></figcaption></figure>
          <figure><?= nu_photo($pow['after']['photo'], $pow['after']['alt'], ['sizes' => '(max-width: 600px) 45vw, 220px']) ?><figcaption>After <small><?= nu_e($pow['after']['time']) ?></small></figcaption></figure>
        </div>
        <div class="escrow">
          <div class="escrow__row"><span>Service</span><b><?= nu_e($esc['currency']) ?> <?= number_format($esc['amount'], 2) ?></b></div>
          <div class="escrow__row"><span>GST (5%)</span><b><?= nu_e($esc['currency']) ?> <?= number_format($esc['tax'], 2) ?></b></div>
          <div class="escrow__row"><span>Held in escrow</span><b><?= nu_e($esc['currency']) ?> <?= number_format($esc['amount'] + $esc['tax'], 2) ?></b></div>
          <p class="escrow__lock"><?= nu_icon('shield-lock') ?> Your payment is held securely and only released to the team after you confirm the work looks right.</p>
<?php if ($released): ?>
          <div class="alert alert-success"><?= nu_icon('check-circle-fill') ?> Payment released to <?= nu_e($trk['team']['name']) ?>. Thanks for confirming! (Preview: nothing was charged.)</div>
<?php else: ?>
          <form method="post" action="?state=completed">
            <input type="hidden" name="confirm" value="1">
            <button class="btn btn-primary btn-block btn-lg" type="submit"<?= $completed ? '' : ' disabled data-escrow-btn' ?>><?= nu_icon('lock-fill') ?> Confirm work &amp; release payment</button>
          </form>
          <p class="hint text-center" data-escrow-hint><?= $completed ? 'Not right? <a href="' . nu_e(nu_url('public/chat.php')) . '?thread=t1">Message the team</a> before releasing.' : 'Available once the team marks the job complete.' ?></p>
<?php endif; ?>
        </div>
      </section>
    </div>
  </div>
  <p class="text-center mt-4"><a class="btn btn-ghost btn-sm" href="?state=<?= $completed ? '' : 'completed' ?>"><?= nu_icon('arrow-repeat') ?> Preview <?= $completed ? '“on the way”' : '“completed”' ?> state</a></p>
</main>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var el = document.getElementById('trk-map'); if (!el || !window.L) return;
  var route = JSON.parse(el.dataset.route), home = JSON.parse(el.dataset.home);
  var map = L.map(el, { zoomControl: false, attributionControl: true, scrollWheelZoom: false }).fitBounds(route, { padding: [36, 36] });
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(map);
  L.polyline(route, { color: '#11706D', weight: 5, opacity: .85, lineCap: 'round' }).addTo(map);
  var hIc = L.divIcon({ className: '', html: '<div class="home-marker"><svg viewBox="0 0 16 16"><path d="M8.7 1.5a1 1 0 0 0-1.4 0L.6 8.1a.5.5 0 0 0 .7.7L2 8.2v5.3A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.2l.6.6a.5.5 0 0 0 .7-.7z"/></svg></div>', iconSize: [30, 30], iconAnchor: [15, 30] });
  L.marker(home, { icon: hIc, keyboard: false }).addTo(map);
  var vIc = L.divIcon({ className: '', html: '<div class="veh-marker"><svg viewBox="0 0 16 16"><path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V5h1.02a1.5 1.5 0 0 1 1.17.56l1.48 1.85a1.5 1.5 0 0 1 .33.94V10.5a1.5 1.5 0 0 1-1.5 1.5H14a2 2 0 1 1-4 0H5a2 2 0 1 1-4 0 1 1 0 0 1-1-1zm1.29 7.25a2 2 0 0 1 3.42 0h5.58a2 2 0 0 1 2.71-.73V3.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .29.25zM12 10a2 2 0 0 1 1.73 1h.77a.5.5 0 0 0 .5-.5V8h-3z"/></svg></div>', iconSize: [38, 38], iconAnchor: [19, 19] });
  if (el.dataset.static) { return; }
  var car = L.marker(route[0], { icon: vIc, keyboard: false, zIndexOffset: 500 }).addTo(map);
  // cumulative segment lengths for constant-speed interpolation
  var seg = [], total = 0;
  for (var i = 1; i < route.length; i++) { var d = map.distance(route[i - 1], route[i]); seg.push(d); total += d; }
  var dur = 22000, t0 = null, minEl = document.querySelector('[data-eta-min]'), startMin = minEl ? +minEl.textContent : 12;
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  function at(f) {
    var want = f * total, acc = 0;
    for (var i = 0; i < seg.length; i++) {
      if (acc + seg[i] >= want) { var k = seg[i] ? (want - acc) / seg[i] : 0; var a = route[i], b = route[i + 1]; return [a[0] + (b[0] - a[0]) * k, a[1] + (b[1] - a[1]) * k]; }
      acc += seg[i];
    }
    return route[route.length - 1];
  }
  function arrived() {
    document.querySelector('[data-arrive]').classList.add('is-on');
    var pill = document.querySelector('[data-pill]'); pill.textContent = 'Arrived'; pill.classList.add('pill-status--arrived');
    var eta = document.querySelector('[data-eta]'); if (eta) eta.style.display = 'none';
    var cur = document.querySelector('[data-step="on_the_way"]'), nxt = document.querySelector('[data-step="in_progress"]');
    if (cur) cur.classList.remove('is-current');
    if (nxt) { nxt.classList.add('is-current'); nxt.querySelector('[data-time]').textContent = 'Now'; }
  }
  function frame(ts) {
    if (t0 === null) t0 = ts;
    var f = Math.min(1, (ts - t0) / dur);
    car.setLatLng(at(f));
    if (minEl) minEl.textContent = Math.max(0, Math.ceil(startMin * (1 - f)));
    if (f < 1) requestAnimationFrame(frame); else arrived();
  }
  if (reduce) { car.setLatLng(at(.6)); } else { setTimeout(function () { requestAnimationFrame(frame); }, 600); }
});
</script>
<?php include_once("../includes/footer.php"); ?>
