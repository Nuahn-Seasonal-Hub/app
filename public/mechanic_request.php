<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/mechanic_request.php: service-specific mobile mechanic request, on live data.
// No ?task=: the request form (guests can fill it in; sign-in happens only when sending, the draft survives it). It posts do=mech_request.
//   Pathway A roadside: a hold is placed first (sandbox: instant), then the request is broadcast to technicians within MK_ROADSIDE_RADIUS_KM, nearest first.
//   Pathway B scheduled repair: broadcast for itemised quotes (quote.php).
// ?task=ID: the request status for the owner (searching / no technician / accepted / quote pending). Job progress continues on track.php.
// Photos only for now; audio and video uploads are pending (see docs/BOOKING-BACKEND.md).
require_once("../config/init.php");

require_once("../includes/marketplace/view.php");
require_once("../includes/onboarding.php");
$mech = nu_fixture('mechanic');
$taskId = (int) ($_GET['task'] ?? 0);
$task = null;
if ($taskId) {
    $acc = mk_task_access($taskId);
    if (!$acc || $acc[1] !== 'client' || $acc[0]['service'] !== 'mobile-mechanics') { http_response_code(404); include_once("../includes/header.php"); echo '<main class="mr"><div class="mr-app"><section class="mr-state"><h1>Request not found</h1><p><a href="' . nu_e(nu_url('public/bookings.php')) . '">My bookings</a></p></section></div></main>'; include_once("../includes/footer.php"); exit; }
    $task = $acc[0];
    $svc = $task['category'];
} else {
    $svc = (string) ($_GET['svc'] ?? 'brake-pads');
}
$road = $mech['roadside'][$svc] ?? null;
$maint = $mech['maintenance'][$svc] ?? null;
if (!$road && !$maint) { $svc = 'brake-pads'; $maint = $mech['maintenance'][$svc]; }
$name = $road ? $road['name'] : $maint['name'];
$state = '';
if ($task) {
    $st = $task['status'];
    if ($st === 'searching') $state = 'broadcast';
    elseif ($st === 'no_provider') $state = 'no_provider';
    elseif ($st === 'quote_pending') $state = 'quote_pending';
    elseif ($st === 'cancelled') $state = 'cancelled';
    else { header('Location: ' . nu_url('public/track.php') . '?id=' . $taskId); exit; }
}
$steps = $road ? ['Details', 'Location', 'Broadcast', 'On the way'] : ['Vehicle', 'Location', 'Quote', 'Schedule'];
$at = $state === '' ? 0 : 2;
$role = (string) (nu_role() ?? '');
$loggedIn = isset($_SESSION['user_id']) && $role !== '';
$isClient = $loggedIn && $role === 'client';
$near = $task ? count(mk_nearby_contractors($task, 50)) : 0;
$nextUrl = rawurlencode('public/mechanic_request.php?svc=' . $svc . '&draft=1');

// Pre-fill service location from ?address= or signed-in client's saved area
$prefillAddr = trim((string) ($_GET['address'] ?? ''));
if ($prefillAddr === '' && $isClient) {
    $u = ob_user_get(mk_uid());
    $pd = ob_prof_data($u);
    $savedParts = array_filter([trim((string) ($pd['city'] ?? '')), trim((string) ($pd['postal_code'] ?? ''))]);
    if ($savedParts) {
        $prefillAddr = implode(', ', $savedParts);
    } elseif (!empty($u['location'])) {
        $prefillAddr = trim(str_replace('·', ',', (string) $u['location']));
    }
}
include_once("../includes/header.php");
?>
<main class="mr" id="request">
  <div class="mr-app">
    <a class="mr-back" href="<?= nu_e(nu_url('public/mechanics.php')) ?>"><?= nu_icon('chevron-left') ?> All mechanic services</a>
    <?= nu_steps($steps, $at, 'mr-steps') ?>
