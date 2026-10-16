<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/book.php: booking review step (doc1 p2 "Service booking"): service details, arrival window,
// price, plan terms and payment method before confirming. Reached from the New booking form on
// public/service.php (POST) or a Premium reminder's "Confirm visit" (GET ?from=reminder).
// UI ONLY: nothing is saved or charged. "Confirm booking" shows a confirmation preview.
// Sir Paul: POST /api/bookings/quote (price + tax + capacity check) then POST /api/bookings (idempotency key,
// hosted / tokenized payment authorization). See docs/UI-DATA-HOOKUP.md.
require_once("../config/init.php");

$slug = nu_service_slug($_GET['service'] ?? $_POST['service'] ?? 'garbage-bin-cleaning');
$services = nu_fixture('services');
if (!isset($services[$slug])) { $slug = array_key_first($services); }
$sv = $services[$slug];
$logged = isset($_SESSION['user_id']);
$confirmed = $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === '1';
$fromReminder = ($_GET['from'] ?? '') === 'reminder';
$in = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : [];
$plan = ($in['plan'] ?? ($fromReminder ? 'premium' : 'basic')) === 'premium' ? 'premium' : 'basic';
$window = (string) ($in['window'] ?? ($sv['window_value'] ?? 'Tuesday · 1:00 PM – 3:00 PM'));
if (!empty($in['date'])) { $window = nu_date($in['date'], 'l, M j') . ' · ' . $window; }

// Sample price preview (illustrative; real prices / tax come from the pricing rule for the address).
$price = (float) $sv['price']; $lines = [[$sv['name'] . ' · ' . $sv['unit'], $price]];
if (!empty($sv['extra_field'])) {
    $n = (int) ($in[$sv['extra_field']] ?? 2);
    if ($n > ($sv['included'] ?? 0)) { $lines[] = ['Extra bins × ' . ($n - $sv['included']), ($n - $sv['included']) * $sv['per_extra']]; }
}
if ($slug === 'car-wash') {
    $w = $in['water_source'] ?? 'provider'; $p = $in['power_source'] ?? 'provider';
    if ($w === 'provider') $lines[] = ['Provider brings water', 6.00];
    if ($p === 'provider') $lines[] = ['Provider brings power', 4.00];
}
$sub = array_sum(array_column($lines, 1)); $tax = round($sub * 0.05, 2); $total = $sub + $tax;

/** Value shown for a form field on the review card. */
function bk_value($f, $in) {
    $v = $in[$f['name']] ?? ($f['value'] ?? null);
    if ($f['type'] === 'address') return $in['address'] ?? 'Sample home address · Edmonton, AB';
    if ($f['type'] === 'utility') {
        $w = ($in['water_source'] ?? 'provider') === 'provider' ? 'Provider brings water' : 'Customer water';
        $p = ($in['power_source'] ?? 'provider') === 'provider' ? 'provider brings power' : 'customer electricity';
        return $w . ' + ' . $p;
    }
    if ($f['type'] === 'upload') return 'Photos can be added after booking';
    if (is_array($v)) return $v ? implode(', ', array_filter($v)) : 'None selected';
    if ($v === null || $v === '') return $f['type'] === 'select' ? $f['options'][0] : '—';
    return (string) $v;
}
$icons = ['address' => 'geo-alt', 'select' => 'list-ul', 'checks' => 'check2-circle', 'radios' => 'record-circle', 'cards' => 'car-front', 'utility' => 'droplet', 'vehicle' => 'car-front', 'text' => 'file-text', 'textarea' => 'file-text', 'upload' => 'camera'];

include_once("../includes/header.php");
?>
<main class="rv" id="book">
<?php if ($confirmed):
    $ref = $sv['code'] . '-' . (1049 + strlen($slug)); ?>
  <section class="rv-ok enter">
    <div class="ok-card__ic"><?= nu_icon('check-lg') ?></div>
    <h1><?= $logged ? 'Booking request received' : 'Almost there' ?></h1>
    <p class="muted"><?= $logged ? 'We’re checking coverage and team capacity for your window. You’ll get booking #' . nu_e($ref) . ' and a receipt as soon as it’s confirmed.' : 'Sign in or create a free account to send this booking.' ?></p>
    <div class="rv-ok__state"><?= nu_booking_chip('requested') ?> <?= nu_payment_chip('unpaid') ?></div>
    <dl class="kv">
      <dt>Service</dt><dd><?= nu_e($sv['name']) ?></dd>
      <dt>Arrival window</dt><dd><?= nu_e($_POST['window_text'] ?? $window) ?></dd>
      <dt>Plan</dt><dd><?= $plan === 'premium' ? 'Premium · monthly provider reminders' : 'Basic · one visit' ?></dd>
      <dt>Total</dt><dd>CAD <?= nu_e(number_format((float) ($_POST['total'] ?? $total), 2)) ?> <small class="muted">(illustrative)</small></dd>
    </dl>
    <p><?= nu_sample_tag('Preview only: nothing was saved or charged') ?></p>
    <div class="row-flex" style="justify-content:center">
