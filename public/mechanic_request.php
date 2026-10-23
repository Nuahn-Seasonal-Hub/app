<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/mechanic_request.php: service-specific mobile mechanic request.
// Pathway A (?path=roadside&svc=flat-tire ...): urgent, no scheduling. The request is BROADCAST to nearby approved technicians; the first to
//   accept confirms a price and an ETA (funds held, not charged). States: broadcast | accepted | no_provider.
// Pathway B (?path=repair&svc=brake-pads ...): vehicle (year, make, model, trim, engine), symptom checklist, optional photo / audio / video,
//   location and parking surface, preferred window -> itemised quote (quote.php). State: quote_pending.
// UI ONLY: SAMPLE DATA from fixtures/mechanic.php. Submitting only shows the resulting UI state. Uploads preview in the browser only.
// Sir Paul: POST /api/mechanic-requests {path, service, vehicle{year,make,model,trim,engine}, symptoms[], details, media[], address, lat, lng, parking, window}
//   -> {id, state}; roadside: broadcast to approved technicians within a radius ordered by proximity, first accept wins (409 for the others),
//   push the state back (poll or SSE); GET /api/mechanic-requests/{id}; POST /api/mechanic-requests/{id}/cancel;
//   POST /api/uploads (photo, audio, video; size and type limits, virus scan). Preview: ?state=broadcast|accepted|no_provider|quote_pending.
require_once("../config/init.php");

$mech = nu_fixture('mechanic');
$svc = (string) ($_GET['svc'] ?? 'brake-pads');
$road = $mech['roadside'][$svc] ?? null;
$maint = $mech['maintenance'][$svc] ?? null;
if (!$road && !$maint) { $svc = 'brake-pads'; $maint = $mech['maintenance'][$svc]; }
$name = $road ? $road['name'] : $maint['name'];
$posted = $_SERVER['REQUEST_METHOD'] === 'POST';
$state = (string) ($_GET['state'] ?? '');
$stepQ = (int) ($_GET['step'] ?? 1);
if ($posted || $stepQ >= 3) $state = $road ? ($state ?: 'broadcast') : 'quote_pending';
if ($stepQ >= 4 && $road) $state = 'accepted';
if ($road && !in_array($state, ['', 'broadcast', 'accepted', 'no_provider'], true)) $state = 'broadcast';
if (!$road && $state !== '') $state = 'quote_pending';
$steps = $road ? ['Details', 'Location', 'Broadcast', 'On the way'] : ['Vehicle', 'Location', 'Quote', 'Schedule'];
$at = $state === '' ? 0 : ($state === 'accepted' ? 3 : 2);
$loggedIn = nu_role() !== '';
$tech = nu_contractor('c-2001');
$near = (int) $mech['techs_nearby'];
include_once("../includes/header.php");
?>
<main class="mr" id="request">
  <div class="mr-app">
    <a class="mr-back" href="<?= nu_e(nu_url('public/mechanics.php')) ?>"><?= nu_icon('chevron-left') ?> All mechanic services</a>
    <?= nu_steps($steps, $at, 'mr-steps') ?>
<?php if ($state === ''): ?>
    <h1 class="mr-title"><?= nu_e($name) ?></h1>
    <p class="mr-sub"><?= $road ? '<span class="mx-pill mx-pill--a">A</span> Roadside help. Tell us what’s happening and where you are; nearby technicians are alerted at once.' : '<span class="mx-pill">B</span> Scheduled repair. Describe the vehicle and symptoms; mechanics send itemised quotes.' ?></p>
    <form class="mr-form" method="post" action="?path=<?= $road ? 'roadside' : 'repair' ?>&amp;svc=<?= nu_e($svc) ?>" data-mr novalidate>
      <section class="mr-box"><h2><?= nu_icon('car-front-fill') ?> Vehicle details</h2><?= nu_vehicle_select('vehicle') ?></section>
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
      <section class="mr-box"><h2><?= nu_icon('camera-fill') ?> Add photos, audio or video <small>Optional. Helps with a more accurate <?= $road ? 'response' : 'quote' ?>.</small></h2>
        <div class="mr-media">
          <label class="mr-drop mr-drop--sm"><?= nu_icon('camera') ?><span>Photo</span><input type="file" name="photos[]" accept="image/*" multiple class="sr-only" data-media="photo"></label>
<?php if (!$road): ?>
          <label class="mr-drop mr-drop--sm"><?= nu_icon('mic') ?><span>Audio</span><input type="file" name="audio[]" accept="audio/*" class="sr-only" data-media="audio"></label>
