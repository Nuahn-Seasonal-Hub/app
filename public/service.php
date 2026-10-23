<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/service.php: one page per service (client guide doc2 p2-7). New booking form (service-specific),
// current visit, Change appointment, Message team, Track this visit, Premium care (enrolled customers only),
// a service-specific side card, completion report and recent activity.
// UI ONLY: every value comes from fixtures/services.php + fixtures/visits.php. The New booking form and the current
// visit are separate records; editing the form never alters the existing job (doc2 p1).
// Sir Paul: GET /api/services/{slug}, GET /api/bookings?service=&active=1, POST /api/bookings (review step = public/book.php).
require_once("../config/init.php");

$slug = nu_service_slug($_GET['s'] ?? $_GET['service'] ?? 'snow-removal');
$all = nu_fixture('services');
if (!isset($all[$slug])) { $slug = array_key_first($all); }
$sv = $all[$slug];
$logged = isset($_SESSION['user_id']) && isRole('client');
$locked = !nu_service_live($slug); // coming soon: hero + what to expect + notify form, no booking
$visit = ($logged && !$locked) ? (nu_fixture('visits')[$slug] ?? null) : null;
// Premium panel only for an enrolled customer (doc2 p1). ?as=basic previews the Basic customer's view.
$asBasic = ($_GET['as'] ?? '') === 'basic';
$premium = $visit && ($visit['plan'] ?? '') === 'premium' && !$asBasic;
$isMech = $slug === 'mobile-mechanics';
$action = $isMech ? nu_url('public/mechanic_request.php') . '?svc=brake-pads&step=3' : nu_url('public/book.php') . '?service=' . rawurlencode($slug);
$GLOBALS['nuPreload'] = '<link rel="preload" as="image" type="image/avif" imagesrcset="' . nu_e(nu_url('public/assets/img/photos/' . $sv['hero'] . '-640.avif') . ' 640w, ' . nu_url('public/assets/img/photos/' . $sv['hero'] . '-1024.avif') . ' 1024w, ' . nu_url('public/assets/img/photos/' . $sv['hero'] . '-1600.avif') . ' 1600w') . '" imagesizes="(max-width: 900px) 100vw, 1200px" fetchpriority="high">';

/** Premium care card (also reused for the snow layout). */
function sv_premium($sv, $visit) {
    $p = $sv['premium']; $chg = nu_url('public/change_appointment.php') . '?id=' . rawurlencode($visit['id']) . '&reminder=1';
    $ok = nu_url('public/book.php') . '?service=' . rawurlencode(array_search($sv, nu_fixture('services'), true) ?: '') . '&from=reminder';
    ob_start(); ?>
    <section class="sv-card sv-prem" data-reminder>
      <h2 class="sv-card__h"><?= nu_icon('flower1') ?> Premium care<?= !empty($p['sub']) ? '<small>' . nu_e($p['sub']) . '</small>' : '' ?><span class="spacer"></span><?= isset($sv['premium']['compact']) ? '' : '<span class="chip chip--soft">Care reminder</span>' ?></h2>
      <div class="sv-prem__box">
        <p class="sv-prem__t"><?= nu_e($p['title']) ?></p>
        <p class="sv-prem__x"><?= nu_e($p['text']) ?></p>
        <div class="sv-prem__acts<?= !empty($p['stacked']) ? ' is-stacked' : '' ?>" data-reminder-acts>
          <a class="btn btn-primary btn-sm" href="<?= nu_e($ok) ?>"><?= nu_icon('calendar-check') ?> Confirm visit</a>
          <a class="btn btn-outline btn-sm" href="<?= nu_e($chg) ?>"><?= nu_icon('calendar3') ?> Change date</a>
          <button class="btn btn-outline btn-sm" type="button" data-skip><?= nu_icon('skip-forward') ?> <?= nu_e($p['skip'] ?? 'Skip') ?></button>
        </div>
        <p class="sv-prem__done" data-skipped hidden><?= nu_icon('check-circle-fill') ?> Skipped this cycle. Your next reminder is next month. <button type="button" class="linkbtn" data-unskip>Undo</button></p>
      </div>
      <p class="sv-note"><?= nu_icon('info-circle') ?> <?= nu_e($p['note'] ?? 'A reminder is not a booking or charge.') ?></p>
    </section>
<?php return ob_get_clean();
}

