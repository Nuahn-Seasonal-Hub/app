<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/bookings.php: My Bookings (client). Live data: the signed-in client's own tasks only (mk_client_tasks).
// Each row opens the screen for its state (offers, quote, approve, track) and shows job status and payment status side by side.
require_once("../config/init.php");
requireLogin('client');
require_once("../includes/marketplace/view.php");
$tab = ($_GET['tab'] ?? 'upcoming') === 'past' ? 'past' : 'upcoming';
$list = mk_client_tasks(mk_uid(), $tab === 'past');
include_once("../includes/header.php");
?>
<main class="screen">
  <div class="screen__head">
    <div><h1>My bookings</h1><p>Your tasks, contractor offers, held payments and past work.</p></div>
    <?= mk_test_note() ?>
  </div>
  <nav class="filters" aria-label="Filter bookings">
    <a href="?tab=upcoming"<?= $tab === 'upcoming' ? ' aria-current="true"' : '' ?>>Upcoming</a>
    <a href="?tab=past"<?= $tab === 'past' ? ' aria-current="true"' : '' ?>>Past</a>
  </nav>
  <div class="blist">
<?php foreach ($list as $i => $b): $svc = $b['service'] === 'snow-removal' ? 'Winter Snow Removal' : 'Mobile Mechanics'; ?>
    <div class="brow-w" data-reveal style="--i:<?= min($i, 12) ?>">
    <a class="brow" href="<?= nu_e(nu_booking_href($b)) ?>">
      <span class="brow__img brow__img--ic"><?= nu_icon($b['service'] === 'snow-removal' ? 'snow' : 'wrench-adjustable') ?></span>
      <span class="brow__main">
        <p class="brow__t"><?= nu_e($b['title']) ?> <small><?= nu_e(mk_ref($b)) ?></small></p>
        <p class="brow__s"><?= nu_icon('calendar3') ?> <?= nu_e(mk_dt($b['created_at'], 'M j')) ?> · <?= nu_e($svc) ?><?= $b['mode'] === 'seasonal' ? ($b['parent_id'] ? ' · plan visit' : ' · seasonal plan') : '' ?></p>
        <p class="brow__s"><?= nu_icon('geo-alt') ?> <?= nu_e($b['address']) ?><?= $b['contractor_name'] ? ' · ' . nu_e($b['contractor_name']) : '' ?><?= $b['offer_count'] ? ' · <b>' . (int) $b['offer_count'] . ' offer' . ($b['offer_count'] > 1 ? 's' : '') . '</b>' : '' ?></p>
      </span>
      <span class="brow__side"><?= nu_booking_chip($b['status']) ?><?= nu_payment_chip($b['payment_status']) ?></span>
    </a>
    <p class="brow__acts">
<?php if ($tab === 'upcoming'): ?>
      <a href="<?= nu_e(nu_booking_href($b)) ?>"><?= nu_icon('arrow-right-circle') ?> Open</a>
      <a href="<?= nu_e(nu_url('public/track.php')) ?>?id=<?= (int) $b['id'] ?>#messages"><?= nu_icon('chat-dots') ?> Message</a>
<?php else: ?>
      <a href="<?= nu_e(nu_start_url($b['service'])) ?>"><?= nu_icon('arrow-repeat') ?> Post again</a>
      <a href="<?= nu_e(nu_url('public/track.php')) ?>?id=<?= (int) $b['id'] ?>"><?= nu_icon('images') ?> Photos</a>
<?php endif; ?>
    </p>
    </div>
<?php endforeach; ?>
<?php if (!$list): nu_empty('calendar3', $tab === 'past' ? 'No past bookings yet' : 'No upcoming bookings', $tab === 'past' ? 'Approved jobs with before and after photos will appear here.' : 'Post a snow task or request a mechanic and it will show up here.'); endif; ?>
  </div>
  <a class="btn btn-primary btn-block mt-6" href="<?= nu_e(nu_url('public/index.php#services')) ?>"><?= nu_icon('plus-lg') ?> Start a task</a>
</main>
<?php include_once("../includes/footer.php"); ?>
