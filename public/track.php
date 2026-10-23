<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/track.php: one live job page for the client AND the assigned contractor (and read-only for Ops). Data: tasks, payments, job_photos,
// job_messages, quotes (revisions), disputes. Access is by ownership (mk_task_access): anyone else gets a 404.
// Client sees: address + map pin, the job timeline, payment status (separate from job status), photos, messages, extra-work approval,
// cancel (before the contractor is on the way), approve / report a problem (approve.php), review.
// Contractor sees the same plus the job controls: on the way / arrived / start, before and after photo upload, submit completion
// (server-side photo gate), an on-site revised quote (client must re-approve; the job pauses), withdraw (before arriving).
// Live GPS of the contractor is NOT built yet (marked on the page); the map shows the job pin only.
require_once("../config/init.php");
requireLogin(['client', 'provider', 'superadmin', 'manager', 'admin']);
require_once("../includes/marketplace/view.php");
$id = (int) ($_GET['id'] ?? 0);
$a = mk_task_access($id);
if ($a && in_array($a[1], ['bidder', 'board'], true)) { header('Location: ' . nu_url('public/open_jobs.php')); exit; }
if (!$a) { http_response_code(404); include_once("../includes/header.php"); echo '<main class="screen"><div class="sv-card"><h1>Job not found</h1><p><a href="' . nu_e(nu_url(isRole('provider') ? 'public/provider_dashboard.php' : 'public/bookings.php')) . '">Back</a></p></div></main>'; include_once("../includes/footer.php"); exit; }
[$t, $rel] = $a;
$isClient = $rel === 'client'; $isCtr = $rel === 'contractor'; $isStaff = $rel === 'staff';
$st = $t['status'];
if ($isClient && $st === 'pending_approval') { header('Location: ' . nu_url('public/approve.php') . '?id=' . $id); exit; }
if ($isClient && $st === 'open_for_bids') { header('Location: ' . nu_url('public/offers.php') . '?task=' . $id); exit; }
if ($isClient && $st === 'quote_pending') { header('Location: ' . nu_url('public/quote.php') . '?task=' . $id); exit; }
if ($isClient && $st === 'awaiting_payment') { header('Location: ' . nu_url('public/pay.php') . '?task=' . $id); exit; }

$pay = mk_payment($id);
$other = $isClient ? ($t['contractor_id'] ? mk_contractor_card($t['contractor_id']) : null) : null;
$msgs = mk_messages($id);
$counts = mk_photo_counts($id);
$rev = mk_pending_revision($id);
$dispute = $st === 'disputed' ? mk_dispute_for($id) : null;
$review = $st === 'approved' ? mk_review_for($id) : null;
$checklist = json_decode((string) $t['checklist'], true) ?: [];
$isPlan = mk_is_plan($t);
$visits = [];
if ($isPlan) { $q = mk_db()->prepare('SELECT id, status, address, created_at, agreed_amount, payment_status FROM tasks WHERE parent_id = ? ORDER BY id DESC LIMIT 30'); $q->execute([$id]); $visits = $q->fetchAll(); }
$times = [];
$map = ['confirmed' => 'accepted_at', 'on_the_way' => 'started_at', 'arrived' => 'arrived_at', 'pending_approval' => 'submitted_at', 'approved' => 'approved_at'];
foreach ($map as $k => $col) if (!empty($t[$col])) $times[$k] = mk_dt($t[$col], 'M j, g:i A');
$sub = (float) ($pay['subtotal'] ?? $t['agreed_amount']); $tax = (float) ($pay['tax'] ?? $t['tax_amount']);
$payout = (float) ($pay['payout_amount'] ?? mk_you_get($sub));
$canBeforePhoto = in_array($st, ['confirmed', 'on_the_way', 'arrived', 'in_progress', 'paused'], true);
$canAfterPhoto = in_array($st, ['arrived', 'in_progress', 'paused'], true);
$hasPin = $t['lat'] !== null && $t['lng'] !== null;
$pill = ['confirmed' => ['calendar-check', 'Confirmed'], 'on_the_way' => ['truck', 'On the way'], 'arrived' => ['house-check', 'Arrived'], 'in_progress' => ['tools', 'In progress'], 'paused' => ['pause-circle', 'Paused: waiting for approval'],
         'approved' => ['check-circle-fill', 'Approved'], 'disputed' => ['flag-fill', 'Disputed'], 'cancelled' => ['x-circle', 'Cancelled'], 'searching' => ['broadcast', 'Searching'], 'no_provider' => ['exclamation-circle', 'No technician yet'],
         'pending_approval' => ['hourglass-split', 'Waiting for client approval']][$st] ?? ['circle', ucfirst(str_replace('_', ' ', $st))];
