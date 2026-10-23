<?php
// public/calendar.php — Client calendar: weather strip, upcoming services, notifications and snow-day queue (client design v2).
// SAMPLE DATA: fixtures/weather.php, bookings.php, notifications.php. Queue actions only show a preview message.
// Sir Paul: GET /api/weather?address=, GET /api/notifications, POST /api/queue/{booking}/defer, POST /api/bookings/{id}/window
require_once("../config/init.php");
requireLogin('client');
$wx = nu_fixture('weather');
$services = nu_fixture('services');
$bookings = array_filter(nu_fixture('bookings'), function ($b) { return !in_array($b['status'], ['approved', 'disputed', 'completed', 'closed', 'cancelled'], true); });
$notes = nu_fixture('notifications');
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = (string) ($_POST['queue_action'] ?? '');
    if ($a === 'defer') { $msg = 'Done: you’ve let others go first. You are now #5 on Jordan Mercer’s list (est. 7:50 – 8:20 AM).'; }
    elseif ($a === 'window') { $msg = 'Request sent: clear my driveway ' . nu_e($_POST['window'] ?? '') . '. Your contractor will confirm in chat.'; }
}
include_once("../includes/header.php");
?>
<main class="screen screen--wide">
  <div class="screen__head">
    <div><h1>Calendar</h1><p><?= nu_icon('geo-alt') ?> <?= nu_e($wx['city']) ?> · 7-day forecast</p></div>
    <?= nu_sample_tag() ?>
  </div>
<?php if ($msg): ?><div class="alert alert-success"><?= nu_icon('check-circle-fill') ?> <span><?= $msg ?> <em>(Preview only)</em></span></div><?php endif; ?>
  <div class="cal-grid">
    <section class="panel2 span-2" aria-label="Weather forecast">
      <div class="wxstrip">
<?php foreach ($wx['days'] as $i => $d): ?>
        <div class="wxday<?= $d['snow'] ? ' is-snow' : '' ?><?= $i === 0 ? ' is-today' : '' ?>"><b><?= nu_e($d['d']) ?></b><?= nu_icon($d['icon']) ?><span class="wxday__t"><?= (int) $d['hi'] ?>°</span> <span class="wxday__lo"><?= (int) $d['lo'] ?>°</span></div>
<?php endforeach; ?>
      </div>
    </section>

    <section class="panel2">
      <h2 class="panel2__h"><?= nu_icon('bell') ?> Notifications</h2>
<?php foreach ($notes as $n): ?>
      <div class="notif notif--<?= nu_e($n['type']) ?>">
        <span class="notif__ic"><?= nu_icon($n['icon']) ?></span>
        <div style="flex:1;min-width:0">
          <div class="row-flex" style="gap:8px;flex-wrap:nowrap"><p class="notif__t"><?= nu_e($n['title']) ?></p><span class="notif__time"><?= nu_e($n['time']) ?></span></div>
          <p class="notif__x"><?= nu_e($n['text']) ?></p>
<?php if ($n['type'] === 'queue'): ?>
          <div class="queue">
            <div class="queue__pos" role="img" aria-label="Queue position <?= (int) $n['position'] ?> of 5"><span class="is-done"></span><span class="is-done"></span><span class="is-me"></span><span></span><span></span></div>
            <form method="post" class="row-flex">
              <button class="btn btn-outline btn-sm" name="queue_action" value="defer"><?= nu_icon('people') ?> Let others go first</button>
              <a class="btn btn-soft btn-sm" href="<?= nu_e(nu_url('public/chat.php')) ?>?thread=t2"><?= nu_icon('chat-dots') ?> Message</a>
            </form>
            <details>
              <summary><?= nu_icon('clock') ?> Pick a time window instead</summary>
              <form method="post" class="mt-2">
                <input type="hidden" name="queue_action" value="window">
                <div class="chips windows" role="radiogroup" aria-label="Time window">
<?php foreach ($n['windows'] as $k => $w): ?>
                  <label class="chipopt"><input type="radio" name="window" value="<?= nu_e($w) ?>"<?= $k === 0 ? ' checked' : '' ?>><span><?= nu_e($w) ?></span></label>
<?php endforeach; ?>
                </div>
                <button class="btn btn-primary btn-sm btn-block mt-2" type="submit">Request this window</button>
              </form>
            </details>
          </div>
<?php endif; ?>
        </div>
      </div>
<?php endforeach; ?>
    </section>

    <section class="panel2">
      <h2 class="panel2__h"><?= nu_icon('calendar-event') ?> Upcoming services</h2>
      <div class="agenda">
<?php foreach ($bookings as $b): $sv = $services[$b['service']]; $ts = strtotime($b['date']); ?>
        <a class="agenda__item" href="<?= nu_e(nu_booking_href($b)) ?>">
          <span class="agenda__date"><small><?= date('D', $ts) ?></small><b><?= date('j', $ts) ?></b></span>
          <span style="flex:1;min-width:0"><span class="brow__t" style="display:block"><?= nu_e($sv['name']) ?></span><span class="brow__s" style="display:block"><?= nu_e($b['window']) ?> · <?= nu_e($b['team']) ?></span></span>
          <?= nu_booking_chip($b['status']) ?>
        </a>
<?php endforeach; ?>
      </div>
      <a class="btn btn-primary btn-block mt-4" href="<?= nu_e(nu_url('public/index.php#services')) ?>"><?= nu_icon('plus-lg') ?> Book a service</a>
    </section>
  </div>
</main>
<?php include_once("../includes/footer.php"); ?>
