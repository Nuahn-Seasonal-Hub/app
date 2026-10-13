<?php
// public/index.php - Landing page for Nuahn Seasonal Hub (client design v2).
// Services, weather, next booking and plans are SAMPLE DATA from /fixtures (see docs/UI-DATA-HOOKUP.md).
require_once("../config/init.php");
$hp = function ($f) { return nu_url('public/assets/img/photos/' . $f); };
$heroPreload = '<link rel="preload" as="image" type="image/avif" media="(min-width: 641px)" imagesrcset="'
    . $hp('hero-winter-house-768.avif') . ' 768w, ' . $hp('hero-winter-house-1280.avif') . ' 1280w, ' . $hp('hero-winter-house-1920.avif') . ' 1920w" imagesizes="100vw" fetchpriority="high">'
    . "\n  " . '<link rel="preload" as="image" type="image/avif" media="(max-width: 640px)" imagesrcset="'
    . $hp('hero-winter-house-m-480.avif') . ' 480w, ' . $hp('hero-winter-house-m-800.avif') . ' 800w" imagesizes="100vw" fetchpriority="high">';
$GLOBALS['nuPreload'] = $heroPreload;
include_once("../includes/header.php");

$services = nu_fixture('services');
$weather  = nu_fixture('weather');
$plans    = nu_fixture('plans');
$next = null;
foreach (nu_fixture('bookings') as $b) { if ($b['status'] !== 'completed') { $next = $b; break; } }
$nextSvc = $next ? ($services[$next['service']] ?? null) : null;
$isClient = isRole('client');
$postUrl = $isClient ? nu_url('public/jobs.php#post') : nu_url('public/register_client.php');
$addr = trim((string) ($_GET['address'] ?? ''));
?>

<section class="lhero">
  <picture class="lhero__img">
    <source media="(max-width: 640px)" type="image/avif" srcset="<?= nu_e($hp('hero-winter-house-m-480.avif') . ' 480w, ' . $hp('hero-winter-house-m-800.avif') . ' 800w') ?>" sizes="100vw">
    <source media="(max-width: 640px)" type="image/webp" srcset="<?= nu_e($hp('hero-winter-house-m-480.webp') . ' 480w, ' . $hp('hero-winter-house-m-800.webp') . ' 800w') ?>" sizes="100vw">
    <source type="image/avif" srcset="<?= nu_e($hp('hero-winter-house-768.avif') . ' 768w, ' . $hp('hero-winter-house-1280.avif') . ' 1280w, ' . $hp('hero-winter-house-1920.avif') . ' 1920w') ?>" sizes="100vw">
    <source type="image/webp" srcset="<?= nu_e($hp('hero-winter-house-768.webp') . ' 768w, ' . $hp('hero-winter-house-1280.webp') . ' 1280w, ' . $hp('hero-winter-house-1920.webp') . ' 1920w') ?>" sizes="100vw">
    <img src="<?= nu_e($hp('hero-winter-house-1920.jpg')) ?>" alt="Snowy suburban street with two-storey homes after a fresh snowfall" width="1920" height="800" fetchpriority="high" decoding="async">
  </picture>
  <div class="wrap lhero__in">
    <h1 class="lhero__title enter">Every season.<br>Every service.<br><em>One hub.</em></h1>
    <p class="lhero__lead enter" style="--i:1">Home and property services, all in one place.<br>Reliable. Local. Built for Canadian seasons.</p>
    <form class="addr enter" style="--i:2" action="#services" method="get" role="search">
      <label class="addr__field">
        <?= nu_icon('geo-alt-fill') ?>
        <span class="sr-only">Service address</span>
        <input type="text" name="address" value="<?= nu_e($addr) ?>" placeholder="Enter your service address" autocomplete="street-address">
      </label>
      <button class="btn btn-primary btn-lg" type="submit"><?= nu_icon('search') ?> Find services</button>
    </form>
  </div>
