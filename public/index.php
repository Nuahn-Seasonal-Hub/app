<?php
// public/index.php - Entry point for Nuahn Seasonal Hub
//session_start();
//include_once("../config/db.php");
// include_once("../includes/header.php");

require_once("../config/init.php");
include_once("../includes/header.php");
?>

<main class="container-fluid p-0">
    <!-- Hero Section -->
    <section class="text-white text-center d-flex flex-column justify-content-center align-items-center" 
             style="height: 70vh; background: linear-gradient(135deg, #007bff, #6610f2);">
        <h1 class="display-3 fw-bold">Nuahn Seasonal Hub</h1>
        <p class="lead">Your trusted platform for seasonal jobs</p>
        <div class="mt-4">
            <a href="jobs.php" class="btn btn-light btn-lg me-3">Find Seasonal Jobs</a>
            <a href="login.php" class="btn btn-outline-light btn-lg">Login / Register</a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="container py-5">
        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <div class="card shadow h-100">
                    <div class="card-body">
                        <i class="bi bi-briefcase-fill display-4 text-primary"></i>
                        <h4 class="mt-3">For Clients</h4>
                        <p>Post seasonal jobs with clear details, fair compensation, and secure payments.</p>
                        <a href="jobs.php" class="btn btn-primary">Post a Job</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card shadow h-100">
                    <div class="card-body">
                        <i class="bi bi-person-workspace display-4 text-success"></i>
                        <h4 class="mt-3">For Providers</h4>
                        <p>Browse and accept seasonal jobs that match your skills and availability.</p>
                        <a href="jobs.php" class="btn btn-success">Find Work</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card shadow h-100">
                    <div class="card-body">
                        <i class="bi bi-shield-lock-fill display-4 text-danger"></i>
                        <h4 class="mt-3">Compliance Ready</h4>
                        <p>Secure transactions, audit logs, and dispute resolution built in for trust and transparency.</p>
                        <a href="dashboard.php" class="btn btn-danger">View Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="bg-light text-center py-5">
        <h2 class="fw-bold">Ready to get started?</h2>
        <p class="lead">Join Nuahn Seasonal Hub today and make seasonal work simple, secure, and rewarding.</p>
        <a href="register.php" class="btn btn-primary btn-lg">Sign Up Now</a>
    </section>
</main>

<?php include_once("../includes/footer.php"); ?>