<?php
// public/book.php — "Book a service": one dynamic flow that adapts per service (client design v2).
// UI ONLY. Services, prices and windows come from fixtures/services.php + fixtures/plans.php.
// Submitting shows a confirmation preview; NOTHING is saved yet.
// Sir Paul: POST /api/bookings {service, address, fields{}, plan, frequency, date, window} → booking id (see docs/UI-DATA-HOOKUP.md).
require_once("../config/init.php");

$services = nu_fixture('services');
$slug = (string) ($_GET['service'] ?? $_POST['service'] ?? 'garbage-can-cleaning');
if (!isset($services[$slug])) { $slug = array_key_first($services); }
$sv = $services[$slug];
$plan = (($_GET['plan'] ?? $_POST['plan'] ?? 'basic') === 'premium') ? 'premium' : 'basic';
$windows = $slug === 'snow-removal'
    ? ['5:00 – 7:00 AM', '7:00 – 9:00 AM', '9:00 – 11:00 AM']
    : ['8:00 – 10:00 AM', '10:00 AM – 12:00 PM', '12:00 – 2:00 PM', '2:00 – 4:00 PM', '4:00 – 6:00 PM', 'Any time'];
$posted = $_SERVER['REQUEST_METHOD'] === 'POST';
$logged = isset($_SESSION['user_id']);
$defaultAddr = $logged && isRole('client') ? '123 Maple Grove Dr SW, Calgary, AB' : '';

include_once("../includes/header.php");
?>
<main class="screen" id="book">
<?php if ($posted):
    $addr = trim((string) ($_POST['address'] ?? ''));
    $win  = (string) ($_POST['window'] ?? '');
    $date = (string) ($_POST['date'] ?? '');
    $freq = (string) ($_POST['frequency'] ?? 'One-time');
    $est  = (string) ($_POST['estimate'] ?? $sv['price']);
?>
  <section class="panel2 ok-card enter">
    <div class="ok-card__ic"><?= nu_icon('check-lg') ?></div>
    <h1 class="serif" style="font-size:1.8rem;margin-bottom:6px"><?= $logged ? 'Booking request sent' : 'Almost there' ?></h1>
    <p class="muted"><?= $logged
        ? ($plan === 'basic' ? 'Your provider will accept your time slot shortly. We’ll notify you and open a chat thread.' : 'Your Premium schedule is set. We’ll confirm each visit the day before.')
        : 'Sign in or create a free account to send this request to a provider.' ?></p>
    <dl class="kv">
      <dt>Service</dt><dd><?= nu_e($sv['name']) ?></dd>
      <dt>Address</dt><dd><?= nu_e($addr ?: '—') ?></dd>
      <dt>Plan</dt><dd><?= $plan === 'premium' ? 'Premium' : 'Basic' ?> · <?= nu_e($freq) ?></dd>
      <dt>When</dt><dd><?= nu_e(nu_date($date, 'D, M j') ?: $date) ?> · <?= nu_e($win) ?></dd>
<?php foreach ($sv['fields'] as $f): $v = $_POST[$f['name']] ?? ''; if ($v === '' || $v === []) continue; ?>
      <dt><?= nu_e($f['label']) ?></dt><dd><?= nu_e(is_array($v) ? implode(', ', $v) : $v) ?></dd>
<?php endforeach; ?>
      <dt>Estimate</dt><dd>CAD <?= nu_e(number_format((float) $est, 2)) ?> + tax</dd>
    </dl>
    <p><?= nu_sample_tag('Preview only: not saved until the bookings API is connected') ?></p>
    <div class="row-flex" style="justify-content:center">
<?php if ($logged): ?>
      <a class="btn btn-primary" href="<?= nu_e(nu_url('public/track.php')) ?>?id=BK-1042">Track a booking</a>
      <a class="btn btn-outline" href="<?= nu_e(nu_url('public/bookings.php')) ?>">My bookings</a>
<?php else: ?>
      <a class="btn btn-primary" href="<?= nu_e(nu_url('public/login_client.php')) ?>">Sign in</a>
      <a class="btn btn-outline" href="<?= nu_e(nu_url('public/register_client.php')) ?>">Create account</a>
