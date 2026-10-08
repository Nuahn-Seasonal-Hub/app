<?php
require_once "../../config/init.php";
// Staff only: superadmin, manager (and legacy admin).
requireLogin(STAFF_ROLES);

// Fetch users
$users = $pdo->query("SELECT id, name, email FROM users ORDER BY name")->fetchAll();

// Fetch plans
$plans = $pdo->query("SELECT id, name, price, duration FROM plans ORDER BY name")->fetchAll();

include "../../includes/header.php";
nu_band('person-badge', 'Billing', 'Assign Subscription to User', 'The subscription starts today and runs for the plan duration.');
?>

<main class="wrap-sm page-body">
<?php if (empty($plans)): ?>
  <?php nu_empty('award', 'Create a plan first.', 'There are no plans to assign yet.', '<a class="btn btn-primary" href="create_plan.php">' . nu_icon('plus-lg') . ' Create plan</a>'); ?>
<?php else: ?>
  <div class="card card-pad" data-reveal>
    <form action="../../actions/assign_subscription.php" method="POST">
      <div class="form-grid">
        <label class="field span-2" for="user_id"><span class="label">Select User</span>
          <select class="input" id="user_id" name="user_id" required>
            <?php foreach ($users as $user): ?>
              <option value="<?= (int) $user['id'] ?>"><?= htmlspecialchars($user['name'] . " (" . $user['email'] . ")") ?></option>
            <?php endforeach; ?>
          </select></label>
        <label class="field span-2" for="plan_id"><span class="label">Select Plan</span>
          <select class="input" id="plan_id" name="plan_id" required>
            <?php foreach ($plans as $plan): ?>
              <option value="<?= (int) $plan['id'] ?>"><?= htmlspecialchars($plan['name'] . " - $" . $plan['price'] . " / " . $plan['duration'] . " days") ?></option>
            <?php endforeach; ?>
          </select></label>
      </div>
      <div class="row-flex mt-6">
        <button type="submit" class="btn btn-primary"><?= nu_icon('check2') ?> Assign Subscription</button>
        <a class="btn btn-ghost" href="subscriptions.php">Cancel</a>
      </div>
    </form>
  </div>
<?php endif; ?>
</main>

<?php include "../../includes/footer.php"; ?>
