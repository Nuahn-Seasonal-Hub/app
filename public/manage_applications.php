<?php
// public/manage_applications.php
require_once("../config/init.php");
include_once("../includes/header.php");
requireLogin();

$allowedRoles = ['manager','admin','superadmin'];
if (!in_array($_SESSION['role'], $allowedRoles)) {
    flashError("Unauthorized access.");
    header("Location: dashboard.php");
    exit;
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Build query depending on role
if ($role === 'superadmin') {
    // Superadmin sees all applications
    $stmt = $pdo->query("
        SELECT applications.*, jobs.title AS job_title, jobs.payment_amount, jobs.image,
               jobs.location_lat, jobs.location_lng,
               providers.name AS provider_name, clients.name AS client_name, clients.department_id
        FROM applications
        JOIN jobs ON applications.job_id = jobs.id
        JOIN users AS providers ON applications.provider_id = providers.id
        JOIN users AS clients ON jobs.client_id = clients.id
        ORDER BY applications.created_at DESC
    ");
} else {
    // Manager/Admin: only see applications for jobs in their department
    // Assumes your users table has department_id
    $stmt = $pdo->prepare("
        SELECT applications.*, jobs.title AS job_title, jobs.payment_amount, jobs.image,
               jobs.location_lat, jobs.location_lng,
               providers.name AS provider_name, clients.name AS client_name, clients.department_id
        FROM applications
        JOIN jobs ON applications.job_id = jobs.id
        JOIN users AS providers ON applications.provider_id = providers.id
        JOIN users AS clients ON jobs.client_id = clients.id
        WHERE clients.department_id = (
            SELECT department_id FROM users WHERE id = ?
        )
        ORDER BY applications.created_at DESC
    ");
    $stmt->execute([$user_id]);
}
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container py-5">
    <h2 class="mb-4 text-center">All Applications (<?= ucfirst($role) ?>)</h2>

    <?php include_once("../includes/flash.php"); ?>

    <?php if (empty($applications)): ?>
        <div class="alert alert-info">No applications found.</div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($applications as $app): ?>
            <div class="col-md-6 mb-4">
                <div class="card shadow h-100">
                    <?php if (!empty($app['image'])): ?>
                        <img src="../uploads/jobs/<?= htmlspecialchars($app['image']) ?>" 
                             class="card-img-top mb-2" alt="Job Image"
                             style="max-height:150px;object-fit:cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <p><strong>Job:</strong> <?= htmlspecialchars($app['job_title']) ?></p>
                        <p><strong>Client:</strong> <?= htmlspecialchars($app['client_name']) ?></p>
                        <p><strong>Provider:</strong> <?= htmlspecialchars($app['provider_name']) ?></p>
                        <p><strong>Payment:</strong> $<?= number_format($app['payment_amount'], 2) ?></p>
                        <p><strong>Status:</strong> 
                            <span class="badge 
                                <?= $app['status'] === 'approved' ? 'bg-success' : 
                                    ($app['status'] === 'rejected' ? 'bg-danger' : 'bg-warning') ?>">
                                <?= htmlspecialchars($app['status']) ?>
                            </span>
                        </p>
                        <p><strong>Applied on:</strong> <?= htmlspecialchars($app['created_at']) ?></p>

                        <!-- Map -->
                        <div id="map<?= $app['id'] ?>" style="height:140px;" class="mb-2"></div>
                        <script>
                          const map<?= $app['id'] ?> = initMap('map<?= $app['id'] ?>', {
                              lat: <?= $app['location_lat'] ?>,
                              lng: <?= $app['location_lng'] ?>,
                              zoom: 13
                          });
                          addMarker(map<?= $app['id'] ?>, <?= $app['location_lat'] ?>, <?= $app['location_lng'] ?>, "<?= htmlspecialchars($app['job_title']) ?>");
                        </script>

                        <!-- Approve / Reject -->
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