<?php endif; ?>
    </div>
  </section>
<?php else: ?>
  <nav class="svc-switch" aria-label="Choose a service">
<?php foreach ($services as $s2 => $o): ?>
    <a href="<?= nu_e(nu_book_url($s2) . ($plan === 'premium' ? '&plan=premium' : '')) ?>"<?= $s2 === $slug ? ' aria-current="page"' : '' ?>><?= nu_icon($o['icon']) ?> <?= nu_e($o['short']) ?></a>
<?php endforeach; ?>
  </nav>

  <div class="bk-hero">
    <?= nu_photo($sv['photo'], $sv['alt'], ['sizes' => '(max-width: 600px) 100vw, 560px', 'eager' => true]) ?>
    <div class="bk-hero__cap"><h1><?= nu_e($sv['name']) ?></h1><p><?= nu_e($sv['tagline']) ?></p></div>
  </div>

  <form class="bk-form" method="post" action="<?= nu_e(nu_book_url($slug)) ?>" data-booking
        data-price="<?= (float) $sv['price'] ?>" data-extra="<?= (float) ($sv['per_extra'] ?? 0) ?>"
        data-extra-field="<?= nu_e($sv['extra_field'] ?? '') ?>" data-included="<?= (int) ($sv['included'] ?? 0) ?>"
        data-multiply-field="<?= nu_e($sv['multiply_field'] ?? '') ?>">
    <input type="hidden" name="service" value="<?= nu_e($slug) ?>">
    <input type="hidden" name="estimate" value="<?= (float) $sv['price'] ?>" data-estimate>

    <section class="panel2">
      <label class="fld" for="bk-addr"><span class="label">Home address</span>
        <span class="input-icon"><?= nu_icon('geo-alt-fill') ?><input class="input" id="bk-addr" name="address" required autocomplete="street-address" placeholder="Street, city" value="<?= nu_e($defaultAddr) ?>"></span>
      </label>

<?php foreach ($sv['fields'] as $f): $id = 'f-' . $f['name']; ?>
      <div class="fld">
<?php if ($f['type'] === 'number'): ?>
        <label class="label" for="<?= $id ?>"><?= nu_e($f['label']) ?></label>
        <div class="stepper" data-stepper>
          <button type="button" data-step="-1" aria-label="Fewer">−</button>
          <input type="number" id="<?= $id ?>" name="<?= nu_e($f['name']) ?>" min="<?= (int) $f['min'] ?>" max="<?= (int) $f['max'] ?>" value="<?= (int) $f['default'] ?>" inputmode="numeric">
          <button type="button" data-step="1" aria-label="More">+</button>
        </div>
<?php elseif ($f['type'] === 'select'): ?>
        <label class="label" for="<?= $id ?>"><?= nu_e($f['label']) ?></label>
        <select class="input" id="<?= $id ?>" name="<?= nu_e($f['name']) ?>">
<?php foreach ($f['options'] as $o): ?>
          <option><?= nu_e($o) ?></option>
<?php endforeach; ?>
        </select>
<?php elseif ($f['type'] === 'chips'): ?>
        <span class="label" id="<?= $id ?>-l"><?= nu_e($f['label']) ?></span>
        <div class="chips" role="group" aria-labelledby="<?= $id ?>-l">
<?php foreach ($f['options'] as $o): ?>
          <label class="chipopt"><input type="checkbox" name="<?= nu_e($f['name']) ?>[]" value="<?= nu_e($o) ?>"<?= in_array($o, $f['default'] ?? [], true) ? ' checked' : '' ?>><span><?= nu_e($o) ?></span></label>
<?php endforeach; ?>
        </div>
<?php else: ?>
        <label class="label" for="<?= $id ?>"><?= nu_e($f['label']) ?></label>
        <input class="input" id="<?= $id ?>" name="<?= nu_e($f['name']) ?>" placeholder="<?= nu_e($f['placeholder'] ?? '') ?>">
<?php endif; ?>
      </div>
