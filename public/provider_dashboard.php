<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/provider_dashboard.php: Contractor workspace, "Good morning, Jordan". Independent contractor model.
// Open jobs near you (accept / counter-bid), today's jobs with the same facts the client approved, job progress, completion checklist,
// MANDATORY before and after photos (the completion report stays disabled until at least one of each is uploaded), earnings
// with payout and commission breakdown, seasonal-plan visits and availability.
// UI ONLY: SAMPLE DATA from fixtures/provider_day.php and fixtures/marketplace.php; buttons only change UI state ("Pending sync" is simulated).
// Sir Paul: see fixtures/provider_day.php header for endpoints. Job events must be real actions; the completion endpoint must enforce the photo rule.
require_once("../config/init.php");
requireLogin('provider');

$d = nu_fixture('provider_day');
$jid = (string) ($_GET['job'] ?? '1042');
if (!isset($d['jobs'][$jid])) $jid = '1042';
$j = $d['jobs'][$jid];
$u = function ($p) { return nu_url('public/' . $p); };
$nav = nu_provider_nav();
$mk = nu_fixture('marketplace'); $ctr = nu_contractor($d['contractor']); $first = explode(' ', $ctr['name'])[0];
$pct = (int) $mk['commission_pct']; $po = $mk['payout']; $commission = round($po['total'] * $pct / 100, 2); $net = $po['total'] - $commission;
$remTone = ['sent' => 'blue', 'scheduled' => '', 'replied' => 'success', 'skipped' => ''];
include_once("../includes/header.php");
?>
<div class="console">
  <?= nu_console_nav($nav, 'today', 'Provider workspace', 'Seasonal care<br>for a brighter<br>tomorrow.') ?>
  <main class="console__main pd" id="today">
    <header class="pd-head">
      <div><h1>Good morning, <?= nu_e($first) ?></h1><p>Open jobs near you, your accepted jobs and your earnings.</p><div class="pd-me"><?= nu_badges($ctr) ?><span><?= nu_icon('star-fill') ?> <?= nu_e($ctr['rating']) ?> · <?= (int) $ctr['jobs'] ?> jobs</span></div></div>
      <div class="pd-clock"><span><?= nu_e($d['date']) ?><b><?= nu_e($d['time']) ?></b></span><?= nu_icon('brightness-high') ?></div>
    </header>
    <div class="pd-stats">
<?php $statHref = [$u('open_jobs.php'), '#today-list', '#job']; foreach ($d['stats'] as $i => $s): ?>
      <a class="pd-stat" href="<?= nu_e($statHref[$i]) ?>"><span class="pd-stat__ic"><?= nu_icon($s[0]) ?></span><span><b><?= (int) $s[1] ?></b> <strong><?= nu_e($s[2]) ?></strong><small><?= nu_e($s[3]) ?></small></span><?= nu_icon('chevron-right') ?></a>
<?php endforeach; ?>
    </div>

    <section class="pd-card pd-open" id="open-jobs" aria-label="Open jobs near you">
      <h2 class="pd-h2"><?= nu_icon('megaphone') ?> Open jobs near you <small>Accept at the client’s price or counter-bid</small></h2>
      <div class="oj-list"><?php foreach (array_slice($mk['open_jobs'], 0, 3) as $oj) echo nu_open_job_card($oj); ?></div>
      <a class="btn btn-soft btn-sm pd-open__all" href="<?= nu_e($u('open_jobs.php')) ?>">See all <?= count($mk['open_jobs']) ?> open jobs <?= nu_icon('chevron-right') ?></a>
    </section>

    <div class="pd-grid">
      <section class="pd-card pd-list" id="today-list" aria-label="Your jobs">
        <h2 class="pd-h2">Your jobs <small><?= nu_e($d['date']) ?></small></h2>
        <ul>
<?php foreach ($d['jobs'] as $k => $job): ?>
          <li><a class="pd-job<?= $k === $jid ? ' is-on' : '' ?>" href="?job=<?= nu_e($k) ?>#job"<?= $k === $jid ? ' aria-current="true"' : '' ?>>
            <span class="pd-job__top"><span><?= nu_e($job['id']) ?><b><?= nu_e($job['service']) ?></b></span><?= nu_booking_chip($job['status']) ?></span>
            <span class="pd-job__m"><?= nu_icon('clock') ?> <?= nu_e($job['window']) ?></span><span class="pd-job__m"><?= nu_icon('geo-alt') ?> <?= nu_e($job['where']) ?></span><span class="pd-job__m"><?= nu_icon('lock') ?> <?= nu_e(nu_payment_label($job['payment'])) ?> · $<?= (int) $job['price'] ?></span><?= nu_icon('chevron-right', 'pd-job__go') ?></a></li>
