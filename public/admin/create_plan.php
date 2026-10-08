<?php
require_once "../../config/init.php";
// Staff only: superadmin, manager (and legacy admin).
requireLogin(STAFF_ROLES);

include "../../includes/header.php";
nu_band('award', 'Billing', 'Create Subscription Plan', 'Name the plan, set a price and how long it lasts.');
?>

<main class="wrap-sm page-body">
  <div class="card card-pad" data-reveal>
    <form action="../../actions/create_plan.php" method="POST">
      <div class="form-grid">
        <label class="field span-2" for="name"><span class="label">Plan Name</span>
          <input type="text" class="input" id="name" name="name" placeholder="e.g. Seasonal Pro" required></label>
        <label class="field" for="price"><span class="label">Price (USD)</span>
          <input type="number" step="0.01" class="input" id="price" name="price" required></label>
        <label class="field" for="duration"><span class="label">Duration (days)</span>
          <input type="number" class="input" id="duration" name="duration" required></label>
      </div>
      <div class="row-flex mt-6">
        <button type="submit" class="btn btn-primary"><?= nu_icon('plus-lg') ?> Create Plan</button>
        <a class="btn btn-ghost" href="subscriptions.php">Cancel</a>
      </div>
    </form>
  </div>
</main>

<?php include "../../includes/footer.php"; ?>