<?php endforeach; ?>
    </section>

    <section class="panel2">
      <div class="fld">
        <span class="label">Service frequency</span>
        <div class="seg">
          <label><input type="radio" name="plan" id="plan-basic" value="basic"<?= $plan === 'basic' ? ' checked' : '' ?>><span>One-time<small>Basic</small></span></label>
          <label><input type="radio" name="plan" id="plan-premium" value="premium"<?= $plan === 'premium' ? ' checked' : '' ?>><span>Recurring<small>Premium</small></span></label>
        </div>
        <input type="hidden" name="frequency" value="One-time" class="freq-basic" data-freq-basic>
        <div class="chips freq-premium" role="group" aria-label="Recurring frequency">
<?php foreach ($sv['premium_freq'] as $k => $o): ?>
          <label class="chipopt"><input type="radio" name="frequency" value="<?= nu_e($o) ?>"<?= $k === 0 ? ' checked' : '' ?> data-freq-premium<?= $plan === 'basic' ? ' disabled' : '' ?>><span><?= nu_icon('arrow-repeat') ?> <?= nu_e($o) ?></span></label>
<?php endforeach; ?>
        </div>
      </div>
      <label class="fld" for="bk-date"><span class="label"><span class="freq-basic">Date</span><span class="freq-premium">Start date</span></span>
        <input class="input" type="date" id="bk-date" name="date" required value="<?= date('Y-m-d', strtotime('+1 day')) ?>" min="<?= date('Y-m-d') ?>">
      </label>
      <div class="fld">
        <span class="label" id="win-l">Arrival window</span>
        <div class="chips windows" role="radiogroup" aria-labelledby="win-l">
<?php foreach ($windows as $k => $w): ?>
          <label class="chipopt"><input type="radio" name="window" value="<?= nu_e($w) ?>"<?= $k === 1 ? ' checked' : '' ?>><span><?= nu_e($w) ?></span></label>
<?php endforeach; ?>
        </div>
        <p class="hint freq-basic">Basic: your provider accepts the slot, or proposes another time in chat.</p>
        <p class="hint freq-premium">Premium: same team every visit, priority on snow days.</p>
      </div>
    </section>

    <div class="price" aria-live="polite">
      <div>
        <div class="price__l"><?= nu_e($sv['name']) ?> · <?= nu_e($sv['unit']) ?></div>
        <div class="price__note">Paid securely, held until you confirm the work. <?= nu_sample_tag('Sample price') ?></div>
      </div>
      <div class="price__v"><small>CAD</small> <span data-price-out><?= (int) $sv['price'] ?></span> <small>+ tax</small></div>
    </div>

    <div class="sticky-cta"><button class="btn btn-primary btn-lg btn-block" type="submit">Confirm booking <?= nu_icon('arrow-right') ?></button></div>
  </form>
<?php endif; ?>
</main>
<script>
(function () {
  var f = document.querySelector('[data-booking]'); if (!f) return;
  var out = f.querySelector('[data-price-out]'), est = f.querySelector('[data-estimate]');
  function calc() {
    var p = +f.dataset.price, ef = f.dataset.extraField, mf = f.dataset.multiplyField;
    if (ef && f.elements[ef]) p += Math.max(0, (+f.elements[ef].value || 0) - (+f.dataset.included || 0)) * (+f.dataset.extra);
    if (mf && f.elements[mf]) p = p * (+f.elements[mf].value || 1);
    out.textContent = Math.round(p); est.value = p;
  }
  f.addEventListener('click', function (e) {
    var b = e.target.closest('[data-step]'); if (!b) return;
    var i = b.parentNode.querySelector('input'), v = (+i.value || 0) + (+b.dataset.step);
    i.value = Math.min(+i.max, Math.max(+i.min, v)); calc();
  });
  function plan() {
    var prem = f.querySelector('#plan-premium').checked;
    f.querySelectorAll('[data-freq-premium]').forEach(function (r) { r.disabled = !prem; });
    f.querySelector('[data-freq-basic]').disabled = prem;
  }
  f.addEventListener('input', calc); f.addEventListener('change', function () { plan(); calc(); });
  plan(); calc();
})();
</script>
<?php include_once("../includes/footer.php"); ?>
