<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/quote.php: mechanic quotes, on live data (quotes + quote_items).
//   Provider (vetted, mobile-mechanics): the itemised quote builder, parts + labour + call-out fee, GST/PST added for the service address.
//   Client (owner only): review each quote, then approve (choose a window; the payment is authorised and held), ask a question or decline.
// Posts go through actions/marketplace.php (quote_send, quote_decide). Extra work found on site is a separate revision flow on track.php.
require_once("../config/init.php");
requireLogin(['client', 'provider']);
require_once("../includes/marketplace/view.php");
$taskId = (int) ($_GET['task'] ?? 0);
$a = mk_task_access($taskId);
$role = mk_role();
$deny = function () { http_response_code(404); include_once("../includes/header.php"); echo '<main class="screen"><div class="sv-card"><h1>Request not found</h1><p><a href="' . nu_e(nu_url(mk_role() === 'provider' ? 'public/open_jobs.php' : 'public/bookings.php')) . '">Back</a></p></div></main>'; include_once("../includes/footer.php"); exit; };
if (!$a || $a[0]['service'] !== 'mobile-mechanics') $deny();
[$task, $rel] = $a;
if ($rel === 'client' && $task['status'] !== 'quote_pending') { header('Location: ' . nu_booking_href(['id' => $taskId, 'status' => $task['status']])); exit; }
if ($rel === 'contractor') { header('Location: ' . nu_url('public/track.php') . '?id=' . $taskId); exit; }
if (!in_array($rel, ['client', 'board', 'bidder'], true)) $deny();
$det = mk_details($task);
$veh = $det['vehicle'] ?? [];
$vehTxt = trim(($veh['year'] ?? '') . ' ' . ($veh['make'] ?? '') . ' ' . ($veh['model'] ?? '') . ' ' . ($veh['trim'] ?? '') . ' ' . ($veh['engine'] ?? ''));
$gst = (float) MK_GST_PCT; $pst = (float) MK_PST_PCT;
$back = 'quote.php?task=' . $taskId;
include_once("../includes/header.php");
?>
<main class="qt" id="quote">
  <div class="ac-wrap qt-wrap">
    <div class="ac-head">
      <div>
        <h1><?= $rel === 'client' ? 'Review mechanic quotes' : 'Send a quote' ?></h1>
        <p class="crumbs"><a href="<?= nu_e(nu_url($rel === 'client' ? 'public/bookings.php' : 'public/open_jobs.php')) ?>"><?= $rel === 'client' ? 'My Bookings' : 'Open jobs' ?></a> / <?= nu_e(mk_ref($task)) ?></p>
      </div>
      <span class="qt-ver"><?= nu_icon('file-earmark-text') ?> <?= nu_e($task['title']) ?> <?= nu_booking_chip($task['status']) ?></span>
    </div>
    <?= mk_test_note() ?>

<?php if ($rel !== 'client'):
    $mine = null;
    foreach (mk_quotes_for_task($taskId) as $q) { if ((int) $q['contractor_id'] === mk_uid()) { $mine = $q; break; } }
    $vetted = mk_is_vetted(mk_uid(), 'mobile-mechanics');
?>
<?php if (!$vetted): ?>
    <section class="ac-box"><p class="mk-note mk-note--warn"><?= nu_icon('exclamation-circle') ?> <span>Only approved mechanics can send quotes. <a href="<?= nu_e(nu_url('public/contractor_setup.php')) ?>">Check your application status</a>.</span></p></section>
<?php elseif ($task['status'] !== 'quote_pending'): ?>
    <section class="ac-box"><p class="qt-p">This request is no longer waiting for quotes.</p></section>
<?php else: ?>
<?php if ($mine): ?>
    <section class="ch-result" role="status"><?= nu_icon('check-circle-fill') ?><div><h2>You sent a quote (v<?= (int) $mine['version'] ?>)</h2><p>Status: <b><?= nu_e($mine['status']) ?></b> · CAD <?= number_format((float) $mine['total'], 2) ?> incl. tax. Sending again replaces it.</p></div></section>
