<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/approve.php: "Review and approve" (client, owner of the task only). Live data.
// pending_approval: before/after photos, checklist and the held amount, then [Approve and release payment] (do=job_approve: captures the hold,
// records the payout and the commission split) or [Report a problem] (do=dispute_open: the job freezes and funds stay held).
// approved: receipt summary and a one-time review form (do=review_add). disputed: the open case. Other states go back to the tracking page.
// If the client does nothing, scripts/auto_release.php approves after MK_AUTO_RELEASE_HOURS.
require_once("../config/init.php");
requireLogin('client');
require_once("../includes/marketplace/view.php");
$id = (int) ($_GET['id'] ?? 0);
$a = mk_task_access($id);
if (!$a || $a[1] !== 'client') { http_response_code(404); include_once("../includes/header.php"); echo '<main class="screen"><div class="sv-card"><h1>Job not found</h1><p><a href="' . nu_e(nu_url('public/bookings.php')) . '">Back to my bookings</a></p></div></main>'; include_once("../includes/footer.php"); exit; }
$t = $a[0];
$view = ['pending_approval' => 'pending', 'approved' => 'approved', 'disputed' => 'disputed'][$t['status']] ?? null;
if ($view === null) { header('Location: ' . nu_url('public/track.php') . '?id=' . $id); exit; }
$ctr = $t['contractor_id'] ? mk_contractor_card($t['contractor_id']) : null;
$first = $ctr ? explode(' ', $ctr['name'])[0] : 'your contractor';
$pay = mk_payment($id);
$sub = (float) ($pay['subtotal'] ?? $t['agreed_amount']); $tax = (float) ($pay['tax'] ?? $t['tax_amount']); $total = $sub + $tax;
$checklist = json_decode((string) $t['checklist'], true) ?: [];
$review = $view === 'approved' ? mk_review_for($id) : null;
$dispute = $view === 'disputed' ? mk_dispute_for($id) : null;
$reasons = ['Work not finished', 'Area missed (steps, walkway)', 'Damage to property', 'Contractor did not match the offer', 'Other'];
$svcIcon = $t['service'] === 'snow-removal' ? 'snow' : 'wrench-adjustable';
include_once("../includes/header.php");
?>
<main class="screen ap" id="approve" data-view="<?= nu_e($view) ?>">
  <p class="crumbs"><a href="<?= nu_e(nu_url('public/bookings.php')) ?>">My bookings</a> / Review and approve</p>
  <div class="screen__head"><div><h1>Review and approve</h1><p><?= nu_e(mk_ref($t)) ?> · <?= nu_e($t['title']) ?><?php if ($ctr): ?> · <a href="<?= nu_e(nu_url('public/contractor.php')) ?>?id=<?= nu_e($ctr['id']) ?>"><?= nu_e($ctr['name']) ?></a><?php endif; ?></p></div><?= mk_test_note() ?></div>

  <section class="sv-card ap-top" aria-label="Job and payment status">
    <div class="ap-top__row">
      <span class="ap-top__ic"><?= nu_icon($svcIcon) ?></span>
      <div><b><?= $view === 'pending' ? 'Completed · pending your approval' : ($view === 'approved' ? 'Approved' : 'Disputed · funds held') ?></b><span><?= nu_icon('geo-alt') ?> <?= nu_e($t['address']) ?></span></div>
      <span><?= nu_payment_chip($t['payment_status']) ?></span>
    </div>
    <div><?= nu_job_machine(mk_machine_key($t)) ?></div>
  </section>

  <div class="ap-grid">
    <div class="ap-main">
      <section class="sv-card" id="ap-proof">
        <h2 class="tk-h"><?= nu_icon('images') ?> Before and after</h2>
        <?= mk_photo_grid($id, 'before', 'No before photo.') ?>
        <?= mk_photo_grid($id, 'after', 'No after photo.') ?>
        <?php if ($checklist): ?><ul class="tk-check ap-check"><?php foreach ($checklist as $c): ?><li><?= nu_icon('check-circle-fill') ?><?= nu_e($c) ?></li><?php endforeach; ?></ul><?php endif; ?>
        <?php if ($t['completion_note'] !== null && $t['completion_note'] !== ''): ?><p class="ap-note"><?= nu_icon('chat-left-text') ?><span><b>Note from <?= nu_e($first) ?>:</b> <?= nu_e($t['completion_note']) ?></span></p><?php endif; ?>
      </section>
    </div>

    <aside class="ap-side">
