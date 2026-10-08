<?php
// actions/remove_saved_job.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once("../config/db.php");
require_once("../includes/auth.php");

requireLogin('provider');
$provider_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_id'])) {
    $job_id = intval($_POST['job_id']);

    // Delete saved job
    $stmt = $pdo->prepare("DELETE FROM saved_jobs WHERE provider_id = ? AND job_id = ?");
    $stmt->execute([$provider_id, $job_id]);

    // Audit log
    $log = $pdo->prepare("INSERT INTO audit_logs (action, target_user_id, job_id, created_at) 
                          VALUES (?, ?, ?, NOW())");
    $log->execute(["Saved job removed", $provider_id, $job_id]);

    header("Location: ../public/saved_jobs.php?success=job_removed");
    exit;
} else {
    header("Location: ../public/saved_jobs.php?error=invalid_request");
    exit;
}