<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/updates.php: in-app notification list (job_notifications) for clients, contractors and staff.
// Email / SMS delivery is not wired yet: this table is the single place to hook those channels (see docs/BOOKING-BACKEND.md).
require_once("../config/init.php");
requireLogin(['client', 'provider', 'manager', 'superadmin', 'admin']);
require_once("../includes/marketplace/view.php");
$uid = mk_uid();
$st = mk_db()->prepare('SELECT *, TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age_s FROM job_notifications WHERE user_id = ? ORDER BY id DESC LIMIT 60');
$st->execute([$uid]);
$rows = $st->fetchAll();
$unread = 0; foreach ($rows as $r) if (!$r['read_at']) $unread++;
$icons = ['offer' => 'megaphone', 'accepted' => 'check-circle-fill', 'payment' => 'credit-card', 'message' => 'chat-dots', 'quote' => 'receipt', 'submitted' => 'clipboard-check',
          'approved' => 'check2-circle', 'dispute' => 'exclamation-triangle', 'vetting' => 'shield-check', 'job' => 'briefcase', 'status' => 'clock'];
$isProv = isRole('provider');
include_once("../includes/header.php");
$body = function () use ($rows, $unread, $icons) { ?>
    <header class="pd-head">
      <div><h1>Updates</h1><p><?= $unread ? (int) $unread . ' unread' : 'You are all caught up.' ?></p></div>
      <?php if ($unread) echo mk_form('notif_read', ['all' => '1'], '', 'updates.php') . '<button class="btn btn-outline btn-sm" type="submit">' . nu_icon('check-all') . ' Mark all as read</button></form>'; ?>
    </header>
<?php if (!$rows): nu_empty('bell', 'No updates yet', 'Offers, payments, messages and job progress will show up here.'); else: ?>
    <ul class="mk-feed">
<?php foreach ($rows as $r): ?>
      <li class="mk-feed__i<?= $r['read_at'] ? '' : ' is-new' ?>">
        <span class="mk-feed__ic"><?= nu_icon($icons[$r['type']] ?? 'bell') ?></span>
        <div class="mk-feed__b">
          <b><?= nu_e($r['title']) ?></b>
          <?php if ($r['body'] !== ''): ?><p><?= nu_e($r['body']) ?></p><?php endif; ?>
          <small><?= nu_e(mk_ago($r['age_s'])) ?></small>
        </div>
        <div class="mk-feed__a">
          <?php if ($r['link'] !== null && $r['link'] !== ''): ?><a class="btn btn-outline btn-sm" href="<?= nu_e($r['link']) ?>">Open</a><?php endif; ?>
          <?php if (!$r['read_at']) echo mk_form('notif_read', ['id' => $r['id']], '', 'updates.php') . '<button class="btn btn-ghost btn-sm" type="submit">Mark read</button></form>'; ?>
        </div>
      </li>
<?php endforeach; ?>
    </ul>
<?php endif;
};
if ($isProv): ?>
<div class="console">
  <?= nu_console_nav(nu_provider_nav(), 'messages', 'Contractor workspace', 'Everything<br>that changed<br>on your jobs.') ?>
  <main class="console__main"><?php $body(); ?></main>
</div>
<?php else: ?>
<main class="screen"><?php $body(); ?></main>
<?php endif;
include_once("../includes/footer.php"); ?>