include_once("../includes/header.php");
?>
<main class="svp svp--<?= nu_e($slug) ?>" id="svp">
  <section class="sv-hero">
    <?= nu_photo($sv['hero'], $sv['alt'], ['widths' => [640, 1024, 1600], 'sizes' => '(max-width: 900px) 100vw, 1200px', 'ratio' => 16 / 6, 'eager' => true, 'class' => 'sv-hero__img']) ?>
    <div class="sv-hero__in">
<?php if ($locked): ?>
      <p class="sv-soon"><?= nu_soon_chip() ?></p>
<?php endif; ?>
<?php if (!empty($sv['eyebrow'])): ?>
      <p class="sv-eyebrow"><?= !empty($sv['eyebrow_icon']) ? nu_icon($sv['eyebrow_icon']) : '' ?><?= nu_e($sv['eyebrow']) ?></p>
<?php endif; ?>
      <h1><?= nu_e($sv['headline']) ?></h1>
      <p class="sv-lead"><?= nu_e($sv['lead']) ?></p>
<?php if ($sv['perks']): ?>
      <ul class="sv-perks">
<?php foreach ($sv['perks'] as $pk): ?>
        <li><?= nu_icon($pk[0]) ?><span><?= nu_e($pk[1]) ?></span></li>
<?php endforeach; ?>
      </ul>
<?php endif; ?>
    </div>
<?php if (!empty($sv['badge'])): ?>
    <p class="sv-hero__badge"><?= nu_icon('tree') ?><span><?= nu_e($sv['badge']) ?></span></p>
<?php endif; ?>
  </section>

  <nav class="svc-switch sv-switch" aria-label="Other services">
<?php foreach (array_merge(nu_services_by_status('live'), nu_services_by_status('coming_soon')) as $s2 => $o): ?>
    <a href="<?= nu_e(nu_book_url($s2)) ?>"<?= $s2 === $slug ? ' aria-current="page"' : '' ?><?= nu_service_live($s2) ? '' : ' class="is-soon"' ?>><?= nu_icon(nu_service_live($s2) ? $o['icon'] : 'lock-fill') ?> <?= nu_e($o['short']) ?></a>
<?php endforeach; ?>
  </nav>
<?php if ($locked): echo nu_soon_panel($slug, $sv); ?>
</main>
<?php include_once("../includes/footer.php"); return; endif; ?>

  <div class="sv-grid<?= $visit ? '' : ' sv-grid--guest' ?>">
    <!-- New booking (separate record from the current visit) -->
    <section class="sv-card sv-form" aria-labelledby="nb-h">
      <h2 class="sv-card__h" id="nb-h"><?= nu_icon('calendar-plus') ?> New booking<small><?= nu_e($isMech ? 'Tell us about your vehicle and service needs.' : 'Tell us about the ' . strtolower($sv['name']) . ' you need.') ?></small></h2>
      <form method="post" action="<?= nu_e($action) ?>" data-svform data-price="<?= (float) $sv['price'] ?>">
        <input type="hidden" name="service" value="<?= nu_e($slug) ?>">
        <?= nu_form_fields($sv['form'], 'nb') ?>
        <fieldset class="fset">
          <legend class="label"><?= $isMech ? 'Service option' : ($slug === 'car-wash' ? 'Visit type' : ($slug === 'snow-removal' ? 'Service type' : ($slug === 'lawn-mowing' ? 'Booking type' : 'Service option'))) ?></legend>
          <div class="plan2<?= ($sv['plan_style'] ?? '') === 'compact' ? ' plan2--compact' : '' ?>">
            <label class="plan2__o"><input type="radio" name="plan" value="basic" checked><span><i class="plan2__r" aria-hidden="true"></i><b><?= nu_e($sv['plan_basic'][0]) ?></b><small><?= nu_e($sv['plan_basic'][1]) ?></small></span></label>
            <label class="plan2__o"><input type="radio" name="plan" value="premium"><span><i class="plan2__r" aria-hidden="true"></i><b><?= nu_e($sv['plan_premium'][0]) ?></b><small><?= nu_e($sv['plan_premium'][1]) ?></small></span></label>
          </div>
          <p class="hint" data-plan-hint hidden><?= nu_icon('info-circle') ?> Premium = monthly provider reminders. Reminders never create visits or charges on their own. <a href="<?= nu_e(nu_url('public/plans.php')) ?>">Compare plans</a></p>
        </fieldset>
