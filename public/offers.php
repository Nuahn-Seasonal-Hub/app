<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/offers.php: "Review contractor offers" for a posted snow task, on live data (task_offers).
// Client-only and owner-only. Accept posts offer_accept: the payment is authorised (funds held, not captured) and the job becomes Confirmed.
// Boost, cancel and a message to the contractors who offered are real actions in actions/marketplace.php.
require_once("../config/init.php");
requireLogin('client');
require_once("../includes/marketplace/view.php");
$taskId = (int) ($_GET['task'] ?? 0);
$a = mk_task_access($taskId);
if (!$a || $a[1] !== 'client') { http_response_code(404); include_once("../includes/header.php"); echo '<main class="screen"><div class="sv-card"><h1>Task not found</h1><p><a href="' . nu_e(nu_url('public/bookings.php')) . '">Back to my bookings</a></p></div></main>'; include_once("../includes/footer.php"); exit; }
$task = $a[0];
if ($task['status'] !== 'open_for_bids') { header('Location: ' . nu_booking_href(['id' => $taskId, 'status' => $task['status']])); exit; }
$posted = !empty($_GET['posted']);
$target = (float) $task['target_budget'] + (float) $task['boost'];
$boosted = (float) $task['boost'] > 0;
$offers = mk_offers($taskId);
$cards = []; $json = []; $rows = [];
foreach ($offers as $i => $o) {
    $c = mk_contractor_card($o['contractor_id']); if (!$c) continue;
    $rows[$i] = $o; $cards[$i] = $c;
    $json[$i] = ['n' => $c['name'], 'p' => (float) $o['amount'], 'eta' => mk_eta_text($o['eta_minutes']), 'r' => (float) $c['rating'], 'rv' => $c['reviews'], 'j' => $c['jobs'], 'b' => $c['badges'], 'id' => (int) $o['id']];
}
$alerted = count(mk_nearby_contractors($task, 50));
$d = mk_details($task);
$boostMax = (int) MK_BOOST_MAX;
include_once("../includes/header.php");
?>
<main class="screen of">
  <p class="crumbs"><a href="<?= nu_e(nu_url('public/bookings.php')) ?>">My bookings</a> / Offers</p>
<?php if ($posted): ?>
  <p class="of-posted" role="status"><?= nu_icon('check-circle-fill') ?> <span><b>Task posted.</b> Verified contractors nearby have been alerted. Nothing is charged yet.</span></p>
<?php endif; ?>
  <div class="screen__head"><div><h1>Review offers</h1><p><?= nu_e(mk_ref($task)) ?> · posted <?= nu_e(mk_ago((int) (time() - strtotime($task['created_at'])))) ?></p></div><?= mk_test_note() ?></div>

  <section class="sv-card of-task">
    <div class="of-task__top"><span class="of-task__ic"><?= nu_icon('snow') ?></span><div><h2><?= nu_e($task['title']) ?></h2><p><?= nu_icon('geo-alt') ?> <?= nu_e($task['address']) ?></p></div></div>
    <p class="of-task__when"><?= nu_icon('cloud-snow') ?> <?= nu_e($task['mode'] === 'seasonal' ? 'Seasonal plan: cleared after every snowfall of ' . (int) MK_SEASONAL_TRIGGER_CM . ' cm or more' : 'One clear, as soon as a contractor can') ?></p>
    <ul class="of-task__scope"><li><?= nu_e(mk_scope($task)) ?></li><?php if (!empty($task['notes'])): ?><li><?= nu_e($task['notes']) ?></li><?php endif; ?></ul>
    <div class="of-task__bud">
      <span><small>Your target budget</small><b>$<?= (int) $target ?></b></span>
      <?= $boosted ? '<span class="chip chip--blue">' . nu_icon('rocket-takeoff-fill') . 'Boosted +$' . (int) $task['boost'] . '</span>' : '' ?>
      <?= nu_payment_chip('unpaid') ?>
    </div>
    <?= nu_job_machine('open_for_bids') ?>
  </section>

<?php if (!$cards): ?>
  <section class="sv-card of-none">
    <div class="of-radar" aria-hidden="true"><i></i><i></i><i></i><span><?= nu_icon('broadcast') ?></span></div>
    <h2>Waiting for offers</h2>
    <p>We alerted <b><?= (int) $alerted ?> verified contractor<?= $alerted === 1 ? '' : 's' ?></b> near this address. Offers usually start arriving within a few minutes. You will see an update here and in your notifications.</p>
    <?= mk_form('task_boost', ['task_id' => $taskId], 'class="of-none__acts"', 'offers.php?task=' . $taskId) ?>
      <label class="fld"><span class="label">Boost (extra $, 0 to <?= $boostMax ?>)</span><input class="input" name="boost" type="number" min="0" max="<?= $boostMax ?>" step="1" value="<?= (int) min($boostMax, max(10, (int) $task['boost'] + 10)) ?>"></label>
      <button class="btn btn-primary" type="submit"><?= nu_icon('rocket-takeoff-fill') ?> Boost your bid</button>
    </form>
    <p class="of-none__tip"><?= nu_icon('lightbulb') ?> Raising the budget by $10–$25 is the fastest way to get picked up.</p>
  </section>
