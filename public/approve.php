<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/approve.php: "Review and approve" (client). The job is Completed, pending approval; payment is Authorized (funds held).
// Shows the contractor's before and after photos and the checklist, then [Approve and release payment] or [Report a problem].
// States (?state=): pending (default) | dispute (report-a-problem form open) | approved (payment captured and paid out) |
// disputed (case opened, funds stay held). The buttons also switch the view locally (preview only, nothing is saved).
// SAMPLE DATA: fixtures/proof_of_work.php and fixtures/tracking.php. Card details never touch Nuahn: UI copy only, no processor names or logos.
// Sir Paul: GET /api/jobs/{id}/completion (photos, checklist, totals),
//           POST /api/jobs/{id}/approve {rating, tip?, comment?} -> capture the held authorization, queue the contractor payout, job = Approved,
//           POST /api/jobs/{id}/dispute {reason, outcome, details, photos[]} -> job = Disputed, payment = Disputed (funds stay held), opens a case in the disputes queue.
require_once("../config/init.php");
requireLogin('client');

$pow = nu_fixture('proof_of_work'); $trk = nu_fixture('tracking'); $m = nu_fixture('marketplace');
$id = preg_replace('/[^A-Za-z0-9\-]/', '', (string) ($_GET['id'] ?? 'SNOW-1035')) ?: 'SNOW-1035';
$view = in_array($_GET['state'] ?? 'pending', ['pending', 'dispute', 'approved', 'disputed'], true) ? ($_GET['state'] ?? 'pending') : 'pending';
$ctr = nu_contractor('c-1002');
$amount = (float) $pow['payment']['amount']; $tax = (float) $pow['payment']['tax']; $total = $amount + $tax;
$sv = nu_fixture('services')['snow-removal'];
$reasons = ['Work not finished', 'Area missed (steps, walkway)', 'Damage to property', 'Contractor did not match the offer', 'Other'];
include_once("../includes/header.php");
?>
<main class="screen ap" id="approve" data-view="<?= nu_e($view) ?>">
  <p class="crumbs"><a href="<?= nu_e(nu_url('public/bookings.php')) ?>">My bookings</a> / Review and approve</p>
  <div class="screen__head"><div><h1>Review and approve</h1><p>#<?= nu_e($id) ?> · <?= nu_e($sv['name']) ?> · <a href="<?= nu_e(nu_url('public/contractor.php')) ?>?id=<?= nu_e($ctr['id']) ?>"><?= nu_e($ctr['name']) ?></a></p></div><?= nu_sample_tag() ?></div>

  <section class="sv-card ap-top" aria-label="Job and payment status">
    <div class="ap-top__row">
      <span class="ap-top__ic"><?= nu_icon('snow') ?></span>
      <div><b>Completed · pending your approval</b><span><?= nu_icon('geo-alt') ?> <?= nu_e($trk['address']) ?></span></div>
      <span data-pay-chip><?= nu_payment_chip('authorized') ?></span>
    </div>
    <div data-jsm><?= nu_job_machine('pending_approval') ?></div>
  </section>

  <div class="ap-grid">
    <div class="ap-main">
      <section class="sv-card" id="ap-proof">
        <h2 class="tk-h"><?= nu_icon('images') ?> Before and after</h2>
        <div class="ba">
          <figure><?= nu_photo($pow['before']['photo'], $pow['before']['alt'], ['sizes' => '(max-width: 600px) 45vw, 280px']) ?><figcaption>Before <small><?= nu_e($pow['before']['time']) ?></small></figcaption></figure>
          <figure><?= nu_photo($pow['after']['photo'], $pow['after']['alt'], ['sizes' => '(max-width: 600px) 45vw, 280px']) ?><figcaption>After <small><?= nu_e($pow['after']['time']) ?></small></figcaption></figure>
        </div>
        <ul class="tk-check ap-check"><?php foreach ($trk['checklist'] as $c): ?><li><?= nu_icon('check-circle-fill') ?><?= nu_e($c) ?></li><?php endforeach; ?></ul>
        <p class="ap-note"><?= nu_icon('chat-left-text') ?><span><b>Note from <?= nu_e(explode(' ', $ctr['name'])[0]) ?>:</b> Cleared to the pavement edge and salted the steps. Gate left closed.</span></p>
      </section>
    </div>

    <aside class="ap-side">
      <section class="sv-card ap-act" id="ap-pending" <?= in_array($view, ['pending'], true) ? '' : 'hidden' ?>>
        <h2 class="tk-h">Happy with the work?</h2>
        <dl class="ap-sum"><div><dt>Job total</dt><dd>$<?= number_format($amount, 2) ?></dd></div><div><dt>GST (5%)</dt><dd>$<?= number_format($tax, 2) ?></dd></div><div class="is-total"><dt>Held for this job</dt><dd>$<?= number_format($total, 2) ?></dd></div></dl>
        <?= nu_trust_note('held') ?>
        <button class="btn btn-primary btn-block" type="button" data-act="approve"><?= nu_icon('check2-circle') ?> Approve and release payment</button>
        <button class="btn btn-outline btn-block" type="button" data-act="dispute"><?= nu_icon('flag') ?> Report a problem</button>
        <p class="ap-fine">If you do not respond within 48 hours, payment is released automatically. <?= nu_sample_tag('Sample rule') ?></p>
      </section>

      <section class="sv-card ap-act" id="ap-dispute" <?= $view === 'dispute' ? '' : 'hidden' ?>>
        <h2 class="tk-h"><?= nu_icon('flag-fill') ?> Report a problem</h2>
        <p class="ap-fine">Your payment stays held while we look into it. Nothing is paid out until this is resolved.</p>
        <form data-dispute-form novalidate>
          <div class="fld"><label class="label" for="dp-reason">What went wrong?</label><select class="input" id="dp-reason" required><option value="">Choose a reason</option><?php foreach ($reasons as $r): ?><option><?= nu_e($r) ?></option><?php endforeach; ?></select></div>
          <fieldset class="fld ap-out"><legend class="label">What would you like?</legend>
            <label class="radio"><input type="radio" name="dp-out" checked><span>Come back and fix it</span></label>
            <label class="radio"><input type="radio" name="dp-out"><span>Partial refund</span></label>
            <label class="radio"><input type="radio" name="dp-out"><span>Full refund</span></label>
          </fieldset>
          <div class="fld"><label class="label" for="dp-text">Tell us more</label><textarea class="input" id="dp-text" rows="3" placeholder="What is missing or wrong? Photos help."></textarea></div>
          <div class="fld"><span class="label">Photos (optional)</span>
            <label class="up up--sm"><input type="file" accept="image/*" multiple data-up="dispute"><span><?= nu_icon('camera') ?> Add photos</span></label>
            <div class="up-prev" data-prev="dispute"></div>
          </div>
          <p class="ap-err" data-dp-err hidden role="alert">Choose a reason so we can route your report.</p>
          <button class="btn btn-primary btn-block" type="submit">Submit report</button>
          <button class="btn btn-ghost btn-block" type="button" data-act="back">Back to review</button>
        </form>
      </section>

      <section class="sv-card ap-act ap-done" id="ap-approved" <?= $view === 'approved' ? '' : 'hidden' ?> role="status">
        <span class="ap-done__ic"><?= nu_icon('check-circle-fill') ?></span>
        <h2>Payment released</h2>
        <p><?= nu_e($ctr['name']) ?> will be paid out. A receipt for $<?= number_format($total, 2) ?> is in your activity.</p>
        <fieldset class="ac-stars"><legend class="ac-lab">How was <?= nu_e(explode(' ', $ctr['name'])[0]) ?>?</legend><?php for ($i = 5; $i >= 1; $i--): ?><input type="radio" name="ap-star" id="ap-s<?= $i ?>" value="<?= $i ?>"<?= $i === 5 ? ' checked' : '' ?>><label for="ap-s<?= $i ?>" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>"><?= nu_icon('star-fill') ?><span class="sr-only"><?= $i ?> star<?= $i > 1 ? 's' : '' ?></span></label><?php endfor; ?></fieldset>
        <a class="btn btn-primary btn-block" href="<?= nu_e(nu_url('public/activity.php')) ?>">View receipt</a>
        <a class="btn btn-outline btn-block" href="<?= nu_e(nu_start_url('snow-removal')) ?>">Post another task</a>
      </section>

      <section class="sv-card ap-act ap-done ap-done--warn" id="ap-disputed" <?= $view === 'disputed' ? '' : 'hidden' ?> role="status">
        <span class="ap-done__ic"><?= nu_icon('flag-fill') ?></span>
        <h2>Report received</h2>
        <p>Case <b>CASE-91</b> is open. Funds stay held ($<?= number_format($total, 2) ?>) while Nuahn reviews the photos and messages. We reply within one business day. <?= nu_sample_tag('Sample') ?></p>
        <a class="btn btn-primary btn-block" href="<?= nu_e(nu_url('public/chat.php')) ?>?thread=support">Message support</a>
        <a class="btn btn-outline btn-block" href="<?= nu_e(nu_url('public/bookings.php')) ?>">Back to bookings</a>
      </section>
    </aside>
  </div>