<?php if ($state === ''): ?>
    <h1 class="mr-title"><?= nu_e($name) ?></h1>
    <p class="mr-sub"><?= $road ? '<span class="mx-pill mx-pill--a">A</span> Roadside help. Tell us what’s happening and where you are; nearby technicians are alerted at once.' : '<span class="mx-pill">B</span> Scheduled repair. Describe the vehicle and symptoms; mechanics send itemised quotes.' ?></p>
    <?= mk_form('mech_request', ['svc' => $svc, 'path' => $road ? 'roadside' : 'repair'], 'class="mr-form" enctype="multipart/form-data" data-mr data-guest="' . ($isClient ? '0' : '1') . '" data-draft="mr-' . nu_e($svc) . '" novalidate', 'mechanic_request.php?svc=' . $svc) ?>

      <!-- STEP 1: Vehicle & Symptoms / Roadside Details -->
      <div class="mr-pane" data-mr-pane="0">
        <section class="mr-box">
          <h2><?= nu_icon('car-front-fill') ?> Vehicle details</h2>
          <?= nu_vehicle_select('vehicle') ?>
          <p class="mr-err" data-veh-err hidden><?= nu_icon('exclamation-circle') ?> Choose your vehicle’s make and model to continue.</p>
        </section>
<?php if ($road): ?>
<?php foreach (array_slice($road['fields'], 1) as $f): ?>
        <section class="mr-box"><?= nu_form_field($f + ['pills' => true], 'mr') ?></section>
<?php endforeach; ?>
        <section class="mr-box"><h2><?= nu_icon('shield-exclamation') ?> Your safety</h2>
          <div class="checks"><?php foreach ($mech['road_sit'] as $i => $s): ?><label class="check"><input type="checkbox" name="safety[]" value="<?= nu_e($s) ?>"<?= $i === 0 ? ' checked' : '' ?>><span class="check__box"><?= nu_icon('check-lg') ?></span><span class="check__t"><?= nu_e($s) ?></span></label><?php endforeach; ?></div>
          <p class="mr-hint"><?= nu_icon('telephone') ?> In an emergency or on a live highway lane, call 911 first.</p></section>
<?php else: ?>
<?php if ($svc === 'brake-pads'): ?>
        <section class="mr-box"><h2><?= nu_icon('disc') ?> Which brakes?</h2>
          <div class="mr-seg" role="radiogroup" aria-label="Which brakes"><?php foreach (['Front', 'Rear', 'Not sure'] as $i => $b): ?><label><input type="radio" name="brakes" value="<?= $b ?>"<?= $i === 0 ? ' checked' : '' ?>><span><?= $b ?></span></label><?php endforeach; ?></div></section>
<?php endif; ?>
        <section class="mr-box"><h2><?= nu_icon('clipboard2-pulse') ?> Symptom checklist</h2>
          <div class="checks checks--2"><?php foreach ($maint['symptoms'] as $i => $s): ?><label class="check"><input type="checkbox" name="symptoms[]" value="<?= nu_e($s) ?>"<?= $i === 0 ? ' checked' : '' ?>><span class="check__box"><?= nu_icon('check-lg') ?></span><span class="check__t"><?= nu_e($s) ?></span></label><?php endforeach; ?></div>
          <label class="label" for="mr-desc">Anything else the mechanic should know? <small>(optional)</small></label><textarea class="input" id="mr-desc" name="details" rows="2" maxlength="400" placeholder="When it started, warning lights, last service date"></textarea></section>
<?php endif; ?>
        <section class="mr-box"><h2><?= nu_icon('camera-fill') ?> Add photos <small>Optional. Helps with a more accurate <?= $road ? 'response' : 'quote' ?>.</small></h2>
          <div class="mr-media">
            <label class="mr-drop mr-drop--sm"><?= nu_icon('camera') ?><span>Photo</span><input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple class="sr-only" data-media="photo"></label>
          </div>
          <ul class="mr-files" data-files aria-live="polite"></ul>
          <p class="mr-hint"><?= nu_icon('info-circle') ?> Audio and video uploads are coming soon. Photos only for now.</p></section>

        <div class="mr-nav">
          <button class="btn btn-primary btn-lg btn-block" type="button" data-mr-next>Continue to Location <?= nu_icon('arrow-right') ?></button>
        </div>
        <p class="mr-foot">Step 1 of 2 · Next: confirm your service location and submit.</p>
      </div>

      <!-- STEP 2: Service Location, Timing & Submit -->
      <div class="mr-pane" data-mr-pane="1" hidden>
        <div class="mr-veh-bar">
          <span><?= nu_icon('car-front-fill') ?> <b data-veh-summary>Your vehicle</b></span>
          <button type="button" class="btn btn-ghost btn-sm" data-mr-back><?= nu_icon('pencil-square') ?> Edit</button>
        </div>

        <section class="mr-box"><h2><?= nu_icon('geo-alt-fill') ?> <?= $road ? 'Where are you?' : 'Service location' ?></h2>
          <?= nu_map_picker(['name' => 'mr_loc', 'address' => $prefillAddr, 'label' => 'Address or map pin', 'placeholder' => 'Enter address or drop a pin']) ?>
          <p class="mr-err" data-addr-err hidden><?= nu_icon('exclamation-circle') ?> Add the address where the vehicle is parked.</p>
          <fieldset class="fld"><legend class="label">Parking surface</legend>
            <div class="radios radios--pills" role="radiogroup"><?php foreach ($mech['parking'] as $i => $pk): ?><label class="radio"><input type="radio" name="parking" value="<?= nu_e($pk) ?>"<?= $i === ($road ? 5 : 0) ? ' checked' : '' ?>><span><?= nu_e($pk) ?></span></label><?php endforeach; ?></div></fieldset>
          <label class="label" for="mr-park">Access notes <small>(optional)</small></label>
          <textarea class="input" id="mr-park" name="notes" rows="2" maxlength="500" placeholder="Level, stall number, gate code, landmarks"></textarea></section>
