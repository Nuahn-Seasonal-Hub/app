<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/post_task.php: Post a snow-removal task with a target budget (marketplace model).
// GUEST EXPLORATION: anyone can enter an address, choose scope and see an estimated price range. The sign-in prompt
// appears only when pressing "Post task". The draft is kept in sessionStorage so it survives sign-in.
// Prices come from the pricing table in fixtures/marketplace.php ['snow'] (static config, mirrored server-side in
// includes/marketplace/config.php mk_snow_estimate). Posting creates a real task (action snow_post in actions/marketplace.php):
// CSRF-protected, client role only, address + map pin lat/lng stored, then broadcast to approved contractors in range.
// Sir Paul: replace the client-side estimator with GET /api/pricing/snow-estimate when the API exists; geocoding key still pending.
require_once("../config/init.php");
require_once("../includes/marketplace/core.php");
$m = nu_fixture('marketplace');
$sn = $m['snow'];
$signedIn = isset($_SESSION['user_id']);
$isClient = $signedIn && isRole('client');
$wrongRole = $signedIn && !$isClient;
$cfg = [
    'sizes' => array_map(fn($s) => ['lo' => $s['lo'], 'hi' => $s['hi'], 'sq' => $s['per_sqft'] ?? null], $sn['sizes']),
    'walk' => array_map(fn($w) => [$w[1], $w[2]], $sn['walk']),
    'steps' => array_values($sn['steps'])[0], 'ice' => [$sn['ice']['lo'], $sn['ice']['hi']],
    'disc' => $sn['seasonal_discount'], 'pickup' => $sn['pickup'], 'boostMax' => $sn['boost_max'], 'trigger' => $sn['trigger_cm'],
];
$next = rawurlencode('public/post_task.php?draft=1');
include_once("../includes/header.php");
?>
<main class="screen pt">
  <p class="crumbs"><a href="<?= nu_e(nu_url('public/service.php')) ?>?s=snow-removal">Winter Snow Removal</a> / Post a task</p>
  <div class="screen__head"><div><h1>Post a snow task</h1><p>Tell contractors what needs clearing and what you would like to pay. Verified local contractors send offers.</p></div></div>

  <?php if ($wrongRole): ?>
  <p class="of-posted" role="status"><?= nu_icon('info-circle') ?> <span>You are signed in as <b><?= nu_e(isRole('provider') ? 'a contractor' : 'staff') ?></b>. Posting tasks needs a client account.</span></p>
