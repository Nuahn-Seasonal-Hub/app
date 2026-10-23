<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/bookings.php: My Bookings list. Each row opens the screen for its state (track, quote, snow event, activity)
// and shows job status and payment status side by side (payment is a separate state, doc2 p21).
// SAMPLE DATA: fixtures/bookings.php. Sir Paul: GET /api/bookings?client_id=&status=upcoming|past
require_once("../config/init.php");
requireLogin('client');
$services = nu_fixture('services');
$all = nu_fixture('bookings');
$pastStates = ['approved', 'completed', 'disputed', 'closed', 'cancelled'];
$tab = ($_GET['tab'] ?? 'upcoming') === 'past' ? 'past' : 'upcoming';
$list = array_values(array_filter($all, function ($b) use ($tab, $pastStates) { return in_array($b['status'], $pastStates, true) === ($tab === 'past'); }));
$open = function ($b) { return nu_booking_href($b); };
include_once("../includes/header.php");
?>
<main class="screen">
  <div class="screen__head">
    <div><h1>My bookings</h1><p>Your tasks, contractor offers, held payments and past work.</p></div>
    <?= nu_sample_tag() ?>
  </div>
  <nav class="filters" aria-label="Filter bookings">
    <a href="?tab=upcoming"<?= $tab === 'upcoming' ? ' aria-current="true"' : '' ?>>Upcoming</a>
    <a href="?tab=past"<?= $tab === 'past' ? ' aria-current="true"' : '' ?>>Past</a>
    <a href="<?= nu_e(nu_url('public/care_plans.php')) ?>">Care plans</a>
  </nav>
  <div class="blist">
<?php foreach ($list as $i => $b): $sv = $services[$b['service']]; ?>
    <div class="brow-w" data-reveal style="--i:<?= $i ?>">
    <a class="brow" href="<?= nu_e($open($b)) ?>">
      <span class="brow__img"><?= nu_photo($sv['photo'], '', ['sizes' => '72px', 'widths' => [400]]) ?></span>
      <span class="brow__main">
        <p class="brow__t"><?= nu_e($sv['name']) ?> <small>#<?= nu_e($b['id']) ?></small></p>
        <p class="brow__s"><?= nu_icon('calendar3') ?> <?= nu_e($b['when']) ?> · <?= nu_e($b['window']) ?></p>
        <p class="brow__s"><?= nu_e($b['team']) ?> · <?= nu_e($b['plan']) ?> · <?= nu_e($b['freq']) ?><?= !empty($b['offers']) ? ' · <b>' . (int) $b['offers'] . ' offers</b>' : '' ?></p>
      </span>
      <span class="brow__side"><?= nu_booking_chip($b['status']) ?><?= nu_payment_chip($b['payment'] ?? 'unpaid') ?></span>
    </a>
    <p class="brow__acts">
<?php if (!in_array($b['status'], $pastStates, true)): ?>
      <a href="<?= nu_e(nu_url('public/change_appointment.php') . '?id=' . rawurlencode($b['id'])) ?>"><?= nu_icon('calendar-check') ?> <?= $b['status'] === 'open_for_bids' ? 'Edit task' : 'Change' ?></a>
<?php else: ?>
      <a href="<?= nu_e(nu_book_url($b['service'])) ?>"><?= nu_icon('arrow-repeat') ?> Post again</a>
<?php endif; ?>
      <a href="<?= nu_e(nu_url('public/activity.php')) ?>"><?= nu_icon('activity') ?> Activity</a>
      <a href="<?= nu_e(nu_url('public/messages.php')) ?>"><?= nu_icon('chat-dots') ?> Message</a>
    </p>
    </div>
<?php endforeach; ?>
<?php if (!$list): nu_empty('calendar3', 'No past bookings yet', 'Approved jobs with before and after photos will appear here.'); endif; ?>
  </div>
  <a class="btn btn-primary btn-block mt-6" href="<?= nu_e(nu_url('public/index.php#services')) ?>"><?= nu_icon('plus-lg') ?> Start a task</a>
</main>
<?php include_once("../includes/footer.php"); ?>