</section>

<main class="wrap lmain" id="services">
  <section aria-labelledby="svc-h">
    <div class="lsec-head">
      <h2 id="svc-h" class="serif">Our services</h2>
      <p class="lsec-head__aside"><?= $addr !== '' ? nu_icon('geo-alt') . ' Available near <strong>' . nu_e($addr) . '</strong>' : 'Local. Experienced. Ready for every season.' ?></p>
    </div>
<?php $main = array_slice($services, 0, 5, true); $more = array_slice($services, 5, null, true); ?>
    <div class="svc-grid">
<?php $i = 0; foreach ($main as $slug => $sv): ?>
      <article class="svc" data-reveal style="--i:<?= $i++ ?>">
        <a class="svc__media" href="<?= nu_e(nu_book_url($slug)) ?>" tabindex="-1" aria-hidden="true"><?= nu_photo($sv['photo'], $sv['alt'], ['sizes' => '(max-width: 640px) 46vw, (max-width: 1100px) 30vw, 230px']) ?></a>
        <div class="svc__body">
          <h3><?= nu_e($sv['name']) ?></h3>
          <a class="btn btn-primary btn-sm btn-block" href="<?= nu_e(nu_book_url($slug)) ?>" aria-label="Book <?= nu_e($sv['name']) ?>">Book service <?= nu_icon('arrow-right') ?></a>
        </div>
      </article>
<?php endforeach; ?>
    </div>
    <div class="svc-more">
<?php foreach ($more as $slug => $sv): ?>
      <a class="svc-wide" href="<?= nu_e(nu_book_url($slug)) ?>" data-reveal>
        <span class="svc-wide__img"><?= nu_photo($sv['photo'], '', ['sizes' => '140px', 'widths' => [400]]) ?></span>
        <span class="svc-wide__main"><span class="svc-wide__t"><?= nu_e($sv['name']) ?> <span class="chip chip--plain"><?= nu_e($sv['season']) ?></span></span><span class="svc-wide__s"><?= nu_e($sv['blurb']) ?></span><span class="svc-wide__cta">Book service <?= nu_icon('arrow-right') ?></span></span>
      </a>
<?php endforeach; ?>
      <a class="svc-wide svc-wide--post" href="<?= nu_e($postUrl) ?>" data-reveal>
        <span class="svc-wide__img"><?= nu_photo('other-jobs', '', ['sizes' => '140px', 'widths' => [400]]) ?></span>
        <span class="svc-wide__main"><span class="svc-wide__t">Other jobs</span><span class="svc-wide__s">Need something else? Post any job and get offers from local providers.</span><span class="svc-wide__cta"><?= nu_icon('plus-lg') ?> Post a job</span></span>
      </a>
    </div>
  </section>

  <div class="linfo">
    <section class="card wx" aria-labelledby="wx-h">
      <div class="wx__body">
        <div class="wx__top">
          <h2 id="wx-h" class="lside__h">Local weather</h2>
          <?= nu_sample_tag('Sample') ?>
        </div>
        <div class="wx__now">
          <span class="wx__icon"><?= nu_icon($weather['now']['icon']) ?></span>
          <div>
            <div class="wx__temp"><?= (int) $weather['now']['temp'] ?>°C <span class="wx__text"><?= nu_e($weather['now']['text']) ?></span></div>
            <div class="wx__city"><?= nu_icon('geo-alt') ?> <?= nu_e($weather['city']) ?></div>
          </div>
        </div>
      </div>
      <a class="wx__link" href="<?= nu_e($isClient ? nu_url('public/calendar.php') : nu_url('public/login_client.php')) ?>">View forecast <?= nu_icon('arrow-right') ?></a>
      <svg class="wx__art" viewBox="0 0 220 110" aria-hidden="true"><g fill="currentColor"><path d="M40 20 22 52h8L16 76h10L8 104h64L54 76h10L50 52h8Z"/><path d="M96 34 82 60h6L76 80h8L70 104h52l-14-24h8L94 60h6Z" opacity=".7"/><path d="M160 8 138 48h10l-18 30h12l-22 30h80l-22-30h12l-18-30h10Z"/><path d="M196 40 184 62h5l-10 17h7l-12 25h44l-12-25h7l-10-17h5Z" opacity=".6"/></g></svg>
    </section>

