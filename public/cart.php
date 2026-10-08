<?php
include "../includes/header.php";
require_once "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT c.*, p.name, p.price 
                       FROM cart c 
                       JOIN products p ON c.product_id = p.id 
                       WHERE c.user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();
?>

<h2 class="mb-4">Your Cart</h2>
<div class="table-responsive">
  <table class="table table-striped">
    <thead>
      <tr>
        <th>Product</th>
        <th>Quantity</th>
        <th>Price</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($cart_items as $item): ?>
        <tr>
          <td><?php echo htmlspecialchars($item['name']); ?></td>
          <td><?php echo $item['quantity']; ?></td>
          <td>$<?php echo number_format($item['price'], 2); ?></td>
          <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<a href="checkout.php" class="btn btn-success w-100">Checkout</a>

<?php include "../includes/footer.php"; ?>
