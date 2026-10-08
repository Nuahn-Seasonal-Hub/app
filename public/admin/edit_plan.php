<?php
require_once "../../config/db.php";
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit;
}

$id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ?");
$stmt->execute([$id]);
$plan = $stmt->fetch();

include "../../includes/header.php";
?>

<div class="container mt-4">
  <h2 class="mb-4">Edit Subscription Plan</h2>
  <form action="../../actions/edit_plan.php" method="POST">
    <input type="hidden" name="id" value="<?php echo $plan['id']; ?>">
    <div class="mb-3">
      <label for="name" class="form-label">Plan Name</label>
      <input type="text" class="form-control" id="name" name="name" value="<?php echo $plan['name']; ?>" required>
    </div>
    <div class="mb-3">
      <label for="price" class="form-label">Price (USD)</label>
      <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo $plan['price']; ?>" required>
    </div>
    <div class="mb-3">
      <label for="duration" class="form-label">Duration (days)</label>
      <input type="number" class="form-control" id="duration" name="duration" value="<?php echo $plan['duration']; ?>" required>
    </div>
    <button type="submit" class="btn btn-primary">Update Plan</button>
  </form>
</div>

<?php include "../../includes/footer.php"; ?>
