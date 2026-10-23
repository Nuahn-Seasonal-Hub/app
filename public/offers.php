<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/offers.php: "Review contractor offers" for a posted task. SAMPLE DATA: fixtures/marketplace.php ['offers'].
// States (?state=): none | one | many | boosted. The state strip at the top is a preview control only.
// Sir Paul: GET /api/tasks/{id}/offers (poll or push; each offer: contractor_id, price, eta, note),
//           POST /api/tasks/{id}/offers/{offer_id}/accept  -> authorises the payment (funds held, NOT captured) then job = Confirmed,
//           POST /api/tasks/{id}/cancel, POST /api/threads {contractor_id, task_id} for Message, POST /api/tasks/{id}/boost.
require_once("../config/init.php");
$m = nu_fixture('marketplace');
$state = in_array($_GET['state'] ?? 'many', ['none', 'one', 'many', 'boosted'], true) ? ($_GET['state'] ?? 'many') : 'many';
$task = $m['task'];
$offers = $m['offers'][$state];
$boosted = $state === 'boosted';
$posted = !empty($_GET['posted']);
$budget = max(10, (int) ($_GET['budget'] ?? $task['target']));
if ($boosted) $task['target'] = 70; elseif (isset($_GET['budget'])) $task['target'] = $budget;
$target = $task['target'];
$jobKey = $state === 'none' ? 'requested' : 'open_for_bids';
$states = ['none' => 'No offers yet', 'one' => 'One offer', 'many' => 'Many offers', 'boosted' => 'Boosted'];
$cards = []; $json = [];
foreach ($offers as $i => $o) { $c = nu_contractor($o['c']); if (!$c) continue; $cards[$i] = $c; $json[$i] = ['n' => $c['name'], 'p' => $o['price'], 'eta' => $o['eta'], 'r' => $c['rating'], 'rv' => $c['reviews'], 'j' => $c['jobs'], 'resp' => $c['response'], 'b' => $c['badges']]; }
include_once("../includes/header.php");
?>
<main class="screen of">
  <p class="crumbs"><a href="<?= nu_e(nu_url('public/bookings.php')) ?>">My bookings</a> / Offers</p>
<?php if ($posted): ?>
  <p class="of-posted" role="status"><?= nu_icon('check-circle-fill') ?> <span><b>Task posted.</b> Verified contractors within 8 km have been alerted. Nothing is charged yet.</span></p>
<?php endif; ?>
  <div class="screen__head"><div><h1>Review offers</h1><p><?= nu_e($task['id']) ?> · posted <?= nu_e($task['posted']) ?></p></div><?= nu_sample_tag() ?></div>

  <nav class="of-states" aria-label="Preview states">
    <?php foreach ($states as $k => $l): ?><a href="?state=<?= $k ?>"<?= $k === $state ? ' aria-current="true"' : '' ?>><?= nu_e($l) ?></a><?php endforeach; ?>
  </nav>

  <section class="sv-card of-task">
    <div class="of-task__top"><span class="of-task__ic"><?= nu_icon('snow') ?></span><div><h2><?= nu_e($task['title']) ?></h2><p><?= nu_icon('geo-alt') ?> <?= nu_e($task['address']) ?></p></div></div>
    <p class="of-task__when"><?= nu_icon('cloud-snow') ?> <?= nu_e($task['when']) ?></p>
    <ul class="of-task__scope"><?php foreach ($task['scope'] as $sc): ?><li><?= nu_e($sc) ?></li><?php endforeach; ?></ul>
    <div class="of-task__bud">
      <span><small>Your target budget</small><b>$<?= (int) $target ?></b></span>
      <?= $boosted ? '<span class="chip chip--blue">' . nu_icon('rocket-takeoff-fill') . 'Boosted +$25</span>' : '' ?>
      <?= nu_payment_chip(nu_job_payment($jobKey)) ?>
    </div>
    <?= nu_job_machine($jobKey) ?>
  </section>