<?php if (!$isMech): ?>
        <div class="fld">
          <label class="label" for="nb-window"><?= nu_icon('clock') ?><?= nu_e($sv['window_label'] ?? 'Preferred arrival window') ?></label>
<?php if (!empty($sv['window_split'])): ?>
          <div class="fld-row fld-row--win">
            <span class="input-icon"><?= nu_icon('calendar3') ?><input class="input" type="date" id="nb-window" name="date" value="<?= date('Y-m-d', strtotime('+7 day')) ?>" min="<?= date('Y-m-d') ?>"></span>
            <select class="input" name="window" aria-label="Arrival window"><option>9 – 11 AM</option><option selected>1:00 PM – 3:00 PM</option><option>3:00 – 5:00 PM</option></select>
          </div>
<?php else: ?>
          <span class="input-icon"><?= nu_icon('clock') ?><select class="input" id="nb-window" name="window">
            <option><?= nu_e($sv['window_value'] ?? '1:00 PM – 3:00 PM') ?></option><option>Tomorrow, 9:00 AM – 11:00 AM</option><option>Tomorrow, 1:00 PM – 3:00 PM</option><option disabled>Today, 3:00 – 5:00 PM (full)</option>
          </select></span>
<?php endif; ?>
          <p class="hint">Only windows with team capacity are listed. <?= nu_sample_tag('Sample availability') ?></p>
        </div>
<?php endif; ?>
        <button class="btn btn-primary btn-lg btn-block sv-cta" type="submit"><?= nu_icon($isMech ? 'arrow-right' : 'file-earmark-check') ?> <?= nu_e($sv['cta']) ?> <?= nu_icon('arrow-right') ?></button>
<?php if (!empty($sv['cta_note'])): ?>
        <p class="sv-note sv-note--c"><?= nu_icon('shield-check') ?> <?= nu_e($sv['cta_note']) ?></p>
<?php endif; ?>
      </form>
    </section>

<?php if ($visit): $st = nu_booking_status($visit['status']); ?>
    <div class="sv-mid">
      <section class="sv-card sv-visit" aria-labelledby="cv-h">
        <h2 class="sv-card__h" id="cv-h"><?= nu_icon('calendar-check') ?> Your current visit<span class="spacer"></span><?= !empty($visit['badge']) ? '<span class="chip chip--success chip--dot">' . nu_e($visit['badge']) . '</span>' : '<span class="chip chip--soft">Booking #' . nu_e($visit['id']) . '</span>' ?></h2>
<?php if (($visit['layout'] ?? '') === 'table'): ?>
        <dl class="sv-vt">
          <div><dt>Booking #</dt><dd><?= nu_e($visit['id']) ?></dd></div><div><dt>Date</dt><dd><?= nu_e($visit['date']) ?></dd></div><div><dt>Arrival window</dt><dd><?= nu_e($visit['window']) ?></dd></div>
          <div><dt>Service</dt><dd><?= nu_icon($sv['icon']) ?> <?= nu_e($sv['name']) ?></dd></div><div><dt>Package</dt><dd><?= nu_e($visit['package'] ?? '') ?></dd></div><div><dt>Team</dt><dd><?= nu_icon('people') ?> <?= nu_e($visit['team']) ?></dd></div>
        </dl>
<?php else: ?>
        <div class="sv-visit__body<?= !empty($visit['photo']) ? ' has-photo' : '' ?>">
          <div>
            <?= !empty($visit['badge']) ? '<p class="sv-visit__id">' . ($slug === 'snow-removal' || $isMech ? 'Booking ' : '') . '#' . nu_e($visit['id']) . '</p>' : '' ?>
            <p class="sv-visit__when"><?= nu_icon('calendar3') ?><span><b><?= nu_e($visit['date']) ?></b><?= nu_e($visit['window']) ?></span></p>
            <p class="sv-visit__team"><?= nu_icon('people') ?><span><b><?= nu_e(($visit['team_label'] ?? '') ? $visit['team_label'] . ' · ' . $visit['team'] : $visit['team']) ?></b><?= nu_e($visit['team_note'] ?? '') ?></span></p>