$back = 'track.php?id=' . $id;
$svcName = $t['service'] === 'snow-removal' ? 'Winter Snow Removal' : 'Mobile Mechanics';
include_once("../includes/header.php");
?>
<main class="tk<?= $st === 'approved' ? ' tk--done' : '' ?>" id="track">
  <div class="tk-head">
    <h1><?= $isCtr ? 'Your job' : 'Your job, step by step' ?></h1>
    <p><?= $isCtr ? 'Update the status, add photos and submit when the work is done.' : 'Live updates from your contractor.' ?> <?= mk_test_note() ?></p>
  </div>
  <section class="tk-bar">
    <div><b><?= nu_e(mk_ref($t)) ?> · <?= nu_e($svcName) ?></b><span><?= nu_icon('geo-alt-fill') ?> <?= nu_e($isStaff || $isClient || $isCtr ? $t['address'] : mk_area($t['address'])) ?></span></div>
    <div class="tk-bar__win"><?= nu_icon('calendar3') ?><span>When<b><?= nu_e($isPlan ? 'Seasonal plan' : mk_when($t)) ?></b></span></div>
  </section>
<?php if ($pay || $st === 'cancelled'): ?>
  <section class="tk-pay" aria-label="Payment status">
    <span class="tk-pay__ic"><?= nu_icon('shield-lock-fill') ?></span>
    <div><b>Payment <?= nu_payment_chip($t['payment_status']) ?></b><span><?php
      if ($isCtr) echo 'Job $' . number_format($sub, 2) . ' · you receive $' . number_format($payout, 2) . ' after Nuahn&rsquo;s ' . rtrim(rtrim(number_format((float) ($pay['commission_pct'] ?? MK_COMMISSION_PCT), 2), '0'), '.') . '% service fee, paid when the client approves.';
      elseif ($t['payment_status'] === 'authorized') echo 'Funds held securely until you approve the work. Your contractor is paid after you approve.';
      elseif (in_array($t['payment_status'], ['captured', 'released'], true)) echo 'Charged $' . number_format((float) $pay['captured_amount'], 2) . '. Thank you.';
      elseif ($t['payment_status'] === 'refunded') echo 'Your hold was released. You were not charged.';
      elseif ($t['payment_status'] === 'disputed') echo 'Funds stay held while Nuahn reviews your report.';
      else echo 'No payment held.'; ?></span></div>
  </section>
<?php endif; ?>

<?php if ($st === 'cancelled'): ?>
  <section class="sv-card mk-note mk-note--warn"><?= nu_icon('x-circle') ?> <span><b>This job was cancelled</b><?= $t['cancel_reason'] ? ': ' . nu_e($t['cancel_reason']) : '' ?>. Any held funds were released.</span></section>
<?php endif; ?>

  <div class="tk-grid">
    <section class="sv-card tk-live">
      <div class="tk-live__top">
        <div class="tk-eta">
          <span class="tk-pill<?= in_array($st, ['approved'], true) ? ' is-done' : '' ?>"><?= nu_icon($pill[0]) ?><span><?= nu_e($pill[1]) ?></span></span>
