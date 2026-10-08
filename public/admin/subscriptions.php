<?php
require_once "../../config/init.php";
// Staff only: superadmin, manager (and legacy admin).
requireLogin(STAFF_ROLES);

// subscriptions columns: client_id, plan (plan name), start_date, end_date, status.
$subs = $pdo->query("SELECT s.id, u.name, s.plan AS plan_name, s.start_date, s.end_date AS expiry_date, s.status
                     FROM subscriptions s
                     JOIN users u ON s.client_id = u.id
                     ORDER BY s.start_date DESC")->fetchAll();

$plans = $pdo->query("SELECT id, name, price, duration FROM plans ORDER BY price")->fetchAll();

include "../../includes/header.php";

nu_band('award', 'Billing', 'Subscription management', 'Plans on offer and who is subscribed to what.',
    '<a class="btn btn-glass" href="assign_subscription.php">' . nu_icon('person-badge') . ' Assign plan</a><a href="create_plan.php" class="btn btn-light">' . nu_icon('plus-lg') . ' Create New Plan</a>');
?>

<main class="wrap page-body">
  <div class="section-title mt-0"><h2>Plans</h2></div>
<?php if (empty($plans)): ?>
  <?php nu_empty('award', 'No plans yet.', 'Create a plan to start offering subscriptions.', '<a class="btn btn-primary" href="create_plan.php">' . nu_icon('plus-lg') . ' Create plan</a>'); ?>
<?php else: ?>
  <div class="grid grid-3">
    <?php foreach ($plans as $i => $plan): ?>
      <div class="card card-pad" data-reveal style="--i:<?= (int) $i ?>">
        <div class="card-head"><span class="stat__icon"><?= nu_icon('award') ?></span><div><h3 class="mb-0"><?= htmlspecialchars($plan['name']) ?></h3><p class="muted mb-0"><?= (int) $plan['duration'] ?> days</p></div></div>
        <div class="row-flex" style="justify-content:space-between">
          <strong style="font-size:1.4rem"><?= nu_money($plan['price']) ?></strong>
          <a class="btn btn-soft btn-sm" href="edit_plan.php?id=<?= (int) $plan['id'] ?>"><?= nu_icon('pencil-square') ?> Edit</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

  <div class="section-title"><h2>Subscriptions</h2></div>
<?php if (empty($subs)): ?>
  <?php nu_empty('inbox', 'No subscriptions yet.', 'Assign a plan to a client and it will appear here.'); ?>
<?php else: ?>
  <div class="table-wrap" data-reveal>
    <table class="table table--stack">
      <thead><tr><th>User</th><th>Plan</th><th>Start Date</th><th>Expiry Date</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($subs as $row): ?>
        <tr>
          <td data-label="User"><strong><?= htmlspecialchars($row['name']) ?></strong></td>
          <td data-label="Plan"><?= htmlspecialchars((string) $row['plan_name']) ?></td>
          <td data-label="Start"><?= nu_e(nu_date($row['start_date'])) ?></td>
          <td data-label="Expiry"><?= nu_e(nu_date($row['expiry_date'])) ?></td>
          <td data-label="Status"><?= nu_status_chip($row['status']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
</main>

<?php include "../../includes/footer.php"; ?>
