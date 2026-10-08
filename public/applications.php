<?php
// public/applications.php - Provider Applications Page
require_once("../config/init.php");
include_once("../includes/header.php");

// Only providers can view this page
requireLogin('provider');

$provider_id = $_SESSION['user_id'];

// Fetch applications for this provider
$stmt = $pdo->prepare("
    SELECT applications.*, jobs.title, jobs.description, users.name AS client_name
    FROM applications
    JOIN jobs ON applications.job_id = jobs.id
    JOIN users ON jobs.client_id = users.id
    WHERE applications.provider_id = ?
    ORDER BY applications.created_at DESC
");
$stmt->execute([$provider_id]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container py-5">
    <h2 class="text-center mb-4">My Applications</h2>

    <?php include_once("../includes/flash.php"); ?>

    <?php if (empty($applications)): ?>
        <div class="alert alert-info">You have not applied to any jobs yet.</div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($applications as $app): ?>
            <div class="col-md-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($app['title']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars($app['description']) ?></p>
                        <p><strong>Client:</strong> <?= htmlspecialchars($app['client_name']) ?></p>
                        <p><strong>Status:</strong> <?= htmlspecialchars($app['status']) ?></p>
                        <p><strong>Applied on:</strong> <?= htmlspecialchars($app['created_at']) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include_once("../includes/footer.php"); ?>