<?php if (in_array($st, ['searching', 'no_provider'], true)): ?>
          <span class="tk-eta__l"><?= $st === 'searching' ? 'Contacting technicians near you' : 'No technician accepted yet' ?></span>
          <span class="tk-eta__u"><?= $st === 'searching' ? 'Searching for about ' . max(0, (int) $t['search_left_min']) . ' more min' : 'Your request is still saved. Please call roadside assistance if this is an emergency.' ?></span>
<?php elseif ($t['eta_minutes'] && in_array($st, ['confirmed', 'on_the_way'], true)): ?>
          <span class="tk-eta__l">Estimated arrival</span><span class="tk-eta__v"><?= nu_e(str_replace('Arrives in about ', '', mk_eta_text($t['eta_minutes']))) ?></span><span class="tk-eta__u">Estimate from the contractor, not live GPS</span>
<?php elseif ($st === 'approved'): ?>
          <span class="tk-eta__l">Approved</span><span class="tk-eta__v"><?= nu_e(mk_dt($t['approved_at'], 'M j')) ?></span>
<?php endif; ?>
        </div>
        <div class="tk-map">
<?php if ($hasPin): ?>
          <div id="trk-map" data-home="<?= nu_e(json_encode([(float) $t['lat'], (float) $t['lng']])) ?>" role="img" aria-label="Map showing the job location"></div>
<?php else: ?>
          <div class="mk-nomap"><?= nu_icon('geo-alt') ?><span>No map pin set for this address.</span></div>
<?php endif; ?>
          <p class="tk-map__note"><?= nu_icon('geo-alt') ?> Job location pin. Live contractor GPS is pending.</p>
        </div>
      </div>
<?php if ($st !== 'cancelled'): ?>
      <div class="tk-jsm"><?= nu_job_machine(mk_machine_key($t), 'list', $times) ?></div>
<?php endif; ?>
    </section>

    <div class="tk-col">
      <section class="sv-card tk-team">
        <h2 class="tk-h"><?= $isCtr ? 'Your client' : 'Your contractor' ?></h2>
<?php if ($isCtr || $isStaff): ?>
        <div class="tk-team__row"><span class="avt avt--a avt--md" aria-hidden="true"><?= nu_e(mk_initials($t['client_name'])) ?></span><div><b><?= nu_e($t['client_name']) ?></b><span>Client<?= $isStaff && $t['contractor_name'] ? ' · contractor ' . nu_e($t['contractor_name']) : '' ?></span></div></div>
<?php elseif ($other): ?>
        <div class="tk-team__row"><?= nu_avatar($other, 'md') ?><div><b><a href="<?= nu_e(nu_url('public/contractor.php')) ?>?id=<?= nu_e($other['id']) ?>"><?= nu_e($other['name']) ?></a></b><span><?= nu_rating($other) ?> · <?= (int) $other['jobs'] ?> jobs</span></div></div>
        <?= nu_badges($other) ?>
        <div class="tk-team__acts"><a class="btn btn-soft btn-sm" href="<?= nu_e(nu_url('public/contractor.php')) ?>?id=<?= nu_e($other['id']) ?>">View profile</a></div>
<?php else: ?>
        <p class="tk-rep__wait"><?= nu_icon('person') ?><span>A contractor is assigned when one accepts.</span></p>
<?php endif; ?>
      </section>

      <section class="sv-card tk-msgs" id="messages" aria-label="Messages">
        <div class="tk-msgs__top"><h2 class="tk-h">Messages</h2></div>
        <ol class="tk-chat">
<?php if (!$msgs): ?><li class="tk-msg"><div><p>No messages yet.</p></div></li><?php endif; ?>
<?php foreach ($msgs as $m): $mine = (int) $m['sender_id'] === mk_uid(); ?>
          <li class="tk-msg tk-msg--<?= $mine ? 'me' : 'them' ?>"><span class="tk-msg__av"><?= $mine ? nu_icon('person-fill') : nu_e(mk_initials($m['name'])) ?></span><div><small><?= nu_e($mine ? 'You' : $m['name']) ?> · <?= nu_e(mk_ago($m['age_s'])) ?></small><p><?= nu_e($m['body']) ?></p></div></li>
