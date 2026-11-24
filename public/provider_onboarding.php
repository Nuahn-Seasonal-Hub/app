<?php
// public/provider_onboarding.php
// 3-step provider signup wizard (1. Services & Area -> 2. Account Details -> 3. Verify Phone),
// followed after account creation by Profile Picture & Links, Google Reviews, and the 5-tab Verification Hub.
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../includes/onboarding.php';
ob_ensure_users_schema();

$step = (int) ($_GET['step'] ?? 1);
if (!in_array($step, [1, 2, 3], true)) $step = 1;

// If already logged in as a provider and visiting step 1, jump straight to post-signup step 2
if (isset($_SESSION['user_id']) && nu_role() === 'provider' && $step === 1) {
    $step = 2;
}
// Steps 2 and 3 require an authenticated provider session
if ($step > 1 && (!isset($_SESSION['user_id']) || nu_role() !== 'provider')) {
    flashError('Complete signup first to continue setting up your provider profile.');
    header('Location: ' . nu_url('public/provider_onboarding.php?step=1'));
    exit;
}

$uid  = isset($_SESSION['user_id']) ? mk_uid() : 0;
$user = $uid ? ob_user_get($uid) : null;
$prof = ($uid && mk_ready()) ? mk_profile_ensure($uid) : null;
$pd   = ob_prof_data($user);
$services = ob_provider_services();
$cities   = ob_canadian_cities();
$placesKey = ob_cfg('google_places_key', '');

include_once __DIR__ . '/../includes/header.php';
?>

<div class="auth">
  <?php nu_auth_aside(
      'Grow your local service business on Nuahn.',
      'Join vetted Canadian pros for winter snow removal, mobile mechanics, and seasonal home care. Get approved once, then quote and earn with protected escrow payouts.'
  ); ?>

  <section class="auth__main">
    <div class="auth-card auth-card--wide">

<?php if ($step === 1): ?>
      <!-- 3-Step Signup Stepper -->
      <ol class="ob-stepper" id="signupStepper" aria-label="Provider signup steps">
        <li class="is-active" data-s="1" aria-current="step">
          <span class="ob-stepper__dot">1</span>
          <span class="ob-stepper__lbl">Services &amp; Area</span>
        </li>
        <li data-s="2">
          <span class="ob-stepper__dot">2</span>
          <span class="ob-stepper__lbl">Account Details</span>
        </li>
        <li data-s="3">
          <span class="ob-stepper__dot">3</span>
          <span class="ob-stepper__lbl">Verify Phone</span>
        </li>
      </ol>

      <?php nu_auth_head('Sign up as a provider', 'Three quick steps to create and verify your provider account.', 'person-badge', true); ?>

      <form action="<?= nu_e(nu_url('actions/onboarding.php')) ?>" method="POST" class="auth-form" id="provStep1Form" novalidate>
        <?= nu_csrf_field() ?>
        <input type="hidden" name="do" value="provider_step1">
        <input type="hidden" name="back" value="public/provider_onboarding.php?step=1">

        <p class="ob-step-err" id="obStepErr" role="alert" aria-live="polite"></p>

        <!-- SUB-STEP 1: Name, Services & City -->
        <div class="ob-step-pane" data-pane="1">
          <label class="field" for="p-name">
            <span class="label">Full legal name *</span>
            <span class="input-icon"><?= nu_icon('person') ?><input type="text" class="input" id="p-name" name="name" placeholder="Must match your Government ID" autocomplete="name" required></span>
          </label>

          <fieldset class="field ob-fieldset">
            <legend class="label">Services you provide * <small class="ob-sub-inline">(pick at least one)</small></legend>
            <div class="ob-chips">