<?php if (!empty($visit['areas'])): ?>
            <p class="sv-visit__team"><?= nu_icon('house') ?><span><b>Service areas</b><?= nu_e($visit['areas']) ?></span></p>
<?php endif; ?>
          </div>
<?php if (!empty($visit['photo'])): ?>
          <?= nu_photo($visit['photo'], 'Leaves raked into bags in a yard', ['sizes' => '160px', 'class' => 'sv-visit__img']) ?>
<?php endif; ?>
        </div>
<?php endif; ?>
        <div class="sv-visit__acts">
          <a class="btn btn-outline btn-sm" href="<?= nu_e(nu_url('public/change_appointment.php') . '?id=' . rawurlencode($visit['id'])) ?>"><?= nu_icon('calendar3') ?> Change appointment</a>
          <a class="btn btn-primary btn-sm" href="<?= nu_e(nu_url('public/chat.php')) ?>?thread=t1"><?= nu_icon('chat-dots') ?> <?= nu_e($visit['msg'] ?? 'Message team') ?></a>
        </div>
<?php if (!empty($visit['change_hint'])): ?>
        <p class="sv-note"><?= nu_e($visit['change_hint']) ?></p>
<?php endif; ?>
      </section>

      <section class="sv-card sv-track" aria-labelledby="tv-h">
        <h2 class="sv-card__h" id="tv-h"><?= nu_icon('truck') ?> Track this visit<span class="spacer"></span><small class="sv-upd"><?= $slug === 'snow-removal' ? 'ETA updates automatically' : 'Updated ' . nu_e($visit['updated']) ?></small></h2>
<?php if (!empty($visit['vertical'])): ?>
        <div class="sv-track__split">
          <div>
            <div class="sv-eta sv-eta--card"><?= nu_icon('truck') ?><div><b><?= nu_e($visit['team']) ?> is on route</b><span>ETA <?= nu_e($visit['eta']) ?></span><small>Updated <?= nu_e($visit['updated']) ?></small></div></div>
            <ol class="vsteps">
<?php foreach ($visit['steps'] as $i => $stp): ?>
              <li class="<?= $i < $visit['at'] ? 'is-done' : ($i === $visit['at'] ? 'is-current' : '') ?>"><span class="vsteps__dot"><?= $i < $visit['at'] ? nu_icon('check-lg') : '' ?></span><span><?= nu_e($stp[0]) ?></span><small><?= nu_e($stp[1] ?: 'Pending') ?></small></li>
<?php endforeach; ?>
            </ol>
          </div>
          <div class="sv-map"><?= nu_mini_map(1) ?><span class="sv-map__tag"><b><?= nu_e($visit['team']) ?></b>ETA <?= nu_e($visit['eta']) ?></span></div>
        </div>
<?php else: ?>
        <div class="sv-track__top">
          <div class="sv-eta"><span class="sv-eta__ic"><?= nu_icon('truck') ?></span><div><b><?= nu_e($st[0]) ?></b><?php if ($slug === 'fall-cleanup'): ?><span>ETA <?= nu_e($visit['eta']) ?></span><?php endif; ?></div></div>
<?php if ($slug !== 'fall-cleanup'): ?>
          <div class="sv-eta__v"><small><?= nu_e($visit['eta_label']) ?></small><b><?= nu_e($visit['eta']) ?></b></div>
<?php else: ?>
          <div class="sv-map sv-map--sm"><?= nu_mini_map(2) ?></div>
<?php endif; ?>
        </div>
<?php if ($slug !== 'fall-cleanup'): ?>
        <?= ($slug === 'garbage-bin-cleaning') ? nu_steps($visit['steps'], $visit['at']) : '' ?>
        <div class="sv-map"><?= nu_mini_map($isMech ? 2 : 0) ?></div>
        <?= ($slug !== 'garbage-bin-cleaning') ? nu_steps($visit['steps'], $visit['at']) : '' ?>
<?php else: ?>
        <?= nu_steps($visit['steps'], $visit['at']) ?>
<?php endif; ?>
<?php endif; ?>
        <p class="sv-track__more"><a href="<?= nu_e(nu_url('public/track.php')) ?>?id=<?= nu_e(rawurlencode($visit['id'])) ?>">Open live tracking &amp; messages <?= nu_icon('arrow-right') ?></a></p>
      </section>