<?php endforeach; ?>
        </ol>
<?php if ($isClient || $isCtr || $isStaff): ?>
        <?= mk_form('message_send', ['task_id' => $id], 'class="tk-send"', $back . '#messages') ?><label class="sr-only" for="tk-msg">Write a message</label><input class="input" id="tk-msg" name="body" placeholder="Write a message…" autocomplete="off" maxlength="1000" required><button class="btn btn-primary btn-sm" type="submit"><?= nu_icon('send-fill') ?> Send</button></form>
<?php endif; ?>
      </section>
    </div>

<?php if ($isPlan): ?>
    <section class="sv-card mk-sec">
      <h2 class="tk-h"><?= nu_icon('cloud-snow') ?> Seasonal plan visits</h2>
      <p class="mk-sub">A visit is created after each snowfall of <?= (int) MK_SEASONAL_TRIGGER_CM ?> cm or more. Each visit is held and approved on its own.</p>
<?php if (!$visits): ?><p class="tk-rep__wait"><?= nu_icon('snow') ?><span>No visits yet. The first one appears after the next qualifying snowfall.</span></p><?php endif; ?>
      <ul class="mk-feed"><?php foreach ($visits as $v): ?><li><a href="<?= nu_e(nu_url('public/track.php')) ?>?id=<?= (int) $v['id'] ?>"><?= nu_e(mk_ref(['service' => 'snow-removal', 'id' => $v['id']])) ?> · <?= nu_e(mk_dt($v['created_at'], 'M j')) ?></a> <?= nu_payment_chip($v['payment_status']) ?> <span class="chip"><?= nu_e(ucfirst(str_replace('_', ' ', $v['status']))) ?></span></li><?php endforeach; ?></ul>
    </section>
<?php endif; ?>

<?php if ($rev): ?>
    <section class="sv-card mk-sec mk-note mk-note--warn" id="revision">
      <h2 class="tk-h"><?= nu_icon('exclamation-triangle-fill') ?> Extra work found on site</h2>
      <p><?= nu_e($rev['found_note']) ?></p>
      <table class="mk-tbl"><thead><tr><th>Item</th><th>Qty</th><th>Price</th></tr></thead><tbody>
      <?php foreach ($rev['items'] as $it): ?><tr><td><?= nu_e($it['type']) ?>: <?= nu_e($it['description']) ?></td><td><?= nu_e(rtrim(rtrim(number_format((float) $it['qty'], 2), '0'), '.')) ?></td><td>$<?= number_format($it['qty'] * $it['unit_price'], 2) ?></td></tr><?php endforeach; ?>
      <tr><td colspan="2">Subtotal</td><td>$<?= number_format((float) $rev['subtotal'], 2) ?></td></tr><tr><td colspan="2">GST/PST</td><td>$<?= number_format((float) $rev['gst'] + (float) $rev['pst'], 2) ?></td></tr><tr><th colspan="2">Additional total</th><th>$<?= number_format((float) $rev['total'], 2) ?></th></tr></tbody></table>
<?php if ($isClient): ?>
      <p class="mk-sub">No extra work starts until you approve. Approving raises the held amount by this total.</p>
      <div class="mk-form mk-form--row">
        <?= mk_form('revision_decide', ['task_id' => $id, 'quote_id' => $rev['id'], 'decision' => 'approve'], '', $back) ?><button class="btn btn-primary" type="submit">Approve extra work</button></form>
        <?= mk_form('revision_decide', ['task_id' => $id, 'quote_id' => $rev['id'], 'decision' => 'decline'], '', $back) ?><button class="btn btn-outline" type="submit">Decline</button></form>
      </div>
<?php else: ?><p class="mk-sub">Waiting for the client to approve. Do not start the extra work before they do.</p><?php endif; ?>
    </section>