<?php else: ?>
  <div class="of-bar">
    <h2><?= count($cards) ?> offer<?= count($cards) > 1 ? 's' : '' ?></h2>
<?php if (count($cards) > 1): ?>
    <label class="of-sort"><span class="sr-only">Sort offers</span>
      <select class="input" id="ofSort" aria-label="Sort offers"><option value="best">Best match</option><option value="price">Lowest price</option><option value="eta">Fastest</option><option value="rating">Top rated</option></select>
    </label>
<?php endif; ?>
  </div>
  <div class="of-list" id="ofList">
    <?php $rank = 0; foreach ($cards as $i => $c): $o = $rows[$i]; $price = (float) $o['amount']; $diff = (int) round($price - $target); ?>
      <article class="sv-card of-offer" data-i="<?= $i ?>" data-price="<?= (int) $price ?>" data-rating="<?= $c['rating'] ?>" data-eta="<?= (int) $o['eta_minutes'] ?: 1000 ?>" data-best="<?= $rank++ ?>">
        <header class="of-offer__top">
          <a class="of-offer__who" href="<?= nu_e(nu_url('public/contractor.php')) ?>?id=<?= nu_e($c['id']) ?>"><?= nu_avatar($c, 'lg') ?><span><b><?= nu_e($c['name']) ?></b><?= nu_rating($c) ?><small><?= (int) $c['jobs'] ?> jobs done</small></span></a>
          <div class="of-offer__price"><b>$<?= number_format($price, $price == floor($price) ? 0 : 2) ?></b><small class="<?= $diff > 0 ? 'is-over' : 'is-ok' ?>"><?= $diff === 0 ? 'Matches your target' : ($diff > 0 ? '$' . $diff . ' over target' : '$' . abs($diff) . ' under target') ?></small></div>
        </header>
        <p class="of-offer__tags"><span class="chip chip--blue"><?= $o['kind'] === 'counter' ? 'Counter-offer' : 'Accepted your price' ?></span></p>
        <?= nu_badges($c) ?>
        <p class="of-offer__eta"><?= nu_icon('clock') ?> <?= nu_e(mk_eta_text($o['eta_minutes'])) ?></p>
        <?php if ($o['message'] !== ''): ?><p class="of-offer__note">“<?= nu_e($o['message']) ?>”</p><?php endif; ?>
        <div class="of-offer__acts">
          <button class="btn btn-primary" type="button" data-accept="<?= $i ?>">Accept offer</button>
          <?php if (count($cards) > 1): ?><label class="of-cmp"><input type="checkbox" data-cmp="<?= $i ?>"><span>Compare</span></label><?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
  <p class="of-foot"><?= nu_icon('info-circle') ?> You are not charged for posting or for receiving offers. The price you accept is the price you pay (plus GST), and extra work is only added if you approve it.</p>

  <div class="of-cmpbar" id="cmpBar" hidden><span><b data-cmpn>0</b> selected</span><button class="btn btn-primary btn-sm" type="button" id="cmpGo">Compare offers</button></div>

  <dialog class="msheet msheet--wide" id="cmpSheet" aria-labelledby="cmpT">
    <div class="msheet__in"><button class="msheet__x" type="button" data-close aria-label="Close"><?= nu_icon('x-lg') ?></button>
      <h2 id="cmpT">Compare offers</h2><div class="of-tblwrap"><table class="of-tbl" id="cmpTbl"></table></div></div>
  </dialog>

  <dialog class="msheet" id="accSheet" aria-labelledby="accT">
    <?= mk_form('offer_accept', ['task_id' => $taskId, 'offer_id' => 0], 'class="msheet__in"', 'track.php?id=' . $taskId) ?>
      <button class="msheet__x" type="button" data-close aria-label="Close"><?= nu_icon('x-lg') ?></button>
      <span class="msheet__ic"><?= nu_icon('lock-fill') ?></span>
      <h2 id="accT">Accept <span data-acc-name></span>’s offer</h2>
      <dl class="msheet__rows"><div><dt>Job</dt><dd><?= nu_e($task['title']) ?></dd></div><div><dt>Arrival</dt><dd data-acc-eta></dd></div><div><dt>Amount held (before GST)</dt><dd><b data-acc-price></b></dd></div></dl>
      <?= nu_trust_note('held') ?>
      <?= mk_test_card_field() ?>
      <button class="btn btn-primary btn-block" type="submit">Confirm and hold funds</button>
      <button class="btn btn-ghost btn-block" type="button" data-close>Not now</button>
    </form>
  </dialog>