<?php if (!$road): ?>
        <section class="mr-box"><h2><?= nu_icon('calendar2-week') ?> When suits you?</h2>
          <div class="radios radios--pills" role="radiogroup"><?php foreach (['This week · mornings', 'This week · afternoons', 'Weekend', 'Flexible'] as $i => $w): ?><label class="radio"><input type="radio" name="window" value="<?= nu_e($w) ?>"<?= $i === 3 ? ' checked' : '' ?>><span><?= nu_e($w) ?></span></label><?php endforeach; ?></div>
          <p class="mr-hint"><?= nu_icon('info-circle') ?> Mechanics propose exact windows in their quotes.</p></section>
<?php endif; ?>
        <section class="mr-box mr-box--note"><?= nu_icon('shield-check') ?><div><b><?= $road ? 'Estimated ' . nu_e($mech['roadside_est'][$svc]) . ' · confirmed when a technician accepts' : 'Quote before repair' ?></b><p><?= $road ? 'The first approved technician to accept shows a price and ETA. Funds are held securely and only released when you approve the work. Cancel any time before someone accepts.' : 'Parts, labour, call-out fee and tax are itemised for your approval. Nothing is booked or held until you approve a quote.' ?></p></div></section>
<?php if ($road): ?>
        <section class="mr-box"><h2><?= nu_icon('lock-fill') ?> Payment hold</h2><?= nu_trust_note('held') ?><?= mk_test_card_field() ?></section>
<?php endif; ?>
<?php if ($loggedIn && !$isClient): ?>
        <p class="mk-note mk-note--warn"><?= nu_icon('exclamation-circle') ?> <span>You are signed in as <?= nu_e($role) ?>. Requests are made from a client account.</span></p>
        <div class="mr-nav">
          <button class="btn btn-outline btn-lg" type="button" data-mr-back><?= nu_icon('arrow-left') ?> Back</button>
        </div>
<?php else: ?>
        <div class="mr-nav mr-nav--split">
          <button class="btn btn-outline btn-lg" type="button" data-mr-back><?= nu_icon('arrow-left') ?> Back</button>
          <button class="btn btn-primary btn-lg btn-block" type="submit"><?= $road ? nu_icon('broadcast') . ' ' . ($isClient ? 'Alert nearby technicians' : 'Sign in to send the alert') : ($isClient ? 'Request quotes ' : 'Sign in to request quotes ') . nu_icon('arrow-right') ?></button>
        </div>
<?php endif; ?>
        <p class="mr-foot"><?= $road ? 'No appointment is needed. A technician has not accepted until you see their name and ETA.' : 'This submits a request, not a confirmed appointment.' ?><?= $isClient ? '' : ' You will be asked to sign in only when you send.' ?></p>
      </div>
    </form>
<?php elseif ($state === 'quote_pending'): ?>
    <section class="mr-state" role="status">
      <span class="mr-state__ic"><?= nu_icon('file-earmark-text') ?></span>
      <h1>Quote requested</h1>
      <p><?= nu_e($task['title']) ?></p>
      <span class="mr-state__chips"><?= nu_booking_chip('quote_pending') ?><?= nu_payment_chip('unpaid') ?></span>
      <p class="mr-state__t"><?= (int) $near ?> approved mechanic<?= $near === 1 ? ' was' : 's were' ?> notified and will send an itemised quote. Nothing is booked or charged until you approve a quote and choose a window.</p>
      <div class="mr-state__acts"><a class="btn btn-primary" href="<?= nu_e(nu_url('public/quote.php')) ?>?task=<?= $taskId ?>">View quotes</a><a class="btn btn-outline" href="<?= nu_e(nu_url('public/bookings.php')) ?>">My bookings</a></div>
      <?= mk_form('task_cancel', ['task_id' => $taskId], 'class="mr-state__acts"', 'bookings.php') ?><button class="btn btn-ghost btn-sm" type="submit">Cancel request</button></form>
    </section>
