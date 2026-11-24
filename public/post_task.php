<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/post_task.php: 5-step guided wizard to post a snow-removal task with a target budget.
// GUEST EXPLORATION: anyone can walk all 5 steps, see live estimates & boost speed, and sign in / create an account
// only on Step 5. The full draft (including notes & step) is kept in sessionStorage so it survives signup/sign-in.
require_once("../config/init.php");
require_once("../includes/marketplace/core.php");
require_once("../includes/onboarding.php");
$m = nu_fixture('marketplace');
$sn = $m['snow'];
$signedIn = isset($_SESSION['user_id']);
$isClient = $signedIn && isRole('client');
$wrongRole = $signedIn && !$isClient;

// Pre-fill property address from homepage search (?address=) or signed-in client's saved area
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

$cfg = [
    'sizes' => array_map(fn($s) => ['lo' => $s['lo'], 'hi' => $s['hi'], 'sq' => $s['per_sqft'] ?? null], $sn['sizes']),
    'walk' => array_map(fn($w) => [$w[1], $w[2]], $sn['walk']),
    'steps' => array_values($sn['steps'])[0], 'ice' => [$sn['ice']['lo'], $sn['ice']['hi']],
    'disc' => $sn['seasonal_discount'], 'pickup' => $sn['pickup'], 'boostMax' => $sn['boost_max'], 'trigger' => $sn['trigger_cm'],
];
$next = rawurlencode('public/post_task.php?draft=1');
$wizSteps = [
    1 => ['Location',       'Property address'],
    2 => ['Driveway Size',  'Area to clear'],
    3 => ['Walkways & Ice', 'Steps & add-ons'],
    4 => ['Schedule',       'Timing & notes'],
    5 => ['Budget & Post',  'Target offer'],
];
include_once("../includes/header.php");
?>
<main class="screen pt pt--wiz">
  <p class="crumbs"><a href="<?= nu_e(nu_url('public/service.php')) ?>?s=snow-removal">Winter Snow Removal</a> / Post a task</p>
  <div class="screen__head">
    <div>
      <h1>Post a snow task</h1>
      <p>Five quick steps to describe your property and target budget. Verified local contractors send offers.</p>
    </div>
  </div>

  <?php if ($wrongRole): ?>
  <p class="of-posted" role="status"><?= nu_icon('info-circle') ?> <span>You are signed in as <b><?= nu_e(isRole('provider') ? 'a contractor' : 'staff') ?></b>. Posting tasks needs a client account.</span></p>
  <?php endif; ?>

  <div class="alert alert-success" id="ptRestored" hidden role="status">
    <?= nu_icon('check-circle-fill') ?> <span><b>Welcome back!</b> Your snow task draft is restored. Review your budget below and tap <b>Post task</b> to send it to local contractors.</span>
  </div>

  <div class="pt-wiz">
    <!-- Left Stepper Rail + Live Estimate Summary (sticky on desktop, compact strip on mobile) -->
    <aside class="pt-wiz__rail" aria-label="Task steps">
      <div class="sv-card pt-rail-card">
        <div class="pt-rail__mob">
          <span class="pt-rail__stepcount" data-step-count>Step 1 of 5</span>
          <b data-step-title>Location</b>
          <div class="ob-progress" aria-hidden="true"><span data-step-bar style="width:20%"></span></div>
        </div>
        <ol class="pt-rail" id="ptRail">
<?php foreach ($wizSteps as $snNum => [$snLbl, $snSub]): ?>
          <li class="<?= $snNum === 1 ? 'is-active' : '' ?>" data-rail-step="<?= $snNum ?>">
            <button type="button" class="pt-rail__btn" data-jump="<?= $snNum ?>">
              <span class="pt-rail__dot"><?= $snNum ?></span>
              <span><b><?= nu_e($snLbl) ?></b><small><?= nu_e($snSub) ?></small></span>
            </button>
          </li>