<?php if ($logged): ?>
      <a class="btn btn-primary" href="<?= nu_e(nu_book_url($slug)) ?>">Back to <?= nu_e($sv['name']) ?></a>
      <a class="btn btn-outline" href="<?= nu_e(nu_url('public/bookings.php')) ?>">My bookings</a>
<?php else: ?>
      <a class="btn btn-primary" href="<?= nu_e(nu_url('public/login_client.php')) ?>">Sign in</a>
      <a class="btn btn-outline" href="<?= nu_e(nu_url('public/register_client.php')) ?>">Create account</a>
<?php endif; ?>
    </div>
  </section>
<?php else: ?>
  <div class="rv-head">
    <h1>Book <?= nu_e(strtolower($sv['name'])) ?></h1>
    <p>Review your details and confirm your booking.</p>
    <?= nu_steps([['Service', ''], ['Details', ''], ['Schedule', ''], ['Review', '']], 3, 'hsteps--num') ?>
  </div>
<?php if ($fromReminder): ?>
  <p class="rv-flag"><?= nu_icon('bell') ?> From your monthly reminder. Confirming here books one visit; the reminder itself never booked or charged anything.</p>
<?php endif; ?>
  <form class="rv-grid" method="post" action="<?= nu_e(nu_url('public/book.php')) ?>?service=<?= nu_e(rawurlencode($slug)) ?>" data-review>
    <input type="hidden" name="confirm" value="1"><input type="hidden" name="plan" value="<?= $plan ?>">
    <input type="hidden" name="window_text" value="<?= nu_e($window) ?>"><input type="hidden" name="total" value="<?= nu_e($total) ?>">
    <div class="rv-main">
      <section class="sv-card">
        <h2 class="rv-h">Service details</h2>
        <p class="sv-sub">Please review your selections before confirming your booking.</p>
        <div class="rv-details">
          <?= nu_photo($sv['photo'], $sv['alt'], ['sizes' => '(max-width: 700px) 92vw, 240px', 'class' => 'rv-photo', 'ratio' => 4 / 5]) ?>
          <dl class="rv-list">
<?php foreach ($sv['form'] as $f): if (in_array($f['name'], ['service'], true)) continue;
      $lbl = $f['label'] ?? ($f['type'] === 'utility' ? 'Water / power' : ucfirst($f['name'])); ?>
            <div><dt><?= nu_e($lbl) ?></dt><dd><?= nu_icon($icons[$f['type']] ?? 'dot') ?><?= nu_e(bk_value($f, $in)) ?></dd></div>
<?php endforeach; ?>
            <div><dt>Frequency</dt><dd><?= nu_icon('arrow-repeat') ?><?= $plan === 'premium' ? 'This visit + monthly provider reminders' : 'One visit (Basic)' ?></dd></div>
            <div><dt>Arrival window</dt><dd><?= nu_icon('calendar3') ?><?= nu_e($window) ?></dd></div>
          </dl>
        </div>
        <label class="btn btn-outline btn-sm rv-photo-btn"><?= nu_icon('camera') ?> Add access photo<input type="file" accept="image/*" class="sr-only" data-files></label>
        <a class="sv-link rv-edit" href="<?= nu_e(nu_book_url($slug)) ?>"><?= nu_icon('pencil') ?> Edit details</a>
      </section>
      <section class="rv-plan<?= $plan === 'premium' ? ' is-premium' : '' ?>">
        <?= nu_icon($plan === 'premium' ? 'calendar-week' : 'calendar-check') ?>
        <div>