<?php elseif ($state === 'broadcast'): ?>
    <section class="mr-state" role="status">
      <span class="mr-state__ic mr-state__ic--pulse"><?= nu_icon('broadcast') ?></span>
      <h1>Alerting nearby technicians</h1>
      <p><?= nu_e($name) ?> · <?= nu_e($task['address']) ?></p>
      <span class="mr-state__chips"><?= nu_booking_chip('searching') ?><?= nu_payment_chip($task['payment_status']) ?></span>
      <ul class="mr-bc" aria-label="Broadcast progress"><li><?= nu_icon('check-circle-fill') ?> Request sent to <b><?= (int) $near ?></b> approved technician<?= $near === 1 ? '' : 's' ?> within <?= (int) MK_ROADSIDE_RADIUS_KM ?> km</li><li class="is-wait"><?= nu_icon('hourglass-split') ?> Waiting for the first technician to accept (about <?= max(0, (int) $task['search_left_min']) ?> min left)</li></ul>
      <p class="mr-state__t">The first technician to accept confirms an ETA. <b>No arrival time is guaranteed until then.</b> Your payment is only held, not charged, until you approve the finished work.</p>
      <div class="mr-state__acts"><a class="btn btn-outline" href="">Refresh</a></div>
      <?= mk_form('task_cancel', ['task_id' => $taskId], 'class="mr-state__acts"', 'bookings.php') ?><button class="btn btn-ghost btn-sm" type="submit">Cancel request</button></form>
    </section>
<?php elseif ($state === 'cancelled'): ?>
    <section class="mr-state mr-state--no" role="status">
      <span class="mr-state__ic"><?= nu_icon('x-circle') ?></span>
      <h1>Request cancelled</h1>
      <p><?= nu_e($name) ?></p>
      <p class="mr-state__t">Nothing was charged. Any held funds were released.</p>
      <div class="mr-state__acts"><a class="btn btn-primary" href="<?= nu_e(nu_url('public/mechanics.php')) ?>">New request</a></div>
    </section>
<?php else: ?>
    <section class="mr-state mr-state--no" role="status">
      <span class="mr-state__ic"><?= nu_icon('person-x') ?></span>
      <h1>No technician available right now</h1>
      <p><?= nu_e($name) ?> · <?= nu_e(mk_area($task['address'])) ?></p>
      <span class="mr-state__chips"><?= nu_booking_chip('no_provider') ?><?= nu_payment_chip($task['payment_status']) ?></span>
      <p class="mr-state__t">No one covering your area accepted in time. You have not been charged. If this is an emergency, call 911 or your roadside plan.</p>
      <div class="mr-state__acts"><a class="btn btn-primary" href="<?= nu_e(nu_url('public/mechanic_request.php')) ?>?path=roadside&amp;svc=<?= nu_e($svc) ?>">Send a new request</a><?= mk_form('task_cancel', ['task_id' => $taskId], 'class="d-inline"', 'bookings.php') ?><button class="btn btn-ghost" type="submit">Cancel and release the hold</button></form></div>
    </section>
<?php endif; ?>
    <?= $road && $state === '' ? mk_test_note() : '' ?>
  </div>
  <aside class="cp-rules mr-rules">
<?php if ($road): ?>
    <h3>Broadcast, not booking</h3><p>Your request goes to approved technicians near you. The first to accept confirms a price and ETA; there is no scheduling.</p>
    <h3>If nobody accepts</h3><p>You can retry, contact support or request towing. You are never charged for an unanswered request.</p>
<?php else: ?>
    <h3>Describe the problem</h3><p>Year, make, model, trim and engine, plus the symptoms you notice. Photos, audio and video are optional but help.</p>
    <h3>Approve before work</h3><p>You receive an itemised quote (parts, labour, call-out fee, tax). Approve it, ask a question or decline.</p>