<?php if ($slug === 'fall-cleanup' || $slug === 'car-wash'): ?>
      <?php sv_activity($visit, true); ?>
<?php endif; ?>
    </div>

    <div class="sv-side">
<?php if ($sv['side']['type'] === 'later'): $lt = $visit['later']; ?>
      <section class="sv-card sv-later" aria-labelledby="bl-h" data-later>
        <h2 class="sv-card__h" id="bl-h"><?= nu_icon('clock-history') ?> Busy? Come later<small>Need a different time? You can request a later visit.</small></h2>
        <form method="post" action="<?= nu_e(nu_url('public/snow_event.php')) ?>?state=pending">
          <div class="radios radios--stack">
            <label class="radio"><input type="radio" name="later" value="keep"><span>Keep current window (<?= nu_e($lt['current']) ?>)</span></label>
            <label class="radio"><input type="radio" name="later" value="later_today" checked><span>Come later today</span></label>
            <label class="radio"><input type="radio" name="later" value="another_day"><span>Another available day</span></label>
          </div>
          <p class="sv-propose"><?= nu_icon('calendar-check') ?><span><b>New proposed window</b><?= nu_e($lt['proposed']) ?></span></p>
          <button class="btn btn-primary btn-block" type="submit">Request later visit</button>
        </form>
        <p class="sv-note">Current slot stays until dispatch confirms.</p>
      </section>
      <section class="sv-card" aria-labelledby="sf-h">
        <h2 class="sv-card__h" id="sf-h"><?= nu_icon('snow') ?> Snowfall update<span class="spacer"></span><small class="sv-upd">Updated <?= nu_e($visit['snowfall']['updated']) ?></small></h2>
        <a class="sv-tip sv-tip--info" href="<?= nu_e(nu_url('public/snow_event.php')) ?>"><?= nu_icon('cloud-snow') ?><span><b><?= nu_e($visit['snowfall']['title']) ?></b><?= nu_e($visit['snowfall']['text']) ?></span></a>
      </section>
      <?= $premium ? sv_premium($sv, $visit) : '' ?>
<?php else: ?>
      <?= $premium ? sv_premium($sv, $visit) : '' ?>
<?php if (!$premium): ?>
      <section class="sv-card sv-upsell">
        <h2 class="sv-card__h"><?= nu_icon('arrow-repeat') ?> Basic booking</h2>
        <p>You book visits when you need them. Tracking, messages, scheduling and rescheduling are all included.</p>
        <a class="btn btn-outline btn-sm" href="<?= nu_e(nu_url('public/plans.php')) ?>">See Premium reminders <?= nu_icon('arrow-right') ?></a>
      </section>
<?php endif; ?>
<?php $sd = $sv['side']; ?>
      <section class="sv-card" aria-labelledby="sd-h">
        <h2 class="sv-card__h" id="sd-h"><?= nu_icon($sd['icon']) ?> <?= nu_e($sd['title']) ?><?= !empty($sd['text']) && $sd['type'] !== 'scope' ? '<small>' . nu_e($sd['text']) . '</small>' : '' ?></h2>
<?php if ($sd['type'] === 'notes' || $sd['type'] === 'quote'): ?>
<?php foreach ($sd['items'] as $it): ?>
        <a class="sv-tip<?= ($it[3] ?? '') === 'info' ? ' sv-tip--info' : '' ?><?= $sd['type'] === 'quote' ? ' sv-tip--quote' : '' ?>" href="<?= nu_e($sd['type'] === 'quote' ? nu_url('public/quote.php') : nu_url('public/status.php')) ?>"><?= nu_icon($it[0]) ?><span><b><?= nu_e($it[1]) ?></b><?= nu_e($it[2]) ?></span><?= $sd['type'] === 'quote' ? nu_icon('chevron-right', 'sv-tip__chev') : '' ?></a>
<?php endforeach; ?>
<?php elseif ($sd['type'] === 'scope'): ?>
        <p class="sv-sub"><?= nu_e($sd['text']) ?></p>
        <div class="sv-empty"><?= nu_icon('file-text') ?><span><?= nu_e($sd['empty']) ?></span></div>
