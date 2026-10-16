<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/track.php: "Your service, step by step" (doc1 p3, doc2 p14). One timeline per accepted booking:
// trip map (provider GPS during the accepted trip only), status steps, assigned team, shared conversation,
// local weather and the completion report. Before/after photos and checklist appear only after upload;
// while on the way, placeholders are shown, never invented work. Payment is a separate state (doc2 p21).
// UI ONLY: everything is SAMPLE DATA from fixtures/tracking.php + fixtures/proof_of_work.php. The vehicle marker
// animates along the sample route; the message box only appends locally.
// Sir Paul: GET /api/bookings/{id}/tracking (SSE), GET/POST /api/threads/{booking_id}/messages, GET /api/bookings/{id}/completion.
// Preview states: ?state=stale (location stale), ?state=completed.
require_once("../config/init.php");
requireLogin('client');

$trk = nu_fixture('tracking');
$pow = nu_fixture('proof_of_work');
$sv  = nu_fixture('services')[$trk['service']];
$state = (string) ($_GET['state'] ?? '');
$completed = $state === 'completed';
$stale = $state === 'stale';
$steps = []; $at = 0;
foreach ($trk['timeline'] as $i => $t) { $steps[] = [$t['label'], $t['time']]; if (!empty($t['current'])) $at = $i; }
if ($completed) { $steps[3][1] = '9:21 AM'; $steps[4][1] = $pow['before']['time']; $steps[5][1] = $pow['after']['time']; $at = 5; }
$inv = $trk['invoice'];
include_once("../includes/header.php");
?>
<main class="tk<?= $completed ? " tk--done" : "" ?>" id="track">
  <div class="tk-head">
    <h1>Your service, step by step</h1>
    <p>Real-time updates for your booking. <?= nu_sample_tag('Sample tracking data') ?></p>
  </div>
  <section class="tk-bar">
    <div><b>#<?= nu_e($trk['booking']) ?> · <?= nu_e($sv['name']) ?></b><span><?= nu_icon('geo-alt-fill') ?> <?= nu_e($trk['address']) ?></span></div>
    <div class="tk-bar__win"><?= nu_icon('calendar3') ?><span>Arrival window<b><?= nu_e($trk['window']) ?></b></span></div>
  </section>

  <div class="tk-grid">
    <section class="sv-card tk-live">
      <div class="tk-live__top">
        <div class="tk-eta">
          <span class="tk-pill<?= $completed ? ' is-done' : '' ?>" data-pill><?= nu_icon($completed ? 'check-circle-fill' : 'truck') ?><span><?= $completed ? 'Completed' : 'On the way' ?></span></span>
<?php if ($completed): ?>
          <span class="tk-eta__l">Finished at</span><span class="tk-eta__v"><?= nu_e($pow['after']['time']) ?></span><span class="tk-eta__u">Arrived 9:21 AM</span>
<?php else: ?>
          <span class="tk-eta__l" data-eta-l>Estimated arrival</span>
          <span class="tk-eta__v" data-eta-v><?= nu_e($stale ? $trk['eta_range'] : $trk['eta']) ?></span>
          <span class="tk-eta__u"><?= $stale ? 'Location last updated ' . nu_e($trk['location_updated']) . ' · ETA less precise' : 'Updated ' . nu_e($trk['location_updated']) . ' · <span data-eta-min>' . (int) $trk['eta_min'] . '</span> min away' ?></span>
<?php endif; ?>
          <div class="arrive" data-arrive role="status"><?= nu_icon('house-check') ?><div><b>Arrived</b><span><?= nu_e($trk['team']['name']) ?> is at your home.</span></div></div>
        </div>
        <div class="tk-map<?= $stale ? ' is-stale' : '' ?>">
          <div id="trk-map" data-route="<?= nu_e(json_encode($trk['route'])) ?>" data-home="<?= nu_e(json_encode($trk['home'])) ?>"<?= ($completed || $stale) ? ' data-static="1"' : '' ?><?= $stale ? ' data-stale="1"' : '' ?> role="img" aria-label="Map showing the team’s vehicle on the way to your home"></div>
          <p class="tk-map__note"><?= nu_icon('geo-alt') ?> <?= $completed ? 'Location sharing ended when the trip finished.' : ($stale ? 'Location is stale. Showing last known position.' : 'Location sharing active for this trip.') ?></p>
        </div>
      </div>
      <?= nu_steps($steps, $at, 'tk-steps') ?>
    </section>

    <div class="tk-col">
      <section class="sv-card tk-team">
        <h2 class="tk-h">Your team</h2>
        <div class="tk-team__row"><span class="tk-team__ic"><?= nu_icon('people-fill') ?></span><div><b><?= nu_e($trk['team']['name']) ?></b><span><?= nu_e($trk['team']['note']) ?></span></div>
        <a class="btn btn-outline btn-sm" href="<?= nu_e(nu_url('public/chat.php')) ?>?thread=t1"><?= nu_icon('chat-dots') ?> Message team</a></div>
      </section>
      <section class="sv-card tk-msgs" aria-label="Messages">
        <div class="tk-msgs__top"><h2 class="tk-h">Messages</h2><span>Today, Oct 16</span></div>
        <ol class="tk-chat" data-chat>
