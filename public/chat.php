<?php
// public/chat.php — Chat thread with confirm / reschedule quick replies (client design v2).
// SAMPLE DATA: fixtures/messages.php. Sending only appends locally in the browser; nothing is stored.
// Sir Paul: GET/POST /api/threads/{id}/messages, POST /api/bookings/{id}/reschedule {window}
require_once("../config/init.php");
requireLogin(['client', 'provider']);
$m = nu_fixture('messages');
$th = $m['thread'];
$tid = (string) ($_GET['thread'] ?? $th['id']);
foreach ($m['threads'] as $t) { if ($t['id'] === $tid && $tid !== $th['id']) { $th = ['id' => $t['id'], 'name' => $t['name'], 'about' => $t['about'], 'initials' => $t['initials'], 'messages' => [['me' => false, 'text' => $t['last'], 'time' => $t['time']]], 'actions' => ['Sounds good', 'Running late?', 'Reschedule']]; } }
include_once("../includes/header.php");
?>
<main class="screen">
  <div class="chat">
    <div class="chat__head">
      <span class="avatar"><?= nu_e($th['initials']) ?></span>
      <div><div class="team__name"><?= nu_e($th['name']) ?></div><div class="team__sub"><?= nu_e($th['about']) ?></div></div>
      <span class="spacer"></span><?= nu_sample_tag('Sample') ?>
    </div>
    <div class="chat__ctx"><?= nu_icon('calendar-event') ?> <?= nu_e($th['about']) ?><a class="btn btn-soft btn-sm" href="<?= nu_e(nu_url('public/bookings.php')) ?>">View</a></div>
    <div class="chat__log" data-log aria-live="polite">
<?php foreach ($th['messages'] as $msg): ?>
<?php if (($msg['type'] ?? '') === 'proposal'): ?>
      <div class="bub bub--prop"><b><?= nu_icon('arrow-repeat') ?> <?= nu_e($msg['title']) ?></b><?= nu_e($msg['text']) ?><span class="bub__time"><?= nu_e($msg['time']) ?></span></div>
<?php else: ?>
      <div class="bub<?= $msg['me'] ? ' bub--me' : '' ?>"><?= nu_e($msg['text']) ?><span class="bub__time"><?= nu_e($msg['time']) ?></span></div>
<?php endif; ?>
<?php endforeach; ?>
    </div>
    <div class="quick" data-quick>
<?php foreach ($th['actions'] as $a): ?>
      <button type="button"><?= nu_e($a) ?></button>
<?php endforeach; ?>
    </div>
    <form class="composer" data-composer>
      <label class="sr-only" for="msg">Message</label>
      <input id="msg" placeholder="Write a message…" autocomplete="off">
      <button class="btn btn-primary btn-icon" type="submit" aria-label="Send"><?= nu_icon('send') ?></button>
    </form>
  </div>
</main>
<script>
(function () {
  var log = document.querySelector('[data-log]'), f = document.querySelector('[data-composer]');
  function add(text, me) {
    var b = document.createElement('div'); b.className = 'bub' + (me ? ' bub--me' : '');
    b.textContent = text; var t = document.createElement('span'); t.className = 'bub__time'; t.textContent = 'now'; b.appendChild(t);
    log.appendChild(b); b.scrollIntoView({ block: 'end', behavior: 'smooth' });
  }
  f.addEventListener('submit', function (e) { e.preventDefault(); var i = f.querySelector('input'); if (i.value.trim()) { add(i.value.trim(), true); i.value = ''; } });
  document.querySelector('[data-quick]').addEventListener('click', function (e) {
    var b = e.target.closest('button'); if (!b) return; add(b.textContent, true);
    if (/confirm/i.test(b.textContent)) setTimeout(function () { add('Perfect, you’re booked for Mon 1:00 – 3:00 PM. See you then!', false); }, 700);
  });
})();
</script>
<?php include_once("../includes/footer.php"); ?>