<?php endif; ?>
    <?= mk_form('quote_send', ['task_id' => $taskId], 'class="ac-box qt-draft" data-draft', $back) ?>
      <div class="qt-scope__top"><h2>Itemised quote</h2><span class="qt-exp"><?= nu_icon('clock') ?> Valid for <?= (int) MK_QUOTE_VALID_HOURS ?> hours</span></div>
      <p class="qt-veh"><?= nu_icon('car-front-fill') ?> <?= nu_e($vehTxt) ?> · <?= nu_e($task['title']) ?> · <?= nu_e(mk_area($task['address'])) ?></p>
<?php if (!empty($det['extras'])): ?>
      <p class="qt-p"><?php foreach ($det['extras'] as $k => $v): if ($v === '' || $v === []) continue; ?><b><?= nu_e(ucfirst(str_replace('_', ' ', $k))) ?>:</b> <?= nu_e(is_array($v) ? implode(', ', $v) : $v) ?> · <?php endforeach; ?></p>
<?php endif; ?>
<?php if ($task['notes']): ?><p class="qt-p">Client notes: <?= nu_e($task['notes']) ?></p><?php endif; ?>
      <div class="mk-photos"><?= mk_photo_grid($taskId, 'issue', '') ?></div>
      <p class="qt-p">Itemise parts, labour and your call-out fee. GST (<?= nu_e(rtrim(rtrim(number_format($gst, 2), '0'), '.')) ?>%)<?= $pst > 0 ? ' and PST (' . nu_e(rtrim(rtrim(number_format($pst, 2), '0'), '.')) . '%)' : '' ?> is added automatically. The client can approve, ask a question or decline. Nothing is charged until they approve.</p>
      <div class="qd-rows" data-rows>
<?php $defaults = [['Fee', 'Call-out fee', 1, 40], ['Parts', '', 1, ''], ['Labour', '', 1, '']]; foreach ($defaults as $i => $r): ?>
        <div class="mk-form mk-form--row qd-row"><select class="input" name="item_type[]" aria-label="Type"><?php foreach (['Parts', 'Labour', 'Fee'] as $ty): ?><option<?= $ty === $r[0] ? ' selected' : '' ?>><?= $ty ?></option><?php endforeach; ?></select><input class="input" name="item_desc[]" value="<?= nu_e($r[1]) ?>" placeholder="Description" maxlength="200" aria-label="Item"><input class="input" name="item_qty[]" type="number" min="0.25" max="999" step="0.25" value="<?= (float) $r[2] ?>" aria-label="Quantity or hours" data-qty><input class="input" name="item_price[]" type="number" min="0" max="99999" step="0.01" value="<?= nu_e($r[3]) ?>" placeholder="0.00" aria-label="Unit price in CAD" data-price></div>
<?php endforeach; ?>
      </div>
      <button class="btn btn-soft btn-sm" type="button" data-add><?= nu_icon('plus-lg') ?> Add line</button>
      <dl class="ap-sum qd-sum"><div><dt>Subtotal</dt><dd data-sub>CAD 0.00</dd></div><div><dt>GST</dt><dd data-tax="<?= $gst / 100 ?>"></dd></div><?php if ($pst > 0): ?><div><dt>PST</dt><dd data-tax="<?= $pst / 100 ?>"></dd></div><?php endif; ?>
        <div class="is-total"><dt>Quote total</dt><dd data-total></dd></div></dl>
      <label class="fld"><span class="label">Scope of work</span><textarea class="input" name="scope" rows="2" maxlength="600" required placeholder="What you will do"></textarea></label>
      <label class="fld"><span class="label">Includes <small>(one per line, optional)</small></span><textarea class="input" name="includes" rows="2" maxlength="400"></textarea></label>
      <label class="fld"><span class="label">Not included <small>(optional)</small></span><textarea class="input" name="excludes" rows="2" maxlength="400"></textarea></label>
      <label class="fld"><span class="label">Time windows you can offer <small>(one per line)</small></span><textarea class="input" name="windows" rows="3" maxlength="400" required><?= nu_e("Tomorrow 9–11 AM\nTomorrow 1–3 PM\nThis weekend") ?></textarea></label>
      <div class="qt-acts"><button class="btn btn-primary" type="submit"><?= nu_icon('send') ?> <?= $mine ? 'Replace quote' : 'Send quote' ?></button><a class="btn btn-ghost" href="<?= nu_e(nu_url('public/open_jobs.php')) ?>">Back to open jobs</a></div>
    </form>
<?php endif; ?>
<?php else:
    $quotes = mk_quotes_for_task($taskId);