<?php endif; ?>
    <h3>Funds held securely</h3><p>Payment is held by a secure processor and released only after you approve the finished work. Card details never touch Nuahn.</p>
  </aside>
  <dialog class="msheet" id="mrSignSheet" aria-labelledby="mrSignT">
    <div class="msheet__in">
      <button class="msheet__x" type="button" data-close aria-label="Close"><?= nu_icon('x-lg') ?></button>
      <span class="msheet__ic"><?= nu_icon('person-circle') ?></span>
      <h2 id="mrSignT">Finish sending your mechanic request</h2>
      <p>Your vehicle, symptoms and location are saved. Create a free client account or sign in and you’ll come straight back to send it in one tap.</p>
      <div class="msheet__sum"><span><?= nu_e($name) ?></span><b><?= $road ? 'Roadside alert' : 'Itemised quotes' ?></b></div>
      <a class="btn btn-primary btn-block" href="<?= nu_e(nu_url('public/register.php')) ?>?next=<?= $nextUrl ?>">Create free account <?= nu_icon('arrow-right') ?></a>
      <a class="btn btn-outline btn-block" href="<?= nu_e(nu_url('public/login.php')) ?>?next=<?= $nextUrl ?>">Already have an account? Sign in</a>
      <p class="msheet__fine"><?= nu_icon('lock-fill') ?> <?= $road ? 'Cancel any time before a technician accepts.' : 'Nothing is booked or charged until you approve a quote.' ?></p>
    </div>
  </dialog>
