<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/provider_dashboard.php: "Good morning, Team A" (doc1 p4, doc2 p19). Provider console: Today list,
// job detail with the same facts the customer approved (scope, property, utilities, access, approved quote, plan
// mode, window), job progress, completion checklist, before/after photos, notes, extra-work request, Premium
// reminders, availability and a weather-review banner. Submit stays disabled until the after photo is added.
// UI ONLY: SAMPLE DATA from fixtures/provider_day.php; buttons only change UI state ("Pending sync" is simulated).
// Sir Paul: see fixtures/provider_day.php header for endpoints. Job events must be real actions (doc2 p15).
require_once("../config/init.php");
requireLogin('provider');

$d = nu_fixture('provider_day');
$jid = (string) ($_GET['job'] ?? '1042');
if (!isset($d['jobs'][$jid])) $jid = '1042';
$j = $d['jobs'][$jid];
$u = function ($p) { return nu_url('public/' . $p); };
$nav = [
  ['today', 'Today', $u('provider_dashboard.php'), 'calendar-event'], ['jobs', 'Assigned Jobs', $u('provider_home.php'), 'list-ul'],
  ['route', 'Route', $u('provider_route.php'), 'geo-alt'], ['messages', 'Messages', $u('messages.php'), 'chat-square', 2],
  ['availability', 'Availability', '#availability', 'calendar2-week'], ['support', 'Support', $u('chat.php') . '?thread=support', 'question-circle'],
];
$remTone = ['sent' => 'blue', 'scheduled' => '', 'replied' => 'success', 'skipped' => ''];
$done = $j['checklist'][3][1];
include_once("../includes/header.php");
?>
<div class="console">
  <?= nu_console_nav($nav, 'today', 'Provider workspace', 'Seasonal care<br>for a brighter<br>tomorrow.') ?>
  <main class="console__main pd" id="today">
    <header class="pd-head">
      <div><h1>Good morning, <?= nu_e($d['team']) ?></h1><p>Your assigned jobs and customer updates.</p></div>
      <div class="pd-clock"><span><?= nu_e($d['date']) ?><b><?= nu_e($d['time']) ?></b></span><?= nu_icon('brightness-high') ?></div>
    </header>
    <div class="pd-stats">
<?php foreach ($d['stats'] as $i => $s): ?>
      <a class="pd-stat" href="<?= $i === 2 ? '#weather' : nu_e($u('provider_home.php')) ?>"><span class="pd-stat__ic"><?= nu_icon($s[0]) ?></span><span><b><?= (int) $s[1] ?></b> <strong><?= nu_e($s[2]) ?></strong><small><?= nu_e($s[3]) ?></small></span><?= nu_icon('chevron-right') ?></a>
<?php endforeach; ?>
    </div>
    <div class="pd-grid">
      <section class="pd-card pd-list" aria-label="Today’s jobs">
        <h2 class="pd-h2">Today <small><?= nu_e(preg_replace('/^(\w+), /', '$1day, ', $d['date'])) ?></small></h2>
        <ul>
<?php foreach ($d['jobs'] as $k => $job): ?>
          <li><a class="pd-job<?= $k === $jid ? ' is-on' : '' ?>" href="?job=<?= nu_e($k) ?>#job"<?= $k === $jid ? ' aria-current="true"' : '' ?>>
            <span class="pd-job__top"><span><?= nu_e($job['id']) ?><b><?= nu_e($job['service']) ?></b></span><?= $job['status'] === 'weather_review' ? '<span class="chip chip--warning">' . nu_icon('cloud') . ' Weather review</span>' : nu_booking_chip($job['status']) ?></span>
            <span class="pd-job__m"><?= nu_icon('clock') ?> <?= nu_e($job['window']) ?></span><span class="pd-job__m"><?= nu_icon('geo-alt') ?> <?= nu_e($job['where']) ?></span><?= nu_icon('chevron-right', 'pd-job__go') ?></a></li>
