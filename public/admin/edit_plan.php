<?php
require_once "../../config/init.php";
// Staff only: superadmin, manager (and legacy admin).
requireLogin(STAFF_ROLES);

$id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ?");
$stmt->execute([$id]);
$plan = $stmt->fetch();

if (!$plan) {
    flashError("That plan doesn't exist.", 'toast');
    header("Location: subscriptions.php");
    exit;
}

include "../../includes/header.php";
nu_band('award', 'Billing', 'Edit Subscription Plan', 'Changes apply to new subscriptions.');
?>

<main class="wrap-sm page-body">
  <div class="card card-pad" data-reveal>
    <form action="../../actions/edit_plan.php" method="POST">
      <input type="hidden" name="id" value="<?= (int) $plan['id'] ?>">
      <div class="form-grid">
        <label class="field span-2" for="name"><span class="label">Plan Name</span>
          <input type="text" class="input" id="name" name="name" value="<?= htmlspecialchars($plan['name']) ?>" required></label>
        <label class="field" for="price"><span class="label">Price (USD)</span>
          <input type="number" step="0.01" class="input" id="price" name="price" value="<?= htmlspecialchars($plan['price']) ?>" required></label>
        <label class="field" for="duration"><span class="label">Duration (days)</span>
          <input type="number" class="input" id="duration" name="duration" value="<?= (int) $plan['duration'] ?>" required></label>
      </div>
      <div class="row-flex mt-6">
        <button type="submit" class="btn btn-primary"><?= nu_icon('check2') ?> Update Plan</button>
        <a class="btn btn-ghost" href="subscriptions.php">Cancel</a>
      </div>
    </form>
  </div>
</main>

<?php include "../../includes/footer.php"; ?>
