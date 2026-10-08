<?php
// actions/save_job.php
require_once("../config/init.php");
requireLogin('provider');

$provider_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_id'])) {
    $job_id = (int)$_POST['job_id'];

    // Insert or ignore if already saved
    $stmt = $pdo->prepare("INSERT IGNORE INTO saved_jobs (provider_id, job_id) VALUES (?, ?)");
    $stmt->execute([$provider_id, $job_id]);

    // Audit log
    $log = $pdo->prepare("INSERT INTO audit_logs (action, target_user_id, job_id, created_at) VALUES (?, ?, ?, NOW())");
    $log->execute(["Job saved", $provider_id, $job_id]);

    flashSuccess("Job saved successfully.");
    header("Location: ../public/jobs.php");
    exit;
} else {
    flashError("Invalid request.");
    header("Location: ../public/jobs.php");
    exit;
}
