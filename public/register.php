<?php
//session_start();
include "../includes/header.php";
?>

<main class="container py-5" style="max-width: 600px;">
    <h2 class="mb-4 text-center">Register</h2>

    <!-- Success & Error Alerts -->
    <?php if(isset($_GET['success']) && $_GET['success'] === 'registered'): ?>
        <div class="alert alert-success">Registration successful! You can now log in.</div>
    <?php endif; ?>

    <?php if(isset($_GET['error'])): ?>
        <?php if($_GET['error'] === 'email_taken'): ?>
            <div class="alert alert-danger">This email is already registered.</div>
        <?php elseif($_GET['error'] === 'weak_password' && isset($_SESSION['register_errors'])): ?>
            <div class="alert alert-danger">
                <strong>Password requirements not met:</strong>
                <ul class="mb-0">
                    <?php foreach($_SESSION['register_errors'] as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['register_errors']); ?>
        <?php elseif($_GET['error'] === 'nomatch'): ?>
            <div class="alert alert-danger">Passwords do not match.</div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Registration Form -->
    <div class="card shadow-lg border-0">
        <div class="card-body p-4">
            <form action="../actions/register.php" method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold">Full Name</label>
                    <input type="text" class="form-control form-control-lg" id="name" name="name" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email</label>
                    <input type="email" class="form-control form-control-lg" id="email" name="email" required>
                </div>

                <!-- Password with strength + toggle + popover -->
                <div class="mb-3 position-relative">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control form-control-lg" id="password" name="password"
                               data-bs-toggle="popover" data-bs-trigger="focus"
                               data-bs-content="At least 8 characters, uppercase, lowercase, number, and symbol." required>
                        <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="progress mt-2" style="height: 6px;">
                        <div id="password-strength" class="progress-bar" role="progressbar"></div>
                    </div>
                </div>

                <!-- Confirm password with toggle + live check -->
                <div class="mb-3 position-relative">
                    <label for="confirm_password" class="form-label fw-semibold">Confirm Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control form-control-lg" id="confirm_password" name="confirm_password" required>
                        <button type="button" class="btn btn-outline-secondary" id="toggleConfirm">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <small id="matchMessage" class="form-text"></small>
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label fw-semibold">Role</label>
                    <select class="form-select form-select-lg" id="role" name="role">
                        <option value="client">Client</option>
                        <option value="provider">Provider</option>
                    </select>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-success btn-lg">Register</button>
                </div>
            </form>
        </div>
    </div>

    <p class="mt-3 text-center">
        Already have an account? <a href="login.php" class="fw-bold">Login here</a>
    </p>
</main>

<?php include "../includes/footer.php"; ?>

<!-- Scripts -->
<script>
  const passwordInput = document.getElementById('password');
  const confirmInput = document.getElementById('confirm_password');
  const strengthBar = document.getElementById('password-strength');
  const matchMessage = document.getElementById('matchMessage');
  const togglePassword = document.getElementById('togglePassword');
  const toggleConfirm = document.getElementById('toggleConfirm');

  // Initialize Bootstrap popover
  const popover = new bootstrap.Popover(passwordInput, {
    html: true,
    content: `
      <ul class="mb-0">
        <li id="req-length">❌ At least 8 characters</li>
        <li id="req-upper">❌ Uppercase letter</li>
        <li id="req-lower">❌ Lowercase letter</li>
        <li id="req-number">❌ Number</li>
        <li id="req-symbol">❌ Symbol</li>
      </ul>
    `
  });

  // Password strength + requirement check
  passwordInput.addEventListener('input', () => {
    const val = passwordInput.value;
    let strength = 0;

    document.getElementById('req-length').textContent = val.length >= 8 ? "✅ At least 8 characters" : "❌ At least 8 characters";
    document.getElementById('req-upper').textContent = /[A-Z]/.test(val) ? "✅ Uppercase letter" : "❌ Uppercase letter";
    document.getElementById('req-lower').textContent = /[a-z]/.test(val) ? "✅ Lowercase letter" : "❌ Lowercase letter";
    document.getElementById('req-number').textContent = /[0-9]/.test(val) ? "✅ Number" : "❌ Number";
    document.getElementById('req-symbol').textContent = /[^A-Za-z0-9]/.test(val) ? "✅ Symbol" : "❌ Symbol";

    if (val.length >= 8) strength++;
    if (/[A-Z]/.test(val)) strength++;
    if (/[a-z]/.test(val)) strength++;
    if (/[0-9]/.test(val)) strength++;
    if (/[^A-Za-z0-9]/.test(val)) strength++;

    strengthBar.style.width = (strength * 20) + "%";
    strengthBar.className = "progress-bar";
    if (strength <= 2) strengthBar.classList.add("bg-danger");
    else if (strength <= 4) strengthBar.classList.add("bg-warning");
    else strengthBar.classList.add("bg-success");
  });

  // Confirm password live check
  function checkMatch() {
    if (confirmInput.value === "") {
      matchMessage.textContent = "";
      return;
    }
    if (confirmInput.value === passwordInput.value) {
      matchMessage.textContent = "✅ Passwords match";
      matchMessage.style.color = "green";
    } else {
      matchMessage.textContent = "❌ Passwords do not match";
      matchMessage.style.color = "red";
    }
  }
  passwordInput.addEventListener('input', checkMatch);
  confirmInput.addEventListener('input', checkMatch);

  // Show/hide toggles
  togglePassword.addEventListener('click', () => {
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    togglePassword.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
  });

  toggleConfirm.addEventListener('click', () => {
    const type = confirmInput.getAttribute('type') === 'password' ? 'text' : 'password';
    confirmInput.setAttribute('type', type);
    toggleConfirm.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
  });
</script>