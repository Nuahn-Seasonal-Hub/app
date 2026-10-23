<?php
// public/contractor.php — Public independent-contractor profile. SAMPLE DATA: fixtures/contractors.php.
// Sir Paul: GET /api/contractors/{id} (profile, badges, stats, services, reviews). Public: no sign-in needed to read.
// Message: if signed in, POST /api/threads {contractor_id}; if a guest, go to sign-in then back here.
require_once("../config/init.php");
$all = nu_fixture('contractors');
$id = (string) ($_GET['id'] ?? 'c-1001');
$c = $all[$id] ?? reset($all);
$id = $c['id'];
$mech = in_array('mobile-mechanics', $c['trades'], true);
$cta = nu_start_url($mech ? 'mobile-mechanics' : 'snow-removal');
$since = $c['since'];
$ver = [
    'id_checked' => ['Identity matched to government ID', 'person-vcard'],
    'insured'    => ['Liability insurance on file', 'shield-check'],
    'verified'   => ['Background and reference check passed', 'patch-check-fill'],
    'certified'  => [$c['cert'] ?? 'Trade certification verified', 'award'],
];
include_once("../includes/header.php");
?>
<main class="screen">
  <p class="crumbs"><a href="<?= nu_e(nu_url('public/offers.php')) ?>">Offers</a> / Contractor</p>

  <section class="sv-card ctr-hero">
    <div class="ctr-hero__top">
      <?= nu_avatar($c, 'xl') ?>
      <div class="ctr-hero__id">
        <h1><?= nu_e($c['name']) ?></h1>
        <p class="ctr-hero__sub"><?= nu_e($c['headline']) ?></p>
        <?= nu_rating($c) ?>
      </div>
    </div>
    <?= nu_badges($c) ?>
    <dl class="ctr-stats">
      <div><dt>Jobs done</dt><dd><?= (int) $c['jobs'] ?></dd></div>
      <div><dt>Replies in</dt><dd><?= nu_e($c['response']) ?></dd></div>
      <div><dt>Member since</dt><dd><?= nu_e($since) ?></dd></div>
      <div><dt>Works within</dt><dd><?= (int) $c['radius'] ?> km</dd></div>
    </dl>
    <div class="ctr-hero__cta">
      <a class="btn btn-primary" href="<?= nu_e($cta) ?>"><?= nu_icon($mech ? 'wrench' : 'snow') ?> <?= $mech ? 'Request a mechanic' : 'Post a task' ?></a>
      <a class="btn btn-outline" href="<?= nu_e(nu_url('public/chat.php')) ?>?c=<?= nu_e($id) ?>"><?= nu_icon('chat-dots') ?> Message</a>
    </div>
    <p class="ctr-note"><?= nu_icon('info-circle') ?> <?= nu_e(explode(' ', $c['name'])[0]) ?> is an independent contractor who sets their own prices and runs their own business. Nuahn verifies and holds the payment.</p>
  </section>

  <section class="sv-card ctr-sec">
    <h2>About</h2>
    <p><?= nu_e($c['about']) ?></p>
    <p class="ctr-meta"><?= nu_icon('geo-alt') ?> <?= nu_e($c['area']) ?></p>
    <?php if (!empty($c['equipment'])): ?><p class="ctr-meta"><?= nu_icon($mech ? 'tools' : 'truck') ?> <?= nu_e($c['equipment']) ?></p><?php endif; ?>
  </section>

  <section class="sv-card ctr-sec">
    <h2>Does</h2>
    <ul class="ctr-tags"><?php foreach ($c['services'] as $sv): ?><li><?= nu_e($sv) ?></li><?php endforeach; ?></ul>
  </section>

  <section class="sv-card ctr-sec">
    <h2>What Nuahn checked</h2>
    <ul class="ctr-ver">
      <?php foreach ($ver as $k => $v): if (!in_array($k, $c['badges'], true)) continue; ?>
        <li><span class="ctr-ver__ic"><?= nu_icon($v[1]) ?></span><span><?= nu_e($v[0]) ?></span><?= nu_icon('check-circle-fill', 'ctr-ver__ok') ?></li>
      <?php endforeach; ?>
      <?php if (!in_array('verified', $c['badges'], true)): ?><li class="is-pending"><span class="ctr-ver__ic"><?= nu_icon('hourglass-split') ?></span><span>Background check in progress</span></li><?php endif; ?>
    </ul>
  </section>

  <section class="sv-card ctr-sec">
    <div class="ctr-sec__head"><h2>Reviews</h2><?= nu_sample_tag() ?></div>
    <?php foreach ($c['reviews_list'] as $r): ?>
      <article class="ctr-rev">
        <header><b><?= nu_e($r[0]) ?></b><span class="rate rate--s"><?= str_repeat(nu_icon('star-fill'), (int) $r[1]) ?></span><small><?= nu_e($r[3]) ?></small></header>
        <p><?= nu_e($r[2]) ?></p>
      </article>
    <?php endforeach; ?>
  </section>

  <section class="ctr-others" aria-label="Other contractors">
    <h2>Other contractors nearby</h2>
    <div class="ctr-others__row">
      <?php foreach ($all as $o): if ($o['id'] === $id || count(array_intersect($o['trades'], $c['trades'])) === 0) continue; ?>
        <a class="ctr-mini" href="<?= nu_e(nu_url('public/contractor.php')) ?>?id=<?= nu_e($o['id']) ?>"><?= nu_avatar($o, 'md') ?><b><?= nu_e($o['name']) ?></b><?= nu_rating($o) ?></a>
      <?php endforeach; ?>
    </div>
  </section>
</main>
<?php include_once("../includes/footer.php"); ?>