<?php foreach ($services as $slug => $svc): ?>
              <label class="ob-chip-opt">
                <input type="checkbox" name="services[]" value="<?= nu_e($slug) ?>">
                <span>
                  <?= nu_e($svc['name']) ?>
                  <?php if ($svc['licensed']): ?><em class="ob-tag">Licence required</em><?php endif; ?>
                  <?php if (!$svc['live']): ?><em class="ob-tag ob-tag--muted">Soon</em><?php endif; ?>
                </span>
              </label>
<?php endforeach; ?>
            </div>
          </fieldset>

          <label class="field" for="p-city">
            <span class="label">Primary city you operate in *</span>
            <span class="input-icon"><?= nu_icon('geo-alt') ?><input type="text" class="input" id="p-city" name="primary_city" list="nu-ca-cities" placeholder="e.g. Edmonton, AB" required></span>
            <datalist id="nu-ca-cities">
<?php foreach ($cities as $c): ?>
              <option value="<?= nu_e($c) ?>">
<?php endforeach; ?>
            </datalist>
          </label>

          <label class="field" for="p-areas">
            <span class="label">Additional service areas <small class="ob-sub-inline">(optional)</small></span>
            <input type="text" class="input" id="p-areas" name="service_areas" placeholder="e.g. St. Albert, Sherwood Park, Leduc">
          </label>

          <div class="ob-step-nav">
            <button type="button" class="btn btn-primary btn-lg" data-next="2">Continue <?= nu_icon('arrow-right') ?></button>
          </div>
        </div>

        <!-- SUB-STEP 2: Email & Password -->
        <div class="ob-step-pane" data-pane="2" hidden>
          <label class="field" for="p-email">
            <span class="label">Email address *</span>
            <span class="input-icon"><?= nu_icon('envelope') ?><input type="email" class="input" id="p-email" name="email" placeholder="you@business.ca" autocomplete="email" required></span>
          </label>

          <div class="field">
            <?php nu_password_field('password', 'p-password', 'Password *', 'new-password', 'aria-describedby="pw-reqs"'); ?>
            <div class="meter" aria-hidden="true" style="margin-top:-10px"><span id="password-strength"></span></div>
            <ul class="reqs" id="pw-reqs">
              <li id="req-length">8+ characters</li>
              <li id="req-upper">Uppercase</li>
              <li id="req-lower">Lowercase</li>
              <li id="req-number">Number</li>
              <li id="req-symbol">Symbol</li>
            </ul>
          </div>

          <div class="ob-step-nav">
            <button type="button" class="btn btn-outline btn-lg" data-prev="1">Back</button>
            <button type="button" class="btn btn-primary btn-lg" data-next="3">Continue <?= nu_icon('arrow-right') ?></button>
          </div>
        </div>

        <!-- SUB-STEP 3: Phone SMS OTP & Terms -->
        <div class="ob-step-pane" data-pane="3" hidden>
          <div class="field">
            <span class="label">Phone number &amp; SMS verification *</span>
            <div class="ob-phone-row">
              <select class="input ob-cc" name="phone_code" id="p-cc" aria-label="Country code">
                <option value="+1" selected>+1</option>
              </select>
              <input type="tel" class="input" id="p-phone" name="phone" placeholder="780 555 1234" maxlength="16" autocomplete="tel-national" required>
            </div>
            <div class="ob-otp-row">
              <input type="text" class="input" id="p-otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="Enter 6-digit OTP code" required>
              <button type="button" class="btn btn-outline" id="p-get-code">Get Code</button>
            </div>
            <p class="hint" id="p-otp-status" aria-live="polite"></p>
          </div>

          <label class="mk-check ob-terms">
            <input type="checkbox" name="agree_terms" id="p-terms" value="1" required>
            <span>I confirm I am authorized to work in Canada and agree to Nuahn’s Terms &amp; Conditions.</span>
          </label>

          <div class="ob-step-nav">
            <button type="button" class="btn btn-outline btn-lg" data-prev="2">Back</button>
            <button type="submit" class="btn btn-primary btn-lg" id="p-submit" disabled>
              Create provider account <?= nu_icon('arrow-right') ?>
            </button>
          </div>
        </div>
      </form>

      <p class="auth-alt">Looking to hire instead? <a href="<?= nu_e(nu_url('public/register.php')) ?>">Client sign up</a> · Already registered? <a href="<?= nu_e(nu_url('public/login.php')) ?>">Sign in</a></p>

