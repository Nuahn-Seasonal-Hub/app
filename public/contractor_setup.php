<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/contractor_setup.php: 5-tab contractor onboarding & verification hub (Contact & Profile,
// Government ID & Selfie, Trade Licence, Insurance, Google Reviews) + "Submit for verification".
// Nuahn staff review applications in operations.php (?view=vetting). Only approved contractors can send offers/quotes.
require_once("../config/init.php");
requireLogin('provider');
require_once("../includes/marketplace/view.php");
require_once("../includes/onboarding.php");

$uid    = mk_uid();
$user   = ob_user_get($uid);
$p      = mk_profile_ensure($uid);
$docs   = mk_documents($uid);
$trades = mk_trades($p);
$pd     = ob_prof_data($user);
$sum    = ob_verification_summary($user, $p);
$status = $p['vetting_status'];

$tab = (string) ($_GET['tab'] ?? 'contact');
if (!in_array($tab, ['contact', 'identity', 'licence', 'insurance', 'reviews'], true)) $tab = 'contact';
$needCat = trim((string) ($_GET['need'] ?? ''));

$labels = [
    'draft'             => ['Not submitted', 'chip--plain'],
    'pending'           => ['Waiting for review', 'chip--warning'],
    'in_review'         => ['In review', 'chip--warning'],
    'changes_requested' => ['Changes requested', 'chip--warning'],
    'approved'          => ['Approved', 'chip--success'],
    'rejected'          => ['Not approved', 'chip--danger'],
];
$chk = [
    'identity'  => ['Government ID', 'chk_identity'],
    'insurance' => ['Liability insurance', 'chk_insurance'],
    'licence'   => ['Trade licence (mechanics)', 'chk_licence'],
    'cert'      => ['Certificate (optional)', 'chk_cert'],
];
$chkWords = [
    'missing'  => 'Not uploaded',
    'review'   => 'Uploaded, waiting for review',
    'ok'       => 'Verified',
    'rejected' => 'Rejected — please upload again',
    'na'       => 'Not required',
];
$locked = in_array($status, ['pending', 'in_review'], true);

$docsByKind = ['identity' => [], 'insurance' => [], 'licence' => [], 'cert' => []];
foreach ($docs as $d) {
    $docsByKind[$d['kind']][] = $d;
}

$tabDefs = [
    'contact'   => ['Contact & Profile', $sum['contact_ok'] ? 'ok' : 'pending'],
    'identity'  => ['Government ID', $sum['id_status']],
    'licence'   => ['Trade Licence', $sum['needs_licence'] ? $sum['lic_status'] : ($p['chk_licence'] === 'ok' ? 'ok' : 'na')],
    'insurance' => ['Insurance', $sum['ins_status']],
    'reviews'   => ['Google Reviews', $sum['gr_status'] ?: 'optional'],
];

$grStatus = $sum['gr_status'];
$grMessages = [
    'approved'  => ['chip--success', 'Approved', 'Your Google reviews are verified and linked to your profile.'],
    'in_review' => ['chip--warning', 'In review', 'Your Google Business listing is being verified. This usually takes less than 24 hours.'],
    'pending'   => ['chip--warning', 'Pending verification', 'Your Google Business submission is waiting for staff verification (usually within 24 hours).'],
    'rejected'  => ['chip--danger',  'Rejected', 'Your Google Business submission was rejected. Check the listing details below and resubmit.'],
    'skipped'   => ['chip--plain',   'Skipped', 'Optional — link your Google Business anytime to display verified client reviews.'],
];

$betaVerifyLink = $_SESSION['ob_last_verify_link'] ?? '';
include_once("../includes/header.php");
?>
<div class="console">
  <?= nu_console_nav(nu_provider_nav(), 'profile', 'Contractor workspace', 'Get verified,<br>then start<br>earning.') ?>
  <main class="console__main">
    <header class="pd-head">
      <div>
        <h1>Verification &amp; contractor profile</h1>
        <p>Complete your contact checks, Government ID, insurance, and trade licence to unlock job offers and quotes.</p>
      </div>
      <span class="chip <?= nu_e($labels[$status][1]) ?>"><?= nu_e($labels[$status][0]) ?></span>
    </header>

<?php if ($needCat === 'mobile-mechanics'): ?>
    <div class="mk-note mk-note--warn">
      <?= nu_icon('exclamation-circle') ?>
      <div><b>This category requires a trade licence to quote</b><p>Upload your mechanic trade licence or Red Seal certification below, then submit your profile for review.</p></div>
    </div>
<?php endif; ?>

<?php if (!empty($p['vet_note']) && in_array($status, ['changes_requested', 'rejected', 'approved'], true)): ?>
    <div class="mk-note<?= $status === 'approved' ? '' : ' mk-note--warn' ?>"><?= nu_icon($status === 'approved' ? 'check-circle-fill' : 'info-circle') ?><div><b>Note from Nuahn</b><p><?= nu_e($p['vet_note']) ?></p></div></div>
<?php endif; ?>

<?php if ($status === 'approved'): ?>
    <div class="mk-note"><?= nu_icon('shield-check') ?><div><b>You are approved.</b><p>You can send offers and quotes on open jobs. <a href="<?= nu_e(nu_url('public/open_jobs.php')) ?>">Browse open jobs</a></p></div></div>
<?php endif; ?>

    <!-- Verification readiness summary -->
    <section class="sv-card mk-sec">
      <div class="ob-progress-head">
        <div>
          <b>Verification readiness: <?= (int) $sum['required_done'] ?> of <?= (int) $sum['required_total'] ?> required documents uploaded</b>
          <small class="hint">Government ID + liability insurance<?= $sum['needs_licence'] ? ' + mobile mechanic trade licence' : '' ?> are required before approval.</small>
        </div>
        <span class="chip <?= $sum['pct'] >= 100 ? 'chip--success' : 'chip--warning' ?>"><?= (int) $sum['pct'] ?>%</span>
      </div>
      <div class="ob-progress" role="progressbar" aria-valuenow="<?= (int) $sum['pct'] ?>" aria-valuemin="0" aria-valuemax="100">
        <span style="width:<?= (int) $sum['pct'] ?>%"></span>
      </div>
    </section>

    <!-- 5-Tab Navigation (Nuahn native .op-tabs) -->
    <nav class="op-tabs" aria-label="Verification sections">
<?php foreach ($tabDefs as $tk => [$tl, $tst]):
        $badge = '';
        if ($tst === 'ok' || $tst === 'approved') $badge = ' ✓';
        elseif ($tst === 'rejected') $badge = ' !';
?>
      <a href="?tab=<?= nu_e($tk) ?>"<?= $tk === $tab ? ' aria-current="page"' : '' ?>><?= nu_e($tl . $badge) ?></a>
<?php endforeach; ?>
    </nav>

<?php if ($tab === 'contact'): ?>
    <!-- TAB 1: CONTACT VERIFICATION + PROFILE + SOCIAL LINKS -->
    <section class="sv-card mk-sec">
      <h2>Contact verification</h2>
      <p class="mk-sub">Verified phone and email details let homeowners and Nuahn reach you quickly for roadside and scheduled jobs.</p>
      <ul class="mk-docs">
        <li>
          <span>
            <b>Email address</b>
            <small><?= nu_e($user['email'] ?? '') ?></small>
          </span>
          <?php if ($sum['email_ok']): ?>
            <span class="chip chip--success">Verified</span>
          <?php else: ?>
            <form action="<?= nu_e(nu_url('actions/onboarding.php')) ?>" method="POST" class="mk-inline">
              <?= nu_csrf_field() ?>
              <input type="hidden" name="do" value="resend_email">
              <button type="submit" class="btn btn-outline btn-sm">Send verify link</button>
            </form>
          <?php endif; ?>
        </li>
        <li>
          <span>
            <b>Phone number</b>
            <small><?= nu_e(($user['phone'] ?? '') ?: 'Not verified yet') ?></small>
          </span>
          <span class="chip <?= $sum['phone_ok'] ? 'chip--success' : 'chip--warning' ?>"><?= $sum['phone_ok'] ? 'Verified' : 'Unverified' ?></span>
        </li>
      </ul>

      <?php if (!$sum['email_ok'] && $betaVerifyLink !== ''): ?>
        <div class="mk-note" style="margin-top:var(--s4)">
          <?= nu_icon('envelope') ?>
          <div><b>Beta email verification shortcut</b><p>If your server doesn't deliver outbound email yet, click here to confirm: <a href="<?= nu_e($betaVerifyLink) ?>">Verify my email now</a></p></div>
        </div>
      <?php endif; ?>

      <?php if (!$sum['phone_ok']): ?>
        <form action="<?= nu_e(nu_url('actions/onboarding.php')) ?>" method="POST" class="mk-form" id="hubPhoneForm" style="margin-top:var(--s4)">
          <?= nu_csrf_field() ?>
          <input type="hidden" name="do" value="verify_phone">
          <input type="hidden" name="back" value="public/contractor_setup.php?tab=contact">
          <div class="ob-phone-row">
            <select class="input ob-cc" name="phone_code" id="h-cc"><option value="+1" selected>+1</option></select>
            <input type="tel" class="input" name="phone" id="h-phone" value="<?= nu_e(preg_replace('/^\+1/', '', (string) ($user['phone'] ?? ''))) ?>" placeholder="780 555 1234" required>
          </div>
          <div class="ob-otp-row">
            <input type="text" class="input" name="otp" id="h-otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="6-digit OTP code" required>
            <button type="button" class="btn btn-outline btn-sm" id="h-get-code">Get Code</button>
            <button type="submit" class="btn btn-primary btn-sm">Verify phone</button>
          </div>
          <p class="hint" id="h-otp-status" aria-live="polite"></p>
        </form>
      <?php endif; ?>
    </section>

    <section class="sv-card mk-sec">
      <h2>Services and base</h2>
      <?= mk_form('profile_save', [], 'class="mk-form"', 'contractor_setup.php?tab=contact') ?>
        <fieldset class="fld"><legend class="label">Services you offer</legend>
          <label class="mk-check"><input type="checkbox" name="trades[]" value="snow-removal" <?= in_array('snow-removal', $trades, true) ? 'checked' : '' ?>> Winter snow removal</label>
          <label class="mk-check"><input type="checkbox" name="trades[]" value="mobile-mechanics" <?= in_array('mobile-mechanics', $trades, true) ? 'checked' : '' ?>> Mobile mechanics <small class="ob-sub-inline">(requires trade licence)</small></label>
        </fieldset>
        <label class="fld"><span class="label">Headline</span><input class="input" name="headline" maxlength="160" value="<?= nu_e($p['headline']) ?>" placeholder="e.g. Reliable driveway clearing, 6 years in Edmonton"></label>
        <label class="fld"><span class="label">About you</span><textarea class="input" name="about" rows="3" maxlength="1500"><?= nu_e($p['about']) ?></textarea></label>
        <label class="fld"><span class="label">Equipment</span><input class="input" name="equipment" maxlength="255" value="<?= nu_e($p['equipment']) ?>" placeholder="e.g. 2 trucks with plows, snow blowers, ice melt"></label>
        <?= nu_map_picker(['name' => 'base', 'label' => 'Base address (where you start from)', 'address' => $p['base_address'], 'lat' => $p['base_lat'] ?? 53.4891, 'lng' => $p['base_lng'] ?? -113.5262, 'placeholder' => 'Your yard, shop or home address']) ?>
        <label class="fld"><span class="label">How far will you travel? (km)</span><input class="input" type="number" name="radius_km" min="1" max="100" value="<?= (int) $p['radius_km'] ?>"></label>
        <button class="btn btn-primary" type="submit"><?= nu_icon('check2') ?> Save services &amp; base</button>
      </form>
    </section>

    <section class="sv-card mk-sec">
      <h2>Profile photo &amp; social links</h2>
      <form action="<?= nu_e(nu_url('actions/onboarding.php')) ?>" method="POST" enctype="multipart/form-data" class="mk-form">
        <?= nu_csrf_field() ?>
        <input type="hidden" name="do" value="save_social">
        <input type="hidden" name="back" value="public/contractor_setup.php?tab=contact">
        <div class="ob-avatar-row">
          <div class="ob-avatar-preview">
            <?php if (!empty($user['photo']) && is_file(__DIR__ . '/../uploads/' . basename($user['photo']))): ?>
              <img src="<?= nu_e(nu_url('uploads/' . basename($user['photo']))) ?>" alt="Profile photo">
            <?php else: ?>
              <span><?= nu_e(nu_initials($user['name'] ?? 'P')) ?></span>
            <?php endif; ?>
          </div>
          <div class="ob-avatar-meta">
            <label class="btn btn-outline btn-sm" for="hub-avatar"><?= nu_icon('upload') ?> Change photo</label>
            <input type="file" id="hub-avatar" name="avatar" accept="image/jpeg,image/png,image/webp" hidden>
            <small class="hint">JPG, PNG or WebP up to 10 MB.</small>
          </div>
        </div>
        <div class="ob-grid-2">
          <label class="fld"><span class="label">Website URL</span><input class="input" type="url" name="website_url" value="<?= nu_e($pd['website_url'] ?? '') ?>" placeholder="https://yourbusiness.ca"></label>
          <label class="fld"><span class="label">Instagram username</span><input class="input" type="text" name="instagram_url" value="<?= nu_e(preg_replace('#^https?://(www\.)?instagram\.com/#i', '', (string) ($pd['instagram_url'] ?? ''))) ?>" placeholder="yourhandle"></label>
          <label class="fld"><span class="label">Facebook URL</span><input class="input" type="url" name="facebook_url" value="<?= nu_e($pd['facebook_url'] ?? '') ?>" placeholder="https://facebook.com/yourpage"></label>
          <label class="fld"><span class="label">HomeStars URL</span><input class="input" type="url" name="homestars_url" value="<?= nu_e($pd['homestars_url'] ?? '') ?>" placeholder="https://homestars.com/companies/..."></label>
          <label class="fld"><span class="label">TikTok URL</span><input class="input" type="url" name="tiktok_url" value="<?= nu_e($pd['tiktok_url'] ?? '') ?>" placeholder="https://tiktok.com/@yourhandle"></label>
        </div>
        <button class="btn btn-outline" type="submit"><?= nu_icon('check2') ?> Save photo &amp; links</button>
      </form>
    </section>

<?php elseif ($tab === 'identity'): ?>
    <!-- TAB 2: GOVERNMENT ID & SELFIE -->
    <section class="sv-card mk-sec">
      <div class="ob-progress-head">
        <div>
          <h2>Identification proof</h2>
          <p class="mk-sub">Identified professionals get more clients. Required for every provider on Nuahn.</p>
        </div>
        <span class="chip <?= $p['chk_identity'] === 'ok' ? 'chip--success' : ($p['chk_identity'] === 'rejected' ? 'chip--danger' : 'chip--warning') ?>"><?= nu_e($chkWords[$p['chk_identity']] ?? $p['chk_identity']) ?></span>
      </div>

      <ul class="ob-rules">
        <li><?= nu_icon('check-circle-fill') ?> Accepted Canadian IDs: <b>Driver’s licence, Canadian passport, provincial photo ID, health card, or Certificate of Indian Status</b>.</li>
        <li><?= nu_icon('check-circle-fill') ?> <b>Name must match your profile name</b> (<?= nu_e($user['name'] ?? '') ?>).</li>
        <li><?= nu_icon('check-circle-fill') ?> <b>Expired documents are not accepted.</b> You must be authorized to work in Canada.</li>
        <li><?= nu_icon('shield-lock') ?> Files are stored privately — only Nuahn vetting reviewers can open them.</li>
      </ul>

<?php if (!$locked && $status !== 'approved'): ?>
      <?= mk_form('doc_upload', ['kind' => 'identity'], 'class="mk-form mk-form--row" enctype="multipart/form-data"', 'contractor_setup.php?tab=identity') ?>
        <label class="fld"><span class="label">Government ID file (PDF, JPG or PNG, max 10 MB)</span><input class="input" type="file" name="doc[]" accept="application/pdf,image/jpeg,image/png" required></label>
        <label class="fld"><span class="label">ID expiry date *</span><input class="input" type="date" name="expires_on" required></label>
        <button class="btn btn-primary" type="submit"><?= nu_icon('upload') ?> Upload ID proof</button>
      </form>
<?php endif; ?>

<?php if ($docsByKind['identity']): ?>
      <ul class="mk-docs mk-docs--files" style="margin-top:var(--s4)">
<?php foreach ($docsByKind['identity'] as $d): ?>
        <li><span><?= nu_icon('file-earmark-text') ?> Government ID <small>uploaded <?= nu_e(mk_dt($d['created_at'])) ?><?= $d['expires_on'] ? ' · expires ' . nu_e($d['expires_on']) : '' ?></small></span>
          <a class="linkbtn" href="<?= nu_e(nu_url('public/media.php?d=' . (int) $d['id'])) ?>" target="_blank" rel="noopener">View</a></li>
<?php endforeach; ?>
      </ul>
<?php endif; ?>
    </section>

    <section class="sv-card mk-sec">
      <div class="ob-progress-head">
        <div>
          <h2>Selfie match <small class="ob-sub-inline">(recommended)</small></h2>
          <p class="mk-sub">Upload a clear front-facing selfie so reviewers can confirm your photo matches your Government ID.</p>
        </div>
        <?php $fv = (string) ($user['face_verified'] ?? 'pending'); ?>
        <span class="chip <?= $fv === 'verified' ? 'chip--success' : ($fv === 'failed' ? 'chip--danger' : 'chip--plain') ?>"><?= !empty($user['selfie_photo']) ? nu_e(ucfirst($fv)) : 'Not uploaded' ?></span>
      </div>
<?php if (!$locked && $status !== 'approved'): ?>
      <form action="<?= nu_e(nu_url('actions/onboarding.php')) ?>" method="POST" enctype="multipart/form-data" class="mk-form mk-form--row">
        <?= nu_csrf_field() ?>
        <input type="hidden" name="do" value="upload_selfie">
        <input type="hidden" name="back" value="public/contractor_setup.php?tab=identity">
        <label class="fld"><span class="label">Selfie photo (JPG or PNG)</span><input class="input" type="file" name="selfie" accept="image/jpeg,image/png,image/webp" required></label>
        <button class="btn btn-outline" type="submit"><?= nu_icon('camera') ?> Upload selfie</button>
      </form>
<?php endif; ?>
      <?php if (!empty($user['selfie_photo'])): ?>
        <p class="hint" style="margin-top:var(--s3)">Selfie on file — <a href="<?= nu_e(nu_url('public/media.php?selfie=' . (int) $uid)) ?>" target="_blank" rel="noopener">view private copy</a>.</p>
      <?php endif; ?>
    </section>

<?php elseif ($tab === 'licence'): ?>
    <!-- TAB 3: TRADE LICENCE & CERTIFICATION (CATEGORY-GATED) -->
    <section class="sv-card mk-sec">
      <div class="ob-progress-head">
        <div>
          <h2>Trade licence &amp; certification</h2>
          <p class="mk-sub">
            <?php if ($sum['needs_licence']): ?>
              <b>Required for Mobile Mechanics:</b> upload your active provincial automotive technician licence or Red Seal certificate before quoting.
            <?php else: ?>
              Optional for Winter Snow Removal. Upload any trade certificate if you also plan to offer Mobile Mechanics or regulated trades.
            <?php endif; ?>
          </p>
        </div>
        <span class="chip <?= $p['chk_licence'] === 'ok' ? 'chip--success' : ($p['chk_licence'] === 'rejected' ? 'chip--danger' : ($sum['needs_licence'] ? 'chip--warning' : 'chip--plain')) ?>">
          <?= nu_e($chkWords[$p['chk_licence']] ?? $p['chk_licence']) ?>
        </span>
      </div>

      <form action="<?= nu_e(nu_url('actions/onboarding.php')) ?>" method="POST" class="mk-form" style="margin-bottom:var(--s5)">
        <?= nu_csrf_field() ?>
        <input type="hidden" name="do" value="save_credentials">
        <input type="hidden" name="tab" value="licence">
        <input type="hidden" name="back" value="public/contractor_setup.php?tab=licence">
        <div class="ob-grid-2">
          <label class="fld"><span class="label">Licence number</span><input class="input" name="license_number" maxlength="100" value="<?= nu_e($user['license_number'] ?? '') ?>" placeholder="e.g. AB-AST-123456"></label>
          <label class="fld"><span class="label">Issuing authority</span><input class="input" name="issuing_authority" maxlength="255" value="<?= nu_e($user['issuing_authority'] ?? '') ?>" placeholder="e.g. Alberta AIT / Skilled Trades Ontario"></label>
          <label class="fld"><span class="label">Red Seal / certification number (optional)</span><input class="input" name="certification_number" maxlength="100" value="<?= nu_e($user['certification_number'] ?? '') ?>"></label>
          <label class="fld"><span class="label">Licence expiry date</span><input class="input" type="date" name="license_expiry" value="<?= nu_e($user['license_expiry'] ?? '') ?>"></label>
        </div>
        <button class="btn btn-outline" type="submit"><?= nu_icon('check2') ?> Save licence details</button>
      </form>

<?php if (!$locked && $status !== 'approved'): ?>
      <?= mk_form('doc_upload', [], 'class="mk-form mk-form--row" enctype="multipart/form-data"', 'contractor_setup.php?tab=licence') ?>
        <label class="fld"><span class="label">Document type</span>
          <select class="input" name="kind">
            <option value="licence">Trade licence</option>
            <option value="cert">Trade certificate (optional)</option>
          </select>
        </label>
        <label class="fld"><span class="label">Document file (PDF, JPG or PNG)</span><input class="input" type="file" name="doc[]" accept="application/pdf,image/jpeg,image/png" required></label>
        <label class="fld"><span class="label">Expires</span><input class="input" type="date" name="expires_on" value="<?= nu_e($user['license_expiry'] ?? '') ?>"></label>
        <button class="btn btn-primary" type="submit"><?= nu_icon('upload') ?> Upload document</button>
      </form>
<?php endif; ?>

<?php $licDocs = array_merge($docsByKind['licence'], $docsByKind['cert']); if ($licDocs): ?>
      <ul class="mk-docs mk-docs--files" style="margin-top:var(--s4)">
<?php foreach ($licDocs as $d): ?>
        <li><span><?= nu_icon('file-earmark-text') ?> <?= nu_e($chk[$d['kind']][0] ?? ucfirst($d['kind'])) ?> <small>uploaded <?= nu_e(mk_dt($d['created_at'])) ?><?= $d['expires_on'] ? ' · expires ' . nu_e($d['expires_on']) : '' ?></small></span>
          <a class="linkbtn" href="<?= nu_e(nu_url('public/media.php?d=' . (int) $d['id'])) ?>" target="_blank" rel="noopener">View</a></li>
<?php endforeach; ?>
      </ul>
<?php endif; ?>
    </section>

<?php elseif ($tab === 'insurance'): ?>
    <!-- TAB 4: LIABILITY INSURANCE -->
    <section class="sv-card mk-sec">
      <div class="ob-progress-head">
        <div>
          <h2>Liability insurance</h2>
          <p class="mk-sub">Active commercial general liability coverage protects both you and homeowners on every booking.</p>
        </div>
        <span class="chip <?= $p['chk_insurance'] === 'ok' ? 'chip--success' : ($p['chk_insurance'] === 'rejected' ? 'chip--danger' : 'chip--warning') ?>"><?= nu_e($chkWords[$p['chk_insurance']] ?? $p['chk_insurance']) ?></span>
      </div>

      <form action="<?= nu_e(nu_url('actions/onboarding.php')) ?>" method="POST" class="mk-form mk-form--row" style="margin-bottom:var(--s5)">
        <?= nu_csrf_field() ?>
        <input type="hidden" name="do" value="save_credentials">
        <input type="hidden" name="tab" value="insurance">
        <input type="hidden" name="back" value="public/contractor_setup.php?tab=insurance">
        <label class="fld"><span class="label">Policy number</span><input class="input" name="insurance_number" maxlength="100" value="<?= nu_e($user['insurance_number'] ?? '') ?>" placeholder="e.g. CGL-98980980"></label>
        <button class="btn btn-outline" type="submit"><?= nu_icon('check2') ?> Save policy #</button>
      </form>

<?php if (!$locked && $status !== 'approved'): ?>
      <?= mk_form('doc_upload', ['kind' => 'insurance'], 'class="mk-form mk-form--row" enctype="multipart/form-data"', 'contractor_setup.php?tab=insurance') ?>
        <label class="fld"><span class="label">Certificate of insurance (PDF, JPG or PNG)</span><input class="input" type="file" name="doc[]" accept="application/pdf,image/jpeg,image/png" required></label>
        <label class="fld"><span class="label">Policy expiry date *</span><input class="input" type="date" name="expires_on" required></label>
        <button class="btn btn-primary" type="submit"><?= nu_icon('upload') ?> Upload insurance</button>
      </form>
<?php endif; ?>

<?php if ($docsByKind['insurance']): ?>
      <ul class="mk-docs mk-docs--files" style="margin-top:var(--s4)">
<?php foreach ($docsByKind['insurance'] as $d): ?>
        <li><span><?= nu_icon('file-earmark-text') ?> Liability insurance <small>uploaded <?= nu_e(mk_dt($d['created_at'])) ?><?= $d['expires_on'] ? ' · expires ' . nu_e($d['expires_on']) : '' ?></small></span>
          <a class="linkbtn" href="<?= nu_e(nu_url('public/media.php?d=' . (int) $d['id'])) ?>" target="_blank" rel="noopener">View</a></li>
<?php endforeach; ?>
      </ul>
<?php endif; ?>
    </section>

<?php else: // TAB 5: GOOGLE REVIEWS ?>
    <section class="sv-card mk-sec">
      <div class="ob-progress-head">
        <div>
          <h2>Google Reviews</h2>
          <p class="mk-sub">Optional — link your Canadian Google Business listing to showcase verified reviews on your profile.</p>
        </div>
        <?php $grBadge = $grMessages[$grStatus] ?? ['chip--plain', 'Not linked', 'Link your Google Business below to display verified client reviews.']; ?>
        <span class="chip <?= nu_e($grBadge[0]) ?>"><?= nu_e($grBadge[1]) ?></span>
      </div>

      <div class="mk-note<?= $grStatus === 'rejected' ? ' mk-note--warn' : '' ?>" style="margin-bottom:var(--s5)">
        <?= nu_icon($grStatus === 'approved' ? 'check-circle-fill' : 'info-circle') ?>
        <div>
          <b><?= nu_e($pd['google_business_name'] ?? 'Google Business Profile') ?></b>
          <p><?= nu_e($grBadge[2]) ?></p>
        </div>
      </div>

<?php if (!in_array($grStatus, ['approved', 'in_review'], true)): ?>
      <form action="<?= nu_e(nu_url('actions/onboarding.php')) ?>" method="POST" class="mk-form">
        <?= nu_csrf_field() ?>
        <input type="hidden" name="do" value="save_google_reviews">
        <input type="hidden" name="back" value="public/contractor_setup.php?tab=reviews">
        <label class="fld"><span class="label">Google Business name *</span><input class="input" name="google_business_name" value="<?= nu_e($pd['google_business_name'] ?? '') ?>" placeholder="Exact business name on Google Maps" required></label>
        <label class="fld"><span class="label">Google Maps / Business link</span><input class="input" type="url" name="google_maps_url" value="<?= nu_e($pd['google_maps_url'] ?? '') ?>" placeholder="https://maps.google.com/?cid=..."></label>
        <p class="hint">You can only link one Google Business per account once verified — choose carefully.</p>
        <button class="btn btn-primary" type="submit"><?= nu_icon('star-fill') ?> <?= $grStatus === 'rejected' ? 'Resubmit for verification' : 'Submit Google Business' ?></button>
      </form>
<?php endif; ?>
    </section>
<?php endif; ?>

    <!-- Submit for Staff Vetting -->
<?php if (in_array($status, ['draft', 'changes_requested', 'rejected'], true)): ?>
    <section class="sv-card mk-sec">
      <h2>Submit for review</h2>
      <p class="mk-sub">Save your services &amp; base address and upload your Government ID and liability insurance first. Mobile mechanics also need a trade licence.</p>
      <?= mk_form('profile_submit', [], '', 'contractor_setup.php?tab=' . $tab) ?>
        <button class="btn btn-primary" type="submit"><?= nu_icon('send') ?> Submit my application for approval</button>
      </form>
    </section>
<?php endif; ?>
  </main>
</div>

<?php if ($tab === 'contact'): ?>
<script src="<?= nu_e(nu_asset('public/assets/map-picker.js')) ?>" defer></script>
<script>
(function () {
  var btn = document.getElementById('h-get-code');
  var form = document.getElementById('hubPhoneForm');
  if (!btn || !form) return;
  var status = document.getElementById('h-otp-status');
  var csrf = form.querySelector('input[name="_csrf"]');
  btn.addEventListener('click', function () {
    btn.disabled = true;
    status.textContent = 'Sending code...';
    var fd = new FormData();
    fd.append('_csrf', csrf ? csrf.value : '');
    fd.append('do', 'otp_send');
    fd.append('purpose', 'verify');
    fd.append('phone_code', document.getElementById('h-cc').value);
    fd.append('phone', document.getElementById('h-phone').value.trim());
    fetch('<?= nu_e(nu_url('actions/onboarding.php')) ?>', { method: 'POST', headers: { 'Accept': 'application/json' }, body: fd })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d.ok) { status.textContent = d.error || 'Could not send code.'; status.style.color = 'var(--danger)'; btn.disabled = false; return; }
        status.textContent = (d.sandbox && d.sandbox_code) ? ('Beta mode — your code is: ' + d.sandbox_code) : ('Code sent to ' + d.phone);
        status.style.color = 'var(--pine)';
        setTimeout(function () { btn.disabled = false; }, 30000);
      })
      .catch(function () { status.textContent = 'Network error.'; status.style.color = 'var(--danger)'; btn.disabled = false; });
  });
})();
</script>
<?php endif; ?>
<?php include_once("../includes/footer.php"); ?>
