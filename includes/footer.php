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
        <p>A trusted marketplace connecting homeowners with vetted local independent pros. Built for Canadian seasons.</p>
      </div>
      <div>
        <h2 class="footer-h">Explore</h2>
        <ul>
          <li><a href="<?= nu_e(nu_url('public/index.php#services')) ?>">Our services</a></li>
<?php foreach (array_merge(nu_services_by_status('live'), nu_services_by_status('coming_soon')) as $nuFsSlug => $nuFsv): ?>
          <li><a href="<?= nu_e(nu_book_url($nuFsSlug)) ?>"><?= nu_e($nuFsv['name']) ?></a><?= nu_service_live($nuFsSlug) ? '' : ' <span class="foot-soon">Coming soon</span>' ?></li>
<?php endforeach; ?>
          <li><a href="<?= nu_e(nu_url('public/plans.php')) ?>">Basic &amp; Premium</a></li>
          <li><a href="<?= nu_e(nu_url('public/mechanics.php')) ?>">Roadside help &amp; repairs</a></li>
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
      <span>Every season. Trusted local pros. One hub.</span>
    </div>
  </div>
</footer>

<nav class="tabbar" aria-label="Primary">
<?php foreach ($nuNav as $item):
    $active = $item[0] === $nuPage['tab'];
    $primary = !empty($item[5]); ?>
<?php $nuTabSheet = ($item[0] === 'book' && nu_role() === 'client'); /* Book tab opens the services sheet (live first, then Coming soon) */ ?>
  <<?= $nuTabSheet ? 'button type="button" popovertarget="services-menu"' : 'a href="' . nu_e($item[2]) . '"' ?> class="tab<?= $primary ? ' tab--primary' : '' ?>"<?= $active ? ' aria-current="page"' : '' ?>>
    <span class="tab__icon"><?= nu_icon($item[3], 'ic-off') ?><?= nu_icon($item[4], 'ic-on') ?></span>
    <span><?= nu_e($item[1]) ?></span><?php if ($item[0] === 'messages' && nu_unread_count()): ?><span class="tab__dot" aria-label="unread"></span><?php endif; ?>
  </<?= $nuTabSheet ? 'button' : 'a' ?>>
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
  <a class="menu-item" href="<?= nu_e(nu_url('public/provider_dashboard.php')) ?>"><?= nu_icon('calendar-check') ?> Today’s jobs</a>
  <a class="menu-item" href="<?= nu_e(nu_url('public/provider_route.php')) ?>"><?= nu_icon('signpost-split') ?> Today’s route</a>
  <a class="menu-item" href="<?= nu_e(nu_url('public/provider_home.php')) ?>"><?= nu_icon('briefcase') ?> Jobs hub</a>
  <a class="menu-item" href="<?= nu_e(nu_url('public/saved_jobs.php')) ?>"><?= nu_icon('bookmark') ?> Saved jobs</a>
  <a class="menu-item" href="<?= nu_e(nu_url('public/applications.php')) ?>"><?= nu_icon('clipboard-check') ?> My applications</a>
  <a class="menu-item" href="<?= nu_e(nu_url('public/my_activity.php')) ?>"><?= nu_icon('collection') ?> My activity</a>
  <a class="menu-item" href="<?= nu_e(nu_url('public/discover_Jobs.php')) ?>"><?= nu_icon('map') ?> Jobs map</a>
<?php elseif (($_SESSION['role'] ?? '') === 'client'): ?>
  <a class="menu-item" href="<?= nu_e(nu_url('public/bookings.php')) ?>"><?= nu_icon('calendar-check') ?> My bookings</a>
  <a class="menu-item" href="<?= nu_e(nu_url('public/care_plans.php')) ?>"><?= nu_icon('arrow-repeat') ?> Care plans &amp; reminders</a>
  <a class="menu-item" href="<?= nu_e(nu_url('public/calendar.php')) ?>"><?= nu_icon('calendar-event') ?> Calendar</a>
  <a class="menu-item" href="<?= nu_e(nu_url('public/jobs.php#post')) ?>"><?= nu_icon('plus-lg') ?> Post a job</a>
  <a class="menu-item" href="<?= nu_e(nu_url('public/my_jobs.php')) ?>"><?= nu_icon('briefcase') ?> My posted jobs</a>
  <a class="menu-item" href="<?= nu_e(nu_url('public/client_applications.php')) ?>"><?= nu_icon('people') ?> Applicants</a>
  <a class="menu-item" href="<?= nu_e(nu_url('public/jobs.php')) ?>"><?= nu_icon('search') ?> Browse jobs</a>
<?php endif; ?>
<?php if (nu_is_staff()): ?>
  <a class="menu-item" href="<?= nu_e(nu_url('public/operations.php')) ?>"><?= nu_icon('speedometer2') ?> Operations overview</a>
  <a class="menu-item" href="<?= nu_e(nu_url('public/service_settings.php')) ?>"><?= nu_icon('sliders') ?> Service settings</a>
<?php endif; ?>
  <a class="menu-item" href="<?= nu_e(nu_url('public/status.php')) ?>"><?= nu_icon('list-check') ?> Booking states</a>
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
