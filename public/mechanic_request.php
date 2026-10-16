<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/mechanic_request.php: service-specific mechanic request (doc2 p17, doc1 p15-16).
// Brake Pad Replacement: vehicle (year/make/model/trim), which brakes, symptoms (incl. Not sure), photo/video,
// service location + parking notes, "Quote before repair". Steps: Vehicle -> Location -> Quote -> Schedule.
// Roadside categories (?svc=flat-tire etc.) use their own fields and show Searching until a mechanic accepts;
// "No provider available" offers retry / support and never a guaranteed ETA.
// UI ONLY: SAMPLE DATA from fixtures/mechanic.php. Submitting only shows the resulting UI state.
// Sir Paul: POST /api/mechanic-requests {service, vehicle{}, brakes, symptoms[], photos[], address, notes}
// -> {id, state: quote_pending | searching | no_provider}; GET /api/mechanic-requests/{id} (poll/SSE for state).
// Preview: ?step=3 (quote requested), ?state=searching|no_provider (roadside).
require_once("../config/init.php");

$mech = nu_fixture('mechanic');
$svc = (string) ($_GET['svc'] ?? 'brake-pads');
$road = $mech['roadside'][$svc] ?? null;
$maint = $mech['maintenance'][$svc] ?? null;
if (!$road && !$maint) { $svc = 'brake-pads'; $maint = $mech['maintenance'][$svc]; }
$name = $road ? $road['name'] : $maint['name'];
$posted = $_SERVER['REQUEST_METHOD'] === 'POST';
$state = (string) ($_GET['state'] ?? '');
if ($posted || (int) ($_GET['step'] ?? 1) >= 3) $state = $road ? ($state ?: 'searching') : 'quote_pending';
$steps = $road ? ['Details', 'Location', 'Searching', 'On the way'] : ['Vehicle', 'Location', 'Quote', 'Schedule'];
$at = $state === '' ? 0 : 2;
$loggedIn = nu_role() !== '';
include_once("../includes/header.php");
?>
<main class="mr" id="request">
  <div class="mr-app">
    <a class="mr-back" href="<?= nu_e(nu_url('public/mechanics.php')) ?>"><?= nu_icon('chevron-left') ?> All mechanic services</a>
    <?= nu_steps($steps, $at, 'mr-steps') ?>
<?php if ($state === ''): ?>
    <h1 class="mr-title"><?= nu_e($name) ?></h1>
    <p class="mr-sub"><?= $road ? 'Tell us what’s happening and where the vehicle is.' : 'Tell us about your vehicle.' ?></p>
    <form class="mr-form" method="post" action="?svc=<?= nu_e($svc) ?>" data-mr novalidate>
      <section class="mr-box"><h2><?= nu_icon('car-front-fill') ?> Vehicle details</h2><?= nu_form_field(['type' => 'vehicle4', 'name' => 'vehicle', 'label' => ''], 'mr') ?></section>
<?php if ($road): ?>
<?php foreach (array_slice($road['fields'], 1) as $f): ?>
      <section class="mr-box"><?= nu_form_field($f + ['pills' => true], 'mr') ?></section>
<?php endforeach; ?>
<?php elseif ($svc === 'brake-pads'): ?>
      <section class="mr-box"><h2><?= nu_icon('disc') ?> Which brakes?</h2>
        <div class="mr-seg" role="radiogroup" aria-label="Which brakes"><?php foreach (['Front', 'Rear', 'Not sure'] as $i => $b): ?><label><input type="radio" name="brakes" value="<?= $b ?>"<?= $i === 0 ? ' checked' : '' ?>><span><?= $b ?></span></label><?php endforeach; ?></div></section>
      <section class="mr-box"><h2><?= nu_icon('clipboard2-pulse') ?> What are you noticing?</h2>
        <div class="checks checks--2"><?php foreach ($mech['brake_symptoms'] as $s): ?><label class="check"><input type="checkbox" name="symptoms[]" value="<?= nu_e($s) ?>"<?= $s === 'Squeaking' ? ' checked' : '' ?>><span class="check__box"><?= nu_icon('check-lg') ?></span><span class="check__t"><?= nu_e($s) ?></span></label><?php endforeach; ?></div></section>
<?php else: ?>
      <section class="mr-box"><h2><?= nu_icon('clipboard2-pulse') ?> What should we know?</h2>
        <label class="sr-only" for="mr-desc">Describe the issue</label><textarea class="input" id="mr-desc" name="details" rows="3" placeholder="Symptoms, warning lights, when it started, last service date"></textarea></section>