</main>
<script src="<?= nu_e(nu_asset('public/assets/map-picker.js')) ?>" defer></script>
<script src="<?= nu_e(nu_asset('public/assets/mech.js')) ?>" defer></script>
<?php if ($state === ''): ?>
<script>
(function () {
  var f = document.querySelector('[data-mr]'); if (!f) return;
  var key = f.dataset.draft, isGuest = f.dataset.guest === '1';
  var sheet = document.getElementById('mrSignSheet');
  var panes = f.querySelectorAll('[data-mr-pane]');
  var stepEls = document.querySelectorAll('.mr-steps li');
  var makeEl = f.querySelector('[data-v="make"]'), modelEl = f.querySelector('[data-v="model"]');
  var yearEl = f.querySelector('[data-v="year"]'), trimEl = f.querySelector('[data-v="trim"]'), engEl = f.querySelector('[data-v="engine"]');
  var vehErr = f.querySelector('[data-veh-err]'), addrEl = f.querySelector('input[name$="_address"]'), addrErr = f.querySelector('[data-addr-err]');
  var vehSummary = f.querySelector('[data-veh-summary]');
  var curStep = 0;

  function fields() { return Array.prototype.filter.call(f.elements, function (e) { return e.name && e.type !== 'file' && e.type !== 'hidden' && e.type !== 'submit' && e.type !== 'button'; }); }

  function updateSummary() {
    if (!vehSummary) return;
    var parts = [yearEl && yearEl.value, makeEl && makeEl.value, modelEl && modelEl.value, trimEl && trimEl.value, engEl && engEl.value].filter(Boolean);
    vehSummary.textContent = parts.length ? parts.join(' ') : 'Your vehicle';
  }

  function validateVehicle() {
    var okMake = makeEl && makeEl.value.trim() !== '';
    var okModel = modelEl && modelEl.value.trim() !== '';
    if (okMake && okModel) {
      if (vehErr) vehErr.hidden = true;
      if (makeEl) makeEl.removeAttribute('aria-invalid');
      if (modelEl) modelEl.removeAttribute('aria-invalid');
      return true;
    }
    if (vehErr) vehErr.hidden = false;
    var bad = !okMake ? makeEl : modelEl;
    if (bad) { bad.setAttribute('aria-invalid', 'true'); bad.focus(); }
    return false;
  }

  function validateAddress() {
    var ok = addrEl && addrEl.value.trim().length >= 5;
    if (ok) {
      if (addrErr) addrErr.hidden = true;
      addrEl.removeAttribute('aria-invalid');
      return true;
    }
    if (addrErr) addrErr.hidden = false;
    if (addrEl) { addrEl.setAttribute('aria-invalid', 'true'); addrEl.focus(); }
    return false;
  }

  function goStep(idx, scroll) {
    curStep = idx;
    panes.forEach(function (p) { p.hidden = Number(p.dataset.mrPane) !== idx; });
    stepEls.forEach(function (li, i) {
      li.classList.toggle('is-done', i < idx);
      li.classList.toggle('is-current', i === idx);
      if (i === idx) li.setAttribute('aria-current', 'step');
      else li.removeAttribute('aria-current');
      li.classList.toggle('is-clickable', i <= 1);
    });
    updateSummary();
    if (scroll) {
      var topEl = document.getElementById('request');
      if (topEl && topEl.scrollIntoView) topEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
      else window.scrollTo(0, 0);
    }
  }

  // Wire Step 1 -> Step 2, Step 2 -> Step 1, and clickable top stepper dots (Vehicle / Location)
  f.querySelectorAll('[data-mr-next]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!validateVehicle()) return;
      goStep(1, true);
    });
  });
  f.querySelectorAll('[data-mr-back]').forEach(function (btn) {
    btn.addEventListener('click', function () { goStep(0, true); });
  });
  stepEls.forEach(function (li, i) {
    if (i > 1) return;
    li.classList.add('is-clickable');
    li.setAttribute('role', 'button');
    li.setAttribute('tabindex', '0');
    function jump() {
      if (i === curStep) return;
      if (i === 1 && !validateVehicle()) return;
      goStep(i, true);
    }
    li.addEventListener('click', jump);
    li.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); jump(); } });
  });

  if (makeEl) makeEl.addEventListener('change', function () { if (vehErr) vehErr.hidden = true; makeEl.removeAttribute('aria-invalid'); });
  if (modelEl) modelEl.addEventListener('change', function () { if (vehErr) vehErr.hidden = true; modelEl.removeAttribute('aria-invalid'); });
  if (addrEl) addrEl.addEventListener('input', function () { if (addrErr) addrErr.hidden = true; addrEl.removeAttribute('aria-invalid'); });

  // Restore saved draft when returning from signup or sign-in (?draft=1)
  window.addEventListener('load', function () {
    try {
      var qs = new URLSearchParams(location.search);
      var preAddr = qs.get('address');
      if (preAddr && addrEl && !addrEl.value) addrEl.value = preAddr;
      var d = JSON.parse(sessionStorage.getItem(key) || 'null');
      if (!d || qs.get('draft') !== '1') return;
      // Restore make first so mech.js populates model options before model value is restored
      d.forEach(function (r) {
        if (r[0] === 'vehicle[make]' && makeEl && r[2]) {
          makeEl.value = r[2];
          makeEl.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });
      fields().forEach(function (el) {
        if (el.name === 'vehicle[make]') return;
        d.forEach(function (r) {
          if (r[0] !== el.name) return;
          if (el.type === 'checkbox' || el.type === 'radio') { if (el.value === r[1]) el.checked = r[2]; }
          else el.value = r[2];
        });
      });
      if (makeEl && makeEl.value && modelEl && modelEl.value) {
        goStep(1, false);
      }
      if (!isGuest) {
        var note = document.createElement('div');
        note.className = 'alert alert-success';
        note.setAttribute('role', 'status');
        note.textContent = 'Welcome back! Your mechanic request draft is restored — review your location and tap the button below to send.';
        f.parentNode.insertBefore(note, f);
        sessionStorage.removeItem(key);
      }
    } catch (x) {}
  });

  // Unified submit validation (Step 1 vehicle + Step 2 address) before guest modal or real POST
  f.addEventListener('submit', function (e) {
    if (!validateVehicle()) {
      e.preventDefault();
      goStep(0, true);
      return;
    }
    if (curStep === 0) {
      e.preventDefault();
      goStep(1, true);
      return;
    }
    if (!validateAddress()) {
      e.preventDefault();
      return;
    }
    if (isGuest) {
      e.preventDefault();
      var d = [];
      fields().forEach(function (el) {
        if (el.type === 'checkbox' || el.type === 'radio') d.push([el.name, el.value, el.checked]);
        else d.push([el.name, null, el.value]);
      });
      try { sessionStorage.setItem(key, JSON.stringify(d)); } catch (x) {}
      if (sheet && sheet.showModal) sheet.showModal();
      else if (sheet) sheet.setAttribute('open', '');
    }
  });
  if (sheet) {
    sheet.querySelector('[data-close]').addEventListener('click', function () { sheet.close ? sheet.close() : sheet.removeAttribute('open'); });
    sheet.addEventListener('click', function (e) { if (e.target === sheet) sheet.close(); });
  }
})();
</script>
<?php endif; ?>
<?php include_once("../includes/footer.php"); ?>
