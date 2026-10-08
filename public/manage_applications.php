<?php
// public/manage_applications.php
require_once("../config/init.php");
// Staff only: superadmin, manager (and legacy admin). Others are sent to their own home.
requireLogin(STAFF_ROLES);
include_once("../includes/header.php");

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// The schema has no departments (users.department_id does not exist), so every staff
// role sees all applications.
$stmt = $pdo->query("
    SELECT applications.*, jobs.title AS job_title, jobs.payment_amount, jobs.image,
           jobs.location_lat, jobs.location_lng,
           providers.name AS provider_name, clients.name AS client_name
    FROM applications
    JOIN jobs ON applications.job_id = jobs.id
    JOIN users AS providers ON applications.provider_id = providers.id
    JOIN users AS clients ON jobs.client_id = clients.id
    ORDER BY applications.created_at DESC
");
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="band">
  <div class="wrap band__row">
    <div>
      <span class="eyebrow"><?= nu_icon('shield-check') ?> <?= ucfirst($role) ?></span>
      <h1>All Applications</h1>
      <p>Review every provider application and approve or reject it.</p>
    </div>
    <span class="chip chip--navy chip--plain" style="background:rgba(255,255,255,.12)"><?= count($applications) ?> total</span>
  </div>
</section>

<main class="wrap page-body">
    <?php include_once("../includes/flash.php"); ?>

    <?php if (empty($applications)): ?>
        <?php nu_empty('inbox', 'No applications found.', 'Applications from providers will appear here.'); ?>
    <?php else: ?>
        <div class="table-wrap" data-reveal>
          <table class="table table--stack">
            <thead><tr><th>Job</th><th>Provider</th><th>Client</th><th>Payment</th><th>Status</th><th>Applied</th><th style="text-align:right">Decision</th></tr></thead>
            <tbody>
            <?php foreach ($applications as $app): ?>
              <tr>
                <td data-label="Job"><strong><?= htmlspecialchars($app['job_title']) ?></strong></td>
                <td data-label="Provider"><span class="row-flex" style="gap:10px;flex-wrap:nowrap"><span class="list-row__icon" style="width:34px;height:34px;border-radius:11px;font-size:.8rem"><?= nu_e(nu_initials($app['provider_name'])) ?></span><?= htmlspecialchars($app['provider_name']) ?></span></td>
                <td data-label="Client"><?= htmlspecialchars($app['client_name']) ?></td>
                <td data-label="Payment"><?= nu_money($app['payment_amount']) ?></td>
                <td data-label="Status"><?= nu_status_chip($app['status']) ?></td>
                <td class="muted" data-label="Applied"><?= nu_e(nu_date($app['created_at'])) ?></td>
                <td style="text-align:right" data-label="Decision">
                  <!-- Approve / Reject -->
                  <form method="POST" action="../actions/update_application.php" class="inline-form" style="gap:6px">
                      <input type="hidden" name="application_id" value="<?= htmlspecialchars($app['id']) ?>">
                      <button type="submit" name="approve" class="btn btn-success-soft btn-sm"><?= nu_icon('check2') ?> Approve</button>
                      <button type="submit" name="reject" class="btn btn-danger-soft btn-sm"><?= nu_icon('x-lg') ?> Reject</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
    <?php endif; ?>
</main>

<?php include_once("../includes/footer.php"); ?>
