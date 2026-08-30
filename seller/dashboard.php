<?php

session_start();
require_once '../config/db.php';

/* ==========================================
   SELLER ONLY
========================================== */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../homepage/html/login.html");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../login.php");
    exit();
}

$sellerId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'Seller';

/* ==========================================
   STATS
========================================== */

$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM products WHERE seller_id = ?");
$stmt->execute([$sellerId]);
$totalListings = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COALESCE(SUM(price * stock), 0) AS value FROM products WHERE seller_id = ?");
$stmt->execute([$sellerId]);
$stockValue = $stmt->fetch()['value'];

$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM products WHERE seller_id = ? AND stock = 0");
$stmt->execute([$sellerId]);
$outOfStock = $stmt->fetch()['total'];

$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(oi.quantity), 0) AS total
     FROM order_items oi
     JOIN products p ON p.id = oi.product_id
     WHERE p.seller_id = ?"
);
$stmt->execute([$sellerId]);
$unitsSold = $stmt->fetch()['total'];

/* ==========================================
   RECENT LISTINGS (last 5)
========================================== */

$stmt = $pdo->prepare(
    "SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC LIMIT 5"
);
$stmt->execute([$sellerId]);
$recentProducts = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard | PustakMandi</title>

    <link rel="stylesheet" href="../homepage/css/style.css">
    <link rel="stylesheet" href="seller.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

<div class="top-bar">
    <div class="top-left">📚 PustakMandi Seller Center</div>
    <div class="top-right">
        <a href="#">Help</a>
        <a href="../customer/dashboard.php">Switch to Buying</a>
    </div>
</div>

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

<section class="welcome">
    <div class="welcome-content">
        <h1>Welcome back, <?php echo htmlspecialchars($fullName); ?> 👋</h1>
        <p>Here's how your shop is doing.</p>
    </div>
</section>

<div class="stats-grid">

    <div class="stat-card">
        <div class="stat-value"><?php echo $totalListings; ?></div>
        <div class="stat-label">Total Listings</div>
    </div>

    <div class="stat-card">
        <div class="stat-value">NPR <?php echo number_format($stockValue, 0); ?></div>
        <div class="stat-label">Stock Value</div>
    </div>

    <div class="stat-card">
        <div class="stat-value"><?php echo $outOfStock; ?></div>
        <div class="stat-label">Out of Stock</div>
    </div>

    <div class="stat-card">
        <div class="stat-value"><?php echo $unitsSold; ?></div>
        <div class="stat-label">Units Sold</div>
    </div>

</div>

<section style="padding:60px 8%;">

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <h2 style="color:var(--dark);">Recent Listings</h2>
        <a href="add_product.php" class="btn">+ Add Product</a>
    </div>

    <?php if (empty($recentProducts)): ?>

        <div style="background:white; border-radius:16px; padding:50px; text-align:center; box-shadow:0 10px 30px rgba(0,0,0,.06);">
            <p style="color:var(--gray); margin-bottom:20px;">You haven't listed anything yet.</p>
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
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentProducts as $product): ?>
                    <tr>
                        <td style="display:flex; align-items:center; gap:12px;">
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="">
                            <?php echo htmlspecialchars($product['title']); ?>
                        </td>
                        <td>NPR <?php echo number_format($product['price'], 2); ?></td>
                        <td><?php echo $product['stock']; ?></td>
                        <td>
                            <?php if ($product['stock'] > 0): ?>
                                <span class="badge badge-instock">In Stock</span>
                            <?php else: ?>
                                <span class="badge badge-outofstock">Out of Stock</span>
                            <?php endif; ?>
                        </td>
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

        <div style="text-align:center; margin-top:24px;">
            <a href="products.php" class="btn" style="background:#e2e8f0; color:#333;">View All Listings</a>
        </div>

    <?php endif; ?>

</section>

</body>

</html>