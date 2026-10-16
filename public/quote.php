<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/quote.php: "Review mechanic quote" (doc2 p18, doc1 p16-17). Itemised parts / labour / call-out fee / tax,
// total and expiry; Approve / Ask a question / Decline; the approved version is recorded. Choose a window ->
// booking confirmation. Payment entry is hosted / tokenised and its status is separate from work status.
// Additional work found on-site: the mechanic pauses affected work and sends a revised version; previously approved
// work stays distinguishable from newly proposed work.
// UI ONLY: amounts are SAMPLE DATA (fixtures/mechanic.php) and must not ship as business prices.
// Sir Paul: GET /api/quotes/{id}?version=, POST /api/quotes/{id}/approve {version, window} -> booking,
// POST /api/quotes/{id}/decline {reason}, POST /api/quotes/{id}/questions {text} (posts to the booking thread),
// POST /api/payments/intents (hosted, idempotency key). Preview: ?state=approved|declined|expired|revised.
require_once("../config/init.php");
requireLogin('client');

$q = nu_fixture('mechanic')['quote'];
$state = (string) ($_GET['state'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') { $state = ($_POST['decision'] ?? '') === 'decline' ? 'declined' : (($_POST['decision'] ?? '') === 'extra' ? 'revised_ok' : 'approved'); }
$revised = in_array($state, ['revised', 'revised_ok'], true);
$sum = function ($items) { $s = 0; foreach ($items as $i) { $s += $i[2] * $i[3]; } return $s; };
$base = $sum($q['items']); $extra = $sum($q['extra']['items']);
$tax = $q['tax_rate'];
$win = (string) ($_POST['window'] ?? $q['windows'][2]);
include_once("../includes/header.php");
?>
<main class="qt" id="quote">
  <div class="ac-wrap qt-wrap">
    <div class="ac-head">
      <div>
        <h1>Review mechanic quote</h1>
        <p class="crumbs"><a href="<?= nu_e(nu_url('public/client_home.php')) ?>">Nuahn</a> / <a href="<?= nu_e(nu_url('public/bookings.php')) ?>">My Bookings</a> / <a href="<?= nu_e(nu_url('public/activity.php')) ?>">Activity</a> / <a href="<?= nu_e(nu_url('public/messages.php')) ?>">Messages</a></p>
      </div>
      <span class="qt-ver"><?= nu_icon('file-earmark-text') ?> <?= nu_e($q['id']) ?> · v<?= $revised ? 2 : 1 ?> <?= nu_booking_chip($state === 'approved' ? 'confirmed' : ($state === 'declined' ? 'cancelled' : ($revised ? ($state === 'revised_ok' ? 'in_progress' : 'paused') : 'quote_pending'))) ?></span>
    </div>

<?php if ($state === 'approved'): ?>
    <section class="ch-result" role="status"><?= nu_icon('check-circle-fill') ?><div><h2>Quote v1 approved · booking confirmed</h2>
      <p><?= nu_e($q['service']) ?> with <?= nu_e($q['mechanic']) ?>, <b><?= nu_e($win) ?></b>. Approved version and time are recorded. Payment is <b>authorized</b>, not charged; it’s captured after the work is completed.</p>
      <div class="row-flex"><a class="btn btn-primary btn-sm" href="<?= nu_e(nu_url('public/track.php')) ?>">Visit status</a><a class="btn btn-outline btn-sm" href="<?= nu_e(nu_url('public/change_appointment.php')) ?>?id=MECH-1042">Change appointment</a></div></div></section>
<?php elseif ($state === 'declined'): ?>
    <section class="ch-result ch-result--declined" role="status"><?= nu_icon('x-circle') ?><div><h2>Quote declined</h2><p>No work will be done and nothing was charged. The mechanic has been told. You can ask for a new quote any time.</p>
      <div class="row-flex"><a class="btn btn-primary btn-sm" href="<?= nu_e(nu_url('public/mechanic_request.php')) ?>">Request a new quote</a></div></div></section>
<?php elseif ($state === 'expired'): ?>
    <section class="ch-result ch-result--pending" role="status"><?= nu_icon('hourglass-bottom') ?><div><h2>This quote has expired</h2><p>Parts prices or availability may have changed. Ask the mechanic to refresh it; you’ll see the revised scope and price before confirming a visit.</p>
      <div class="row-flex"><button class="btn btn-primary btn-sm" type="button">Request refreshed quote</button></div></div></section>
<?php elseif ($state === 'revised_ok'): ?>
    <section class="ch-result" role="status"><?= nu_icon('check-circle-fill') ?><div><h2>Additional work approved (v2)</h2><p>The mechanic resumes work. Your invoice will show the original and additional work separately.</p></div></section>
<?php endif; ?>

    <section class="ac-box qt-scope">
      <div class="qt-scope__top"><h2><?= nu_e($q['service']) ?></h2><span class="qt-exp"><?= nu_icon('clock') ?> <?= nu_e($q['expires']) ?></span></div>
      <p class="qt-veh"><?= nu_icon('car-front-fill') ?> <?= nu_e($q['vehicle']) ?> · <?= nu_e($q['mechanic']) ?></p>
      <p class="qt-p"><?= nu_e($q['scope']) ?></p>
      <table class="qt-tbl">
        <thead><tr><th scope="col">Item</th><th scope="col">Amount</th></tr></thead>
<?php if ($revised): ?><tbody><tr class="qt-grp"><td colspan="2"><?= nu_icon('check2-circle') ?> Approved in v1</td></tr></tbody><?php endif; ?>
        <tbody<?= $revised ? ' class="is-approved"' : '' ?>>
<?php foreach ($q['items'] as $i): ?>
          <tr><td><?= nu_e($i[0]) ?><span class="qt-meta"><span class="qt-type qt-type--<?= strtolower($i[1]) ?>"><?= nu_e($i[1]) ?></span><?= $i[1] === 'Labour' ? $i[2] . ' h × CAD ' . number_format($i[3], 2) : 'Qty ' . (int) $i[2] ?></span></td><td>CAD <?= number_format($i[2] * $i[3], 2) ?></td></tr>
<?php endforeach; ?>
        </tbody>
<?php if ($revised): ?>
        <tbody class="is-new"><tr class="qt-grp"><td colspan="2"><?= nu_icon('plus-circle') ?> Newly proposed in v2</td></tr>
<?php foreach ($q['extra']['items'] as $i): ?>
          <tr><td><?= nu_e($i[0]) ?><span class="qt-meta"><span class="qt-type qt-type--<?= strtolower($i[1]) ?>"><?= nu_e($i[1]) ?></span><?= $i[1] === 'Labour' ? $i[2] . ' h × CAD ' . number_format($i[3], 2) : 'Qty ' . (int) $i[2] ?></span></td><td>CAD <?= number_format($i[2] * $i[3], 2) ?></td></tr>
<?php endforeach; ?>
        </tbody>
<?php endif; ?>
        <tfoot>
          <tr><td>Subtotal</td><td>CAD <?= number_format($base + ($revised ? $extra : 0), 2) ?></td></tr>
          <tr><td><?= nu_e($q['tax_label']) ?></td><td>CAD <?= number_format(($base + ($revised ? $extra : 0)) * $tax, 2) ?></td></tr>
          <tr class="is-total"><td>Quote total<?= $revised ? ' (v2)' : '' ?></td><td>CAD <?= number_format(($base + ($revised ? $extra : 0)) * (1 + $tax), 2) ?></td></tr>
        </tfoot>
      </table>
      <div class="qt-inc"><div><b><?= nu_icon('check-circle') ?> Includes</b><?php foreach ($q['includes'] as $x): ?><span><?= nu_e($x) ?></span><?php endforeach; ?></div>
        <div><b><?= nu_icon('dash-circle') ?> Not included</b><?php foreach ($q['excludes'] as $x): ?><span><?= nu_e($x) ?></span><?php endforeach; ?></div></div>
      <?= nu_sample_tag('Sample amounts, not business prices') ?>
    </section>

<?php if ($revised): ?>
    <section class="ac-box qt-extra">
      <h2><?= nu_icon('exclamation-triangle') ?> Additional work found on-site</h2>
      <p class="qt-p"><b><?= nu_e($q['extra']['found']) ?></b> Work on the front brakes is <b>paused</b> until you decide. Previously approved work and charges stay separate from this proposal.</p>
      <div class="qt-extra__sum"><span>Additional (incl. tax)</span><b>+ CAD <?= number_format($extra * (1 + $tax), 2) ?></b></div>
<?php if ($state === 'revised'): ?>
      <form method="post" action="?state=revised" class="qt-acts"><button class="btn btn-primary" name="decision" value="extra">Approve additional work</button><button class="btn btn-outline" name="decision" value="extra_decline" type="button" data-extra-no>Decline: finish approved work only</button><a class="btn btn-ghost" href="<?= nu_e(nu_url('public/track.php')) ?>"><?= nu_icon('chat-dots') ?> Ask the mechanic</a></form>
      <p class="ac-ok" data-extra-msg hidden role="status"><?= nu_icon('info-circle') ?> Declined. The mechanic will finish the approved v1 work only and note the rotor condition in the report. (Preview)</p>
<?php endif; ?>
    </section>
<?php elseif (!in_array($state, ['approved', 'declined', 'expired'], true)): ?>
    <form class="ac-two" method="post" data-quote>
      <section class="ac-box">
        <h2>Approve or ask</h2>
        <div class="qt-dec">
          <label class="qt-opt"><input type="radio" name="decision" value="approve" checked><span><?= nu_icon('check-circle') ?><b>Approve quote</b><small>The approved version (v1) and time are recorded.</small></span></label>
          <label class="qt-opt"><input type="radio" name="decision" value="ask"><span><?= nu_icon('chat-dots') ?><b>Ask a question</b><small>Goes to your booking thread; the quote stays open.</small></span></label>
          <label class="qt-opt"><input type="radio" name="decision" value="decline"><span><?= nu_icon('x-circle') ?><b>Decline quote</b><small>No work, no charge.</small></span></label>
        </div>
        <div class="qt-ask" data-ask hidden><label class="sr-only" for="qt-q">Your question</label><textarea class="input" id="qt-q" rows="2" placeholder="e.g. Can you check the rear pads too?"></textarea><p class="ac-ok" data-ask-ok hidden><?= nu_icon('check-circle-fill') ?> Question added to your thread (preview).</p></div>
      </section>
      <section class="ac-box" data-win-box>
        <h2>Choose your window</h2>
        <div class="ch-wins">
<?php foreach ($q['windows'] as $i => $w): ?>
          <label class="ch-win<?= $i === 2 ? '' : '' ?>"><input type="radio" name="window" value="<?= nu_e($w) ?>"<?= $i === 2 ? ' checked' : '' ?>><i class="ch-win__r" aria-hidden="true"></i><span><?= nu_e($w) ?></span><em>Available</em></label>
<?php endforeach; ?>
        </div>
        <p class="qt-mini"><?= nu_icon('info-circle') ?> You can change the appointment later. Visit reminders and live status follow.</p>
      </section>
      <section class="ac-box qt-pay">
        <h2><?= nu_icon('credit-card') ?> Payment</h2>
        <div class="qt-card"><span class="qt-card__brand">VISA</span><span>•••• 4242 · exp 08/28</span><button class="linkbtn" type="button">Change</button></div>
        <p class="qt-mini"><?= nu_icon('shield-lock') ?> Secure hosted card entry. Approving <b>authorizes</b> CAD <?= number_format($base * (1 + $tax), 2) ?>; it’s captured after the work is completed. Payment status is shown separately from the job. Retries never duplicate a charge.</p>
        <p class="qt-mini"><?= nu_icon('gem') ?> Premium care reminders never charge you by themselves; any plan or visit charge is shown before you confirm.</p>
      </section>
      <div class="qt-submit">
        <button class="btn btn-primary btn-lg" type="submit" data-submit>Approve &amp; book <?= nu_icon('arrow-right') ?></button>
        <span class="qt-mini">Total CAD <?= number_format($base * (1 + $tax), 2) ?> · <?= nu_e($q['expires']) ?></span>
      </div>
    </form>
<?php endif; ?>
    <p class="ch-demo">Preview states: <a href="?">Quote ready</a> · <a href="?state=approved">Approved</a> · <a href="?state=declined">Declined</a> · <a href="?state=expired">Expired</a> · <a href="?state=revised">Additional work (v2)</a></p>
  </div>
  <aside class="cp-rules ac-rules">
    <h3>Real prices only</h3><p>Mockup amounts are not business prices. Record inclusions, exclusions and parts availability.</p>
    <h3>Reschedule impact</h3><p>If the quote expires or parts / provider change, show the revised scope and price before confirming a new visit.</p>
    <h3>Secure payments</h3><p>Use hosted / tokenised payment entry. Keep payment status separate from work status. Retry without duplicate charges.</p>
    <h3>No surprise recurring fees</h3><p>Premium terms must name any plan charge and visit charges. A monthly reminder alone never charges the customer.</p>
  </aside>
</main>
<script>
(function () {
  var f = document.querySelector('[data-quote]');
  if (f) {
    var ask = f.querySelector('[data-ask]'), btn = f.querySelector('[data-submit]'), win = f.querySelector('[data-win-box]');
    var upd = function () {
      var d = f.querySelector('input[name=decision]:checked').value;
      ask.hidden = d !== 'ask'; win.style.opacity = d === 'approve' ? '' : '.5';
      btn.firstChild.textContent = d === 'approve' ? 'Approve & book ' : d === 'ask' ? 'Send question ' : 'Decline quote ';
    };
    f.addEventListener('change', upd);
    f.addEventListener('submit', function (e) {
      if (f.querySelector('input[name=decision]:checked').value === 'ask') { e.preventDefault(); f.querySelector('[data-ask-ok]').hidden = false; }
    });
  }
  var no = document.querySelector('[data-extra-no]');
  if (no) no.addEventListener('click', function () { document.querySelector('[data-extra-msg]').hidden = false; no.closest('form').hidden = true; });
})();
</script>
<?php include_once("../includes/footer.php"); ?>
