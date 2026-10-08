<?php
// public/client_applications.php - Applications for Client's Jobs
require_once("../config/init.php");
include_once("../includes/header.php");

// Only clients can view this page
requireLogin('client');

$client_id = $_SESSION['user_id'];

// Fetch applications for jobs posted by this client
$stmt = $pdo->prepare("
    SELECT applications.*, jobs.title, jobs.description, users.name AS provider_name
    FROM applications
    JOIN jobs ON applications.job_id = jobs.id
    JOIN users ON applications.provider_id = users.id
    WHERE jobs.client_id = ?
    ORDER BY applications.created_at DESC
");
$stmt->execute([$client_id]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container py-5">
    <h2 class="text-center mb-4">Applications for My Jobs</h2>

    <?php include_once("../includes/flash.php"); ?>

    <?php if (empty($applications)): ?>
        <div class="alert alert-info">No providers have applied to your jobs yet.</div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($applications as $app): ?>
            <div class="col-md-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($app['title']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars($app['description']) ?></p>
                        <p><strong>Provider:</strong> <?= htmlspecialchars($app['provider_name']) ?></p>
                        <p><strong>Status:</strong> <?= htmlspecialchars($app['status']) ?></p>
                        <p><strong>Applied on:</strong> <?= htmlspecialchars($app['created_at']) ?></p>

                        <!-- Action buttons for client -->
                        <form method="POST" action="../actions/update_application.php" class="d-inline">
                            <input type="hidden" name="application_id" value="<?= htmlspecialchars($app['id']) ?>">
                            <button type="submit" name="approve" class="btn btn-success btn-sm">Approve</button>
                            <button type="submit" name="reject" class="btn btn-danger btn-sm">Reject</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include_once("../includes/footer.php"); ?>