<?php endif; ?>

    <section class="sv-card tk-rep" id="photos">
      <div class="tk-rep__top"><h2 class="tk-h"><?= nu_icon('images') ?> Photos and completion</h2></div>
      <div class="tk-rep__body tk-rep__body--stack">
        <h3 class="mk-sub">Before (<?= (int) ($counts['before'] ?? 0) ?>)</h3>
        <?= mk_photo_grid($id, 'before', $isCtr ? 'Add at least one BEFORE photo before you start.' : 'Before photos appear when the contractor adds them.') ?>
<?php if ($isCtr && $canBeforePhoto): ?>
        <?= mk_form('photo_upload', ['task_id' => $id, 'kind' => 'before'], 'enctype="multipart/form-data" class="mk-form mk-form--row"', $back . '#photos') ?>
          <label class="up up--sm"><input type="file" name="photo[]" accept="image/jpeg,image/png,image/webp" multiple required><span><?= nu_icon('camera') ?> Choose before photos</span></label><button class="btn btn-outline btn-sm" type="submit">Upload</button></form>
<?php endif; ?>
        <h3 class="mk-sub">After (<?= (int) ($counts['after'] ?? 0) ?>)</h3>
        <?= mk_photo_grid($id, 'after', $isCtr ? 'Add at least one AFTER photo, then submit the job.' : 'After photos appear when the work is submitted.') ?>
<?php if ($isCtr && $canAfterPhoto): ?>
        <?= mk_form('photo_upload', ['task_id' => $id, 'kind' => 'after'], 'enctype="multipart/form-data" class="mk-form mk-form--row"', $back . '#photos') ?>
          <label class="up up--sm"><input type="file" name="photo[]" accept="image/jpeg,image/png,image/webp" multiple required><span><?= nu_icon('camera') ?> Choose after photos</span></label><button class="btn btn-outline btn-sm" type="submit">Upload</button></form>
<?php endif; ?>
<?php if ($dispute): ?>
        <h3 class="mk-sub">Reported problem</h3>
        <p class="mk-note mk-note--warn"><?= nu_icon('flag-fill') ?> <span><b><?= nu_e($dispute['reason']) ?></b> · wants <?= nu_e($dispute['wanted']) ?>. <?= nu_e($dispute['details']) ?></span></p>
        <?= mk_photo_grid($id, 'dispute') ?>
<?php endif; ?>
<?php if ($checklist || ($t['completion_note'] ?? '') !== ''): ?>
        <ul class="tk-check"><?php foreach ($checklist as $c): ?><li><?= nu_icon('check-circle-fill') ?><?= nu_e($c) ?></li><?php endforeach; ?></ul>
        <?php if ($t['completion_note'] !== null && $t['completion_note'] !== ''): ?><p class="ap-note"><?= nu_icon('chat-left-text') ?><span><b>Completion note:</b> <?= nu_e($t['completion_note']) ?></span></p><?php endif; ?>
<?php endif; ?>
      </div>
    </section>

<?php if ($isCtr && !$isPlan): ?>
    <section class="sv-card mk-sec" id="controls">
      <h2 class="tk-h"><?= nu_icon('lightning-charge-fill') ?> Job controls</h2>
<?php if ($st === 'confirmed'): ?>
      <?= mk_form('job_step', ['task_id' => $id, 'step' => 'trip'], '', $back) ?><button class="btn btn-primary btn-block" type="submit"><?= nu_icon('truck') ?> I am on the way</button></form>
<?php elseif ($st === 'on_the_way'): ?>
      <?= mk_form('job_step', ['task_id' => $id, 'step' => 'arrived'], '', $back) ?><button class="btn btn-primary btn-block" type="submit"><?= nu_icon('house-check') ?> I have arrived</button></form>