<?php foreach ($trk['messages'] as $m): ?>
          <li class="tk-msg tk-msg--<?= $m['from'] ?>"><span class="tk-msg__av"><?= nu_icon($m['from'] === 'me' ? 'person-fill' : 'tree-fill') ?></span><div><small><?= nu_e($m['name']) ?> · <?= nu_e($m['time']) ?></small><p><?= nu_e($m['text']) ?></p></div></li>
<?php endforeach; ?>
        </ol>
        <form class="tk-send" data-send><label class="sr-only" for="tk-msg">Write a message</label><input class="input" id="tk-msg" placeholder="Write a message…" autocomplete="off"><button class="btn btn-primary btn-sm" type="submit"><?= nu_icon('send-fill') ?> Send</button></form>
      </section>
    </div>

    <section class="sv-card tk-wx">
      <h2 class="tk-h">Local weather</h2>
      <div class="tk-wx__row"><?= nu_icon('cloud-snow') ?><div><b><?= nu_e($trk['weather']['temp']) ?> · <?= nu_e($trk['weather']['text']) ?></b><span><?= nu_e($trk['weather']['note']) ?></span></div></div>
      <svg class="tk-wx__art" viewBox="0 0 240 90" aria-hidden="true"><path d="M0 90 C 60 62, 120 70, 240 56 V90Z" fill="#fff" opacity=".9"/><g fill="#2f6f63"><path d="M150 70 l10 -30 l10 30z"/><path d="M168 72 l13 -40 l13 40z"/><path d="M196 66 l9 -26 l9 26z"/><path d="M40 74 l8 -22 l8 22z"/></g><path d="M80 74 v-16 l16 -12 l16 12 v16z" fill="#cfd9dc"/><rect x="92" y="62" width="8" height="12" fill="#7a8a90"/></svg>
    </section>

    <section class="sv-card tk-rep" id="work">
      <div class="tk-rep__top"><h2 class="tk-h"><?= nu_icon('images') ?> Completion report</h2><a class="btn btn-outline btn-sm" href="<?= nu_e(nu_url('public/chat.php')) ?>?thread=support"><?= nu_icon('headset') ?> Contact support</a></div>
<?php if ($completed): ?>
      <div class="tk-rep__body">
        <div class="ba">
          <figure><?= nu_photo($pow['before']['photo'], $pow['before']['alt'], ['sizes' => '(max-width: 600px) 45vw, 200px']) ?><figcaption>Before <small><?= nu_e($pow['before']['time']) ?></small></figcaption></figure>
          <figure><?= nu_photo($pow['after']['photo'], $pow['after']['alt'], ['sizes' => '(max-width: 600px) 45vw, 200px']) ?><figcaption>After <small><?= nu_e($pow['after']['time']) ?></small></figcaption></figure>
        </div>
        <div class="tk-rep__side">
          <ul class="tk-check"><?php foreach ($trk['checklist'] as $c): ?><li><?= nu_icon('check-circle-fill') ?><?= nu_e($c) ?></li><?php endforeach; ?></ul>
          <div class="tk-inv"><span>Invoice <?= nu_e($inv['number']) ?> · <?= nu_e($inv['currency']) ?> <?= number_format($inv['amount'] + $inv['tax'], 2) ?></span><?= nu_payment_chip($inv['payment']) ?></div>
          <a class="btn btn-primary btn-sm" href="<?= nu_e(nu_url('public/activity.php')) ?>#feedback">Review &amp; rate this visit</a>
        </div>
      </div>
<?php else: ?>
      <div class="tk-rep__body tk-rep__body--empty">
        <div class="tk-ph"><?= nu_icon('image') ?><span>Before</span></div>
        <div class="tk-ph"><?= nu_icon('image') ?><span>After</span></div>
        <p class="tk-rep__wait"><?= nu_icon('file-earmark-text') ?><span>Photos and checklist will appear when the job is completed.</span></p>
      </div>
