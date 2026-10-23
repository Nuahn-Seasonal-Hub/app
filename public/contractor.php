<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/contractor.php: public independent-contractor profile, on live data (contractor_profiles + reviews).
// Public: no sign-in needed to read. Only APPROVED contractors are listed; anyone else is a 404. Contact details are never shown.
require_once("../config/init.php");
require_once("../includes/marketplace/view.php");
$id = (int) ($_GET['id'] ?? 0);
$c = (mk_ready() && $id > 0) ? mk_contractor_card($id) : null;
$own = $c && isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] === $id;
if (!$c || ($c['vetting'] !== 'approved' && !$own && !mk_is_staff())) {
    http_response_code(404);
    include_once("../includes/header.php");
    echo '<main class="screen"><div class="sv-card"><h1>Contractor not found</h1><p><a href="' . nu_e(nu_url('public/index.php')) . '">Home</a></p></div></main>';
    include_once("../includes/footer.php");
    exit;
}
$mech = in_array('mobile-mechanics', $c['trades'], true);
$cta = nu_start_url($mech ? 'mobile-mechanics' : 'snow-removal');
$names = ['snow-removal' => 'Winter snow removal', 'mobile-mechanics' => 'Mobile mechanics'];
$rs = mk_db()->prepare('SELECT r.rating, r.comment, r.created_at, u.name FROM reviews r JOIN users u ON u.id = r.client_id WHERE r.contractor_id = ? ORDER BY r.id DESC LIMIT 20');
$rs->execute([$id]);
$reviews = $rs->fetchAll();
$ver = [
    'id_checked' => ['Identity matched to government ID', 'person-vcard'],
    'insured'    => ['Liability insurance on file', 'shield-check'],
    'verified'   => ['Approved by Nuahn after document review', 'patch-check-fill'],
];
include_once("../includes/header.php");
?>
<main class="screen">
  <p class="crumbs"><a href="<?= nu_e(nu_url('public/index.php')) ?>">Home</a> / Contractor</p>

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
      <div><dt>Reviews</dt><dd><?= (int) $c['reviews'] ?></dd></div>
      <div><dt>Works within</dt><dd><?= (int) $c['radius'] ?> km</dd></div>
      <div><dt>Status</dt><dd><?= $c['vetting'] === 'approved' ? 'Approved' : nu_e(ucfirst(str_replace('_', ' ', $c['vetting']))) ?></dd></div>
    </dl>
    <div class="ctr-hero__cta">
      <a class="btn btn-primary" href="<?= nu_e($cta) ?>"><?= nu_icon($mech ? 'wrench' : 'snow') ?> <?= $mech ? 'Request a mechanic' : 'Post a task' ?></a>
    </div>
    <p class="ctr-note"><?= nu_icon('info-circle') ?> <?= nu_e(explode(' ', $c['name'])[0]) ?> is an independent contractor who sets their own prices and runs their own business. Nuahn verifies and holds the payment.</p>
  </section>

  <section class="sv-card ctr-sec">
    <h2>About</h2>
    <p><?= nu_e($c['about'] !== '' ? $c['about'] : 'This contractor has not added a description yet.') ?></p>
    <?php if ($c['area'] !== ''): ?><p class="ctr-meta"><?= nu_icon('geo-alt') ?> <?= nu_e($c['area']) ?></p><?php endif; ?>
    <?php if ($c['equipment'] !== ''): ?><p class="ctr-meta"><?= nu_icon($mech ? 'tools' : 'truck') ?> <?= nu_e($c['equipment']) ?></p><?php endif; ?>
  </section>

  <section class="sv-card ctr-sec">
    <h2>Does</h2>
    <ul class="ctr-tags"><?php foreach ($c['trades'] as $tr): ?><li><?= nu_e($names[$tr] ?? $tr) ?></li><?php endforeach; ?></ul>
  </section>

  <section class="sv-card ctr-sec">
    <h2>What Nuahn checked</h2>
    <ul class="ctr-ver">
      <?php foreach ($ver as $k => $v): if (!in_array($k, $c['badges'], true)) continue; ?>
        <li><span class="ctr-ver__ic"><?= nu_icon($v[1]) ?></span><span><?= nu_e($v[0]) ?></span><?= nu_icon('check-circle-fill', 'ctr-ver__ok') ?></li>
      <?php endforeach; ?>
      <?php if (!in_array('verified', $c['badges'], true)): ?><li class="is-pending"><span class="ctr-ver__ic"><?= nu_icon('hourglass-split') ?></span><span>Application in review</span></li><?php endif; ?>
    </ul>
  </section>

  <section class="sv-card ctr-sec">
    <div class="ctr-sec__head"><h2>Reviews</h2></div>
    <?php foreach ($reviews as $r): ?>
      <article class="ctr-rev">
        <header><b><?= nu_e(explode(' ', $r['name'])[0]) ?></b><span class="rate rate--s"><?= str_repeat(nu_icon('star-fill'), (int) $r['rating']) ?></span><small><?= nu_e(mk_dt($r['created_at'], 'M j, Y')) ?></small></header>
        <?php if ($r['comment'] !== ''): ?><p><?= nu_e($r['comment']) ?></p><?php endif; ?>
      </article>
    <?php endforeach; ?>
    <?php if (!$reviews): ?><p class="muted">No reviews yet. Reviews appear after a client approves a finished job.</p><?php endif; ?>
  </section>
</main>
<?php include_once("../includes/footer.php"); ?>
