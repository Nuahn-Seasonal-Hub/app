<?php
// public/client_home.php
require_once("../config/init.php"); // handles session, db, auth, flash helpers

// Only clients can view this page
requireLogin('client');

include "../includes/header.php";
?>

<main class="container py-5">
    <h2 class="mb-4 text-center">
        Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Client'); ?>!
    </h2>

    <!-- Quick Alerts -->
    <?php if(isset($_GET['success']) && $_GET['success'] === 'profile_updated'): ?>
        <div class="alert alert-success">Your profile has been updated successfully.</div>
    <?php endif; ?>

    <!-- Dashboard Cards -->
    <div class="row g-4">
        <!-- Seasonal Jobs -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Browse Seasonal Jobs</h5>
                    <p class="card-text">Find opportunities tailored to your skills and availability.</p>
                    <a href="jobs.php" class="btn btn-primary">View Jobs</a>
                </div>
            </div>
        </div>

        <!-- My Applications -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">My Applications</h5>
                    <p class="card-text">Track the status of jobs you’ve applied for.</p>
                    <a href="client_applications.php" class="btn btn-primary">View Applications</a>
                </div>
            </div>
        </div>

        <!-- Profile Settings -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Profile Settings</h5>
                    <p class="card-text">Update your personal details and preferences.</p>
                    <a href="profile.php" class="btn btn-secondary">Edit Profile</a>
                </div>
            </div>
        </div>

        <!-- Support -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Support</h5>
                    <p class="card-text">Need help? Contact our support team for assistance.</p>
                    <a href="support.php" class="btn btn-secondary">Get Support</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include "../includes/footer.php"; ?>