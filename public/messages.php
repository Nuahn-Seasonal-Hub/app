<?php
// public/messages.php — Messages list (client design v2). SAMPLE DATA: fixtures/messages.php.
// Sir Paul: GET /api/threads?user_id= (threads + unread counts)
require_once("../config/init.php");
requireLogin(['client', 'provider']);
$threads = nu_fixture('messages')['threads'];
include_once("../includes/header.php");
?>
<main class="screen">
  <div class="screen__head">
    <div><h1>Messages</h1><p>Confirm, reschedule or ask your team anything.</p></div>
    <?= nu_sample_tag() ?>
  </div>
  <div class="panel2 list-card">
<?php foreach ($threads as $t): ?>
    <a class="thread<?= $t['unread'] ? ' is-unread' : '' ?>" href="<?= nu_e(nu_url('public/chat.php') . '?thread=' . rawurlencode($t['id'])) ?>">
      <span class="avatar"><?= nu_e($t['initials']) ?></span>
      <span class="thread__main">
        <span class="thread__top"><span class="thread__name"><?= nu_e($t['name']) ?></span><span class="thread__time"><?= nu_e($t['time']) ?></span></span>
        <span class="thread__about" style="display:block"><?= nu_e($t['about']) ?></span>
        <span class="thread__last" style="display:block"><?= nu_e($t['last']) ?></span>
      </span>
      <?= $t['unread'] ? '<span class="badge-n">' . (int) $t['unread'] . '</span>' : '' ?>
    </a>
<?php endforeach; ?>
  </div>
</main>
<?php include_once("../includes/footer.php"); ?>