<?php endif; ?>
  <form class="pt-form" id="ptForm" method="post" action="<?= nu_e(nu_url('actions/marketplace.php')) ?>" data-pt='<?= nu_e(json_encode($cfg)) ?>' data-signed="<?= $isClient ? '1' : '0' ?>" novalidate>
    <?= mk_csrf_field() ?><input type="hidden" name="do" value="snow_post">

    <section class="sv-card pt-sec">
      <h2 class="pt-h"><span class="pt-n">1</span> Where is the property?</h2>
      <?= nu_map_picker(['name' => 'loc', 'address' => '', 'label' => 'Property address']) ?>
    </section>

    <section class="sv-card pt-sec">
      <h2 class="pt-h"><span class="pt-n">2</span> Driveway size</h2>
      <div class="mk-opts mk-opts--3" role="radiogroup" aria-label="Driveway size">
        <?php foreach ($sn['sizes'] as $k => $s): ?>
          <label class="mk-opt"><input type="radio" name="size" value="<?= nu_e($k) ?>" <?= $k === 'standard' ? 'checked' : '' ?>><span><?= nu_icon($s['icon']) ?><b><?= nu_e($s['label']) ?></b><small><?= nu_e($s['sub']) ?></small></span></label>
        <?php endforeach; ?>
      </div>
      <div class="pt-sqft" data-sqft hidden>
        <label class="label" for="sqft">Approximate area (sq ft)</label>
        <input class="input" id="sqft" name="sqft" type="number" inputmode="numeric" min="400" step="50" value="2500">
      </div>
    </section>

    <section class="sv-card pt-sec">
      <h2 class="pt-h"><span class="pt-n">3</span> Walkways, steps and ice</h2>
      <div class="mk-opts mk-opts--3" role="radiogroup" aria-label="Walkways">
        <?php foreach ($sn['walk'] as $k => $w): ?>
          <label class="mk-opt"><input type="radio" name="walk" value="<?= nu_e($k) ?>" <?= $k === 'front' ? 'checked' : '' ?>><span><b><?= nu_e($w[0]) ?></b><small><?= $w[1] ? '+$' . $w[1] . '–' . $w[2] : 'Driveway only' ?></small></span></label>
        <?php endforeach; ?>
      </div>
      <label class="mk-check"><input type="checkbox" name="steps" value="1" checked><span><b>Steps and stairs</b><small>Cleared and brushed down. +$<?= array_values($sn['steps'])[0][0] ?>–<?= array_values($sn['steps'])[0][1] ?></small></span></label>
      <label class="mk-check"><input type="checkbox" name="ice" value="1" checked><span><b>Ice melt add-on</b><small><?= nu_e($sn['ice']['label']) ?>. +$<?= $sn['ice']['lo'] ?>–<?= $sn['ice']['hi'] ?></small></span></label>
    </section>

    <section class="sv-card pt-sec">
      <h2 class="pt-h"><span class="pt-n">4</span> When</h2>
      <div class="mk-opts mk-opts--2" role="radiogroup" aria-label="Scheduling">
        <label class="mk-opt"><input type="radio" name="plan" value="demand" checked><span><?= nu_icon('lightning-charge-fill') ?><b>On demand</b><small>One clear, as soon as a contractor can</small></span></label>
        <label class="mk-opt"><input type="radio" name="plan" value="seasonal"><span><?= nu_icon('cloud-snow') ?><b>Seasonal plan</b><small>Every snowfall of <?= (int) $sn['trigger_cm'] ?> cm or more, all winter</small></span></label>
      </div>
      <p class="pt-info" data-seasonal hidden><?= nu_icon('thermometer-snow') ?> <span>A contractor clears after every snowfall of <b><?= (int) $sn['trigger_cm'] ?> cm</b> or more, based on the local forecast and report. Seasonal plans save about <?= (int) round($sn['seasonal_discount'] * 100) ?>% per visit. You pay per visit, held until you approve it.</span></p>
    </section>

    <section class="sv-card pt-sec pt-budget">
      <h2 class="pt-h"><span class="pt-n">5</span> Your target budget</h2>
      <p class="pt-sugg">Suggested for your job: <b data-range>$55–$85</b></p>
      <div class="pt-money">
        <button class="pt-step" type="button" data-bd="-5" aria-label="Lower budget by 5 dollars"><?= nu_icon('dash-lg') ?></button>
        <label class="pt-money__in"><span>$</span><input id="budget" name="budget" type="number" inputmode="numeric" min="10" max="999" value="45" aria-label="Target budget in dollars"></label>
        <button class="pt-step" type="button" data-bd="5" aria-label="Raise budget by 5 dollars"><?= nu_icon('plus-lg') ?></button>
      </div>
      <p class="pt-hint" data-hint></p>

      <div class="pt-boost">
        <div class="pt-boost__top"><span class="pt-boost__t"><?= nu_icon('rocket-takeoff-fill') ?> Boost your bid</span><b data-boosttotal>$45</b></div>
        <p class="pt-boost__s">Add to your budget to get picked up sooner. Your task is shown first to contractors nearby. You still choose the offer.</p>
        <input class="pt-range" type="range" name="boost" id="boost" min="0" max="<?= (int) $sn['boost_max'] ?>" step="1" value="0" aria-label="Boost amount in dollars" aria-describedby="boostOut">
        <div class="pt-range__l"><span>No boost</span><span>+$<?= (int) $sn['boost_max'] ?></span></div>
        <div class="pt-pick" id="boostOut" role="status">
          <span class="pt-pick__ic"><?= nu_icon('stopwatch') ?></span>
          <span><small>Expected pickup time</small><b data-pick>Around 35 min</b></span>
          <span class="pt-meter" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
        </div>
      </div>
    </section>

    <section class="sv-card pt-sec">
      <h2 class="pt-h"><span class="pt-n">6</span> Anything else? <small>Optional</small></h2>
      <textarea class="input" name="notes" rows="3" placeholder="Gate code, where to pile the snow, a car in the driveway..."></textarea>
    </section>

    <div class="pt-bar">
      <div class="pt-bar__sum"><small>Estimated price</small><b data-est>$55–$85</b><span data-planline>per clear</span></div>
      <button class="btn btn-primary btn-lg" type="button" id="postBtn" data-signed="<?= $isClient ? '1' : '0' ?>"<?= $wrongRole ? ' disabled' : '' ?>><?= nu_icon('send-fill') ?> Post task</button>
      <button class="btn btn-primary btn-lg" type="submit" id="postGo" hidden>Post task</button>
    </div>
    <?= nu_trust_note('held') ?>
  </form>

  <dialog class="msheet" id="signSheet" aria-labelledby="signT">
    <div class="msheet__in">
      <button class="msheet__x" type="button" data-close aria-label="Close"><?= nu_icon('x-lg') ?></button>
      <span class="msheet__ic"><?= nu_icon('person-circle') ?></span>
      <h2 id="signT">Sign in to post your task</h2>
      <p>Your address, scope and budget are saved. Sign in or create a free account and you can send it to contractors in a tap.</p>
      <div class="msheet__sum"><span data-sum-line>Standard driveway</span><b data-sum-est></b></div>
      <a class="btn btn-primary btn-block" href="<?= nu_e(nu_url('public/login.php')) ?>?next=<?= $next ?>">Sign in</a>
      <a class="btn btn-outline btn-block" href="<?= nu_e(nu_url('public/register.php')) ?>?next=<?= $next ?>">Create an account</a>
      <p class="msheet__fine"><?= nu_icon('lock-fill') ?> Nothing is charged when you post. Funds are held only after you accept an offer.</p>
    </div>
  </dialog>
