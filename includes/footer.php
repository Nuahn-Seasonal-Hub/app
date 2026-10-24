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

<footer class="site-footer" aria-labelledby="site-footer-title">
  <h2 id="site-footer-title" class="sr-only">Site footer</h2>
  <div class="wrap">
    <div class="sf-grid">
      <div class="sf-brand">
        <a class="brand" href="<?= nu_e(nu_url('public/index.php')) ?>" aria-label="Nuahn Seasonal Hub home"><?= nu_logo(40, 'dark') ?></a>
        <p>A trusted marketplace connecting homeowners with vetted local independent pros. Built for every Canadian season.</p>
        <ul class="sf-social" aria-label="Nuahn on social media">
          <li><a href="#" aria-label="Facebook (coming soon)"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M13.5 21v-7.5h2.6l.4-3h-3V8.6c0-.9.3-1.5 1.6-1.5h1.6V4.4c-.3 0-1.2-.1-2.3-.1-2.3 0-3.9 1.4-3.9 4v2.2H7.9v3h2.6V21h3z"/></svg></a></li>
          <li><a href="#" aria-label="Instagram (coming soon)"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="5" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="17.2" cy="6.8" r="1.1" fill="currentColor"/></svg></a></li>
          <li><a href="#" aria-label="X (coming soon)"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M17.8 3.5h3l-6.6 7.5 7.7 9.5h-6l-4.7-5.8-5.4 5.8h-3l7-7.9L2.4 3.5h6.1l4.3 5.3 5-5.3zm-1 15.3h1.7L7.6 5.1H5.8l11 13.7z"/></svg></a></li>
          <li><a href="#" aria-label="LinkedIn (coming soon)"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4.5 9h3.3v11H4.5V9zm1.7-5.3a1.9 1.9 0 110 3.8 1.9 1.9 0 010-3.8zM10 9h3.2v1.5h.1c.4-.8 1.5-1.8 3.2-1.8 3.4 0 4 2.2 4 5.1V20h-3.3v-5.4c0-1.3 0-3-1.8-3s-2.1 1.4-2.1 2.9V20H10V9z"/></svg></a></li>
        </ul>
      </div>
      <nav class="sf-col" aria-labelledby="sf-services">
        <h3 class="sf-h" id="sf-services">Services</h3>
        <ul>
<?php foreach (array_merge(nu_services_by_status('live'), nu_services_by_status('coming_soon')) as $nuFsSlug => $nuFsv): ?>
          <li><a href="<?= nu_e(nu_book_url($nuFsSlug)) ?>"><?= nu_e($nuFsv['name']) ?></a><?= nu_service_live($nuFsSlug) ? '' : ' <span class="sf-soon">Soon</span>' ?></li>
<?php endforeach; ?>
        </ul>
      </nav>
      <nav class="sf-col" aria-labelledby="sf-explore">
        <h3 class="sf-h" id="sf-explore">Explore</h3>
        <ul>
          <li><a href="<?= nu_e(nu_url('public/index.php#how')) ?>">How it works</a></li>
          <li><a href="<?= nu_e(nu_url('public/plans.php')) ?>">Basic &amp; Premium</a></li>
          <li><a href="<?= nu_e(nu_url('public/mechanics.php')) ?>">Roadside help</a></li>
          <li><a href="<?= nu_e(nu_url('public/jobs.php')) ?>">Open jobs</a></li>
          <li><a href="<?= nu_e(nu_url('public/register_provider.php')) ?>">Become a pro</a></li>
        </ul>
      </nav>
      <nav class="sf-col" aria-labelledby="sf-account">
        <h3 class="sf-h" id="sf-account"><?= $nuLogged ? 'Account' : 'Account &amp; help' ?></h3>
        <ul>
<?php if ($nuLogged): ?>
          <li><a href="<?= nu_e(nu_home_url()) ?>">My home</a></li>
          <li><a href="<?= nu_e(nu_url('public/profile.php')) ?>">Profile</a></li>
          <li><a href="<?= nu_e(nu_url('public/status.php')) ?>">Job states</a></li>
          <li><a href="<?= nu_e(nu_url('public/logout.php')) ?>" data-no-prefetch>Sign out</a></li>
<?php else: ?>
          <li><a href="<?= nu_e(nu_url('public/login.php')) ?>">Sign in</a></li>
          <li><a href="<?= nu_e(nu_url('public/register.php')) ?>">Create account</a></li>
          <li><a href="<?= nu_e(nu_url('public/auth.php')) ?>">Choose your role</a></li>
          <li><a href="<?= nu_e(nu_url('public/status.php')) ?>">Help &amp; job states</a></li>
<?php endif; ?>
        </ul>
      </nav>
    </div>
    <div class="sf-bottom">
      <span>© <?= date('Y') ?> Nuahn Seasonal Hub. All rights reserved.</span>
      <nav class="sf-legal" aria-label="Legal">
        <a href="#">Privacy</a><a href="#">Terms</a><a href="#">Accessibility</a>
      </nav>
      <button type="button" class="sf-theme" data-theme-toggle aria-label="Toggle dark mode"><?= nu_icon('moon-stars', 'theme-ic-light') ?><?= nu_icon('sun', 'theme-ic-dark') ?><span>Theme</span></button>
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
  <a class="menu-item" href="<?= nu_e(nu_url('public/status.php')) ?>"><?= nu_icon('list-check') ?> Job states</a>
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
