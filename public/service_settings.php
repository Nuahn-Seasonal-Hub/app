<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/service_settings.php: per-service configuration (doc1 p10 config sheet, doc2 p8, p20, p23 business values).
// Add forms, pricing / quote rules, equipment, plan terms, weather rules and the evidence checklist without a new
// booking database. UI ONLY: values are SAMPLE DATA from fixtures/services.php; "Save" only previews.
// Sir Paul: GET/PUT /api/admin/services/{slug} {form[], pricing{mode, base, unit, tax}, equipment[], plans{basic, premium},
// weather_rules[], checklist[], evidence{required_photos}, cutoffs{change_hours}} with versioning + audit log.
require_once("../config/init.php");
requireLogin(['manager', 'admin', 'superadmin']);

$services = nu_fixture('services');
$new = !empty($_GET['new']);
$slug = nu_service_slug((string) ($_GET['s'] ?? 'car-wash'));
if (!isset($services[$slug])) $slug = 'car-wash';
$s = $services[$slug];
$u = function ($p) { return nu_url('public/' . $p); };
$nav = [
  ['overview', 'Overview', $u('operations.php'), 'grid-fill'], ['bookings', 'Bookings', $u('jobs.php'), 'calendar3'],
  ['dispatch', 'Dispatch', $u('operations.php') . '#queue', 'truck'], ['teams', 'Teams', $u('operations.php') . '#teams', 'people-fill'],
  ['payments', 'Payments', $u('operations.php') . '#payments', 'credit-card'], ['weather', 'Weather', $u('operations.php') . '#weather', 'cloud'],
  ['settings', 'Service Settings', $u('service_settings.php'), 'gear-fill'], ['support', 'Support', $u('operations.php') . '?queue=issues#queue', 'question-circle'],
];
$types = ['address' => 'Address', 'select' => 'Dropdown', 'checks' => 'Checkboxes', 'radios' => 'Choice', 'cards' => 'Choice cards', 'utility' => 'Water + power', 'vehicle' => 'Vehicle', 'vehicle4' => 'Vehicle', 'text' => 'Text', 'textarea' => 'Long text', 'upload' => 'Photos'];
include_once("../includes/header.php");
?>
<div class="console">
  <?= nu_console_nav($nav, 'settings', 'Operations') ?>
  <main class="console__main op ss" id="settings">
    <header class="op-head">
      <div><p class="crumbs"><a href="<?= nu_e($u('operations.php')) ?>">Operations</a> / Service settings</p><h1><?= $new ? 'Add a service' : nu_e($s['name']) ?></h1></div>
      <nav class="ss-pick" aria-label="Services">
<?php foreach ($services as $k => $sv): ?>
        <a href="?s=<?= $k ?>"<?= !$new && $k === $slug ? ' aria-current="page"' : '' ?> title="<?= nu_e($sv['name']) ?>"><?= nu_icon($sv['icon']) ?><span><?= nu_e($sv['short']) ?></span></a>
<?php endforeach; ?>
        <a href="?new=1"<?= $new ? ' aria-current="page"' : '' ?>><?= nu_icon('plus-lg') ?><span>New</span></a>
      </nav>
    </header>
    <form class="ss-grid" data-ss onsubmit="event.preventDefault()">
      <section class="op-card">
        <h2 class="op-h2">Basics</h2>
        <div class="fld-row">
          <div class="fld"><label class="label" for="ss-name">Service name</label><input class="input" id="ss-name" value="<?= $new ? '' : nu_e($s['name']) ?>" placeholder="e.g. Window Cleaning"></div>
          <div class="fld"><label class="label" for="ss-code">Booking code</label><input class="input" id="ss-code" value="<?= $new ? '' : nu_e($s['code']) ?>" placeholder="e.g. WIN"></div>
        </div>
        <div class="fld-row">
          <div class="fld"><label class="label" for="ss-season">Season</label><input class="input" id="ss-season" value="<?= $new ? '' : nu_e($s['season']) ?>"></div>
          <div class="fld"><label class="label" for="ss-area">Service areas</label><input class="input" id="ss-area" value="Edmonton, St. Albert, Sherwood Park"></div>
        </div>
        <label class="check"><input type="checkbox" checked><span class="check__box"><?= nu_icon('check-lg') ?></span><span class="check__t">Visible to customers<small>Draft services stay hidden until published.</small></span></label>
      </section>
      <section class="op-card">
        <h2 class="op-h2">Pricing / quote rule</h2>
        <div class="mr-seg" role="radiogroup" aria-label="Pricing mode"><?php foreach (['fixed' => 'Fixed price', 'quote' => 'Quote first', 'from' => 'From price + extras'] as $k => $l): ?><label><input type="radio" name="pricing" value="<?= $k ?>"<?= (!$new && $s['pricing'] === $k) || ($new && $k === 'fixed') ? ' checked' : '' ?>><span><?= $l ?></span></label><?php endforeach; ?></div>
        <div class="fld-row">
          <div class="fld"><label class="label" for="ss-price">Base price (CAD)</label><input class="input" id="ss-price" inputmode="decimal" value="<?= $new ? '' : number_format((float) $s['price'], 2) ?>"></div>
          <div class="fld"><label class="label" for="ss-unit">Per</label><input class="input" id="ss-unit" value="<?= $new ? '' : nu_e($s['unit']) ?>"></div>
        </div>
        <div class="fld-row">
          <div class="fld"><label class="label" for="ss-tax">Tax</label><select class="input" id="ss-tax"><option>GST 5% (Alberta)</option></select></div>
          <div class="fld"><label class="label" for="ss-cut">Free change cutoff</label><select class="input" id="ss-cut"><option>24 h before window</option><option>12 h</option><option>Before departure</option></select></div>
        </div>
        <p class="op-mini"><?= nu_icon('exclamation-triangle') ?> Mockup amounts are placeholders. Enter real business prices before launch.</p>
      </section>
      <section class="op-card ss-wide">
        <h2 class="op-h2">Booking form <small>Fields customers fill in, in order</small></h2>
        <ol class="ss-fields">
