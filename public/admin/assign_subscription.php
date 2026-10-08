<?php
require_once "../../config/db.php";
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit;
}

// Fetch users
$users = $pdo->query("SELECT id, name, email FROM users ORDER BY name")->fetchAll();

// Fetch plans
$plans = $pdo->query("SELECT id, name, price, duration FROM plans ORDER BY name")->fetchAll();

include "../../includes/header.php";
?>

<div class="container mt-4">
  <h2 class="mb-4">Assign Subscription to User</h2>
  <form action="../../actions/assign_subscription.php" method="POST">
    <div class="mb-3">
      <label for="user_id" class="form-label">Select User</label>
      <select class="form-select" id="user_id" name="user_id" required>
        <?php foreach ($users as $user): ?>
          <option value="<?php echo $user['id']; ?>">
            <?php echo $user['name'] . " (" . $user['email'] . ")"; ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label for="plan_id" class="form-label">Select Plan</label>
      <select class="form-select" id="plan_id" name="plan_id" required>
        <?php foreach ($plans as $plan): ?>
          <option value="<?php echo $plan['id']; ?>">
            <?php echo $plan['name'] . " - $" . $plan['price'] . " / " . $plan['duration'] . " days"; ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">Assign Subscription</button>
  </form>
</div>

<?php include "../../includes/footer.php"; ?>