<?php if ($view === 'pending'): ?>
      <section class="sv-card ap-act" id="ap-pending">
        <h2 class="tk-h">Happy with the work?</h2>
        <dl class="ap-sum"><div><dt>Job total</dt><dd>$<?= number_format($sub, 2) ?></dd></div><div><dt>GST (<?= rtrim(rtrim(number_format(MK_GST_PCT, 2), '0'), '.') ?>%)</dt><dd>$<?= number_format($tax, 2) ?></dd></div><div class="is-total"><dt>Held for this job</dt><dd>$<?= number_format($total, 2) ?></dd></div></dl>
        <?= nu_trust_note('held') ?>
        <?= mk_form('job_approve', ['task_id' => $id], '', 'approve.php?id=' . $id) ?>
          <button class="btn btn-primary btn-block" type="submit"><?= nu_icon('check2-circle') ?> Approve and release payment</button>
        </form>
        <button class="btn btn-outline btn-block" type="button" data-act="dispute"><?= nu_icon('flag') ?> Report a problem</button>
        <p class="ap-fine">If you do not respond within <?= (int) MK_AUTO_RELEASE_HOURS ?> hours, payment is released automatically.<?= $t['release_in_min'] !== null ? ' About ' . (int) max(0, round($t['release_in_min'] / 60)) . ' h left.' : '' ?></p>
      </section>

      <section class="sv-card ap-act" id="ap-dispute" hidden>
        <h2 class="tk-h"><?= nu_icon('flag-fill') ?> Report a problem</h2>
        <p class="ap-fine">Your payment stays held while we look into it. Nothing is paid out until this is resolved.</p>
        <?= mk_form('dispute_open', ['task_id' => $id], 'enctype="multipart/form-data"', 'track.php?id=' . $id) ?>
          <div class="fld"><label class="label" for="dp-reason">What went wrong?</label><select class="input" id="dp-reason" name="reason" required><option value="">Choose a reason</option><?php foreach ($reasons as $r): ?><option><?= nu_e($r) ?></option><?php endforeach; ?></select></div>
          <fieldset class="fld ap-out"><legend class="label">What would you like?</legend>
            <label class="radio"><input type="radio" name="wanted" value="redo" checked><span>Come back and fix it</span></label>
            <label class="radio"><input type="radio" name="wanted" value="partial"><span>Partial refund</span></label>
            <label class="radio"><input type="radio" name="wanted" value="refund"><span>Full refund</span></label>
          </fieldset>
          <div class="fld"><label class="label" for="dp-text">Tell us more</label><textarea class="input" id="dp-text" name="details" rows="3" maxlength="1500" placeholder="What is missing or wrong? A photo helps."></textarea></div>
          <div class="fld"><span class="label">Photo (optional)</span>
            <label class="up up--sm"><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"><span><?= nu_icon('camera') ?> Add a photo</span></label>
          </div>
          <button class="btn btn-primary btn-block" type="submit">Submit report</button>
          <button class="btn btn-ghost btn-block" type="button" data-act="back">Back to review</button>
        </form>
      </section>
<?php elseif ($view === 'approved'): ?>
      <section class="sv-card ap-act ap-done" id="ap-approved" role="status">
        <span class="ap-done__ic"><?= nu_icon('check-circle-fill') ?></span>
        <h2>Payment captured</h2>
        <p><?= nu_e($ctr['name'] ?? 'Your contractor') ?> is being paid. Total charged: <b>$<?= number_format((float) ($pay['captured_amount'] ?? $total), 2) ?></b>.</p>
        <?php if ($review): ?>
          <p class="ap-fine"><?= nu_icon('star-fill') ?> You rated <?= nu_e($first) ?> <?= (int) $review['rating'] ?>/5. Thank you.</p>
        <?php else: ?>
        <?= mk_form('review_add', ['task_id' => $id], '', 'approve.php?id=' . $id) ?>
          <fieldset class="ac-stars"><legend class="ac-lab">How was <?= nu_e($first) ?>?</legend><?php for ($i = 5; $i >= 1; $i--): ?><input type="radio" name="rating" id="ap-s<?= $i ?>" value="<?= $i ?>"<?= $i === 5 ? ' checked' : '' ?>><label for="ap-s<?= $i ?>" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>"><?= nu_icon('star-fill') ?></label><?php endfor; ?></fieldset>
          <div class="fld"><label class="sr-only" for="ap-rc">Comment</label><textarea class="input" id="ap-rc" name="comment" rows="2" maxlength="1000" placeholder="Say a few words (optional)"></textarea></div>
          <button class="btn btn-primary btn-block" type="submit">Send review</button>
        </form>
        <?php endif; ?>
        <a class="btn btn-outline btn-block" href="<?= nu_e(nu_start_url($t['service'])) ?>">Book another</a>
      </section>
<?php else: ?>
      <section class="sv-card ap-act ap-done ap-done--warn" id="ap-disputed" role="status">
        <span class="ap-done__ic"><?= nu_icon('flag-fill') ?></span>
        <h2>Report received</h2>
        <p>Case <b>CASE-<?= (int) ($dispute['id'] ?? 0) ?></b> is <?= nu_e(str_replace('_', ' ', $dispute['status'] ?? 'open')) ?>. Funds stay held ($<?= number_format($total, 2) ?>) while Nuahn reviews the photos and messages. We reply within one business day.</p>
        <a class="btn btn-primary btn-block" href="<?= nu_e(nu_url('public/track.php')) ?>?id=<?= $id ?>">Open the job thread</a>
        <a class="btn btn-outline btn-block" href="<?= nu_e(nu_url('public/bookings.php')) ?>">Back to bookings</a>
      </section>
<?php endif; ?>
    </aside>
  </div>
</main>
<script>
(function () {
  var root = document.getElementById('approve'), p = document.getElementById('ap-pending'), d = document.getElementById('ap-dispute');
  if (!p || !d) return;
  root.addEventListener('click', function (e) { var b = e.target.closest('[data-act]'); if (!b) return; var dis = b.dataset.act === 'dispute'; p.hidden = dis; d.hidden = !dis; (dis ? d : p).scrollIntoView({ block: 'nearest', behavior: 'smooth' }); });
})();
</script>
<?php include_once("../includes/footer.php"); ?>
