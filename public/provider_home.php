<?php
// public/provider_home.php
require_once("../config/init.php");
include_once("../includes/header.php");

// Only providers can view this page
requireLogin('provider');
?>

<main class="container py-5">
    <h2 class="mb-4 text-center">
        Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Provider'); ?>!
    </h2>

    <?php include_once("../includes/flash.php"); ?>

    <!-- Dashboard Cards -->
    <div class="row g-4">
        <!-- Browse Jobs -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Browse Seasonal Jobs</h5>
                    <p class="card-text">Find new job listings and apply to opportunities that match your skills.</p>
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
                    <a href="applications.php" class="btn btn-primary">View Applications</a>
                </div>
            </div>
        </div>

        <!-- Saved Jobs -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Saved Jobs</h5>
                    <p class="card-text">Review jobs you’ve bookmarked for later.</p>
                    <a href="saved_jobs.php" class="btn btn-secondary">View Saved Jobs</a>
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
    </div>
</main>

<?php include "../includes/footer.php"; ?>