<?php endif; ?>
      <section class="mr-box"><h2><?= nu_icon('camera-fill') ?> Add a photo or short video</h2>
        <label class="mr-drop" for="mr-photo"><?= nu_icon('camera') ?><span>Tap to add a photo or video<small>Helps us provide a more accurate quote.</small></span><input type="file" id="mr-photo" name="photos[]" accept="image/*,video/*" multiple class="sr-only" data-files></label></section>
      <section class="mr-box"><h2><?= nu_icon('geo-alt-fill') ?> Service location <small>Confirm address or map pin.</small></h2>
        <label class="mr-addr"><?= nu_icon('geo-alt') ?><span class="sr-only">Address</span><input class="input" name="address" placeholder="Enter address or choose on map" value="" required data-addr><?= nu_icon('chevron-right') ?></label>
        <p class="mr-err" data-addr-err hidden><?= nu_icon('exclamation-circle') ?> Add the address where the vehicle is parked.</p>
        <label class="label" for="mr-park">Parking / access notes</label>
        <textarea class="input" id="mr-park" name="notes" rows="2" placeholder="Tell us where the vehicle is parked."></textarea></section>
      <section class="mr-box mr-box--note"><?= nu_icon('shield-check') ?><div><b><?= $road ? 'Request, not a promise' : 'Quote before repair' ?></b><p><?= $road ? 'We’ll look for a nearby mechanic. The price and ETA appear when one accepts. You can cancel any time before then.' : 'Parts and labour depend on your vehicle. We will confirm the scope and price before work begins.' ?></p></div></section>
      <button class="btn btn-primary btn-lg btn-block" type="submit"><?= $road ? 'Request roadside help' : 'Request quote' ?> <?= nu_icon('arrow-right') ?></button>
      <p class="mr-foot">This submits a request, not a confirmed appointment.<?= $loggedIn ? '' : ' You’ll be asked to sign in first.' ?></p>
    </form>
<?php elseif ($state === 'quote_pending'): ?>
    <section class="mr-state" role="status">
      <span class="mr-state__ic"><?= nu_icon('file-earmark-text') ?></span>
      <h1>Quote requested</h1>
      <p><?= nu_e($name) ?> · 2020 Toyota RAV4 · LE 2.5 L</p>
      <?= nu_booking_chip('quote_pending') ?>
      <p class="mr-state__t">A qualified mechanic reviews your details and photos and sends an itemised quote. Nothing is booked or charged until you approve it and choose a window.</p>
      <div class="mr-state__acts"><a class="btn btn-primary" href="<?= nu_e(nu_url('public/quote.php')) ?>">Preview the quote (sample)</a><a class="btn btn-outline" href="<?= nu_e(nu_url('public/messages.php')) ?>">Messages</a></div>
    </section>
<?php elseif ($state === 'searching'): ?>
    <section class="mr-state" role="status">
      <span class="mr-state__ic mr-state__ic--pulse"><?= nu_icon($road['icon']) ?></span>
      <h1>Searching for a mechanic</h1>
      <p><?= nu_e($name) ?> · Edmonton, AB</p>
      <?= nu_booking_chip('searching') ?>
      <p class="mr-state__t">We’re contacting qualified mechanics near you. The ETA and price appear once one accepts. <b>No arrival time is guaranteed until then.</b></p>
      <div class="mr-state__acts"><button class="btn btn-outline" type="button">Cancel request</button><a class="btn btn-ghost" href="?svc=<?= nu_e($svc) ?>&amp;state=no_provider">Preview: no provider</a></div>
    </section>
<?php else: ?>
    <section class="mr-state mr-state--no" role="status">
      <span class="mr-state__ic"><?= nu_icon('person-x') ?></span>
      <h1>No mechanic available right now</h1>
      <p><?= nu_e($name) ?> · Edmonton, AB</p>
      <?= nu_booking_chip('no_provider') ?>
      <p class="mr-state__t">No one covering your area accepted in time. You haven’t been charged.</p>
      <div class="mr-state__acts"><a class="btn btn-primary" href="?svc=<?= nu_e($svc) ?>&amp;state=searching">Try again</a><a class="btn btn-outline" href="<?= nu_e(nu_url('public/chat.php')) ?>?thread=support"><?= nu_icon('headset') ?> Contact support</a><a class="btn btn-ghost" href="?svc=towing">Request towing instead</a></div>
    </section>
<?php endif; ?>
    <?= nu_sample_tag('Sample form') ?>
  </div>
  <aside class="cp-rules mr-rules">
    <h3>Identify the vehicle</h3><p>Year, make, model and trim / engine if known. Ask which brakes and the symptoms; allow Not sure.</p>
    <h3>Review work scope</h3><p>Photos help the mechanic assess the request. Diagnosis may change the proposed repair; the quote is approved before repairs begin.</p>
    <h3>Then schedule</h3><p>After the scope and quote are approved, reserve an available arrival window. It can be changed later through the shared flow.</p>
    <h3>Roadside request state</h3><p>Show Searching until a suitable mechanic accepts. If no one is available, offer a retry or support; never a guaranteed immediate ETA.</p>
  </aside>
</main>
<script>
(function () {
  var f = document.querySelector('[data-mr]'); if (!f) return;
  f.addEventListener('submit', function (e) {
    var a = f.querySelector('[data-addr]'), err = f.querySelector('[data-addr-err]');
    if (!a.value.trim()) { e.preventDefault(); err.hidden = false; a.setAttribute('aria-invalid', 'true'); a.focus(); }
  });
  f.querySelector('[data-addr]').addEventListener('input', function () { f.querySelector('[data-addr-err]').hidden = true; this.removeAttribute('aria-invalid'); });
})();
</script>
<?php include_once("../includes/footer.php"); ?>