</main>
<script src="<?= nu_e(nu_asset('public/assets/map-picker.js')) ?>" defer></script>
<script>
(function () {
  var f = document.getElementById('ptForm'), C = JSON.parse(f.dataset.pt), $ = function (s) { return f.querySelector(s); };
  var bd = $('#budget'), boost = $('#boost'), key = 'nuahn.taskDraft';
  function val(n) { var e = f.querySelector('[name=' + n + ']:checked'); return e ? e.value : ''; }
  function est() {
    var s = C.sizes[val('size')], lo, hi;
    if (s.sq) { var a = Math.max(400, parseInt($('#sqft').value, 10) || 0); lo = Math.max(60, a * s.sq[0]); hi = Math.max(90, a * s.sq[1]); } else { lo = s.lo; hi = s.hi; }
    var w = C.walk[val('walk')]; lo += w[0]; hi += w[1];
    if (f.steps.checked) { lo += C.steps[0]; hi += C.steps[1]; }
    if (f.ice.checked) { lo += C.ice[0]; hi += C.ice[1]; }
    if (val('plan') === 'seasonal') { lo *= 1 - C.disc; hi *= 1 - C.disc; }
    return [Math.round(lo / 5) * 5, Math.round(hi / 5) * 5];
  }
  var touched = false, last = 0;
  function render() {
    var r = est(), mid = (r[0] + r[1]) / 2, seasonal = val('plan') === 'seasonal';
    if (!touched) bd.value = Math.round(mid * 0.82 / 5) * 5;
    var b = parseInt(bd.value, 10) || 0, bo = parseInt(boost.value, 10) || 0, tot = b + bo;
    $('[data-range]').textContent = '$' + r[0] + '–$' + r[1];
    $('[data-est]').textContent = '$' + r[0] + '–$' + r[1];
    $('[data-planline]').textContent = seasonal ? 'per visit · every ' + C.trigger + ' cm snowfall' : 'one clear';
    $('[data-boosttotal]').textContent = '$' + tot + (bo ? ' (' + b + ' + ' + bo + ')' : '');
    var ratio = tot / mid, t = C.pickup[C.pickup.length - 1], idx = C.pickup.length - 1;
    for (var i = 0; i < C.pickup.length; i++) if (ratio < C.pickup[i][0]) { t = C.pickup[i]; idx = i; break; }
    $('[data-pick]').textContent = t[1];
    var m = $('.pt-meter'); m.className = 'pt-meter is-' + t[2]; m.querySelectorAll('i').forEach(function (e, j) { e.classList.toggle('on', j <= idx); });
    var h = $('[data-hint]');
    h.textContent = b < r[0] * 0.8 ? 'This is below what most contractors accept. Raise it or add a boost.' : (b > r[1] ? 'Above the typical range, so offers should arrive quickly.' : 'Inside the typical range. You can still add a boost.');
    h.className = 'pt-hint' + (b < r[0] * 0.8 ? ' is-low' : '');
    $('[data-seasonal]').hidden = !seasonal; $('[data-sqft]').hidden = val('size') !== 'commercial';
    f.querySelectorAll('.pt-step').forEach(function (e) { e.disabled = false; });
    last = tot; save();
  }
  function save() { try { sessionStorage.setItem(key, JSON.stringify({ size: val('size'), walk: val('walk'), plan: val('plan'), steps: f.steps.checked, ice: f.ice.checked, budget: bd.value, boost: boost.value, addr: f.loc_address.value, sqft: f.sqft.value, touched: touched })); } catch (e) {} }
  function load() {
    try { var d = JSON.parse(sessionStorage.getItem(key) || 'null'); if (!d) return;
      ['size', 'walk', 'plan'].forEach(function (n) { var e = f.querySelector('[name=' + n + '][value=' + d[n] + ']'); if (e) e.checked = true; });
      f.steps.checked = !!d.steps; f.ice.checked = !!d.ice; f.loc_address.value = d.addr || ''; f.sqft.value = d.sqft || 2500; boost.value = d.boost || 0; touched = !!d.touched; if (touched) bd.value = d.budget;
    } catch (e) {}
  }
  load(); try { if (new URLSearchParams(location.search).get('plan') === 'seasonal') { var sp = f.querySelector('[name=plan][value=seasonal]'); if (sp) sp.checked = true; } } catch (e) {}
  render();
  f.addEventListener('change', function (e) { if (e.target === bd) touched = true; render(); });
  f.addEventListener('input', function (e) { if (e.target === bd) touched = true; if (e.target === boost || e.target === bd || e.target.id === 'sqft') render(); });
  f.addEventListener('mp:change', save);
  f.querySelectorAll('[data-bd]').forEach(function (b) { b.addEventListener('click', function () { touched = true; bd.value = Math.max(10, (parseInt(bd.value, 10) || 0) + parseInt(b.dataset.bd, 10)); render(); }); });
  var sheet = document.getElementById('signSheet'), btn = document.getElementById('postBtn');
  btn.addEventListener('click', function () {
    var a = f.loc_address.value.trim();
    if (!a) { f.loc_address.focus(); f.loc_address.setAttribute('aria-invalid', 'true'); $('[data-mp-text]').textContent = 'Enter the property address (or use your location) to post.'; return; }
    f.loc_address.removeAttribute('aria-invalid'); save();
    if (btn.dataset.signed === '1') { f.submit(); return; }
    var r = est(); sheet.querySelector('[data-sum-line]').textContent = f.querySelector('[name=size]:checked').parentNode.querySelector('b').textContent + ' · budget $' + last;
    sheet.querySelector('[data-sum-est]').textContent = '$' + r[0] + '–$' + r[1];
    if (sheet.showModal) sheet.showModal(); else sheet.setAttribute('open', '');
  });
  sheet.querySelector('[data-close]').addEventListener('click', function () { sheet.close ? sheet.close() : sheet.removeAttribute('open'); });
  sheet.addEventListener('click', function (e) { if (e.target === sheet) sheet.close(); });
  f.addEventListener('submit', function () { try { sessionStorage.removeItem(key); } catch (e) {} });
}());
</script>
<?php include_once("../includes/footer.php"); ?>