</main>
<script>
(function () {
  var root = document.getElementById('approve'), secs = { pending: '#ap-pending', dispute: '#ap-dispute', approved: '#ap-approved', disputed: '#ap-disputed' };
  function show(v) {
    root.dataset.view = v;
    Object.keys(secs).forEach(function (k) { root.querySelector(secs[k]).hidden = k !== v; });
    var chip = root.querySelector('[data-pay-chip]'), j = root.querySelector('[data-jsm]');
    if (v === 'approved') { chip.innerHTML = <?= json_encode(nu_payment_chip('paid')) ?>; j.innerHTML = <?= json_encode(nu_job_machine('approved')) ?>; }
    else if (v === 'disputed') { chip.innerHTML = <?= json_encode(nu_payment_chip('disputed')) ?>; j.innerHTML = <?= json_encode(nu_job_machine('disputed')) ?>; }
    else { chip.innerHTML = <?= json_encode(nu_payment_chip('authorized')) ?>; j.innerHTML = <?= json_encode(nu_job_machine('pending_approval')) ?>; }
    var s = root.querySelector(secs[v]); if (s && root.dataset.ready) s.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
  }
  root.addEventListener('click', function (e) { var b = e.target.closest('[data-act]'); if (!b) return; var a = b.dataset.act; show(a === 'approve' ? 'approved' : a === 'dispute' ? 'dispute' : 'pending'); });
  show(root.dataset.view); root.dataset.ready = '1';
  var f = root.querySelector('[data-dispute-form]');
  f.addEventListener('submit', function (e) { e.preventDefault(); var r = f.querySelector('#dp-reason'), er = f.querySelector('[data-dp-err]'); if (!r.value) { er.hidden = false; r.focus(); return; } er.hidden = true; show('disputed'); });
  document.querySelectorAll('[data-up]').forEach(function (inp) { inp.addEventListener('change', function () {
    var box = document.querySelector('[data-prev="' + inp.dataset.up + '"]'); Array.prototype.forEach.call(inp.files, function (fl) { var im = new Image(); im.alt = 'Selected photo'; im.src = URL.createObjectURL(fl); box.appendChild(im); });
  }); });
})();
</script>
<?php include_once("../includes/footer.php"); ?>
