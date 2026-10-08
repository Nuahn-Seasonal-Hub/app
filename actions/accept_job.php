<?php
// actions/accept_job.php
//session_start();

require_once("../config/init.php");
include_once("../includes/header.php");
// Only providers can apply
requireLogin('provider');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_id'])) {
    $job_id = intval($_POST['job_id']);
    $provider_id = $_SESSION['user_id'];

    // Check if job exists and is still posted
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND status = 'posted'");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        header("Location: ../public/jobs.php?error=job_not_found");
        exit;
    }

    // Insert application
    $apply = $pdo->prepare("INSERT INTO applications (job_id, provider_id, status, created_at) 
                            VALUES (?, ?, 'pending', NOW())");
    $apply->execute([$job_id, $provider_id]);

    // Audit log
    $log = $pdo->prepare("INSERT INTO audit_logs (action, target_user_id, job_id, created_at) 
                          VALUES (?, ?, ?, NOW())");
    $log->execute(["Job applied", $provider_id, $job_id]);

    header("Location: ../public/jobs.php?success=applied");
    exit;
} else {
    header("Location: ../public/jobs.php?error=invalid_request");
    exit;
}