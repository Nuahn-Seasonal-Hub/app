<?php
// index.php - Entry point for Nuahn Seasonal Hub

require_once("config/init.php");
include_once("includes/header.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Nuahn Seasonal Hub</title>
    <link rel="stylesheet" href="public/css/bootstrap.min.css">
</head>
<body>
    <!-- Header -->
    <header class="bg-primary text-white text-center p-4">
        <h1>Nuahn Seasonal Hub</h1>
        <p>Your trusted seasonal job coordination platform</p>
    </header>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="index.php">Home</a>
        <a class="nav-link" href="public/jobs.php">Seasonal Jobs</a>
        <a class="nav-link" href="public/auth.php">Login</a>
        <a class="nav-link" href="public/dashboard.php">Dashboard</a>
    </nav>

    <!-- Main Content -->
    <main class="container mt-4">
        <h2>Welcome</h2>
        <p>Post or accept seasonal jobs with secure payments and compliance built in.</p>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">For Clients</div>
                    <div class="card-body">
                        <p>Need help with seasonal tasks? Post your job here.</p>
                        <a href="jobs.php" class="btn btn-primary">Post a Job</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">For Providers</div>
                    <div class="card-body">
                        <p>Looking for seasonal work? Browse available jobs.</p>
                        <a href="jobs.php" class="btn btn-success">Find Jobs</a>
                    </div>
                </div>
            </div>
        </div>
    <h2>Welcome to Nuahn Seasonal Hub</h2>
    <p>
        Seasonal jobs are short-term opportunities that help businesses and households 
        meet peak demand during holidays, harvests, events, and busy periods. They also 
        give providers flexible work options to earn extra income when it matters most.
    </p>

    <h3 class="mt-4">Why Choose Our Services?</h3>
    <ul>
        <li><strong>Trusted Platform:</strong> We connect clients and providers securely with verified profiles.</li>
        <li><strong>Fair Payments:</strong> Built-in payment tracking ensures transparency and accountability.</li>
        <li><strong>Location-Based Matching:</strong> Integrated maps help you find jobs near you quickly.</li>
        <li><strong>Compliance & Safety:</strong> Every job is logged and monitored for fairness and reliability.</li>
        <li><strong>Flexibility:</strong> Providers can choose jobs that fit their schedule and skills.</li>
    </ul>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">For Clients</div>
                <div class="card-body">
                    <p>Need help with seasonal tasks? Post your job here and reach trusted providers.</p>
                    <a href="public/jobs.php" class="btn btn-primary">Post a Job</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">For Providers</div>
                <div class="card-body">
                    <p>Looking for seasonal work? Browse available jobs and apply instantly.</p>
                    <a href="public/jobs.php" class="btn btn-success">Find Jobs</a>
                </div>
            </div>
        </div>
    </div>
</main>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center p-3 mt-4">
        <small>&copy; <?php echo date("Y"); ?> PTNT Solutions</small>
    </footer>
</body>
</html>