<?php if (!$cards): ?>
  <section class="sv-card of-none">
    <div class="of-radar" aria-hidden="true"><i></i><i></i><i></i><span><?= nu_icon('broadcast') ?></span></div>
    <h2>Waiting for offers</h2>
    <p>We alerted <b>14 verified contractors</b> within 8 km. Offers usually start arriving within a few minutes. We will notify you.</p>
    <div class="of-none__acts">
      <a class="btn btn-primary" href="<?= nu_e(nu_url('public/post_task.php')) ?>"><?= nu_icon('rocket-takeoff-fill') ?> Boost your bid</a>
      <a class="btn btn-outline" href="?state=many">Preview: offers arrive</a>
    </div>
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
  <?php if ($boosted): ?><p class="of-boost"><?= nu_icon('rocket-takeoff-fill') ?> <span>Your boost put this task at the top of the list. Three contractors replied in under 10 minutes.</span></p><?php endif; ?>
  <div class="of-list" id="ofList">
    <?php foreach ($cards as $i => $c): $o = $offers[$i]; $diff = $o['price'] - $target; ?>
      <article class="sv-card of-offer" data-i="<?= $i ?>" data-price="<?= (int) $o['price'] ?>" data-rating="<?= $c['rating'] ?>" data-eta="<?= preg_match('/(\d+) min/', $o['eta'], $mm) ? (int) $mm[1] : (preg_match('/about (\d+) h(?: (\d+) min)?/', $o['eta'], $hh) ? $hh[1] * 60 + (int) ($hh[2] ?? 0) : 1000) ?>" data-best="<?= $i ?>">
        <header class="of-offer__top">
          <a class="of-offer__who" href="<?= nu_e(nu_url('public/contractor.php')) ?>?id=<?= nu_e($c['id']) ?>"><?= nu_avatar($c, 'lg') ?><span><b><?= nu_e($c['name']) ?></b><?= nu_rating($c) ?><small><?= (int) $c['jobs'] ?> jobs · replies in <?= nu_e($c['response']) ?></small></span></a>
          <div class="of-offer__price"><b>$<?= (int) $o['price'] ?></b><small class="<?= $diff > 0 ? 'is-over' : 'is-ok' ?>"><?= $diff === 0 ? 'Matches your target' : ($diff > 0 ? '$' . $diff . ' over target' : '$' . abs($diff) . ' under target') ?></small></div>
        </header>
        <?php if ($o['tags']): ?><p class="of-offer__tags"><?php foreach ($o['tags'] as $t): ?><span class="chip chip--blue"><?= nu_e($t) ?></span><?php endforeach; ?></p><?php endif; ?>
        <?= nu_badges($c) ?>
        <p class="of-offer__eta"><?= nu_icon('clock') ?> <?= nu_e($o['eta']) ?></p>
        <p class="of-offer__note">“<?= nu_e($o['note']) ?>”</p>
        <div class="of-offer__acts">
          <button class="btn btn-primary" type="button" data-accept="<?= $i ?>">Accept offer</button>
          <a class="btn btn-outline" href="<?= nu_e(nu_url('public/chat.php')) ?>?c=<?= nu_e($c['id']) ?>"><?= nu_icon('chat-dots') ?> Message</a>
          <?php if (count($cards) > 1): ?><label class="of-cmp"><input type="checkbox" data-cmp="<?= $i ?>"><span>Compare</span></label><?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
  <p class="of-foot"><?= nu_icon('info-circle') ?> You are not charged for posting or for receiving offers. The price you accept is the price you pay, plus any extra work you approve on site.</p>

  <div class="of-cmpbar" id="cmpBar" hidden><span><b data-cmpn>0</b> selected</span><button class="btn btn-primary btn-sm" type="button" id="cmpGo">Compare offers</button></div>

  <dialog class="msheet msheet--wide" id="cmpSheet" aria-labelledby="cmpT">
    <div class="msheet__in"><button class="msheet__x" type="button" data-close aria-label="Close"><?= nu_icon('x-lg') ?></button>
      <h2 id="cmpT">Compare offers</h2><div class="of-tblwrap"><table class="of-tbl" id="cmpTbl"></table></div></div>
  </dialog>

  <dialog class="msheet" id="accSheet" aria-labelledby="accT">
    <form class="msheet__in" method="get" action="<?= nu_e(nu_url('public/track.php')) ?>">
      <button class="msheet__x" type="button" data-close aria-label="Close"><?= nu_icon('x-lg') ?></button>
      <span class="msheet__ic"><?= nu_icon('lock-fill') ?></span>
      <h2 id="accT">Accept <span data-acc-name></span>’s offer</h2>
      <dl class="msheet__rows"><div><dt>Job</dt><dd><?= nu_e($task['title']) ?></dd></div><div><dt>Arrival</dt><dd data-acc-eta></dd></div><div><dt>Amount held</dt><dd><b data-acc-price></b></dd></div></dl>
      <?= nu_trust_note('held') ?>
      <p class="msheet__card"><?= nu_icon('credit-card') ?> Card ending 4242 <?= nu_sample_tag() ?></p>
      <input type="hidden" name="id" value="<?= nu_e($task['id']) ?>"><input type="hidden" name="accepted" value="1"><input type="hidden" name="c" value="" data-acc-c>
      <button class="btn btn-primary btn-block" type="submit">Confirm and hold funds</button>
      <button class="btn btn-ghost btn-block" type="button" data-close>Not now</button>
    </form>
  </dialog>