?>
<?php if (!$quotes): ?>
    <section class="ac-box"><?php nu_empty('hourglass-split', 'Waiting for quotes', 'Approved mechanics near you were notified. You will get an alert when the first itemised quote arrives. Nothing is charged until you approve one.'); ?>
      <?= mk_form('task_cancel', ['task_id' => $taskId], 'class="qt-acts"', 'bookings.php') ?><button class="btn btn-ghost btn-sm" type="submit">Cancel request</button></form></section>
<?php endif; ?>
<?php foreach ($quotes as $q): $mech = mk_contractor_card($q['contractor_id']); $open = in_array($q['status'], ['sent', 'question'], true) && (int) $q['left_min'] > 0; $wins = array_values(array_filter(explode('|', (string) $q['windows']))); ?>
    <section class="ac-box qt-scope">
      <div class="qt-scope__top"><h2><?= nu_e($task['title']) ?> · v<?= (int) $q['version'] ?></h2><span class="qt-exp"><?= nu_icon('clock') ?> <?= $q['status'] === 'declined' ? 'Declined' : ((int) $q['left_min'] > 0 ? 'Expires in ' . ((int) $q['left_min'] >= 90 ? (int) round($q['left_min'] / 60) . ' h' : (int) $q['left_min'] . ' min') : 'Expired') ?></span></div>
<?php if ($mech): ?>
      <div class="qt-mech"><?= nu_avatar($mech, 'sm') ?><span><a href="<?= nu_e(nu_url('public/contractor.php')) ?>?id=<?= (int) $mech['id'] ?>"><b><?= nu_e($mech['name']) ?></b></a><small><?= nu_icon('star-fill') ?> <?= $mech['reviews'] ? nu_e(number_format($mech['rating'], 1)) : 'New' ?> · <?= (int) $mech['jobs'] ?> jobs</small></span><?= nu_badges($mech) ?></div>
<?php endif; ?>
      <p class="qt-p"><?= nu_e($q['scope']) ?></p>
      <table class="qt-tbl">
        <thead><tr><th scope="col">Item</th><th scope="col">Amount</th></tr></thead>
        <tbody>
<?php foreach ($q['items'] as $i): ?>
          <tr><td><?= nu_e($i['description']) ?><span class="qt-meta"><span class="qt-type qt-type--<?= strtolower($i['type']) ?>"><?= nu_e($i['type']) ?></span><?= $i['type'] === 'Labour' ? rtrim(rtrim(number_format((float) $i['qty'], 2), '0'), '.') . ' h × CAD ' . number_format((float) $i['unit_price'], 2) : 'Qty ' . rtrim(rtrim(number_format((float) $i['qty'], 2), '0'), '.') ?></span></td><td>CAD <?= number_format((float) $i['qty'] * (float) $i['unit_price'], 2) ?></td></tr>
<?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr><td>Subtotal</td><td>CAD <?= number_format((float) $q['subtotal'], 2) ?></td></tr>
          <tr><td>GST</td><td>CAD <?= number_format((float) $q['gst'], 2) ?></td></tr>
<?php if ((float) $q['pst'] > 0): ?><tr><td>PST</td><td>CAD <?= number_format((float) $q['pst'], 2) ?></td></tr><?php endif; ?>
          <tr class="is-total"><td>Quote total</td><td>CAD <?= number_format((float) $q['total'], 2) ?></td></tr>
        </tfoot>
      </table>
<?php if ($q['includes_text'] || $q['excludes_text']): ?>
      <div class="qt-inc"><div><b><?= nu_icon('check-circle') ?> Includes</b><?php foreach (array_filter(preg_split('/\R/', (string) $q['includes_text'])) as $x): ?><span><?= nu_e($x) ?></span><?php endforeach; ?></div>
        <div><b><?= nu_icon('dash-circle') ?> Not included</b><?php foreach (array_filter(preg_split('/\R/', (string) $q['excludes_text'])) as $x): ?><span><?= nu_e($x) ?></span><?php endforeach; ?></div></div>
<?php endif; ?>
<?php if ($open): ?>
      <?= mk_form('quote_decide', ['quote_id' => $q['id'], 'task_id' => $taskId], 'class="ac-two"', $back) ?>
        <section class="ac-box" data-win-box>
          <h2>Choose your window</h2>
          <div class="ch-wins">
