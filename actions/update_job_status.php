<?php
// actions/update_job_status.php
require_once("../config/init.php");
requireLogin('client');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_id'], $_POST['status'])) {
    $job_id = (int)$_POST['job_id'];
    $status = $_POST['status'];

    // Only allow valid statuses
    $validStatuses = ['posted', 'filled', 'closed'];
    if (!in_array($status, $validStatuses)) {
        flashError("Invalid status selected.");
        header("Location: ../public/my_jobs.php?error=invalid_status");
        exit;
    }

    // Update job status
    $stmt = $pdo->prepare("UPDATE jobs SET status = ? WHERE id = ? AND client_id = ?");
    $stmt->execute([$status, $job_id, $_SESSION['user_id']]);

    // Audit log
    $log = $pdo->prepare("INSERT INTO audit_logs (action, target_user_id, job_id, created_at) VALUES (?, ?, ?, NOW())");
    $log->execute(["Job status updated to $status", $_SESSION['user_id'], $job_id]);

    flashSuccess("Job status updated to $status.");
    header("Location: ../public/my_jobs.php?success=status_updated");
    exit;
}
?>