<?php endforeach; ?>
        </ol>
      </div>

      <div class="sv-card pt-rail-est" aria-live="polite">
        <small>Estimated price range</small>
        <strong data-est>$55–$85</strong>
        <span data-planline>one clear</span>
        <p class="pt-rail-est__note"><?= nu_icon('lock-fill') ?> Nothing is charged when you post. Funds are held only after you accept an offer.</p>
      </div>
    </aside>

    <!-- Active Step Card -->
    <div class="pt-wiz__main">
      <form class="pt-form" id="ptForm" method="post" action="<?= nu_e(nu_url('actions/marketplace.php')) ?>" data-pt='<?= nu_e(json_encode($cfg)) ?>' data-signed="<?= $isClient ? '1' : '0' ?>" novalidate>
        <?= mk_csrf_field() ?><input type="hidden" name="do" value="snow_post">

        <!-- STEP 1: Property Location -->
        <section class="sv-card pt-sec pt-pane" data-pt-step="1">
          <h2 class="pt-h"><span class="pt-n">1</span> Where is the property?</h2>
          <p class="mk-sub" style="margin-bottom:var(--s4)">Enter your street address or drop a map pin so nearby snow contractors can quote accurately.</p>
          <?= nu_map_picker(['name' => 'loc', 'address' => $prefillAddr, 'label' => 'Property address']) ?>
          <div class="pt-wiz__nav">
            <button class="btn btn-primary btn-lg" type="button" data-pt-next="2">Continue to driveway size <?= nu_icon('arrow-right') ?></button>
          </div>
        </section>

        <!-- STEP 2: Driveway Size -->
        <section class="sv-card pt-sec pt-pane" data-pt-step="2" hidden>
          <h2 class="pt-h"><span class="pt-n">2</span> Driveway size</h2>
          <p class="mk-sub" style="margin-bottom:var(--s4)">Choose the size that best matches your driveway. Your suggested budget updates automatically.</p>
          <div class="mk-opts mk-opts--3" role="radiogroup" aria-label="Driveway size">
            <?php foreach ($sn['sizes'] as $k => $s): ?>
              <label class="mk-opt"><input type="radio" name="size" value="<?= nu_e($k) ?>" <?= $k === 'standard' ? 'checked' : '' ?>><span><?= nu_icon($s['icon']) ?><b><?= nu_e($s['label']) ?></b><small><?= nu_e($s['sub']) ?></small></span></label>
            <?php endforeach; ?>
          </div>
          <div class="pt-sqft" data-sqft hidden>
            <label class="label" for="sqft">Approximate area (sq ft)</label>
            <input class="input" id="sqft" name="sqft" type="number" inputmode="numeric" min="400" step="50" value="2500">
          </div>
          <div class="pt-wiz__nav">
            <button class="btn btn-outline btn-lg" type="button" data-pt-prev="1">Back</button>
            <button class="btn btn-primary btn-lg" type="button" data-pt-next="3">Continue to walkways <?= nu_icon('arrow-right') ?></button>
          </div>
        </section>

        <!-- STEP 3: Walkways, Steps & Ice -->
        <section class="sv-card pt-sec pt-pane" data-pt-step="3" hidden>
          <h2 class="pt-h"><span class="pt-n">3</span> Walkways, steps and ice</h2>
          <p class="mk-sub" style="margin-bottom:var(--s4)">Include any walkways, stairs, or ice melt you’d like cleared along with your driveway.</p>
          <div class="mk-opts mk-opts--3" role="radiogroup" aria-label="Walkways">
            <?php foreach ($sn['walk'] as $k => $w): ?>
              <label class="mk-opt"><input type="radio" name="walk" value="<?= nu_e($k) ?>" <?= $k === 'front' ? 'checked' : '' ?>><span><b><?= nu_e($w[0]) ?></b><small><?= $w[1] ? '+$' . $w[1] . '–' . $w[2] : 'Driveway only' ?></small></span></label>
            <?php endforeach; ?>
          </div>
          <label class="mk-check"><input type="checkbox" name="steps" value="1" checked><span><b>Steps and stairs</b><small>Cleared and brushed down. +$<?= array_values($sn['steps'])[0][0] ?>–<?= array_values($sn['steps'])[0][1] ?></small></span></label>
          <label class="mk-check"><input type="checkbox" name="ice" value="1" checked><span><b>Ice melt add-on</b><small><?= nu_e($sn['ice']['label']) ?>. +$<?= $sn['ice']['lo'] ?>–<?= $sn['ice']['hi'] ?></small></span></label>
          <div class="pt-wiz__nav">
            <button class="btn btn-outline btn-lg" type="button" data-pt-prev="2">Back</button>
            <button class="btn btn-primary btn-lg" type="button" data-pt-next="4">Continue to schedule <?= nu_icon('arrow-right') ?></button>
          </div>
        </section>

        <!-- STEP 4: Schedule & Notes -->
        <section class="sv-card pt-sec pt-pane" data-pt-step="4" hidden>
          <h2 class="pt-h"><span class="pt-n">4</span> When &amp; details</h2>
          <p class="mk-sub" style="margin-bottom:var(--s4)">Pick a one-time clear or a winter seasonal plan, and add any helpful notes for your contractor.</p>
          <div class="mk-opts mk-opts--2" role="radiogroup" aria-label="Scheduling">
            <label class="mk-opt"><input type="radio" name="plan" value="demand" checked><span><?= nu_icon('lightning-charge-fill') ?><b>On demand</b><small>One clear, as soon as a contractor can</small></span></label>
            <label class="mk-opt"><input type="radio" name="plan" value="seasonal"><span><?= nu_icon('cloud-snow') ?><b>Seasonal plan</b><small>Every snowfall of <?= (int) $sn['trigger_cm'] ?> cm or more, all winter</small></span></label>
          </div>
          <p class="pt-info" data-seasonal hidden><?= nu_icon('thermometer-snow') ?> <span>A contractor clears after every snowfall of <b><?= (int) $sn['trigger_cm'] ?> cm</b> or more, based on the local forecast and report. Seasonal plans save about <?= (int) round($sn['seasonal_discount'] * 100) ?>% per visit. You pay per visit, held until you approve it.</span></p>

          <label class="field mt-4" for="pt-notes">
            <span class="label">Special instructions <small class="ob-sub-inline">(optional — specific details get more accurate offers)</small></span>
            <textarea class="input" id="pt-notes" name="notes" rows="3" placeholder="Gate code, where to pile the snow, a car in the driveway..."></textarea>
          </label>

          <div class="pt-wiz__nav">
            <button class="btn btn-outline btn-lg" type="button" data-pt-prev="3">Back</button>
            <button class="btn btn-primary btn-lg" type="button" data-pt-next="5">Continue to budget <?= nu_icon('arrow-right') ?></button>
          </div>
        </section>

        <!-- STEP 5: Target Budget, Boost & Post -->
        <section class="sv-card pt-sec pt-budget pt-pane" data-pt-step="5" hidden>
          <h2 class="pt-h"><span class="pt-n">5</span> Your target budget</h2>

          <div class="pt-recap" id="ptRecap">
            <div><small>Property</small><b data-recap-addr>—</b></div>
            <div><small>Scope</small><b data-recap-scope>Standard driveway</b></div>
            <div><small>Schedule</small><b data-recap-plan>On demand</b></div>
          </div>

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

          <div class="pt-wiz__nav mt-4">
            <button class="btn btn-outline btn-lg" type="button" data-pt-prev="4">Back</button>
            <button class="btn btn-primary btn-lg" type="button" id="postBtn" data-signed="<?= $isClient ? '1' : '0' ?>"<?= $wrongRole ? ' disabled' : '' ?>><?= nu_icon('send-fill') ?> <?= $isClient ? 'Post task' : 'Sign in or sign up to post' ?></button>
            <button class="btn btn-primary btn-lg" type="submit" id="postGo" hidden>Post task</button>
          </div>

          <div class="mt-4"><?= nu_trust_note('held') ?></div>
        </section>
      </form>
    </div>
  </div>

  <dialog class="msheet" id="signSheet" aria-labelledby="signT">
    <div class="msheet__in">
      <button class="msheet__x" type="button" data-close aria-label="Close"><?= nu_icon('x-lg') ?></button>
      <span class="msheet__ic"><?= nu_icon('person-circle') ?></span>
      <h2 id="signT">Finish posting your task</h2>
      <p>Your address, scope, notes and budget are saved. Create a free account (email or SMS OTP) or sign in and you’ll return straight to Step 5 to send it in one tap.</p>
      <div class="msheet__sum"><span data-sum-line>Standard driveway</span><b data-sum-est></b></div>
      <a class="btn btn-primary btn-block" href="<?= nu_e(nu_url('public/register.php')) ?>?next=<?= $next ?>">Create free account <?= nu_icon('arrow-right') ?></a>
      <a class="btn btn-outline btn-block" href="<?= nu_e(nu_url('public/login.php')) ?>?next=<?= $next ?>">Already have an account? Sign in</a>
      <p class="msheet__fine"><?= nu_icon('lock-fill') ?> Nothing is charged when you post. Funds are held only after you accept an offer.</p>
    </div>
  </dialog>