<?php elseif ($sd['type'] === 'equipment'): $u = $sv['utilities']; ?>
        <div class="sv-equip">
          <p><?= nu_icon('droplet-fill') ?><span>Water supply<b><?= $u['water'] === 'provider' ? 'Provider brings water' : 'Customer tap' ?></b></span></p>
          <p><?= nu_icon('plug-fill') ?><span>Electricity supply<b><?= $u['power'] === 'provider' ? 'Provider brings power' : 'Customer outlet' ?></b></span></p>
        </div>
<?php endif; ?>
      </section>
<?php endif; ?>

      <section class="sv-card sv-done" aria-labelledby="cr-h">
        <h2 class="sv-card__h" id="cr-h"><?= nu_icon('camera') ?> Completion report<?= $slug === 'fall-cleanup' || $slug === 'car-wash' ? '<small>' . nu_e($sv['evidence_title'] ?? $sv['evidence']) . '</small>' : ($slug === 'snow-removal' ? '<small>Available after work is completed.</small>' : '') ?></h2>
        <div class="sv-ph<?= !empty($sv['photo_slots']) ? ' sv-ph--3' : '' ?>">
<?php foreach (($sv['photo_slots'] ?? ['Before', 'After']) as $ps): ?>
          <div class="sv-ph__slot"><?= nu_icon('image') ?><span><?= nu_e($ps) ?></span><small><?= $slug === 'garbage-bin-cleaning' ? '' : 'Photo will appear here' ?></small></div>
<?php endforeach; ?>
        </div>
<?php if (!empty($sv['evidence_title']) && $slug !== 'car-wash' && $slug !== 'fall-cleanup'): ?>
        <p class="sv-check"><?= nu_icon('check2-circle') ?><span><b><?= nu_e($sv['evidence_title']) ?></b><?= nu_e($sv['evidence']) ?></span></p>
<?php else: ?>
        <p class="sv-note"><?= nu_e($slug === 'car-wash' ? $sv['evidence'] : ($slug === 'fall-cleanup' ? $sv['evidence'] : $sv['evidence'])) ?></p>
<?php endif; ?>
        <p class="sv-note">On-the-way visits show placeholders, never invented work. <a href="<?= nu_e(nu_url('public/activity.php')) ?>?id=WASH-1048">See a completed example</a></p>
      </section>
<?php if ($slug === 'car-wash'): ?>
<?php endif; ?>
    </div>

<?php if ($slug !== 'fall-cleanup' && $slug !== 'car-wash'): ?>
    <?php sv_activity($visit, false); ?>
<?php endif; ?>
<?php else: ?>
    <div class="sv-mid">
      <section class="sv-card sv-signin">
        <h2 class="sv-card__h"><?= nu_icon('person-circle') ?> Your visits appear here</h2>
        <p>Browse and price a booking without an account. Sign in to see your current visit, change the appointment, message the team and track the trip.</p>
        <div class="row-flex"><a class="btn btn-primary" href="<?= nu_e(nu_url('public/login_client.php')) ?>">Sign in</a><a class="btn btn-outline" href="<?= nu_e(nu_url('public/register_client.php')) ?>">Create account</a></div>
      </section>
      <section class="sv-card">
        <h2 class="sv-card__h"><?= nu_icon('list-check') ?> How <?= nu_e(strtolower($sv['name'])) ?> works</h2>
        <?= nu_steps([['Book', ''], ['Assigned', ''], ['On the way', ''], [$sv['work_step'], ''], ['Completed', '']], -1) ?>
        <p class="sv-note"><?= nu_e($sv['note']) ?></p>
      </section>
    </div>
    <div class="sv-side">
      <section class="sv-card">
        <h2 class="sv-card__h"><?= nu_icon('arrow-repeat') ?> Basic or Premium</h2>
        <p class="sv-sub"><b>Basic</b>: book one visit when needed. <b>Premium</b>: monthly provider reminders to confirm, change or skip your next visit.</p>
        <a class="btn btn-outline btn-sm" href="<?= nu_e(nu_url('public/plans.php')) ?>">Compare plans <?= nu_icon('arrow-right') ?></a>
      </section>
      <section class="sv-card">
        <h2 class="sv-card__h"><?= nu_icon('camera') ?> Completion evidence</h2>
        <p class="sv-sub">After every visit: <?= nu_e(implode(', ', array_map('strtolower', $sv['checklist']))) ?>, plus before / after photos.</p>
      </section>
    </div>