<?php endforeach; ?>
        </ul>
        <p class="pd-sync"><?= nu_icon('wifi') ?> Online · last synced <?= nu_e($d['time']) ?></p>
      </section>

      <section class="pd-card pd-job-d" id="job" aria-label="Job detail">
        <div class="pd-job-d__top"><h2><?= nu_e($j['id']) ?> — <?= nu_e($j['service']) ?></h2><?= $j['status'] === 'weather_review' ? '<span class="chip chip--warning">Weather review</span>' : nu_booking_chip($j['status']) ?></div>
        <p class="pd-addr"><?= nu_icon('geo-alt') ?> <?= nu_e($j['where']) ?></p>
        <div class="pd-facts">
          <span><?= nu_icon('list-ul') ?> <b>Scope:</b> <?= nu_e($j['scope']) ?></span>
          <span><?= nu_icon('lock') ?> <b>Access:</b> <?= nu_e($j['access']) ?></span>
        </div>
        <details class="pd-prep"><summary><?= nu_icon('clipboard-check') ?> Work preparation <small>Same facts the customer approved</small></summary>
          <dl><div><dt>Property</dt><dd><?= nu_e($j['property']) ?></dd></div><div><dt>Utilities</dt><dd><?= nu_e($j['utilities']) ?></dd></div><div><dt>Approved quote</dt><dd><?= nu_e($j['quote']) ?></dd></div><div><dt>Plan mode</dt><dd><?= nu_e($j['plan']) ?></dd></div><div><dt>Arrival window</dt><dd><?= nu_e($j['window']) ?></dd></div></dl>
        </details>
<?php if ($j['status'] === 'weather_review'): ?>
        <p class="se-wx se-wx--down"><?= nu_icon('cloud-rain') ?><span><b>Waiting on dispatch</b> Wet ground may delay mowing. Don’t travel until dispatch confirms a time. Next update 10:00 AM.</span></p>
<?php endif; ?>
        <h3 class="pd-h3">Job progress</h3>
        <?= nu_steps(array_map(function ($p) { return [$p, '']; }, $j['progress']), $j['at'], 'pd-steps') ?>
        <div class="pd-acts-top">
<?php if ($j['at'] < 0): ?>
          <button class="btn btn-primary btn-sm" type="button" data-ui-state="Trip started · customer notified"<?= $j['status'] === 'weather_review' ? ' disabled' : '' ?>><?= nu_icon('truck') ?> Start trip</button>
<?php else: ?>
          <button class="btn btn-outline btn-sm" type="button" data-ui-state="Paused · customer sees “Paused”"><?= nu_icon('pause-circle') ?> Pause</button>
          <button class="btn btn-outline btn-sm" type="button" data-ui-state="Delay reported · customer sees new ETA"><?= nu_icon('hourglass-split') ?> Report delay</button>
<?php endif; ?>
          <span class="pd-ui" data-ui-out role="status"></span>
        </div>
        <div class="pd-work">
          <div>
            <h3 class="pd-h3">Job completion checklist</h3>
            <ul class="pd-check">
<?php foreach ($j['checklist'] as $i => $c): ?>
              <li><label class="check"><input type="checkbox"<?= $c[1] ? ' checked' : '' ?><?= $i === 3 ? ' disabled data-after-check' : '' ?>><span class="check__box"><?= nu_icon('check-lg') ?></span><span class="check__t"><?= nu_e($c[0]) ?></span></label></li>