</main>
<script src="<?= nu_e(nu_asset('public/assets/map-picker.js')) ?>" defer></script>
<script>
(function () {
  var f = document.getElementById('ptForm'), C = JSON.parse(f.dataset.pt), $ = function (s) { return f.querySelector(s); };
  var bd = $('#budget'), boost = $('#boost'), notes = $('#pt-notes'), key = 'nuahn.taskDraft';
  var panes = Array.from(f.querySelectorAll('.pt-pane'));
  var railItems = Array.from(document.querySelectorAll('#ptRail li'));
  var stepTitles = { 1: 'Location', 2: 'Driveway Size', 3: 'Walkways & Ice', 4: 'Schedule & Notes', 5: 'Budget & Post' };
  var step = 1, maxReached = 1;

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
    document.querySelectorAll('[data-est]').forEach(function (el) { el.textContent = '$' + r[0] + '–$' + r[1]; });
    document.querySelectorAll('[data-planline]').forEach(function (el) { el.textContent = seasonal ? 'per visit · every ' + C.trigger + ' cm snowfall' : 'one clear'; });
    $('[data-boosttotal]').textContent = '$' + tot + (bo ? ' (' + b + ' + ' + bo + ')' : '');

    var ratio = tot / mid, t = C.pickup[C.pickup.length - 1], idx = C.pickup.length - 1;
    for (var i = 0; i < C.pickup.length; i++) if (ratio < C.pickup[i][0]) { t = C.pickup[i]; idx = i; break; }
    $('[data-pick]').textContent = t[1];
    var m = $('.pt-meter'); m.className = 'pt-meter is-' + t[2]; m.querySelectorAll('i').forEach(function (e, j) { e.classList.toggle('on', j <= idx); });
    var h = $('[data-hint]');
    h.textContent = b < r[0] * 0.8 ? 'This is below what most contractors accept. Raise it or add a boost.' : (b > r[1] ? 'Above the typical range, so offers should arrive quickly.' : 'Inside the typical range. You can still add a boost.');
    h.className = 'pt-hint' + (b < r[0] * 0.8 ? ' is-low' : '');
    $('[data-seasonal]').hidden = !seasonal;
    $('[data-sqft]').hidden = val('size') !== 'commercial';

    // Update Step 5 recap
    var sizeLbl = f.querySelector('[name=size]:checked').parentNode.querySelector('b').textContent;
    var walkLbl = f.querySelector('[name=walk]:checked').parentNode.querySelector('b').textContent;
    $('[data-recap-addr]').textContent = f.loc_address.value.trim() || 'Add address on Step 1';
    $('[data-recap-scope]').textContent = sizeLbl + ' · ' + walkLbl + (f.steps.checked ? ' · Steps' : '') + (f.ice.checked ? ' · Ice melt' : '');
    $('[data-recap-plan]').textContent = seasonal ? 'Seasonal plan (' + C.trigger + ' cm)' : 'On demand';

    last = tot;
    save();
  }

  function showStep(n) {
    step = Math.max(1, Math.min(5, n));
    if (step > maxReached) maxReached = step;
    panes.forEach(function (p) { p.hidden = Number(p.dataset.ptStep) !== step; });
    railItems.forEach(function (li) {
      var k = Number(li.dataset.railStep);
      li.classList.toggle('is-active', k === step);
      li.classList.toggle('is-done', k < step);
    });
    var sc = document.querySelector('[data-step-count]');
    var st = document.querySelector('[data-step-title]');
    var sb = document.querySelector('[data-step-bar]');
    if (sc) sc.textContent = 'Step ' + step + ' of 5';
    if (st) st.textContent = stepTitles[step] || '';
    if (sb) sb.style.width = (step * 20) + '%';
    save();
    window.scrollTo({ top: Math.max(0, document.querySelector('.pt-wiz').offsetTop - 90), behavior: 'smooth' });
  }

  function validateLocation() {
    var a = f.loc_address.value.trim();
    if (!a) {
      f.loc_address.focus();
      f.loc_address.setAttribute('aria-invalid', 'true');
      var txt = $('[data-mp-text]');
      if (txt) txt.textContent = 'Enter the property address (or use your location) to continue.';
      return false;
    }
    f.loc_address.removeAttribute('aria-invalid');
    return true;
  }

  function save() {
    try {
      sessionStorage.setItem(key, JSON.stringify({
        size: val('size'), walk: val('walk'), plan: val('plan'),
        steps: f.steps.checked, ice: f.ice.checked,
        budget: bd.value, boost: boost.value,
        addr: f.loc_address.value, sqft: f.sqft.value,
        notes: notes ? notes.value : '',
        touched: touched, step: step
      }));
    } catch (e) {}
  }

  var restored = false;
  function load() {
    try {
      var d = JSON.parse(sessionStorage.getItem(key) || 'null');
      if (!d) return;
      ['size', 'walk', 'plan'].forEach(function (n) { var e = f.querySelector('[name=' + n + '][value=' + d[n] + ']'); if (e) e.checked = true; });
      f.steps.checked = !!d.steps;
      f.ice.checked = !!d.ice;
      if (d.addr && !f.loc_address.value) f.loc_address.value = d.addr;
      else if (d.addr && /draft=1/.test(location.search)) f.loc_address.value = d.addr;
      f.sqft.value = d.sqft || 2500;
      boost.value = d.boost || 0;
      if (notes && d.notes) notes.value = d.notes;
      touched = !!d.touched;
      if (touched && d.budget) bd.value = d.budget;
      restored = true;
    } catch (e) {}
  }

  load();
  try {
    var qs = new URLSearchParams(location.search);
    if (qs.get('plan') === 'seasonal') {
      var sp = f.querySelector('[name=plan][value=seasonal]');
      if (sp) sp.checked = true;
    }
    if (qs.get('draft') === '1' && restored) {
      maxReached = 5;
      step = 5;
      if (f.dataset.signed === '1') {
        var rb = document.getElementById('ptRestored');
        if (rb) rb.hidden = false;
      }
    }
  } catch (e) {}

  render();
  showStep(step);

  f.querySelectorAll('[data-pt-next]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (step === 1 && !validateLocation()) return;
      showStep(Number(btn.dataset.ptNext));
    });
  });
  f.querySelectorAll('[data-pt-prev]').forEach(function (btn) {
    btn.addEventListener('click', function () { showStep(Number(btn.dataset.ptPrev)); });
  });
  document.querySelectorAll('[data-jump]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var target = Number(btn.dataset.jump);
      if (target > 1 && !validateLocation()) { showStep(1); return; }
      showStep(target);
    });
  });

  f.addEventListener('change', function (e) { if (e.target === bd) touched = true; render(); });
  f.addEventListener('input', function (e) {
    if (e.target === bd) touched = true;
    if (e.target === boost || e.target === bd || e.target.id === 'sqft' || e.target === notes || e.target === f.loc_address) render();
  });
  f.addEventListener('mp:change', render);
  f.querySelectorAll('[data-bd]').forEach(function (b) {
    b.addEventListener('click', function () {
      touched = true;
      bd.value = Math.max(10, (parseInt(bd.value, 10) || 0) + parseInt(b.dataset.bd, 10));
      render();
    });
  });

  var sheet = document.getElementById('signSheet'), btn = document.getElementById('postBtn');
  btn.addEventListener('click', function () {
    if (!validateLocation()) { showStep(1); return; }
    save();
    if (btn.dataset.signed === '1') { f.submit(); return; }
    var r = est();
    sheet.querySelector('[data-sum-line]').textContent = f.querySelector('[name=size]:checked').parentNode.querySelector('b').textContent + ' · budget $' + last;
    sheet.querySelector('[data-sum-est]').textContent = '$' + r[0] + '–$' + r[1];
    if (sheet.showModal) sheet.showModal(); else sheet.setAttribute('open', '');
  });
  sheet.querySelector('[data-close]').addEventListener('click', function () { sheet.close ? sheet.close() : sheet.removeAttribute('open'); });
  sheet.addEventListener('click', function (e) { if (e.target === sheet) sheet.close(); });
  f.addEventListener('submit', function () { try { sessionStorage.removeItem(key); } catch (e) {} });
}());
</script>
<?php include_once("../includes/footer.php"); ?>
