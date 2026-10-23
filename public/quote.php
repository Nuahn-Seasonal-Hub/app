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
// POST /api/payments/intents (hosted, idempotency key; authorize and hold, capture on client approval of the work),
// POST /api/quotes (provider composes: line items by type, call-out fee, taxes by service address province), POST /api/quotes/{id}/amend (on-site revision, pauses the job).
// Preview: ?state=approved|declined|expired|revised, provider composer ?state=draft.
require_once("../config/init.php");
$draft = ($_GET['state'] ?? '') === 'draft';
requireLogin($draft ? 'provider' : 'client');

$q = nu_fixture('mechanic')['quote'];
$state = (string) ($_GET['state'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') { $state = ($_POST['decision'] ?? '') === 'decline' ? 'declined' : (($_POST['decision'] ?? '') === 'extra' ? 'revised_ok' : 'approved'); }
$revised = in_array($state, ['revised', 'revised_ok'], true);
$sum = function ($items) { $s = 0; foreach ($items as $i) { $s += $i[2] * $i[3]; } return $s; };
$base = $sum($q['items']); $extra = $sum($q['extra']['items']);
$tax = array_sum(array_column($q['taxes'], 1));
$mech = nu_contractor('c-2001');
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
      <span class="qt-ver"><?= nu_icon('file-earmark-text') ?> <?= nu_e($q['id']) ?> · v<?= $revised ? 2 : 1 ?> <?= $draft ? nu_booking_chip('quote_pending') : '' ?><?= $draft ? '' : nu_booking_chip($state === 'approved' ? 'confirmed' : ($state === 'declined' ? 'cancelled' : ($revised ? ($state === 'revised_ok' ? 'in_progress' : 'paused') : 'quote_pending'))) ?></span>
    </div>

<?php if ($draft): ?>
    <form class="ac-box qt-draft" data-draft onsubmit="return false">
      <div class="qt-scope__top"><h2>Send a quote</h2><span class="qt-exp"><?= nu_icon('clock') ?> Valid for 24 hours</span></div>
      <p class="qt-veh"><?= nu_icon('car-front-fill') ?> <?= nu_e($q['vehicle']) ?> · <?= nu_e($q['service']) ?> · customer Alice M.</p>
      <p class="qt-p">Itemise parts, labour and your call-out fee. Taxes are added automatically for the service address. The customer can approve, ask a question or decline. <?= nu_sample_tag('Sample form') ?></p>
      <div class="qd-rows" data-rows>
<?php foreach ($q['items'] as $i): ?>
        <div class="qd-row"><input class="input" value="<?= nu_e($i[0]) ?>" aria-label="Item"><span class="qt-type qt-type--<?= strtolower($i[1]) ?>"><?= nu_e($i[1]) ?></span><input class="input qd-amt" inputmode="decimal" value="<?= number_format($i[2] * $i[3], 2, '.', '') ?>" aria-label="Amount in CAD" data-amt></div>
<?php endforeach; ?>
      </div>
      <button class="btn btn-soft btn-sm" type="button" data-add><?= nu_icon('plus-lg') ?> Add line</button>
      <dl class="ap-sum qd-sum"><div><dt>Subtotal</dt><dd data-sub></dd></div>
<?php foreach ($q['taxes'] as $t): ?><div><dt><?= nu_e($t[0]) ?></dt><dd data-tax="<?= (float) $t[1] ?>"></dd></div><?php endforeach; ?>
        <div class="is-total"><dt>Quote total</dt><dd data-total></dd></div></dl>
      <label class="label" for="qd-note">Note to the customer <small>(optional)</small></label><textarea class="input" id="qd-note" rows="2" placeholder="Parts brand, what is not included, earliest window"></textarea>
      <div class="qt-acts"><button class="btn btn-primary" type="button" data-send><?= nu_icon('send') ?> Send quote</button><a class="btn btn-ghost" href="<?= nu_e(nu_url('public/open_jobs.php')) ?>">Back to open jobs</a></div>
      <p class="ac-ok" data-sent hidden role="status"><?= nu_icon('check-circle-fill') ?> Quote sent. The customer is notified. Nothing is charged until they approve. (Preview)</p>
    </form>
<?php else: ?>
<?php if ($state === 'approved'): ?>
    <section class="ch-result" role="status"><?= nu_icon('check-circle-fill') ?><div><h2>Quote v1 approved · booking confirmed</h2>
      <p><?= nu_e($q['service']) ?> with <?= nu_e($q['mechanic']) ?>, <b><?= nu_e($win) ?></b>. Approved version and time are recorded. Funds are <b>held securely</b> and released only when you approve the finished work.</p>
      <p class="qt-chips"><?= nu_payment_chip('authorized') ?></p>
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
      <p class="qt-veh"><?= nu_icon('car-front-fill') ?> <?= nu_e($q['vehicle']) ?></p>
      <div class="qt-mech"><?= nu_avatar($mech, 'sm') ?><span><a href="<?= nu_e(nu_url('public/contractor.php')) ?>?id=<?= nu_e($mech['id']) ?>"><b><?= nu_e($mech['name']) ?></b></a><small><?= nu_icon('star-fill') ?> <?= nu_e($mech['rating']) ?> · <?= (int) $mech['jobs'] ?> jobs</small></span><?= nu_badges($mech) ?></div>
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
<?php foreach ($q['taxes'] as $t): ?>
          <tr><td><?= nu_e($t[0]) ?></td><td>CAD <?= number_format(($base + ($revised ? $extra : 0)) * $t[1], 2) ?></td></tr>
<?php endforeach; ?>
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
        <h2>Your decision</h2>
        <input type="hidden" name="decision" value="approve" data-dec>
        <div class="qt-btns"><button class="btn btn-primary" type="button" data-d="approve"><?= nu_icon('check-circle') ?> Approve quote</button><button class="btn btn-outline" type="button" data-d="ask"><?= nu_icon('chat-dots') ?> Ask question</button><button class="btn btn-outline qt-no" type="button" data-d="decline"><?= nu_icon('x-circle') ?> Decline</button></div>
        <div class="qt-ask" data-ask hidden><label class="sr-only" for="qt-q">Your question</label><textarea class="input" id="qt-q" rows="2" placeholder="e.g. Can you check the rear pads too?"></textarea><button class="btn btn-soft btn-sm" type="button" data-ask-send>Send question</button><p class="ac-ok" data-ask-ok hidden><?= nu_icon('check-circle-fill') ?> Question sent to the mechanic. The quote stays open. (Preview)</p></div>
        <div class="qt-ask" data-decline hidden><p class="qt-mini"><?= nu_icon('info-circle') ?> No work will be done and nothing is charged.</p><button class="btn btn-danger btn-sm" type="submit" name="decision" value="decline">Confirm decline</button></div>
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
        <h2><?= nu_icon('credit-card') ?> Payment <?= nu_payment_chip('unpaid') ?></h2>
        <div class="qt-card"><span class="qt-card__ic"><?= nu_icon('credit-card-2-front') ?></span><span>Card ending 4242 · exp 08/28</span><button class="linkbtn" type="button">Change</button></div>
        <?= nu_trust_note('held') ?>
        <p class="qt-mini"><?= nu_icon('shield-lock') ?> Approving <b>holds</b> CAD <?= number_format($base * (1 + $tax), 2) ?>; it is not charged until you approve the finished work. Payment status is separate from job status. Retries never duplicate a hold.</p>
      </section>
      <div class="qt-submit">
        <button class="btn btn-primary btn-lg" type="submit" data-submit>Approve &amp; book <?= nu_icon('arrow-right') ?></button>
        <span class="qt-mini">Total CAD <?= number_format($base * (1 + $tax), 2) ?> · <?= nu_e($q['expires']) ?></span>
      </div>
    </form>
<?php endif; ?>
<?php endif; /* draft */ ?>
    <p class="ch-demo">Preview states: <a href="?">Quote ready</a> · <a href="?state=approved">Approved</a> · <a href="?state=declined">Declined</a> · <a href="?state=expired">Expired</a> · <a href="?state=revised">Additional work (v2)</a> · <?= nu_role() === 'provider' ? '<a href="?state=draft">Mechanic: send a quote</a>' : '' ?></p>
  </div>
  <aside class="cp-rules ac-rules">
    <h3>Itemised and clear</h3><p>Parts, labour, call-out fee and tax are listed separately, with what is and is not included.</p>
    <h3>Extra work needs your OK</h3><p>If the mechanic finds more work on site they pause and send a revised quote. Approved work and new work are shown separately.</p>
    <h3>Funds held securely</h3><p>Funds are held until you approve the finished work. Card details never touch Nuahn.</p>
  </aside>
</main>
<script>
(function () {
  var f = document.querySelector('[data-quote]');
  if (f) {
    var ask = f.querySelector('[data-ask]'), dec = f.querySelector('[data-decline]'), btn = f.querySelector('[data-submit]'), win = f.querySelector('[data-win-box]'), hid = f.querySelector('[data-dec]');
    var set = function (d) {
      hid.value = d; ask.hidden = d !== 'ask'; dec.hidden = d !== 'decline'; win.style.opacity = d === 'approve' ? '' : '.5';
      f.querySelectorAll('[data-d]').forEach(function (b) { b.classList.toggle('btn-primary', b.dataset.d === d); b.classList.toggle('btn-outline', b.dataset.d !== d); b.setAttribute('aria-pressed', b.dataset.d === d); });
      btn.disabled = d !== 'approve';
    };
    f.querySelectorAll('[data-d]').forEach(function (b) { b.addEventListener('click', function () { set(b.dataset.d); }); });
    f.querySelector('[data-ask-send]').addEventListener('click', function () { f.querySelector('[data-ask-ok]').hidden = false; });
    set('approve');
  }
  var no = document.querySelector('[data-extra-no]');
  if (no) no.addEventListener('click', function () { document.querySelector('[data-extra-msg]').hidden = false; no.closest('form').hidden = true; });
  var d = document.querySelector('[data-draft]');
  if (d) {
    var m = function (n) { return 'CAD ' + n.toFixed(2); };
    var calc = function () {
      var t = 0; d.querySelectorAll('[data-amt]').forEach(function (i) { t += parseFloat(i.value) || 0; });
      d.querySelector('[data-sub]').textContent = m(t); var tot = t;
      d.querySelectorAll('[data-tax]').forEach(function (x) { var v = t * parseFloat(x.dataset.tax); x.textContent = m(v); tot += v; });
      d.querySelector('[data-total]').textContent = m(tot);
    };
    d.addEventListener('input', calc); calc();
    d.querySelector('[data-add]').addEventListener('click', function () {
      var r = document.createElement('div'); r.className = 'qd-row';
      r.innerHTML = '<input class="input" placeholder="Item" aria-label="Item"><span class="qt-type qt-type--parts">Parts</span><input class="input qd-amt" inputmode="decimal" value="0.00" aria-label="Amount in CAD" data-amt>';
      d.querySelector('[data-rows]').appendChild(r); calc();
    });
    d.querySelector('[data-send]').addEventListener('click', function () { d.querySelector('[data-sent]').hidden = false; });
  }
})();
</script>
<?php include_once("../includes/footer.php"); ?>