<?php endif; ?>
  </div>

  <p class="sv-foot"><?= nu_e($sv['note']) ?> <?= nu_sample_tag('Sample data') ?><?php if ($visit): ?> · <a href="?s=<?= nu_e($slug) ?><?= $asBasic ? '' : '&amp;as=basic' ?>">Preview as a <?= $asBasic ? 'Premium' : 'Basic' ?> customer</a><?php endif; ?></p>
</main>
<?php
/** Recent activity strip (horizontal) or list (fall / car wash variants). */
function sv_activity($visit, $inMid) {
    $style = $visit['activity_style'] ?? 'row'; ?>
    <section class="sv-card sv-act sv-act--<?= nu_e($style) ?>" aria-labelledby="ra-h">
      <h2 class="sv-card__h" id="ra-h"><?= nu_icon('clock-history') ?> <?= $style === 'row' ? 'Recent activity' : 'Activity' ?><?= $style === 'vertical' ? '<small>Latest updates on your booking.</small>' : '' ?><span class="spacer"></span><a class="sv-link" href="<?= nu_e(nu_url('public/activity.php')) ?>?id=<?= nu_e(rawurlencode($visit['id'])) ?>">View all activity <?= nu_icon('arrow-right') ?></a></h2>
      <ol class="sv-act__list">
<?php foreach ($visit['activity'] as $i => $a): $last = $i === count($visit['activity']) - 1; ?>
        <li class="<?= $last ? 'is-now' : '' ?>"><span class="sv-act__ic"><?= nu_icon($a[3] ?? ($last ? 'record-circle' : 'check-lg')) ?></span><span class="sv-act__b"><small><?= nu_e($a[0]) ?></small><b><?= nu_e($a[1]) ?></b><?= $a[2] !== '' ? '<span>' . nu_e($a[2]) . '</span>' : '' ?></span><?= $style === 'list' ? '<a class="sv-link" href="' . nu_e(nu_url('public/activity.php')) . '?id=' . nu_e(rawurlencode($visit['id'])) . '">Details ' . nu_icon('chevron-right') . '</a>' : '' ?></li>
<?php endforeach; ?>
      </ol>
    </section>
<?php }
?>
<script>
(function () {
  var f = document.querySelector('[data-svform]');
  if (f) {
    var hint = f.querySelector('[data-plan-hint]');
    f.addEventListener('change', function () { if (hint) hint.hidden = !f.querySelector('input[name=plan][value=premium]').checked; util(); });

    f.querySelectorAll('[data-files]').forEach(function (i) { i.addEventListener('change', function () { var s = i.closest('label').querySelector('small'); if (s) s.textContent = i.files.length + ' photo(s) ready to upload'; }); });
    // Utilities & equipment matching preview (doc2 p11). The real price / team re-check is server-side.
    var notes = { 'customer-customer': 'Team brings cleaning equipment, hoses and leads. You make a tap and outlet available.', 'provider-customer': 'Team brings a water tank. You make an outlet available.', 'customer-provider': 'Team brings self-contained power. You make a tap available.', 'provider-provider': 'Self-contained visit: provider brings water and power.' };
    function util() { var u = f.querySelector('[data-util]'); if (!u) return; var w = f.querySelector('input[name=water_source]:checked'), p = f.querySelector('input[name=power_source]:checked'); u.querySelector('[data-util-note] span').textContent = notes[w.value + '-' + p.value] + (w.value === 'provider' || p.value === 'provider' ? ' Any equipment charge is shown before you confirm.' : ''); }
    util();
  }
  document.querySelectorAll('[data-reminder]').forEach(function (r) {
    var acts = r.querySelector('[data-reminder-acts]'), done = r.querySelector('[data-skipped]');
    r.addEventListener('click', function (e) {
      if (e.target.closest('[data-skip]')) { acts.hidden = true; done.hidden = false; }
      if (e.target.closest('[data-unskip]')) { acts.hidden = false; done.hidden = true; }
    });
  });
})();
</script>
<?php include_once("../includes/footer.php"); ?>