<?php endif; ?>

  <section class="sv-card mk-sec">
    <details class="mk-more"><summary><?= nu_icon('chat-dots') ?> Message the contractors who offered</summary>
      <?= mk_form('message_send', ['task_id' => $taskId], 'class="mk-form"', 'offers.php?task=' . $taskId) ?>
        <label class="fld"><span class="label">Your message</span><textarea class="input" name="body" rows="2" maxlength="1000" required></textarea></label>
        <button class="btn btn-outline btn-sm" type="submit"><?= nu_icon('send') ?> Send</button>
      </form>
    </details>
    <details class="mk-more"><summary><?= nu_icon('x-circle') ?> Cancel this task</summary>
      <?= mk_form('task_cancel', ['task_id' => $taskId], 'class="mk-form"', 'bookings.php') ?>
        <label class="fld"><span class="label">Reason (optional)</span><input class="input" name="reason" maxlength="200"></label>
        <button class="btn btn-ghost btn-sm" type="submit">Cancel task</button>
      </form>
    </details>
  </section>
</main>
<?php if ($cards): ?>
<script>
(function () {
  var D = <?= json_encode($json) ?>, list = document.getElementById('ofList');
  function openD(d) { if (d.showModal) d.showModal(); else d.setAttribute('open', ''); }
  document.querySelectorAll('.msheet').forEach(function (d) { d.addEventListener('click', function (e) { if (e.target === d || e.target.closest('[data-close]')) { d.close ? d.close() : d.removeAttribute('open'); } }); });
  var sort = document.getElementById('ofSort');
  if (sort) sort.addEventListener('change', function () {
    var k = sort.value, a = Array.prototype.slice.call(list.children);
    a.sort(function (x, y) { return k === 'price' ? x.dataset.price - y.dataset.price : k === 'eta' ? x.dataset.eta - y.dataset.eta : k === 'rating' ? y.dataset.rating - x.dataset.rating : x.dataset.best - y.dataset.best; });
    a.forEach(function (n) { list.appendChild(n); });
  });
  var acc = document.getElementById('accSheet');
  document.querySelectorAll('[data-accept]').forEach(function (b) { b.addEventListener('click', function () {
    var d = D[b.dataset.accept]; acc.querySelector('[data-acc-name]').textContent = d.n.split(' ')[0]; acc.querySelector('[data-acc-eta]').textContent = d.eta; acc.querySelector('[data-acc-price]').textContent = '$' + d.p.toFixed(2); acc.querySelector('[name=offer_id]').value = d.id; openD(acc);
  }); });
  var bar = document.getElementById('cmpBar'), cks = document.querySelectorAll('[data-cmp]');
  function upd() { var n = document.querySelectorAll('[data-cmp]:checked').length; bar.hidden = n < 2; bar.querySelector('[data-cmpn]').textContent = n; cks.forEach(function (c) { c.disabled = n >= 3 && !c.checked; }); }
  cks.forEach(function (c) { c.addEventListener('change', upd); });
  var labels = { b: { verified: 'Verified', insured: 'Insured', id_checked: 'ID checked', certified: 'Certified' } };
  document.getElementById('cmpGo').addEventListener('click', function () {
    var ids = Array.prototype.map.call(document.querySelectorAll('[data-cmp]:checked'), function (c) { return D[c.dataset.cmp]; });
    var rows = [['Price', function (d) { return '<b>$' + d.p + '</b>'; }], ['Arrives', function (d) { return d.eta; }], ['Rating', function (d) { return d.r.toFixed(1) + ' (' + d.rv + ')'; }], ['Jobs done', function (d) { return d.j; }], ['Checks', function (d) { return d.b.map(function (x) { return labels.b[x]; }).join(', '); }]];
    var h = '<thead><tr><th></th>' + ids.map(function (d) { return '<th>' + d.n + '</th>'; }).join('') + '</tr></thead><tbody>';
    rows.forEach(function (r) { h += '<tr><th scope="row">' + r[0] + '</th>' + ids.map(function (d) { return '<td>' + r[1](d) + '</td>'; }).join('') + '</tr>'; });
    document.getElementById('cmpTbl').innerHTML = h + '</tbody>'; openD(document.getElementById('cmpSheet'));
  });
}());
</script>
<?php endif; ?>
<?php include_once("../includes/footer.php"); ?>