<?php elseif ($step === 2): ?>
      <!-- Post-Signup Onboarding Stepper -->
      <ol class="ob-stepper" aria-label="Profile onboarding steps">
        <li class="is-done"><span class="ob-stepper__dot"><?= nu_icon('check2') ?></span><span class="ob-stepper__lbl">Account Created</span></li>
        <li class="is-active" aria-current="step"><span class="ob-stepper__dot">2</span><span class="ob-stepper__lbl">Profile Picture &amp; Links</span></li>
        <li><span class="ob-stepper__dot">3</span><span class="ob-stepper__lbl">Google Reviews</span></li>
      </ol>

      <?php nu_auth_head('Add your photo & links', 'A clear headshot and your online profiles help homeowners trust and pick you faster.', 'camera', true); ?>

      <form action="<?= nu_e(nu_url('actions/onboarding.php')) ?>" method="POST" enctype="multipart/form-data" class="auth-form">
        <?= nu_csrf_field() ?>
        <input type="hidden" name="do" value="provider_step2">
        <input type="hidden" name="back" value="public/provider_onboarding.php?step=2">

        <div class="ob-avatar-row">
          <div class="ob-avatar-preview" id="ob-avatar-box">
            <?php if (!empty($user['photo']) && is_file(__DIR__ . '/../uploads/' . basename($user['photo']))): ?>
              <img src="<?= nu_e(nu_url('uploads/' . basename($user['photo']))) ?>" alt="Profile picture">
            <?php else: ?>
              <span><?= nu_e(nu_initials($user['name'] ?? 'P')) ?></span>
            <?php endif; ?>
          </div>
          <div class="ob-avatar-meta">
            <label class="btn btn-outline btn-sm" for="ob-avatar-input"><?= nu_icon('upload') ?> Upload profile picture</label>
            <input type="file" id="ob-avatar-input" name="avatar" accept="image/jpeg,image/png,image/webp" hidden>
            <small class="hint">JPG, PNG or WebP up to 10 MB. Clear headshot works best.</small>
          </div>
        </div>

        <div class="ob-grid-2">
          <label class="field" for="p-headline">
            <span class="label">Short headline</span>
            <input type="text" class="input" id="p-headline" name="headline" maxlength="160" value="<?= nu_e($prof['headline'] ?? '') ?>" placeholder="e.g. Fast driveway clearing, 6 yrs in Edmonton">
          </label>
          <label class="field" for="p-years">
            <span class="label">Years of experience</span>
            <input type="number" class="input" id="p-years" name="years_experience" min="0" max="60" value="<?= nu_e($user['years_experience'] ?? '') ?>" placeholder="e.g. 5">
          </label>
        </div>

        <label class="field" for="p-bio">
          <span class="label">About you &amp; your work</span>
          <textarea class="input" id="p-bio" name="professional_bio" rows="3" maxlength="1500" placeholder="Tell homeowners about your background, reliability, and service standards."><?= nu_e($user['professional_bio'] ?? ($prof['about'] ?? '')) ?></textarea>
        </label>

        <label class="field" for="p-equip">
          <span class="label">Equipment &amp; tools</span>
          <input type="text" class="input" id="p-equip" name="equipment" maxlength="255" value="<?= nu_e($prof['equipment'] ?? '') ?>" placeholder="e.g. Plow truck, walk-behind blower, scan tool, jump pack">
        </label>

        <div class="ob-subhead">Social media &amp; business links <small>(optional)</small></div>

        <label class="field" for="s-web">
          <span class="label">Website URL</span>
          <input type="url" class="input" id="s-web" name="website_url" value="<?= nu_e($pd['website_url'] ?? '') ?>" placeholder="https://yourbusiness.ca">
        </label>
        <div class="ob-grid-2">
          <label class="field" for="s-ig">
            <span class="label">Instagram username</span>
            <input type="text" class="input" id="s-ig" name="instagram_url" value="<?= nu_e(preg_replace('#^https?://(www\.)?instagram\.com/#i', '', (string) ($pd['instagram_url'] ?? ''))) ?>" placeholder="yourhandle">
          </label>
          <label class="field" for="s-fb">
            <span class="label">Facebook URL</span>
            <input type="url" class="input" id="s-fb" name="facebook_url" value="<?= nu_e($pd['facebook_url'] ?? '') ?>" placeholder="https://facebook.com/yourpage">
          </label>
        </div>
        <div class="ob-grid-2">
          <label class="field" for="s-hs">
            <span class="label">HomeStars URL</span>
            <input type="url" class="input" id="s-hs" name="homestars_url" value="<?= nu_e($pd['homestars_url'] ?? '') ?>" placeholder="https://homestars.com/companies/...">
          </label>
          <label class="field" for="s-tt">
            <span class="label">TikTok URL</span>
            <input type="url" class="input" id="s-tt" name="tiktok_url" value="<?= nu_e($pd['tiktok_url'] ?? '') ?>" placeholder="https://tiktok.com/@yourhandle">
          </label>
        </div>

        <div class="ob-actions">
          <button type="submit" class="btn btn-primary btn-lg">Continue to Google Reviews <?= nu_icon('arrow-right') ?></button>
          <button type="submit" name="skip_step" value="1" formnovalidate class="btn btn-ghost">Skip for now</button>
        </div>
      </form>

