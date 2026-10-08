<?php
// public/login.php
require_once("../config/init.php");
include_once("../includes/header.php");

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    redirectByRole($_SESSION['role']);
    exit;
}
?>

<main class="container py-5" style="max-width: 500px;">
    <h2 class="mb-4 text-center">Login</h2>

    <!-- Flash Messages -->
    <?php if (isset($_GET['success'])): ?>
        <?php if ($_GET['success'] === 'registered'): ?>
            <div class="alert alert-success text-center">Registration successful! Please log in.</div>
        <?php elseif ($_GET['success'] === 'loggedout'): ?>
            <div class="alert alert-info text-center">You’ve been logged out successfully.</div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <?php if ($_GET['error'] === 'invalid'): ?>
            <div class="alert alert-danger text-center">Invalid email or password.</div>
        <?php elseif ($_GET['error'] === 'unauthorized'): ?>
            <div class="alert alert-warning text-center">You must log in to access that page.</div>
        <?php elseif ($_GET['error'] === 'suspended'): ?>
            <div class="alert alert-danger text-center">Your account has been suspended. Contact support.</div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Login Form -->
    <div class="card shadow">
        <div class="card-body">
            <form method="POST" action="../actions/login_action.php">
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" name="email" id="email" class="form-control" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>

                <!-- Optional: Role dropdown if you want to enforce role at login -->
                <div class="mb-3">
                    <label for="role" class="form-label">Login as</label>
                    <select name="role" id="role" class="form-select" required>
                        <option value="">Select role...</option>
                        <option value="client">Client</option>
                        <option value="provider">Provider</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Admin</option>
                        <option value="superadmin">Superadmin</option>
                    </select>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>
        </div>
    </div>

    <p class="mt-3 text-center">
        Don’t have an account? <a href="auth.php">Register here</a>
    </p>
</main>

<?php include_once("../includes/footer.php"); ?>
