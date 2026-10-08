<?php
// actions/remove_saved_job.php
require_once("../config/init.php");
requireLogin('provider');

$provider_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_id'])) {
    $job_id = (int)$_POST['job_id'];

    // Delete saved job
    $stmt = $pdo->prepare("DELETE FROM saved_jobs WHERE provider_id = ? AND job_id = ?");
    $stmt->execute([$provider_id, $job_id]);

    // Audit log
    $log = $pdo->prepare("INSERT INTO audit_logs (action, target_user_id, job_id, created_at) VALUES (?, ?, ?, NOW())");
    $log->execute(["Job unsaved", $provider_id, $job_id]);

    flashSuccess("Job removed from saved list.");
    header("Location: ../public/saved_jobs.php");
    exit;
} else {
    flashError("Invalid request.");
    header("Location: ../public/saved_jobs.php");
    exit;
}
