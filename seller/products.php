<?php

session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'seller') {
    header("Location: ../homepage/html/login.html");
    exit();
}

$sellerId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'Seller';

$stmt = $pdo->prepare("SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC");
$stmt->execute([$sellerId]);
$products = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Listings | PustakMandi</title>

    <link rel="stylesheet" href="../homepage/css/style.css">
    <link rel="stylesheet" href="seller.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

<header>
    <nav class="navbar">
        <div class="logo">
            <img class="plogo" src="../homepage/images/pustakmandi.jpg" alt="PustakMandi Logo">
            <h2>PustakMandi</h2>
        </div>
        <ul class="nav-links">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="products.php">My Listings</a></li>
            <li><a href="orders.php">Orders</a></li>
            <li><a href="add_product.php">Add Product</a></li>
        </ul>
        <div class="icons">
            <a href="profile.php" class="profile" style="text-decoration:none; color:inherit;">
                <div class="profile-icon"><i class="fa-solid fa-store"></i></div>
                <div class="profile-info">
                    <strong><?php echo htmlspecialchars($fullName); ?></strong>
                    <small>Seller</small>
                </div>
            </a>
        </div>
    </nav>
</header>

<?php if ($flash): ?>
    <div style="max-width:1300px;margin:20px auto 0;padding:14px 8%;background:#dcfce7;color:#166534;border-radius:12px;">
        <?php echo htmlspecialchars($flash); ?>
    </div>
<?php endif; ?>

<section style="padding:50px 8%;">

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
        <div>
            <h2 style="color:var(--dark);">My Listings</h2>
            <p style="color:var(--gray);"><?php echo count($products); ?> product<?php echo count($products) === 1 ? '' : 's'; ?> listed</p>
        </div>
        <a href="add_product.php" class="btn">+ Add Product</a>
    </div>

    <?php if (empty($products)): ?>

        <div style="background:white; border-radius:16px; padding:60px; text-align:center; box-shadow:0 10px 30px rgba(0,0,0,.06);">
            <p style="color:var(--gray); margin-bottom:20px; font-size:18px;">You haven't listed anything yet.</p>
            <a href="add_product.php" class="btn">List Your First Product</a>
        </div>

    <?php else: ?>

        <table class="listing-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Listed</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td style="display:flex; align-items:center; gap:12px;">
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="">
                            <?php echo htmlspecialchars($product['title']); ?>
                        </td>
                        <td>
                            NPR <?php echo number_format($product['price'], 2); ?>
                            <?php if ($product['old_price']): ?>
                                <br><span style="color:var(--gray); text-decoration:line-through; font-size:12px;">
                                    NPR <?php echo number_format($product['old_price'], 2); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $product['stock']; ?></td>
                        <td>
                            <?php if ($product['stock'] > 0): ?>
                                <span class="badge badge-instock">In Stock</span>
                            <?php else: ?>
                                <span class="badge badge-outofstock">Out of Stock</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
                        <td class="table-actions">
                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn-edit">Edit</a>
                            <form action="product_actions.php" method="post" style="display:inline;"
                                  onsubmit="return confirm('Delete this listing? This cannot be undone.');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" class="btn-delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>

</section>

</body>

</html>