<?php endforeach; ?>
        </ul>
        <p class="pd-sync"><?= nu_icon('wifi') ?> Online · last synced <?= nu_e($d['time']) ?></p>
      </section>

      <section class="pd-card pd-job-d" id="job" aria-label="Job detail" data-st="<?= nu_e($j['status']) ?>">
        <div class="pd-job-d__top"><h2><?= nu_e($j['id']) ?>: <?= nu_e($j['service']) ?></h2><span data-job-chip><?= nu_booking_chip($j['status']) ?></span></div>
        <p class="pd-addr"><?= nu_icon('geo-alt') ?> <?= nu_e($j['where']) ?></p>
        <div class="pd-facts">
          <span><?= nu_icon('list-ul') ?> <b>Scope:</b> <?= nu_e($j['scope']) ?></span>
          <span><?= nu_icon('lock') ?> <b>Access:</b> <?= nu_e($j['access']) ?></span>
          <span data-pay-chip><?= nu_payment_chip($j['payment']) ?></span>
        </div>
        <details class="pd-prep"><summary><?= nu_icon('clipboard-check') ?> Work preparation <small>Same facts the client approved</small></summary>
          <dl><div><dt>Property</dt><dd><?= nu_e($j['property']) ?></dd></div><div><dt>Agreed price</dt><dd><?= nu_e($j['quote']) ?></dd></div><div><dt>Plan mode</dt><dd><?= nu_e($j['plan']) ?></dd></div><div><dt>Arrival window</dt><dd><?= nu_e($j['window']) ?></dd></div></dl>
        </details>
<?php if ($j['status'] === 'open_for_bids'): ?>
        <p class="pd-note"><?= nu_icon('hourglass-split') ?><span><b>Offer sent.</b> The client is comparing offers. You will be notified if they accept; the exact address and access notes are shared once you are confirmed.</span></p>
        <div class="pd-acts"><a class="btn btn-outline" href="<?= nu_e($u('messages.php')) ?>"><?= nu_icon('chat-square') ?> Message client</a><a class="btn btn-ghost" href="<?= nu_e($u('open_jobs.php')) ?>">Back to open jobs</a></div>
<?php else: ?>
        <p class="pd-note pd-note--lock"><?= nu_icon('lock-fill') ?><span><b>Funds held securely.</b> The client’s payment is held until they approve your work, then paid out to you.</span></p>
        <h3 class="pd-h3">Job progress</h3>
        <?= nu_steps(array_map(function ($p) { return [$p, '']; }, $j['progress']), $j['at'], 'pd-steps') ?>
        <div class="pd-acts-top">
<?php if ($j['at'] < 0): ?>
          <button class="btn btn-primary btn-sm" type="button" data-ui-state="Trip started · client notified"><?= nu_icon('truck') ?> Start trip</button>
<?php else: ?>
          <button class="btn btn-outline btn-sm" type="button" data-ui-state="Paused · client sees “Paused”"><?= nu_icon('pause-circle') ?> Pause</button>
          <button class="btn btn-outline btn-sm" type="button" data-ui-state="Delay reported · client sees the new ETA"><?= nu_icon('hourglass-split') ?> Report delay</button>
<?php endif; ?>
          <span class="pd-ui" data-ui-out role="status"></span>
        </div>
        <div class="pd-work">
          <div>
            <h3 class="pd-h3">Job completion checklist</h3>
            <ul class="pd-check">
<?php foreach ($j['checklist'] as $c): ?>
              <li><label class="check"><input type="checkbox"<?= $c[1] ? ' checked' : '' ?>><span class="check__box"><?= nu_icon('check-lg') ?></span><span class="check__t"><?= nu_e($c[0]) ?></span></label></li>