<?php foreach ($wins as $i => $w): ?>
            <label class="ch-win"><input type="radio" name="window" value="<?= nu_e($w) ?>"<?= $i === 0 ? ' checked' : '' ?>><i class="ch-win__r" aria-hidden="true"></i><span><?= nu_e($w) ?></span><em>Offered</em></label>
<?php endforeach; ?>
          </div>
        </section>
        <section class="ac-box qt-pay">
          <h2><?= nu_icon('credit-card') ?> Payment <?= nu_payment_chip('unpaid') ?></h2>
          <?= mk_test_card_field() ?>
          <?= nu_trust_note('held') ?>
          <p class="qt-mini"><?= nu_icon('shield-lock') ?> Approving <b>holds</b> CAD <?= number_format((float) $q['total'], 2) ?>. It is not charged until you approve the finished work. Card details are entered in the secure payment form and never reach Nuahn.</p>
        </section>
        <section class="ac-box">
          <details class="mk-more"><summary><?= nu_icon('chat-dots') ?> Ask a question first</summary>
            <label class="sr-only" for="qq<?= (int) $q['id'] ?>">Your question</label><textarea class="input" id="qq<?= (int) $q['id'] ?>" name="message" rows="2" maxlength="800" placeholder="e.g. Can you check the rear pads too?"></textarea>
            <button class="btn btn-soft btn-sm" type="submit" name="decision" value="question">Send question</button></details>
        </section>
        <div class="qt-submit">
          <button class="btn btn-primary btn-lg" type="submit" name="decision" value="approve">Approve &amp; book <?= nu_icon('arrow-right') ?></button>
          <button class="btn btn-outline" type="submit" name="decision" value="decline" data-confirm="Decline this quote? No work will be done and nothing is charged.">Decline</button>
          <span class="qt-mini">Total CAD <?= number_format((float) $q['total'], 2) ?><?= $q['status'] === 'question' ? ' · question sent, waiting for a reply' : '' ?></span>
        </div>
      </form>
<?php else: ?>
      <p class="qt-mini"><?= nu_icon('info-circle') ?> <?= $q['status'] === 'declined' ? 'You declined this quote.' : 'This quote has expired. Ask the mechanic to send a fresh one.' ?></p>
<?php endif; ?>
    </section>
<?php endforeach; ?>
<?php endif; ?>
  </div>
  <aside class="cp-rules ac-rules">
    <h3>Itemised and clear</h3><p>Parts, labour, call-out fee and tax are listed separately, with what is and is not included.</p>
    <h3>Extra work needs your OK</h3><p>If the mechanic finds more work on site they pause and send a revised quote. Nothing extra happens until you approve it.</p>
    <h3>Funds held securely</h3><p>Funds are held until you approve the finished work. Card details never touch Nuahn.</p>
  </aside>
</main>
<script>
(function () {
  var d = document.querySelector('[data-draft]');
  if (d) {
    var m = function (n) { return 'CAD ' + n.toFixed(2); };
    var calc = function () {
      var t = 0; d.querySelectorAll('[data-rows] .qd-row').forEach(function (r) { t += (parseFloat(r.querySelector('[data-qty]').value) || 0) * (parseFloat(r.querySelector('[data-price]').value) || 0); });
      d.querySelector('[data-sub]').textContent = m(t); var tot = t;
      d.querySelectorAll('[data-tax]').forEach(function (x) { var v = Math.round(t * parseFloat(x.dataset.tax) * 100) / 100; x.textContent = m(v); tot += v; });
      d.querySelector('[data-total]').textContent = m(tot);
    };
    d.addEventListener('input', calc); calc();
    d.querySelector('[data-add]').addEventListener('click', function () {
      var src = d.querySelector('[data-rows] .qd-row'), r = src.cloneNode(true);
      r.querySelectorAll('input').forEach(function (i) { i.value = i.hasAttribute('data-qty') ? '1' : ''; }); r.querySelector('select').value = 'Parts';
      d.querySelector('[data-rows]').appendChild(r); calc();
    });
  }
  document.addEventListener('click', function (e) { var b = e.target.closest('[data-confirm]'); if (b && !window.confirm(b.dataset.confirm)) e.preventDefault(); });
})();
</script>
<?php include_once("../includes/footer.php"); ?>