<?php endif; ?>
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
    var d = D[b.dataset.accept]; acc.querySelector('[data-acc-name]').textContent = d.n.split(' ')[0]; acc.querySelector('[data-acc-eta]').textContent = d.eta; acc.querySelector('[data-acc-price]').textContent = '$' + d.p.toFixed(2); acc.querySelector('[data-acc-c]').value = d.n; openD(acc);
  }); });
  var bar = document.getElementById('cmpBar'), cks = document.querySelectorAll('[data-cmp]');
  function upd() { var n = document.querySelectorAll('[data-cmp]:checked').length; bar.hidden = n < 2; bar.querySelector('[data-cmpn]').textContent = n; cks.forEach(function (c) { c.disabled = n >= 3 && !c.checked; }); }
  cks.forEach(function (c) { c.addEventListener('change', upd); });
  var labels = { b: { verified: 'Verified', insured: 'Insured', id_checked: 'ID checked', certified: 'Certified' } };
  document.getElementById('cmpGo').addEventListener('click', function () {
    var ids = Array.prototype.map.call(document.querySelectorAll('[data-cmp]:checked'), function (c) { return D[c.dataset.cmp]; });
    var rows = [['Price', function (d) { return '<b>$' + d.p + '</b>'; }], ['Arrives', function (d) { return d.eta; }], ['Rating', function (d) { return d.r.toFixed(1) + ' (' + d.rv + ')'; }], ['Jobs done', function (d) { return d.j; }], ['Replies in', function (d) { return d.resp; }], ['Checks', function (d) { return d.b.map(function (x) { return labels.b[x]; }).join(', '); }]];
    var h = '<thead><tr><th></th>' + ids.map(function (d) { return '<th>' + d.n + '</th>'; }).join('') + '</tr></thead><tbody>';
    rows.forEach(function (r) { h += '<tr><th scope="row">' + r[0] + '</th>' + ids.map(function (d) { return '<td>' + r[1](d) + '</td>'; }).join('') + '</tr>'; });
    document.getElementById('cmpTbl').innerHTML = h + '</tbody>'; openD(document.getElementById('cmpSheet'));
  });
}());
</script>
<?php endif; ?>
<?php include_once("../includes/footer.php"); ?>