<?php endforeach; ?>
              <li><label class="check"><input type="checkbox" disabled data-before-check><span class="check__box"><?= nu_icon('check-lg') ?></span><span class="check__t">Before photo uploaded</span></label></li>
              <li><label class="check"><input type="checkbox" disabled data-after-check><span class="check__box"><?= nu_icon('check-lg') ?></span><span class="check__t">After photo uploaded</span></label></li>
            </ul>
          </div>
          <div>
            <h3 class="pd-h3">Photos <small>required: one before and one after</small></h3>
            <div class="pd-photos">
              <figure><figcaption>Before</figcaption><label class="pd-up" data-up="before"><input type="file" accept="image/jpeg,image/png" class="sr-only" data-before><?= nu_icon('camera') ?><b>Add before photo</b><small>JPG or PNG (max 10 MB)</small><img alt="Before photo preview" hidden data-prev="before"></label></figure>
              <figure><figcaption>After</figcaption><label class="pd-up" data-up="after"><input type="file" accept="image/jpeg,image/png" class="sr-only" data-after><?= nu_icon('camera') ?><b>Add after photo</b><small>JPG or PNG (max 10 MB)</small><img alt="After photo preview" hidden data-prev="after"></label></figure>
            </div>
            <p class="pd-pend" data-pend hidden><?= nu_icon('cloud-arrow-up') ?> <span>Pending sync · will upload when signal returns</span></p>
          </div>
        </div>
        <label class="pd-h3" for="pd-notes">Work notes <small>(optional)</small></label>
        <textarea class="input" id="pd-notes" rows="2" maxlength="500" placeholder="Add any notes for the client…" data-chars></textarea>
        <span class="counter" aria-hidden="true"><span data-count-out>0</span>/500</span>
        <div class="pd-acts">
          <button class="btn btn-primary" type="button" disabled data-submit>Submit completion report</button>
          <a class="btn btn-outline" href="<?= nu_e($u('messages.php')) ?>"><?= nu_icon('chat-square') ?> Message client</a>
          <button class="btn btn-outline pd-extra" type="button" data-ui-state="Extra-work request sent · the client approves the new amount first (preview)"><?= nu_icon('file-earmark-plus') ?><span>Request extra-work approval<small>The client approves before you start</small></span></button>
        </div>
        <p class="pd-hint" data-hint><?= nu_icon('info-circle') ?> <span>Add a before photo and an after photo to submit.</span></p>
<?php endif; ?>
      </section>
    </div>

    <section class="pd-banner" id="weather"><?= nu_icon('cloud-snow') ?><div><b>Snowfall expected tonight</b><span>Expect more open jobs near you from 6 AM. Seasonal-plan visits start once 5 cm has fallen.</span></div><a class="btn btn-outline btn-sm" href="<?= nu_e($u('open_jobs.php')) ?>">See open jobs <?= nu_icon('chevron-right') ?></a></section>

    <section class="pd-card pd-earn" id="earnings" aria-label="Earnings">
      <h2 class="pd-h2"><?= nu_icon('wallet2') ?> Earnings <small>Payout and commission breakdown <?= nu_sample_tag() ?></small></h2>
      <div class="pd-earn__grid">
        <div>
          <h3 class="pd-h3">Job <?= nu_e($po['job']) ?></h3>
          <dl class="ap-sum">
            <div><dt>Job total</dt><dd>$<?= number_format($po['total'], 2) ?></dd></div>
            <div><dt>Nuahn commission (<?= $pct ?>%)</dt><dd>−$<?= number_format($commission, 2) ?></dd></div>
            <div class="is-total"><dt>Your payout</dt><dd>$<?= number_format($net, 2) ?></dd></div>
          </dl>
          <p class="pd-hint"><?= nu_icon('lock-fill') ?> <?= nu_payment_chip('authorized') ?> Released when the client approves your work.</p>
          <p class="pd-hint"><?= nu_icon('info-circle') ?> <?= nu_e($po['tax_note']) ?></p>
        </div>
        <div>
          <h3 class="pd-h3">Recent payouts</h3>
          <ul class="pd-pay">
<?php foreach ($mk['payouts'] as $r): ?>
            <li><span><b><?= nu_e($r[0]) ?></b><small><?= nu_e($r[1]) ?> · <?= nu_e($r[4]) ?></small></span><span class="pd-pay__a"><b>$<?= number_format($r[2] * (100 - $pct) / 100, 2) ?></b><?= nu_payment_chip($r[3]) ?></span></li>