<?php else: // Step 3: Google Reviews ?>
      <ol class="ob-stepper" aria-label="Profile onboarding steps">
        <li class="is-done"><span class="ob-stepper__dot"><?= nu_icon('check2') ?></span><span class="ob-stepper__lbl">Account Created</span></li>
        <li class="is-done"><span class="ob-stepper__dot"><?= nu_icon('check2') ?></span><span class="ob-stepper__lbl">Profile Picture &amp; Links</span></li>
        <li class="is-active" aria-current="step"><span class="ob-stepper__dot">3</span><span class="ob-stepper__lbl">Google Reviews</span></li>
      </ol>

      <?php nu_auth_head('Bring your Google reviews', 'Link your Google Business Profile to display verified client trust on Nuahn.', 'star-fill', true); ?>

      <div class="mk-note" style="margin-bottom:var(--s5)">
        <?= nu_icon('shield-check') ?>
        <div>
          <b>Build trust faster from day one</b>
          <p>Reviews appear on your public Nuahn contractor profile after a quick staff verification (usually within 24 hours). You can only link one Google Business per account — choose carefully.</p>
        </div>
      </div>

      <form action="<?= nu_e(nu_url('actions/onboarding.php')) ?>" method="POST" class="auth-form">
        <?= nu_csrf_field() ?>
        <input type="hidden" name="do" value="provider_step3">
        <input type="hidden" name="back" value="public/provider_onboarding.php?step=3">
        <input type="hidden" name="google_place_id" id="ob-place-id" value="<?= nu_e($pd['google_place_id'] ?? '') ?>">

        <label class="field" for="ob-g-name">
          <span class="label">Google Business name</span>
          <span class="input-icon"><?= nu_icon('search') ?><input type="text" class="input" id="ob-g-name" name="google_business_name" value="<?= nu_e($pd['google_business_name'] ?? '') ?>" placeholder="Search or enter your exact Google Business name" required></span>
        </label>

        <label class="field" for="ob-g-url">
          <span class="label">Google Maps / Business profile link</span>
          <input type="url" class="input" id="ob-g-url" name="google_maps_url" value="<?= nu_e($pd['google_maps_url'] ?? '') ?>" placeholder="https://maps.google.com/?cid=... or https://g.page/...">
          <small class="hint">Open your business on Google Maps, click Share, and paste the link here.</small>
        </label>

        <div class="ob-actions">
          <button type="submit" class="btn btn-primary btn-lg">Submit &amp; go to verification <?= nu_icon('arrow-right') ?></button>
          <button type="submit" name="skip_step" value="1" formnovalidate class="btn btn-ghost">Skip for now</button>
        </div>
      </form>