<?php foreach ($new ? [['type' => 'address', 'label' => 'Service address'], ['type' => 'textarea', 'label' => 'Access notes']] : $s['form'] as $f): ?>
          <li><span class="ss-grip" aria-hidden="true"><?= nu_icon('grip-vertical') ?></span><b><?= nu_e($f['label'] ?? ucfirst($f['type'])) ?></b><span class="qt-type"><?= nu_e($types[$f['type']] ?? $f['type']) ?></span><?php if (!empty($f['options']) && is_array($f['options'])): ?><small><?= nu_e(implode(' · ', array_map(function ($o) { return is_array($o) ? $o[0] : explode('|', $o)[0]; }, array_slice($f['options'], 0, 4)))) ?><?= count($f['options']) > 4 ? ' …' : '' ?></small><?php endif; ?><button class="linkbtn" type="button">Edit</button></li>
<?php endforeach; ?>
        </ol>
        <button class="btn btn-soft btn-sm" type="button"><?= nu_icon('plus-lg') ?> Add field</button>
      </section>
      <section class="op-card">
        <h2 class="op-h2">Equipment &amp; matching</h2>
        <p class="ss-p"><?= nu_e($new ? 'List equipment a team must carry to be matched.' : $s['provider']) ?></p>
        <div class="checks"><?php foreach (($slug === 'car-wash' && !$new) ? ['Water tank (provider water)', 'Generator (provider power)', 'Pressure washer'] : ($slug === 'snow-removal' ? ['Plow truck', 'Snow blower', 'Ice melt'] : ['Standard kit', 'Trailer']) as $i => $e): ?><label class="check"><input type="checkbox" checked><span class="check__box"><?= nu_icon('check-lg') ?></span><span class="check__t"><?= nu_e($e) ?></span></label><?php endforeach; ?></div>
      </section>
      <section class="op-card">
        <h2 class="op-h2">Plans</h2>
        <dl class="se-terms"><div><dt>Basic</dt><dd><?= nu_e($new ? 'One visit' : $s['plan_basic'][1]) ?></dd></div><div><dt>Premium</dt><dd><?= nu_e($new ? 'Monthly provider reminder' : $s['plan_premium'][1]) ?></dd></div><div><dt>Plan terms</dt><dd><?= nu_e($new ? '—' : $s['plans']) ?></dd></div></dl>
        <p class="op-mini"><?= nu_icon('gem') ?> A reminder never creates a job or a charge. Auto-visits need a separately agreed mode.</p>
      </section>
      <section class="op-card">
        <h2 class="op-h2">Weather rules</h2>
        <p class="ss-p"><?= nu_e($new ? 'Describe conditions that pause or move this service.' : $s['weather']) ?></p>
        <div class="fld-row"><div class="fld"><label class="label" for="ss-wx">When triggered</label><select class="input" id="ss-wx"><option>Flag for dispatch review</option><option>Auto-propose next window</option></select></div><div class="fld"><label class="label" for="ss-wxn">Next update shown</label><select class="input" id="ss-wxn"><option>Every 2 h</option><option>Every 4 h</option></select></div></div>
      </section>
      <section class="op-card">
        <h2 class="op-h2">Evidence checklist</h2>
        <ul class="tk-check"><?php foreach ($new ? ['Before photo', 'After photo'] : $s['checklist'] as $c): ?><li><?= nu_icon('check-circle-fill') ?><?= nu_e($c) ?></li><?php endforeach; ?></ul>
        <label class="check ss-req"><input type="checkbox" checked><span class="check__box"><?= nu_icon('check-lg') ?></span><span class="check__t">Before and after photos required to submit</span></label>
      </section>
      <div class="ss-save ss-wide">
        <button class="btn btn-primary" type="button" data-save>Save as new version</button>
        <a class="btn btn-outline" href="<?= nu_e($new ? $u('operations.php') : nu_book_url($slug)) ?>"><?= $new ? 'Cancel' : 'View customer page' ?></a>
        <span class="pd-ui" data-ui-out role="status"></span>
        <span class="op-mini">Changes are versioned and logged. Existing bookings keep the terms they were booked under.</span>
      </div>
    </form>
    <p class="ch-demo"><?= nu_sample_tag('Sample configuration') ?></p>
  </main>
</div>
<script>
document.querySelector('[data-save]').addEventListener('click', function () { document.querySelector('[data-ui-out]').textContent = 'Preview: version 4 would be saved. Nothing was changed.'; });
</script>
<?php include_once("../includes/footer.php"); ?>
