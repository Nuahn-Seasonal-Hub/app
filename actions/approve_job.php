<?php
// actions/approve_job.php — staff decision on a job waiting for approval.
// New jobs are created with status 'pending'; approve sets it to 'posted'.
require_once("../config/init.php");
requireLogin(STAFF_ROLES);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_id'])) {
    $job_id = intval($_POST['job_id']);

    $stmt = $pdo->prepare("UPDATE jobs SET status = 'posted' WHERE id = ? AND status = 'pending'");
    $stmt->execute([$job_id]);

    if ($stmt->rowCount() > 0) {
        $log = $pdo->prepare("INSERT INTO audit_logs (action, job_id, created_at, performed_by, role) VALUES (?, ?, NOW(), ?, ?)");
        $log->execute(["Job approved", $job_id, $_SESSION['user_id'], $_SESSION['role']]);
        flashSuccess("Job approved.", 'toast');
    } else {
        flashError("That job is no longer waiting for approval.", 'toast');
    }
}

header("Location: ../public/dashboard.php");
exit;
