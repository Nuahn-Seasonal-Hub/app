<?php
//session_start();
include "../includes/header.php";
?>

<div class="auth">
  <?php nu_auth_aside('Join Nuahn in under a minute.', 'Create a free account to post seasonal jobs or find flexible work near you.'); ?>
  <section class="auth__main">
    <div class="auth-card">
      <?php nu_auth_head('Create your account', 'It’s free. Pick how you’ll use Nuahn.', 'stars'); ?>

      <!-- Success & Error Alerts -->
      <?php if(isset($_GET['success']) && $_GET['success'] === 'registered'): ?>
        <div class="alert alert-success"><?= nu_icon('check-circle-fill') ?> Registration successful! You can now log in.</div>
      <?php endif; ?>

      <?php if(isset($_GET['error'])): ?>
        <?php if($_GET['error'] === 'email_taken'): ?>
          <div class="alert alert-danger"><?= nu_icon('x-circle') ?> This email is already registered.</div>
        <?php elseif($_GET['error'] === 'weak_password' && isset($_SESSION['register_errors'])): ?>
          <div class="alert alert-danger">
            <div><strong>Password requirements not met:</strong>
              <ul class="mb-0">
                <?php foreach($_SESSION['register_errors'] as $err): ?>
                  <li><?php echo htmlspecialchars($err); ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <?php unset($_SESSION['register_errors']); ?>
        <?php elseif($_GET['error'] === 'nomatch'): ?>
          <div class="alert alert-danger"><?= nu_icon('x-circle') ?> Passwords do not match.</div>
        <?php endif; ?>
      <?php endif; ?>

      <!-- Registration Form -->
      <form action="../actions/register.php" method="POST" class="auth-form" id="registerForm">
        <fieldset class="field" style="border:0;padding:0;margin:0 0 var(--s5)">
          <legend class="label">I want to</legend>
          <div class="roles">
            <label class="role-opt"><input type="radio" name="role" value="client" checked><span><?= nu_icon('briefcase-fill') ?> Hire help</span></label>
            <label class="role-opt"><input type="radio" name="role" value="provider"><span><?= nu_icon('person-badge') ?> Find work</span></label>
          </div>
        </fieldset>

        <label class="field" for="name"><span class="label">Full name</span>
          <span class="input-icon"><?= nu_icon('person') ?><input type="text" class="input" id="name" name="name" placeholder="Jane Doe" autocomplete="name" required></span>
        </label>

        <label class="field" for="email"><span class="label">Email</span>
          <span class="input-icon"><?= nu_icon('envelope') ?><input type="email" class="input" id="email" name="email" placeholder="you@example.com" autocomplete="email" required></span>
        </label>

        <!-- Password with strength + toggle -->
        <div class="field">
          <?php nu_password_field('password', 'password', 'Password', 'new-password', 'aria-describedby="pw-reqs"'); ?>
          <div class="meter" aria-hidden="true" style="margin-top:-10px"><span id="password-strength"></span></div>
          <ul class="reqs" id="pw-reqs">
            <li id="req-length">8+ characters</li>
            <li id="req-upper">Uppercase</li>
            <li id="req-lower">Lowercase</li>
            <li id="req-number">Number</li>
            <li id="req-symbol">Symbol</li>
          </ul>
        </div>

        <!-- Confirm password with toggle + live check -->
        <?php nu_password_field('confirm_password', 'confirm_password', 'Confirm password', 'new-password'); ?>
        <p id="matchMessage" class="hint" style="margin-top:-12px;min-height:1.2em"></p>

        <button type="submit" class="btn btn-primary btn-lg btn-block">Create account <?= nu_icon('arrow-right') ?></button>
      </form>

      <p class="auth-alt">Already have an account? <a href="login.php">Sign in</a></p>
    </div>
  </section>
</div>

<script>
  (function () {
    var pw = document.getElementById('password');
    var cf = document.getElementById('confirm_password');
    var bar = document.getElementById('password-strength');
    var msg = document.getElementById('matchMessage');
    var rules = [
      ['req-length', function (v) { return v.length >= 8; }],
      ['req-upper', function (v) { return /[A-Z]/.test(v); }],
      ['req-lower', function (v) { return /[a-z]/.test(v); }],
      ['req-number', function (v) { return /[0-9]/.test(v); }],
      ['req-symbol', function (v) { return /[^A-Za-z0-9]/.test(v); }]
    ];
    function strength() {
      var v = pw.value, s = 0;
      rules.forEach(function (r) { var ok = r[1](v); if (ok) s++; document.getElementById(r[0]).classList.toggle('ok', ok); });
      bar.style.width = (s * 20) + '%';
      bar.style.background = s <= 2 ? 'var(--danger)' : (s <= 4 ? 'var(--warning)' : 'var(--success)');
    }
    function match() {
      if (!cf.value) { msg.textContent = ''; return; }
      var ok = cf.value === pw.value;
      msg.textContent = ok ? '✓ Passwords match' : 'Passwords do not match';
      msg.style.color = ok ? 'var(--success)' : 'var(--danger)';
    }
    pw.addEventListener('input', function () { strength(); match(); });
    cf.addEventListener('input', match);
  })();
</script>

<?php include "../includes/footer.php"; ?>
