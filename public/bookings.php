<?php
// public/bookings.php — My Bookings list (client design v2). SAMPLE DATA: fixtures/bookings.php.
// Sir Paul: GET /api/bookings?client_id=&status=upcoming|past
require_once("../config/init.php");
requireLogin('client');
$services = nu_fixture('services');
$all = nu_fixture('bookings');
$tab = ($_GET['tab'] ?? 'upcoming') === 'past' ? 'past' : 'upcoming';
$list = array_values(array_filter($all, function ($b) use ($tab) { return ($b['status'] === 'completed') === ($tab === 'past'); }));
include_once("../includes/header.php");
?>
<main class="screen">
  <div class="screen__head">
    <div><h1>My bookings</h1><p>Upcoming visits, recurring plans and past work.</p></div>
    <?= nu_sample_tag() ?>
  </div>
  <nav class="filters" aria-label="Filter bookings">
    <a href="?tab=upcoming"<?= $tab === 'upcoming' ? ' aria-current="true"' : '' ?>>Upcoming</a>
    <a href="?tab=past"<?= $tab === 'past' ? ' aria-current="true"' : '' ?>>Past</a>
  </nav>
  <div class="blist">
<?php foreach ($list as $i => $b): $sv = $services[$b['service']]; $href = nu_url('public/track.php') . '?id=' . rawurlencode($b['id']) . ($b['status'] === 'completed' ? '&state=completed' : ''); ?>
    <a class="brow" href="<?= nu_e($href) ?>" data-reveal style="--i:<?= $i ?>">
      <span class="brow__img"><?= nu_photo($sv['photo'], '', ['sizes' => '72px', 'widths' => [400]]) ?></span>
      <span class="brow__main">
        <p class="brow__t"><?= nu_e($sv['name']) ?></p>
        <p class="brow__s"><?= nu_icon('calendar3') ?> <?= nu_e($b['when']) ?> · <?= nu_e($b['window']) ?></p>
        <p class="brow__s"><?= nu_e($b['team']) ?> · <?= nu_e($b['plan']) ?><?= $b['plan'] === 'Premium' ? ' · ' . nu_e($b['freq']) : '' ?><?= !empty($b['queue']) ? ' · #' . (int) $b['queue'] . ' in queue' : '' ?></p>
      </span>
      <span class="brow__side"><?= nu_booking_chip($b['status']) ?><?= nu_icon('chevron-right') ?></span>
    </a>
<?php endforeach; ?>
<?php if (!$list): nu_empty('calendar3', 'No past bookings yet', 'Completed visits with before/after photos will appear here.'); endif; ?>
  </div>
  <a class="btn btn-primary btn-block mt-6" href="<?= nu_e(nu_url('public/index.php#services')) ?>"><?= nu_icon('plus-lg') ?> Book a service</a>
</main>
<?php include_once("../includes/footer.php"); ?>