<?php endif; ?>
          <label class="mr-drop mr-drop--sm"><?= nu_icon('camera-video') ?><span>Video</span><input type="file" name="video[]" accept="video/*" class="sr-only" data-media="video"></label>
        </div>
        <ul class="mr-files" data-files aria-live="polite"></ul>
        <?= $road ? '' : '<p class="mr-hint">' . nu_icon('mic') . ' A short recording of the noise (10 to 20 seconds) often helps.</p>' ?></section>
      <section class="mr-box"><h2><?= nu_icon('geo-alt-fill') ?> <?= $road ? 'Where are you?' : 'Service location' ?></h2>
        <?= nu_map_picker(['name' => 'mr_loc', 'label' => 'Address or map pin', 'placeholder' => 'Enter address or drop a pin']) ?>
        <p class="mr-err" data-addr-err hidden><?= nu_icon('exclamation-circle') ?> Add the address where the vehicle is parked.</p>
        <fieldset class="fld"><legend class="label">Parking surface</legend>
          <div class="radios radios--pills" role="radiogroup"><?php foreach ($mech['parking'] as $i => $pk): ?><label class="radio"><input type="radio" name="parking" value="<?= nu_e($pk) ?>"<?= $i === ($road ? 5 : 0) ? ' checked' : '' ?>><span><?= nu_e($pk) ?></span></label><?php endforeach; ?></div></fieldset>
        <label class="label" for="mr-park">Access notes <small>(optional)</small></label>
        <textarea class="input" id="mr-park" name="notes" rows="2" placeholder="Level, stall number, gate code, landmarks"></textarea></section>
<?php if (!$road): ?>
      <section class="mr-box"><h2><?= nu_icon('calendar2-week') ?> When suits you?</h2>
        <div class="radios radios--pills" role="radiogroup"><?php foreach (['This week · mornings', 'This week · afternoons', 'Weekend', 'Flexible'] as $i => $w): ?><label class="radio"><input type="radio" name="window" value="<?= nu_e($w) ?>"<?= $i === 3 ? ' checked' : '' ?>><span><?= nu_e($w) ?></span></label><?php endforeach; ?></div>
        <p class="mr-hint"><?= nu_icon('info-circle') ?> Mechanics propose exact windows in their quotes.</p></section>
<?php endif; ?>
      <section class="mr-box mr-box--note"><?= nu_icon('shield-check') ?><div><b><?= $road ? 'Estimated ' . nu_e($mech['roadside_est'][$svc]) . ' · confirmed when a technician accepts' : 'Quote before repair' ?></b><p><?= $road ? 'The first approved technician to accept shows a price and ETA. Funds are held securely and only released when you approve the work. Cancel any time before someone accepts.' : 'Parts, labour, call-out fee and tax are itemised for your approval. Nothing is booked or held until you approve a quote.' ?> <?= nu_sample_tag($road ? 'Sample estimate' : 'Sample form') ?></p></div></section>
      <button class="btn btn-primary btn-lg btn-block" type="submit"><?= $road ? nu_icon('broadcast') . ' Alert nearby technicians' : 'Request quotes ' . nu_icon('arrow-right') ?></button>
      <p class="mr-foot"><?= $road ? 'No appointment is needed. A technician has not accepted until you see their name and ETA.' : 'This submits a request, not a confirmed appointment.' ?><?= $loggedIn ? '' : ' You’ll be asked to sign in first.' ?></p>
    </form>
<?php elseif ($state === 'quote_pending'): ?>
    <section class="mr-state" role="status">
      <span class="mr-state__ic"><?= nu_icon('file-earmark-text') ?></span>
      <h1>Quote requested</h1>
      <p><?= nu_e($name) ?> · 2020 Toyota RAV4 · LE 2.5 L</p>
      <span class="mr-state__chips"><?= nu_booking_chip('quote_pending') ?><?= nu_payment_chip('unpaid') ?></span>
      <p class="mr-state__t"><?= (int) ($near - 3) ?> approved mechanics near you were notified and will send itemised quotes. Nothing is booked or charged until you approve one and choose a window.</p>
      <div class="mr-state__acts"><a class="btn btn-primary" href="<?= nu_e(nu_url('public/quote.php')) ?>">Preview the quote (sample)</a><a class="btn btn-outline" href="<?= nu_e(nu_url('public/messages.php')) ?>">Messages</a></div>
    </section>
