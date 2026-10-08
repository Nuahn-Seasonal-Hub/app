<?php
// includes/flash.php

if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
    switch ($error) {
        case 'unauthorized':
            echo '<div class="alert alert-danger text-center">You must be logged in with the correct role to access that page.</div>';
            break;
        case 'invalid':
            echo '<div class="alert alert-danger text-center">Invalid email or password. Please try again.</div>';
            break;
        default:
            echo '<div class="alert alert-danger text-center">An error occurred.</div>';
    }
}

if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
    switch ($success) {
        case 'logged_out':
            echo '<div class="alert alert-success text-center">You have been logged out successfully.</div>';
            break;
        case 'job_saved':
            echo '<div class="alert alert-success text-center">Job saved to your list.</div>';
            break;
        case 'job_removed':
            echo '<div class="alert alert-success text-center">Job removed from your saved list.</div>';
            break;
        default:
            echo '<div class="alert alert-success text-center">Action completed successfully.</div>';
    }
}
?>