<?php if ($next && $nextSvc): ?>
    <section class="card nb" aria-labelledby="nb-h">
      <div class="nb__main">
        <div class="wx__top">
          <h2 id="nb-h" class="lside__h">Your next booking</h2>
          <?= $isClient ? nu_booking_chip($next['status']) : nu_sample_tag('Preview') ?>
        </div>
        <div class="nb__row">
          <span class="nb__icon"><?= nu_icon('calendar3') ?></span>
          <div>
            <div class="nb__title"><?= nu_e($nextSvc['name']) ?> · <?= nu_e($next['when']) ?>, <?= nu_e($next['window']) ?></div>
            <div class="nb__when"><?= nu_e($next['address']) ?></div>
            <div class="nb__team"><?= nu_e($next['team']) ?></div>
          </div>
        </div>
      </div>
      <div class="nb__actions">
        <a class="btn btn-primary btn-sm" href="<?= nu_e(nu_url('public/track.php') . '?id=' . rawurlencode($next['id'])) ?>">View booking</a>
        <a class="btn btn-outline btn-sm" href="<?= nu_e(nu_url('public/chat.php') . '?thread=t1') ?>">Message team</a>
      </div>
    </section>
<?php endif; ?>
  </div>

  <section class="steps4" id="how" aria-label="How it works">
<?php foreach ([['search', 'Select a service', 'Choose from seasonal home and property services.'], ['calendar-check', 'Book', 'Pick your address, options and arrival window.'], ['bell', 'Receive updates', 'Live map, arrival alerts and chat with your team.'], ['image', 'View completed work', 'Before/after photos, then release payment.']] as $n => $st): ?>
    <div class="step4" data-reveal style="--i:<?= $n ?>">
      <span class="step4__ic"><?= nu_icon($st[0]) ?></span>
      <div><div class="step4__t"><?= nu_e($st[1]) ?></div><p><?= nu_e($st[2]) ?></p></div>
    </div>
<?php endforeach; ?>
  </section>

  <section class="plans" id="plans" aria-labelledby="plans-h">
    <div class="lsec-head lsec-head--center">
      <h2 id="plans-h" class="serif">Basic or Premium: your choice</h2>
      <p class="muted">Book on demand, or put your seasons on autopilot.</p>
    </div>
    <div class="plans__grid">
<?php foreach ($plans as $key => $pl): ?>
      <article class="card plan plan--<?= nu_e($key) ?>" data-reveal>
        <div class="plan__head">
          <span class="plan__tag"><?= nu_e($pl['tag']) ?></span>
          <h3 class="serif"><?= nu_e($pl['name']) ?></h3>
          <div class="plan__price"><?= nu_e($pl['price']) ?></div>
        </div>
        <ul class="plan__list">
<?php foreach ($pl['points'] as $pt): ?>
          <li><?= nu_icon('check-lg') ?> <?= nu_e($pt) ?></li>
<?php endforeach; ?>
        </ul>
        <a class="btn <?= $key === 'premium' ? 'btn-primary' : 'btn-outline' ?> btn-block" href="<?= nu_e(nu_book_url('garbage-can-cleaning') . '&plan=' . $key) ?>"><?= $key === 'premium' ? 'Go Premium' : 'Book on demand' ?></a>
      </article>
<?php endforeach; ?>
    </div>
    <p class="text-center mt-4"><?= nu_sample_tag('Plan details are sample content') ?></p>
  </section>
</main>

<?php include_once("../includes/footer.php"); ?>