<?php endif; ?>
    </section>
  </div>
  <p class="ch-demo tk-demo">Preview states: <a href="?"<?= $state === '' ? ' aria-current="page"' : '' ?>>On the way (live)</a> · <a href="?state=stale"<?= $stale ? ' aria-current="page"' : '' ?>>Stale location</a> · <a href="?state=completed"<?= $completed ? ' aria-current="page"' : '' ?>>Completed</a> · <a href="<?= nu_e(nu_url('public/status.php')) ?>">All booking states</a></p>
</main>
<script>
(function () {
  var f = document.querySelector('[data-send]'); if (!f) return;
  f.addEventListener('submit', function (e) {
    e.preventDefault(); var i = f.querySelector('input'), t = i.value.trim(); if (!t) return;
    var li = document.createElement('li'); li.className = 'tk-msg tk-msg--me';
    var d = new Date(), h = d.getHours(), m = ('0' + d.getMinutes()).slice(-2);
    li.innerHTML = '<span class="tk-msg__av"></span><div><small></small><p></p></div>';
    li.querySelector('small').textContent = 'You · ' + ((h % 12) || 12) + ':' + m + (h < 12 ? ' AM' : ' PM') + ' · preview, not sent';
    li.querySelector('p').textContent = t;
    var c = document.querySelector('[data-chat]'); c.appendChild(li); c.scrollTop = c.scrollHeight; i.value = '';
  });
})();
document.addEventListener('DOMContentLoaded', function () {
  var el = document.getElementById('trk-map'); if (!el || !window.L) return;
  var route = JSON.parse(el.dataset.route), home = JSON.parse(el.dataset.home);
  var map = L.map(el, { zoomControl: false, attributionControl: true, scrollWheelZoom: false }).fitBounds(route, { padding: [36, 36] });
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(map);
  L.polyline(route, { color: '#11706D', weight: 4, opacity: .9, dashArray: '2 8', lineCap: 'round' }).addTo(map);
  var hIc = L.divIcon({ className: '', html: '<div class="home-marker"><svg viewBox="0 0 16 16"><path d="M8.7 1.5a1 1 0 0 0-1.4 0L.6 8.1a.5.5 0 0 0 .7.7L2 8.2v5.3A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.2l.6.6a.5.5 0 0 0 .7-.7z"/></svg></div>', iconSize: [30, 30], iconAnchor: [15, 30] });
  L.marker(home, { icon: hIc, keyboard: false }).addTo(map);
  var vIc = L.divIcon({ className: '', html: '<div class="veh-marker' + (el.dataset.stale ? ' is-stale' : '') + '"><svg viewBox="0 0 16 16"><path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V5h1.02a1.5 1.5 0 0 1 1.17.56l1.48 1.85a1.5 1.5 0 0 1 .33.94V10.5a1.5 1.5 0 0 1-1.5 1.5H14a2 2 0 1 1-4 0H5a2 2 0 1 1-4 0 1 1 0 0 1-1-1zm1.29 7.25a2 2 0 0 1 3.42 0h5.58a2 2 0 0 1 2.71-.73V3.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .29.25zM12 10a2 2 0 0 1 1.73 1h.77a.5.5 0 0 0 .5-.5V8h-3z"/></svg></div>', iconSize: [38, 38], iconAnchor: [19, 19] });
  if (el.dataset.static && !el.dataset.stale) { return; }
  var car = L.marker(route[0], { icon: vIc, keyboard: false, zIndexOffset: 500 }).addTo(map);
  if (el.dataset.stale) { car.setLatLng(route[3]); L.circle(route[3], { radius: 220, color: '#b7791f', weight: 1, fillOpacity: .08 }).addTo(map); return; }
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
    var pill = document.querySelector('[data-pill] span'); pill.textContent = 'Arrived'; pill.parentNode.classList.add('is-done');
    var steps = document.querySelectorAll('.tk-steps li');
    if (steps[2] && steps[3]) { steps[2].classList.remove('is-current'); steps[2].classList.add('is-done'); steps[2].removeAttribute('aria-current'); steps[3].classList.add('is-current'); steps[3].setAttribute('aria-current', 'step');
      var dot = steps[3].querySelector('.hsteps__dot'); dot.innerHTML = steps[2].querySelector('.hsteps__dot').innerHTML;
      var t = document.createElement('span'); t.className = 'hsteps__t'; t.textContent = 'Now'; steps[3].appendChild(t); }
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
