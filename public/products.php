<?php
include "../includes/header.php";
require_once "../config/db.php";

$stmt = $pdo->query("SELECT p.*, c.name AS category_name 
                     FROM products p 
                     JOIN categories c ON p.category_id = c.id 
                     ORDER BY p.created_at DESC");
$products = $stmt->fetchAll();
?>

<h2 class="mb-4">Product Catalog</h2>
<div class="row">
  <?php foreach ($products as $product): ?>
    <div class="col-md-4 col-sm-6 mb-4">
      <div class="card h-100">
        <?php if($product['photo']): ?>
          <img src="../uploads/<?php echo htmlspecialchars($product['photo']); ?>" 
               class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
        <?php endif; ?>
        <div class="card-body">
          <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
          <p class="card-text text-muted"><?php echo htmlspecialchars($product['category_name']); ?></p>
          <p class="card-text"><?php echo htmlspecialchars($product['description']); ?></p>
          <p class="fw-bold">$<?php echo number_format($product['price'], 2); ?></p>
          <a href="add_to_cart.php?id=<?php echo $product['id']; ?>" 
             class="btn btn-primary w-100">Add to Cart</a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php include "../includes/footer.php"; ?>