<?php endforeach; ?>
            </ul>
          </div>
          <div>
            <h3 class="pd-h3">Photos</h3>
            <div class="pd-photos">
              <figure><figcaption>Before</figcaption><?= $j['checklist'][0][1] ? nu_photo('work-before', 'Before photo of the driveway', ['sizes' => '160px']) : '<span class="pd-ph">' . nu_icon('image') . 'Not yet</span>' ?></figure>
              <figure><figcaption>After</figcaption><label class="pd-up" data-up><input type="file" accept="image/jpeg,image/png" class="sr-only" data-after><?= nu_icon('camera') ?><b>Upload photo</b><small>JPG or PNG (max 10 MB)</small><img alt="After photo preview" hidden data-prev></label></figure>
            </div>
            <p class="pd-pend" data-pend hidden><?= nu_icon('cloud-arrow-up') ?> <span>Pending sync · will upload when signal returns</span></p>
          </div>
        </div>
        <label class="pd-h3" for="pd-notes">Work notes <small>(optional)</small></label>
        <textarea class="input" id="pd-notes" rows="2" maxlength="500" placeholder="Add any additional notes about the work completed…" data-chars></textarea>
        <span class="counter" aria-hidden="true"><span data-count-out>0</span>/500</span>
        <div class="pd-acts">
          <button class="btn btn-primary" type="button" disabled data-submit>Submit completion report</button>
          <a class="btn btn-outline" href="<?= nu_e($u('messages.php')) ?>"><?= nu_icon('chat-square') ?> Message customer</a>
          <button class="btn btn-outline pd-extra" type="button" data-ui-state="Extra-work request sent to dispatch (preview)"><?= nu_icon('file-earmark-plus') ?><span>Request extra-work approval<small>Sent to dispatch for review</small></span></button>
        </div>
        <p class="pd-hint" data-hint><?= nu_icon('info-circle') ?> Upload the after photo to continue.</p>
      </section>
    </div>

    <section class="pd-banner" id="weather"><?= nu_icon('cloud-snow') ?><div><b>Review weather-sensitive appointments with dispatch</b><span>Weather conditions may impact #1044 (Lawn Mowing) and other scheduled jobs.</span></div><a class="btn btn-outline btn-sm" href="<?= nu_e($u('operations.php')) ?>#weather">View weather updates <?= nu_icon('chevron-right') ?></a></section>

    <div class="pd-two">
      <section class="pd-card">
        <h2 class="pd-h2"><?= nu_icon('bell') ?> Provider reminders <small>Premium customers due for contact</small></h2>
        <table class="pd-tbl"><thead><tr><th>Customer</th><th>Service</th><th>Next / last</th><th>Status</th></tr></thead><tbody>
<?php foreach ($d['reminders'] as $r): ?>
          <tr><td><?= nu_e($r[0]) ?></td><td><?= nu_e($r[1]) ?></td><td><?= nu_e($r[2]) ?></td><td><span class="chip<?= $remTone[$r[3]] ? ' chip--' . $remTone[$r[3]] : '' ?>"><?= nu_e(ucfirst($r[3])) ?></span></td></tr>
<?php endforeach; ?>
        </tbody></table>
        <p class="pd-hint"><?= nu_icon('shield-check') ?> One reminder per cycle; duplicates are never auto-sent.</p>
      </section>
      <section class="pd-card" id="availability">
        <h2 class="pd-h2"><?= nu_icon('calendar2-week') ?> Availability <small>This week</small></h2>
        <ul class="pd-week"><?php foreach ($d['week'] as $w): ?><li class="<?= $w[1] === 'Off' ? 'is-off' : '' ?>"><b><?= $w[0] ?></b><span><?= nu_e($w[1]) ?></span></li><?php endforeach; ?></ul>
        <button class="btn btn-soft btn-sm" type="button" data-ui-state="Availability editor opens here (preview)">Edit availability</button>
      </section>
    </div>
    <p class="ch-demo"><?= nu_sample_tag('Sample day') ?> Jobs: <a href="?job=1042#job">#1042 in progress</a> · <a href="?job=1043#job">#1043 assigned</a> · <a href="?job=1044#job">#1044 weather review</a></p>
  </main>
</div>
<script>
(function () {
  var out = document.querySelector('[data-ui-out]');
  document.querySelectorAll('[data-ui-state]').forEach(function (b) { b.addEventListener('click', function () { out.textContent = b.dataset.uiState; }); });
  var inp = document.querySelector('[data-after]'); if (!inp) return;
  inp.addEventListener('change', function () {
    var f = inp.files && inp.files[0]; if (!f) return;
    var img = document.querySelector('[data-prev]'); img.src = URL.createObjectURL(f); img.hidden = false;
    document.querySelector('[data-up]').classList.add('has-img');
    var pend = document.querySelector('[data-pend]'); pend.hidden = false;
    setTimeout(function () { pend.classList.add('is-ok'); pend.querySelector('span').textContent = 'Uploaded · synced'; }, 1400);
    var c = document.querySelector('[data-after-check]'); c.checked = true;
    document.querySelector('[data-submit]').disabled = false;
    document.querySelector('[data-hint]').hidden = true;
  });
  document.querySelector('[data-submit]').addEventListener('click', function () { out.textContent = 'Completion report submitted · customer can now review (preview)'; this.disabled = true; });
})();
</script>
<?php include_once("../includes/footer.php"); ?>