<?php if ($plan === 'premium'): ?>
          <b>Premium · Review reminder and billing terms before confirming.</b>
          <p>Your provider will send a monthly reminder to confirm, change or skip your next visit. Reminders do not create visits or charges unless you authorize them. Any plan fee and per-visit charges are shown here before activation. You can pause or cancel from <a href="<?= nu_e(nu_url('public/care_plans.php')) ?>">Your care plans</a>, subject to the <a href="<?= nu_e(nu_url('public/plans.php')) ?>#terms">cancellation terms</a>.</p>
<?php else: ?>
          <b>Basic · One visit.</b>
          <p>You pay for this visit only. Tracking, messages and appointment changes are included. <a href="<?= nu_e(nu_url('public/plans.php')) ?>">Prefer monthly reminders?</a></p>
<?php endif; ?>
        </div>
      </section>
    </div>

    <aside class="rv-side">
      <section class="sv-card rv-sum">
        <h2 class="rv-h">Your booking summary</h2>
        <div class="rv-sum__svc"><?= nu_photo($sv['photo'], '', ['sizes' => '64px', 'class' => 'rv-thumb', 'ratio' => 1]) ?><div><b><?= nu_e($sv['name']) ?></b><span><?= nu_e(bk_value($sv['form'][1] ?? $sv['form'][0], $in)) ?> · <?= $plan === 'premium' ? 'Monthly reminders' : 'One visit' ?></span></div></div>
        <dl class="rv-price">
<?php foreach ($lines as $l): ?>
          <div><dt><?= nu_e($l[0]) ?></dt><dd>CAD <?= number_format($l[1], 2) ?></dd></div>
<?php endforeach; ?>
          <div><dt>Sample tax (5%)</dt><dd>CAD <?= number_format($tax, 2) ?></dd></div>
          <div class="rv-price__t"><dt>Total</dt><dd>CAD <?= number_format($total, 2) ?></dd></div>
        </dl>
        <p class="rv-illus">Illustrative pricing only. <?= $sv['pricing'] === 'quote' ? 'Final price needs an approved quote.' : 'Tax is calculated for your service address.' ?></p>
        <div class="rv-pay">
          <label class="label" for="rv-pm">Choose payment method</label>
          <select class="input" id="rv-pm" name="payment_method" required>
            <option value="">Select a payment method</option><option>Visa •••• 4242 (saved)</option><option>Add a new card (secure hosted form)</option>
          </select>
          <label class="check rv-terms"><input type="checkbox" name="terms" value="1" required data-terms><span class="check__box"><?= nu_icon('check-lg') ?></span><span class="check__t">I agree to the displayed <a href="<?= nu_e(nu_url('public/plans.php')) ?>#terms">service and cancellation terms</a>.</span></label>
          <button class="btn btn-primary btn-lg btn-block" type="submit" data-confirm disabled>Confirm booking</button>
          <p class="hint" data-confirm-hint>Choose a payment method and accept the terms to continue.</p>
        </div>
      </section>
      <section class="sv-card rv-next">
        <h2 class="rv-h">What happens next?</h2>
        <ul>
          <li><?= nu_icon('envelope') ?><span><b>Booking confirmation</b>You’ll receive an email with your booking details.</span></li>
          <li><?= nu_icon('person') ?><span><b>Provider assignment</b>We’ll match you with a qualified local team.</span></li>
          <li><?= nu_icon('bell') ?><span><b>Status and arrival updates</b>You’ll get real-time updates on your team’s status and ETA.</span></li>
        </ul>
      </section>
    </aside>
  </form>
  <p class="sv-foot"><?= nu_sample_tag('Sample prices and availability') ?></p>
<?php endif; ?>
</main>
<script>
(function () {
  var f = document.querySelector('[data-review]'); if (!f) return;
  var btn = f.querySelector('[data-confirm]'), hint = f.querySelector('[data-confirm-hint]');
  function check() { var ok = f.querySelector('[data-terms]').checked && f.elements.payment_method.value; btn.disabled = !ok; hint.hidden = !!ok; }
  f.addEventListener('change', check); check();
  // One tap only: prevents duplicate bookings if the customer taps twice (doc1 p3).
  f.addEventListener('submit', function () { btn.disabled = true; btn.textContent = 'Confirming…'; });
  f.querySelectorAll('[data-files]').forEach(function (i) { i.addEventListener('change', function () { i.parentNode.lastChild.textContent = ''; i.parentNode.insertAdjacentText('beforeend', ' ' + i.files.length + ' photo ready'); }); });
})();
</script>
<?php include_once("../includes/footer.php"); ?>
