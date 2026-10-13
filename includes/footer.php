<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// includes/footer.php — Nuahn Seasonal Hub app shell (closing half).
require_once __DIR__ . '/ui.php';
if (!isset($nuPage)) { $nuPage = nu_page_config(); }
$nuLogged = isset($_SESSION['user_id']);
$nuNav = nu_nav_items();
?>
</div><!-- /.app-main -->

<footer class="site-footer">
  <div class="wrap">
    <div class="footer-grid">
      <div>
        <a class="brand" href="<?= nu_e(nu_url('public/index.php')) ?>"><?= nu_logo(38) ?></a>
        <p>Home and property services, all in one place. Reliable. Local. Built for Canadian seasons.</p>
      </div>
      <div>
        <h2 class="footer-h">Explore</h2>
        <ul>
          <li><a href="<?= nu_e(nu_url('public/index.php#services')) ?>">Our services</a></li>
          <li><a href="<?= nu_e(nu_url('public/index.php#plans')) ?>">Basic &amp; Premium plans</a></li>
          <li><a href="<?= nu_e(nu_url('public/index.php#how')) ?>">How it works</a></li>
          <li><a href="<?= nu_e(nu_url('public/jobs.php')) ?>">Open jobs</a></li>
        </ul>
      </div>
      <div>
        <h2 class="footer-h">Account</h2>
        <ul>
<?php if ($nuLogged): ?>
          <li><a href="<?= nu_e(nu_home_url()) ?>">My home</a></li>
          <li><a href="<?= nu_e(nu_url('public/profile.php')) ?>">Profile</a></li>
          <li><a href="<?= nu_e(nu_url('public/logout.php')) ?>" data-no-prefetch>Sign out</a></li>
<?php else: ?>
          <li><a href="<?= nu_e(nu_url('public/login.php')) ?>">Sign in</a></li>
          <li><a href="<?= nu_e(nu_url('public/register.php')) ?>">Create account</a></li>
          <li><a href="<?= nu_e(nu_url('public/auth.php')) ?>">Choose your role</a></li>
<?php endif; ?>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> Nuahn Seasonal Hub. All rights reserved.</span>
      <span>Every season. Every service. One hub.</span>
    </div>
  </div>
</footer>

<nav class="tabbar" aria-label="Primary">
<?php foreach ($nuNav as $item):
    $active = $item[0] === $nuPage['tab'];
    $primary = !empty($item[5]); ?>
  <a class="tab<?= $primary ? ' tab--primary' : '' ?>" href="<?= nu_e($item[2]) ?>"<?= $active ? ' aria-current="page"' : '' ?>>
    <span class="tab__icon"><?= nu_icon($item[3], 'ic-off') ?><?= nu_icon($item[4], 'ic-on') ?></span>
    <span><?= nu_e($item[1]) ?></span><?php if ($item[0] === 'messages' && nu_unread_count()): ?><span class="tab__dot" aria-label="unread"></span><?php endif; ?>
  </a>
<?php endforeach; ?>
</nav>

<?php if ($nuLogged): ?>
<div id="user-menu" class="sheet" popover>
  <div class="sheet__head">
    <span class="avatar"><?= nu_e(nu_initials($_SESSION['user_name'] ?? 'U')) ?></span>
    <div><div class="sheet__name"><?= nu_e($_SESSION['user_name'] ?? 'Account') ?></div><div class="sheet__role"><?= nu_e($_SESSION['role'] ?? '') ?></div></div>
  </div>
  <div class="menu-sep"></div>
  <a class="menu-item" href="<?= nu_e(nu_home_url()) ?>"><?= nu_icon('house') ?> Home</a>
  <a class="menu-item" href="<?= nu_e(nu_url('public/profile.php')) ?>"><?= nu_icon('person') ?> Profile settings</a>
<?php if (($_SESSION['role'] ?? '') === 'provider'): ?>
  <a class="menu-item" href="<?= nu_e(nu_url('public/provider_route.php')) ?>"><?= nu_icon('signpost-split') ?> Today’s route</a>
  <a class="menu-item" href="<?= nu_e(nu_url('public/saved_jobs.php')) ?>"><?= nu_icon('bookmark') ?> Saved jobs</a>
  <a class="menu-item" href="<?= nu_e(nu_url('public/applications.php')) ?>"><?= nu_icon('clipboard-check') ?> My applications</a>
  <a class="menu-item" href="<?= nu_e(nu_url('public/my_activity.php')) ?>"><?= nu_icon('collection') ?> My activity</a>
  <a class="menu-item" href="<?= nu_e(nu_url('public/discover_Jobs.php')) ?>"><?= nu_icon('map') ?> Jobs map</a>
<?php elseif (($_SESSION['role'] ?? '') === 'client'): ?>
  <a class="menu-item" href="<?= nu_e(nu_url('public/bookings.php')) ?>"><?= nu_icon('calendar-check') ?> My bookings</a>
  <a class="menu-item" href="<?= nu_e(nu_url('public/jobs.php#post')) ?>"><?= nu_icon('plus-lg') ?> Post a job</a>
  <a class="menu-item" href="<?= nu_e(nu_url('public/my_jobs.php')) ?>"><?= nu_icon('briefcase') ?> My posted jobs</a>
  <a class="menu-item" href="<?= nu_e(nu_url('public/client_applications.php')) ?>"><?= nu_icon('people') ?> Applicants</a>
  <a class="menu-item" href="<?= nu_e(nu_url('public/jobs.php')) ?>"><?= nu_icon('search') ?> Browse jobs</a>
<?php endif; ?>
  <button type="button" class="menu-item" data-theme-toggle><?= nu_icon('moon-stars') ?> Toggle dark mode</button>
  <div class="menu-sep"></div>
  <a class="menu-item menu-item--danger" href="<?= nu_e(nu_url('public/logout.php')) ?>" data-no-prefetch><?= nu_icon('box-arrow-right') ?> Sign out</a>
</div>
<?php endif; ?>

<?php if (!empty($nuPage['bsjs'])): ?>
<script src="<?= nu_asset('public/assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<?php endif; ?>
</body>
</html>