<?php endif; ?>
    </div>
  </section>
</div>

<script>
(function () {
  var form1 = document.getElementById('provStep1Form');
  if (form1) {
    var cur = 1;
    var errBox = document.getElementById('obStepErr');
    var panes  = form1.querySelectorAll('.ob-step-pane');
    var steps  = document.querySelectorAll('#signupStepper li');
    var pw     = document.getElementById('p-password');
    var bar    = document.getElementById('password-strength');
    var rules  = [
      ['req-length', function (v) { return v.length >= 8; }],
      ['req-upper',  function (v) { return /[A-Z]/.test(v); }],
      ['req-lower',  function (v) { return /[a-z]/.test(v); }],
      ['req-number', function (v) { return /[0-9]/.test(v); }],
      ['req-symbol', function (v) { return /[^A-Za-z0-9]/.test(v); }]
    ];

    function pwScore(v) {
      var s = 0;
      rules.forEach(function (r) { var ok = r[1](v); if (ok) s++; var el = document.getElementById(r[0]); if (el) el.classList.toggle('ok', ok); });
      if (bar) {
        bar.style.width = (s * 20) + '%';
        bar.style.background = s <= 2 ? 'var(--danger)' : (s <= 4 ? 'var(--warning)' : 'var(--success)');
      }
      return s;
    }
    if (pw) pw.addEventListener('input', function () { pwScore(pw.value); });

    function showErr(msg) { errBox.textContent = msg || ''; }

    function validatePane(n) {
      showErr('');
      if (n === 1) {
        var nm = document.getElementById('p-name').value.trim();
        var checked = form1.querySelectorAll('input[name="services[]"]:checked').length;
        var city = document.getElementById('p-city').value.trim();
        if (nm.length < 2) { showErr('Enter your full legal name (must match your Government ID).'); document.getElementById('p-name').focus(); return false; }
        if (checked < 1) { showErr('Select at least one service you provide.'); return false; }
        if (city.length < 2) { showErr('Enter the primary city you operate in.'); document.getElementById('p-city').focus(); return false; }
      }
      if (n === 2) {
        var em = document.getElementById('p-email').value.trim();
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) { showErr('Enter a valid email address.'); document.getElementById('p-email').focus(); return false; }
        if (pwScore(pw.value) < 5) { showErr('Password must meet all 5 requirements below.'); pw.focus(); return false; }
      }
      return true;
    }

    function goPane(target) {
      cur = target;
      panes.forEach(function (p) { p.hidden = Number(p.getAttribute('data-pane')) !== cur; });
      steps.forEach(function (li) {
        var n = Number(li.getAttribute('data-s'));
        li.classList.toggle('is-active', n === cur);
        li.classList.toggle('is-done', n < cur);
        if (n === cur) li.setAttribute('aria-current', 'step'); else li.removeAttribute('aria-current');
        var dot = li.querySelector('.ob-stepper__dot');
        if (dot) dot.textContent = n < cur ? '✓' : String(n);
      });
      showErr('');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    form1.querySelectorAll('[data-next]').forEach(function (b) {
      b.addEventListener('click', function () {
        var nxt = Number(b.getAttribute('data-next'));
        if (validatePane(cur)) goPane(nxt);
      });
    });
    form1.querySelectorAll('[data-prev]').forEach(function (b) {
      b.addEventListener('click', function () { goPane(Number(b.getAttribute('data-prev'))); });
    });
    form1.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && cur < 3 && e.target.tagName === 'INPUT') {
        e.preventDefault();
        if (validatePane(cur)) goPane(cur + 1);
      }
    });

    var otpBtn = document.getElementById('p-get-code');
    var otpIn  = document.getElementById('p-otp');
    var terms  = document.getElementById('p-terms');
    var submit = document.getElementById('p-submit');
    var status = document.getElementById('p-otp-status');
    var csrf   = form1.querySelector('input[name="_csrf"]');
    var timer  = null;

    function syncSubmit() {
      var otpReady = /^\d{6}$/.test((otpIn.value || '').trim());
      submit.disabled = !(otpReady && terms.checked);
    }
    otpIn.addEventListener('input', syncSubmit);
    terms.addEventListener('change', syncSubmit);

    otpBtn.addEventListener('click', function () {
      var phone = document.getElementById('p-phone').value.trim();
      var cc    = document.getElementById('p-cc').value;
      status.textContent = 'Sending code...';
      status.style.color = 'var(--muted)';
      otpBtn.disabled = true;

      var fd = new FormData();
      fd.append('_csrf', csrf ? csrf.value : '');
      fd.append('do', 'otp_send');
      fd.append('purpose', 'signup');
      fd.append('phone_code', cc);
      fd.append('phone', phone);

      fetch('<?= nu_e(nu_url('actions/onboarding.php')) ?>', {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: fd
      }).then(function (r) { return r.json(); }).then(function (data) {
        if (!data.ok) {
          status.textContent = data.error || 'Could not send code.';
          status.style.color = 'var(--danger)';
          otpBtn.disabled = false;
          return;
        }
        if (data.sandbox && data.sandbox_code) {
          status.textContent = 'Beta mode (SMS not connected yet) — your verification code is: ' + data.sandbox_code;
          status.style.color = 'var(--pine)';
        } else {
          status.textContent = 'Verification code sent to ' + data.phone + '.';
          status.style.color = 'var(--success)';
        }
        var secs = 30;
        otpBtn.textContent = 'Resend (' + secs + 's)';
        clearInterval(timer);
        timer = setInterval(function () {
          secs--;
          if (secs <= 0) {
            clearInterval(timer);
            otpBtn.disabled = false;
            otpBtn.textContent = 'Resend Code';
          } else {
            otpBtn.textContent = 'Resend (' + secs + 's)';
          }
        }, 1000);
      }).catch(function () {
        status.textContent = 'Network error. Please try again.';
        status.style.color = 'var(--danger)';
        otpBtn.disabled = false;
      });
    });
  }

  var avIn = document.getElementById('ob-avatar-input');
  var avBox = document.getElementById('ob-avatar-box');
  if (avIn && avBox) {
    avIn.addEventListener('change', function () {
      if (!avIn.files || !avIn.files[0]) return;
      var reader = new FileReader();
      reader.onload = function (e) { avBox.innerHTML = '<img src="' + e.target.result + '" alt="Profile preview">'; };
      reader.readAsDataURL(avIn.files[0]);
    });
  }
})();
</script>
<?php if ($step === 3 && $placesKey !== ''): ?>
<script>
function nuInitGooglePlaces() {
  var inp = document.getElementById('ob-g-name');
  if (!inp || !window.google || !google.maps || !google.maps.places) return;
  var ac = new google.maps.places.Autocomplete(inp, {
    componentRestrictions: { country: ['ca'] },
    fields: ['place_id', 'name', 'url']
  });
  ac.addListener('place_changed', function () {
    var pl = ac.getPlace();
    if (pl && pl.place_id) {
      document.getElementById('ob-place-id').value = pl.place_id;
      if (pl.name) inp.value = pl.name;
      if (pl.url) document.getElementById('ob-g-url').value = pl.url;
    }
  });
}
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= nu_e($placesKey) ?>&libraries=places&callback=nuInitGooglePlaces" async defer></script>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