<?php elseif ($state === 'broadcast'): ?>
    <section class="mr-state" role="status">
      <span class="mr-state__ic mr-state__ic--pulse"><?= nu_icon('broadcast') ?></span>
      <h1>Alerting nearby technicians</h1>
      <p><?= nu_e($name) ?> · Whitemud Dr at 111 St, Edmonton</p>
      <span class="mr-state__chips"><?= nu_booking_chip('searching') ?><?= nu_payment_chip('unpaid') ?></span>
      <ul class="mr-bc" aria-label="Broadcast progress"><li><?= nu_icon('check-circle-fill') ?> Request sent to <b><?= $near ?></b> approved technicians within 12 km</li><li><?= nu_icon('eye') ?> <b>2</b> are looking at it now</li><li class="is-wait"><?= nu_icon('hourglass-split') ?> Waiting for the first to accept</li></ul>
      <p class="mr-state__t">The first technician to accept confirms the price and an ETA. <b>No arrival time is guaranteed until then.</b> You are not charged.</p>
      <div class="mr-state__acts"><button class="btn btn-outline" type="button" data-cancel>Cancel request</button><a class="btn btn-ghost" href="?path=roadside&amp;svc=<?= nu_e($svc) ?>&amp;state=accepted">Preview: a technician accepts</a><a class="btn btn-ghost" href="?path=roadside&amp;svc=<?= nu_e($svc) ?>&amp;state=no_provider">Preview: no one available</a></div>
      <p class="ac-ok" data-cancel-msg hidden role="status"><?= nu_icon('info-circle') ?> Request cancelled. Nothing was charged. (Preview)</p>
    </section>
<?php elseif ($state === 'accepted'): ?>
    <section class="mr-state mr-state--ok" role="status">
      <span class="mr-state__ic"><?= nu_icon('check-circle-fill') ?></span>
      <h1><?= nu_e(explode(' ', $tech['name'])[0]) ?> is on the way</h1>
      <p><?= nu_e($name) ?> · ETA <b>18 min</b></p>
      <span class="mr-state__chips"><?= nu_booking_chip('on_the_way') ?><?= nu_payment_chip('authorized') ?></span>
      <div class="mr-tech">
        <?= nu_avatar($tech, 'md') ?>
        <div><b><a href="<?= nu_e(nu_url('public/contractor.php')) ?>?id=<?= nu_e($tech['id']) ?>"><?= nu_e($tech['name']) ?></a></b><span><?= nu_icon('star-fill') ?> <?= nu_e($tech['rating']) ?> · <?= (int) $tech['jobs'] ?> jobs · replies <?= nu_e($tech['response']) ?></span><?= nu_badges($tech) ?></div>
        <div class="mr-tech__p"><small>Price</small><b>$95.00</b><small>incl. call-out and tax</small></div>
      </div>
      <?= nu_job_machine('on_the_way') ?>
      <?= nu_trust_note('held') ?>
      <div class="mr-state__acts"><a class="btn btn-primary" href="<?= nu_e(nu_url('public/track.php')) ?>"><?= nu_icon('geo-alt') ?> Track technician</a><a class="btn btn-outline" href="<?= nu_e(nu_url('public/messages.php')) ?>?thread=t3"><?= nu_icon('chat-dots') ?> Message</a></div>
    </section>
<?php else: ?>
    <section class="mr-state mr-state--no" role="status">
      <span class="mr-state__ic"><?= nu_icon('person-x') ?></span>
      <h1>No technician available right now</h1>
      <p><?= nu_e($name) ?> · Edmonton, AB</p>
      <span class="mr-state__chips"><?= nu_booking_chip('no_provider') ?><?= nu_payment_chip('unpaid') ?></span>
      <p class="mr-state__t">No one covering your area accepted in time. You haven’t been charged. You can raise your offered price or try towing.</p>
      <div class="mr-state__acts"><a class="btn btn-primary" href="?path=roadside&amp;svc=<?= nu_e($svc) ?>&amp;state=broadcast">Try again</a><a class="btn btn-outline" href="<?= nu_e(nu_url('public/chat.php')) ?>?thread=support"><?= nu_icon('headset') ?> Contact support</a><a class="btn btn-ghost" href="?path=roadside&amp;svc=towing">Request towing instead</a></div>
    </section>
<?php endif; ?>
    <?= nu_sample_tag('Sample form') ?>
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
</main>
<script src="<?= nu_e(nu_asset('public/assets/map-picker.js')) ?>" defer></script>
<script src="<?= nu_e(nu_asset('public/assets/mech.js')) ?>" defer></script>
<?php include_once("../includes/footer.php"); ?>