<?php elseif ($st === 'arrived'): ?>
      <?= mk_form('job_step', ['task_id' => $id, 'step' => 'start'], '', $back) ?><button class="btn btn-primary btn-block" type="submit"<?= ($counts['before'] ?? 0) < 1 ? ' disabled aria-disabled="true"' : '' ?>><?= nu_icon('play-fill') ?> Start work</button></form>
<?php if (($counts['before'] ?? 0) < 1): ?><p class="mk-sub">Upload a before photo first.</p><?php endif; ?>
<?php elseif ($st === 'in_progress'): $ready = ($counts['before'] ?? 0) >= 1 && ($counts['after'] ?? 0) >= 1; ?>
      <?= mk_form('job_submit', ['task_id' => $id], 'class="mk-form"', $back) ?>
        <label class="fld"><span class="label">Completion note</span><textarea class="input" name="note" rows="2" maxlength="800" placeholder="What you did, anything the client should know"></textarea></label>
        <fieldset class="fld"><legend class="label">Checklist</legend>
          <?php foreach ($t['service'] === 'snow-removal' ? ['Driveway cleared', 'Walkway cleared', 'Steps cleared', 'Ice melt applied'] : ['Work completed as quoted', 'Area left clean', 'Old parts returned or disposed', 'Vehicle tested'] as $c): ?><label class="mk-check"><input type="checkbox" name="checklist[]" value="<?= nu_e($c) ?>"><span><b><?= nu_e($c) ?></b></span></label><?php endforeach; ?>
        </fieldset>
        <button class="btn btn-primary btn-block" type="submit"<?= $ready ? '' : ' disabled aria-disabled="true"' ?>><?= nu_icon('check2-circle') ?> Submit completion</button>
        <?php if (!$ready): ?><p class="mk-sub">Needs at least one before and one after photo.</p><?php endif; ?>
      </form>
<?php elseif ($st === 'paused'): ?>
      <p class="mk-sub">Paused while the client decides on your revised quote.</p>
<?php elseif ($st === 'pending_approval'): ?>
      <p class="mk-sub">Submitted. The client has <?= (int) MK_AUTO_RELEASE_HOURS ?> hours to review; otherwise payment is released automatically.</p>
<?php elseif ($st === 'approved'): ?>
      <p class="mk-sub">Approved. Your payout of $<?= number_format($payout, 2) ?> is <?= ($pay['payout_status'] ?? '') === 'paid' ? 'paid' : 'queued' ?>.</p>
<?php endif; ?>
<?php if ($t['mode'] !== 'seasonal' || !$t['parent_id']): ?>
<?php if (in_array($st, ['arrived', 'in_progress'], true) && $t['service'] === 'mobile-mechanics' && !$rev): ?>
      <details class="mk-more"><summary><?= nu_icon('exclamation-triangle') ?> Found something extra? Send a revised quote</summary>
        <?= mk_form('quote_revise', ['task_id' => $id], 'class="mk-form"', $back) ?>
          <label class="fld"><span class="label">What did you find?</span><textarea class="input" name="found" rows="2" maxlength="500" required></textarea></label>
          <?php for ($i = 0; $i < 3; $i++): ?>
          <div class="mk-form mk-form--row"><select class="input" name="item_type[]"><option>Parts</option><option>Labour</option><option>Fee</option></select><input class="input" name="item_desc[]" placeholder="Description" maxlength="200"<?= $i === 0 ? ' required' : '' ?>><input class="input" name="item_qty[]" type="number" min="0.25" step="0.25" value="1" aria-label="Quantity"><input class="input" name="item_price[]" type="number" min="0" step="0.01" placeholder="Unit price" aria-label="Unit price"<?= $i === 0 ? ' required' : '' ?>></div>
          <?php endfor; ?>
          <p class="mk-sub">The job pauses until the client approves. Do not start the extra work before then.</p>
          <button class="btn btn-outline" type="submit">Send revised quote</button>
        </form>
      </details>