<?php endforeach; ?>
          </ul>
        </div>
      </div>
    </section>

    <div class="pd-two">
      <section class="pd-card">
        <h2 class="pd-h2"><?= nu_icon('snow') ?> Seasonal-plan visits <small>Clients who chose a snowfall trigger</small></h2>
        <table class="pd-tbl"><thead><tr><th>Client</th><th>Plan</th><th>Next / last</th><th>Status</th></tr></thead><tbody>
<?php foreach ($d['plan_visits'] as $r): ?>
          <tr><td><?= nu_e($r[0]) ?></td><td><?= nu_e($r[1]) ?></td><td><?= nu_e($r[2]) ?></td><td><span class="chip<?= $remTone[$r[3]] ? ' chip--' . $remTone[$r[3]] : '' ?>"><?= nu_e(ucfirst($r[3])) ?></span></td></tr>
<?php endforeach; ?>
        </tbody></table>
        <p class="pd-hint"><?= nu_icon('shield-check') ?> One visit per snow event; under 5 cm the visit is skipped automatically.</p>
      </section>
      <section class="pd-card" id="availability">
        <h2 class="pd-h2"><?= nu_icon('calendar2-week') ?> Availability <small>This week</small></h2>
        <ul class="pd-week"><?php foreach ($d['week'] as $w): ?><li class="<?= $w[1] === 'Off' ? 'is-off' : '' ?>"><b><?= $w[0] ?></b><span><?= nu_e($w[1]) ?></span></li><?php endforeach; ?></ul>
        <button class="btn btn-soft btn-sm" type="button" data-ui-state="Availability editor opens here (preview)">Edit availability</button>
      </section>
    </div>
    <p class="ch-demo"><?= nu_sample_tag('Sample day') ?> Jobs: <a href="?job=1042#job">NSH-1042 in progress</a> · <a href="?job=1045#job">SNOW-1045 confirmed</a> · <a href="?job=2051#job">TASK-2051 offer sent</a></p>
  </main>
</div>
<script src="<?= nu_e(nu_asset('public/assets/jobs.js')) ?>" defer></script>
<script>
(function () {
  var out = document.querySelector('[data-ui-out]') || document.createElement('span');
  document.querySelectorAll('[data-ui-state]').forEach(function (b) { b.addEventListener('click', function () { out.textContent = b.dataset.uiState; }); });
  var sub = document.querySelector('[data-submit]'); if (!sub) return;
  var got = { before: false, after: false }, hint = document.querySelector('[data-hint] span');
  function refresh() {
    document.querySelector('[data-before-check]').checked = got.before; document.querySelector('[data-after-check]').checked = got.after;
    sub.disabled = !(got.before && got.after);
    var need = [!got.before ? 'a before photo' : '', !got.after ? 'an after photo' : ''].filter(Boolean);
    document.querySelector('[data-hint]').hidden = !need.length; if (need.length) hint.textContent = 'Add ' + need.join(' and ') + ' to submit.';
  }
  ['before', 'after'].forEach(function (k) {
    var inp = document.querySelector('[data-' + k + ']');
    inp.addEventListener('change', function () {
      var f = inp.files && inp.files[0]; if (!f) return;
      var img = document.querySelector('[data-prev="' + k + '"]'); img.src = URL.createObjectURL(f); img.hidden = false;
      document.querySelector('[data-up="' + k + '"]').classList.add('has-img'); got[k] = true;
      var pend = document.querySelector('[data-pend]'); pend.hidden = false; pend.classList.remove('is-ok'); pend.querySelector('span').textContent = 'Pending sync · will upload when signal returns';
      setTimeout(function () { pend.classList.add('is-ok'); pend.querySelector('span').textContent = 'Uploaded · synced'; }, 1400);
      refresh();
    });
  });
  refresh();
  sub.addEventListener('click', function () {
    out.textContent = 'Completion report submitted · the client can now review and approve (preview)'; this.disabled = true;
    document.querySelector('[data-job-chip]').innerHTML = <?= json_encode(nu_booking_chip('pending_approval')) ?>; document.querySelector('[data-hint]').hidden = true;
  });
})();
</script>
<?php include_once("../includes/footer.php"); ?>
