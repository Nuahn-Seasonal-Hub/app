<?php
// public/provider_route.php — Contractor route for today (snow day) with status buttons and mandatory before and after photos.
// SAMPLE DATA: fixtures/queue.php. Buttons and photo picks update the UI locally only; nothing is saved.
// Sir Paul: GET /api/providers/{id}/route?date=, POST /api/stops/{id}/status, POST /api/bookings/{id}/photos
require_once("../config/init.php");
requireLogin('provider');
$q = nu_fixture('queue');
$done = count(array_filter($q['stops'], function ($s) { return $s['status'] === 'completed'; }));
include_once("../includes/header.php");
?>
<main class="screen">
  <div class="screen__head">
    <div><h1>Today’s route</h1><p><?= nu_e($q['contractor']) ?> · <?= nu_e($q['date']) ?> · <?= nu_icon('snow') ?> <?= nu_e($q['snow']) ?></p></div>
    <?= nu_sample_tag() ?>
  </div>
  <div class="route-sum">
    <div><b><?= count($q['stops']) ?></b><small>Stops</small></div>
    <div><b><?= $done ?></b><small>Done</small></div>
    <div><b>2h 40m</b><small>Remaining</small></div>
  </div>
  <ol class="stops">
<?php foreach ($q['stops'] as $s):
    $cls = $s['status'] === 'completed' ? 'is-done' : ($s['status'] === 'in_progress' ? 'is-active' : ''); ?>
    <li class="stop <?= $cls ?>" data-stop>
      <div class="stop__top">
        <span class="stop__n"><?= $s['status'] === 'completed' ? nu_icon('check-lg') : (int) $s['n'] ?></span>
        <div style="min-width:0">
          <p class="stop__t"><?= nu_e($s['address']) ?></p>
          <p class="stop__s"><?= nu_e($s['client']) ?> · <?= nu_e($s['service']) ?> · <?= nu_e($s['detail']) ?></p>
          <?php if (!empty($s['note'])): ?><p class="stop__s" style="color:var(--warning)"><?= nu_icon('info-circle') ?> <?= nu_e($s['note']) ?></p><?php endif; ?>
        </div>
        <div class="stop__side"><span><?= nu_e($s['window']) ?></span><?= nu_booking_chip($s['status']) ?><?= $s['plan'] === 'Seasonal plan' ? '<span class="chip chip--plain">Seasonal plan</span>' : '' ?></div>
      </div>
<?php if ($s['status'] !== 'completed'): ?>
      <div class="stop__actions" role="group" aria-label="Update status">
        <button type="button" class="btn btn-outline" data-act="omw"><?= nu_icon('truck') ?> On my way</button>
        <button type="button" class="btn btn-outline" data-act="arrived"><?= nu_icon('geo-alt-fill') ?> Arrived</button>
        <button type="button" class="btn btn-outline<?= $s['status'] === 'in_progress' ? ' is-on' : '' ?>" data-act="start"><?= nu_icon('play-fill') ?> Start</button>
        <button type="button" class="btn btn-primary" data-act="complete"><?= nu_icon('check-lg') ?> Complete</button>
      </div>
      <div class="stop__upload">
        <div class="stop__ba">
          <label class="drop"><?= nu_icon('camera') ?> <span>Before photo</span><input type="file" accept="image/*" capture="environment" data-photos="before"></label>
          <label class="drop"><?= nu_icon('camera') ?> <span>After photo</span><input type="file" accept="image/*" capture="environment" data-photos="after"></label>
        </div>
        <div class="thumbs" data-thumbs></div>
        <button type="button" class="btn btn-primary btn-block mt-2" data-act="finish" disabled><?= nu_icon('upload') ?> Submit completion report</button>
        <p class="stop__hint" data-ba-hint><?= nu_icon('info-circle') ?> <span>Add a before photo and an after photo to submit.</span></p>
      </div>
<?php endif; ?>
    </li>
<?php endforeach; ?>
  </ol>
</main>
<script>
document.addEventListener('click', function (e) {
  var b = e.target.closest('[data-act]'); if (!b) return;
  var stop = b.closest('[data-stop]'), a = b.dataset.act;
  stop.querySelectorAll('.stop__actions .btn').forEach(function (x) { if (x.dataset.act !== 'complete') x.classList.remove('is-on'); });
  var chip = stop.querySelector('.stop__side .chip');
  var labels = { omw: 'On the way', arrived: 'Arrived', start: 'In progress', complete: 'Add photos', finish: 'Pending approval' };
  if (a !== 'complete' && a !== 'finish') { b.classList.add('is-on'); stop.classList.add('is-active'); }
  if (a === 'complete') stop.classList.add('is-completing');
  if (a === 'finish') { stop.classList.remove('is-completing', 'is-active'); stop.classList.add('is-done'); }
  if (chip) { chip.textContent = labels[a]; chip.className = 'chip chip--success'; }
});
document.addEventListener('change', function (e) {
  if (!e.target.matches('[data-photos]')) return;
  var stop = e.target.closest('[data-stop]'), kind = e.target.dataset.photos, box = stop.querySelector('[data-thumbs]');
  var old = box.querySelector('[data-k="' + kind + '"]'); if (old) old.remove();
  var f = e.target.files[0]; if (!f) return;
  var fig = document.createElement('figure'); fig.dataset.k = kind; var i = new Image(); i.alt = kind + ' photo'; i.src = URL.createObjectURL(f);
  var c = document.createElement('figcaption'); c.textContent = kind === 'before' ? 'Before' : 'After'; fig.appendChild(i); fig.appendChild(c); box.appendChild(fig);
  e.target.closest('.drop').classList.add('is-set');
  var b = !!box.querySelector('[data-k="before"]'), a = !!box.querySelector('[data-k="after"]');
  stop.querySelector('[data-act="finish"]').disabled = !(a && b);
  var h = stop.querySelector('[data-ba-hint]'); h.hidden = a && b;
  h.querySelector('span').textContent = 'Add ' + [b ? '' : 'a before photo', a ? '' : 'an after photo'].filter(Boolean).join(' and ') + ' to submit.';
});
</script>
<?php include_once("../includes/footer.php"); ?>