<?php endif; ?>
<?php if (in_array($st, ['confirmed', 'on_the_way'], true) && !$t['parent_id']): ?>
      <details class="mk-more"><summary><?= nu_icon('x-circle') ?> I can no longer do this job</summary>
        <?= mk_form('job_withdraw', ['task_id' => $id], 'class="mk-form"', 'open_jobs.php') ?>
          <label class="fld"><span class="label">Reason</span><input class="input" name="reason" maxlength="200"></label>
          <button class="btn btn-ghost btn-sm" type="submit">Withdraw from job</button>
        </form>
      </details>
<?php endif; ?>
<?php endif; ?>
    </section>
<?php endif; ?>

<?php if ($isClient && $st === 'confirmed'): ?>
    <section class="sv-card mk-sec">
      <details class="mk-more"><summary><?= nu_icon('x-circle') ?> Cancel this job</summary>
        <?= mk_form('task_cancel', ['task_id' => $id], 'class="mk-form"', 'bookings.php') ?>
          <p class="mk-sub">The contractor has not left yet, so your hold is released in full.</p>
          <label class="fld"><span class="label">Reason (optional)</span><input class="input" name="reason" maxlength="200"></label>
          <button class="btn btn-ghost btn-sm" type="submit">Cancel job</button>
        </form>
      </details>
    </section>
<?php elseif ($isClient && in_array($st, ['on_the_way', 'arrived', 'in_progress', 'paused'], true)): ?>
    <section class="sv-card mk-sec">
      <p class="mk-sub">The contractor is on this job, so it can no longer be cancelled here. If something is wrong, report it and your payment stays held.</p>
      <details class="mk-more"><summary><?= nu_icon('flag') ?> Report a problem</summary>
        <?= mk_form('dispute_open', ['task_id' => $id], 'class="mk-form" enctype="multipart/form-data"', $back) ?>
          <label class="fld"><span class="label">What went wrong?</span><select class="input" name="reason" required><option value="">Choose a reason</option><option>Contractor did not show up</option><option>Work not finished</option><option>Damage to property</option><option>Other</option></select></label>
          <label class="fld"><span class="label">What would you like?</span><select class="input" name="wanted"><option value="redo">Come back and fix it</option><option value="partial">Partial refund</option><option value="refund">Full refund</option></select></label>
          <label class="fld"><span class="label">Details</span><textarea class="input" name="details" rows="2" maxlength="1500"></textarea></label>
          <label class="up up--sm"><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"><span><?= nu_icon('camera') ?> Add a photo (optional)</span></label>
          <button class="btn btn-primary btn-sm" type="submit">Submit report</button>
        </form>
      </details>
    </section>
<?php endif; ?>

<?php if ($isClient && $st === 'approved'): ?>
    <section class="sv-card mk-sec"><p><?= $review ? 'You rated this job ' . (int) $review['rating'] . '/5.' : 'Job approved.' ?> <a href="<?= nu_e(nu_url('public/approve.php')) ?>?id=<?= $id ?>"><?= $review ? 'View receipt' : 'Leave a review' ?></a></p></section>
<?php endif; ?>
  </div>
</main>
<?php if ($hasPin): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var el = document.getElementById('trk-map'); if (!el || !window.L) return;
  var home = JSON.parse(el.dataset.home);
  var map = L.map(el, { zoomControl: false, attributionControl: true, scrollWheelZoom: false }).setView(home, 15);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(map);
  var hIc = L.divIcon({ className: '', html: '<div class="home-marker"><svg viewBox="0 0 16 16"><path d="M8.7 1.5a1 1 0 0 0-1.4 0L.6 8.1a.5.5 0 0 0 .7.7L2 8.2v5.3A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.2l.6.6a.5.5 0 0 0 .7-.7z"/></svg></div>', iconSize: [30, 30], iconAnchor: [15, 30] });
  L.marker(home, { icon: hIc, keyboard: false }).addTo(map);
});
</script>
<?php endif; ?>
<?php include_once("../includes/footer.php"); ?>
