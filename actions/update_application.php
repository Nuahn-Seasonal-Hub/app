<?php
// actions/update_application.php
require_once("../config/init.php");
requireLogin();

$allowedRoles = ['manager','admin','superadmin'];
if (!in_array($_SESSION['role'], $allowedRoles)) {
    flashError("Unauthorized action.");
    header("Location: ../public/dashboard.php");
    exit;
}

$application_id = intval($_POST['application_id']);
$status = null;

if (isset($_POST['approve'])) {
    $status = 'approved';
} elseif (isset($_POST['reject'])) {
    $status = 'rejected';
}

if ($status) {
    // Update application status
    $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
    $stmt->execute([$status, $application_id]);

    // Fetch job + provider + client info
    $fetch = $pdo->prepare("
        SELECT applications.job_id, applications.provider_id, jobs.title, jobs.client_id,
               providers.email AS provider_email, clients.email AS client_email
        FROM applications
        JOIN jobs ON applications.job_id = jobs.id
        JOIN users AS providers ON applications.provider_id = providers.id
        JOIN users AS clients ON jobs.client_id = clients.id
        WHERE applications.id = ?
    ");
    $fetch->execute([$application_id]);
    $app = $fetch->fetch(PDO::FETCH_ASSOC);

    // Audit log entry
    $log = $pdo->prepare("INSERT INTO audit_logs (action, target_user_id, job_id, created_at, performed_by, role) 
                          VALUES (?, ?, ?, NOW(), ?, ?)");
    $log->execute([
        "Application $status",
        $app['provider_id'],
        $app['job_id'],
        $_SESSION['user_id'],
        $_SESSION['role']
    ]);

    // Notify provider
    $providerSubject = "Your application for '{$app['title']}' has been $status";
    $providerBody = "Hello,\n\nYour application for the job '{$app['title']}' has been $status by {$_SESSION['role']}.\n\nRegards,\nNuahn Team";
    mail($app['provider_email'], $providerSubject, $providerBody);

    // Notify client
    $clientSubject = "Application for your job '{$app['title']}' has been $status";
    $clientBody = "Hello,\n\nAn application for your job '{$app['title']}' has been $status by {$_SESSION['role']}.\n\nRegards,\nNuahn Team";
    mail($app['client_email'], $clientSubject, $clientBody);

    flashSuccess("Application $status successfully and notifications sent.");
}

header("Location: ../public/manage_applications.php");
exit;
