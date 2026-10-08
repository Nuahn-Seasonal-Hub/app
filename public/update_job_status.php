<?php
// public/update_job_status.php
//session_start();
//require_once("../config/db.php");
//require_once("../includes/auth.php");

require_once("../config/init.php");
include_once("../includes/header.php");
requireLogin('client');
$client_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_id'], $_POST['status'])) {
    $job_id = intval($_POST['job_id']);
    $status = $_POST['status'];

    // Only allow valid statuses
    $validStatuses = ['posted', 'filled', 'closed'];
    if (!in_array($status, $validStatuses)) {
        header("Location: my_jobs.php?error=invalid_status");
        exit;
    }

    // Update job status
    $stmt = $pdo->prepare("UPDATE jobs SET status = ? WHERE id = ? AND client_id = ?");
    $stmt->execute([$status, $job_id, $client_id]);

    // Audit log
    $log = $pdo->prepare("INSERT INTO audit_logs (action, target_user_id, job_id, created_at) 
                          VALUES (?, ?, ?, NOW())");
    $log->execute(["Job status updated to $status", $client_id, $job_id]);

    header("Location: my_jobs.php?success=status_updated");
    exit;
} else {
    header("Location: my_jobs.php?error=invalid_request");
    exit;
}