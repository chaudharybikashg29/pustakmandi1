<?php

session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../homepage/html/login.html");
    exit();
}

$fullName = $_SESSION['full_name'] ?? 'Admin';

/* ==========================================
   FILTER BY CATEGORY
========================================== */

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$categoryFilter = (int) ($_GET['category'] ?? 0);

if ($categoryFilter > 0) {
    $stmt = $pdo->prepare(
        "SELECT p.*, c.name AS category_name, u.full_name AS seller_name
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         LEFT JOIN users u ON u.user_id = p.seller_id
         WHERE p.category_id = ?
         ORDER BY p.created_at DESC"
    );
    $stmt->execute([$categoryFilter]);
} else {
    $stmt = $pdo->query(
        "SELECT p.*, c.name AS category_name, u.full_name AS seller_name
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         LEFT JOIN users u ON u.user_id = p.seller_id
         ORDER BY p.created_at DESC"
    );
}

$products = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products | PustakMandi Admin</title>

    <link rel="stylesheet" href="../homepage/css/style.css">
    <link rel="stylesheet" href="admin.css">
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
            <li><a href="users.php">Users</a></li>
            <li><a href="products.php">Products</a></li>
            <li><a href="orders.php">Orders</a></li>
        </ul>
        <div class="icons">
            <div class="profile">
                <div class="profile-icon"><i class="fa-solid fa-user-shield"></i></div>
                <div class="profile-info">
                    <strong><?php echo htmlspecialchars($fullName); ?></strong>
                    <small>Admin</small>
                </div>
            </div>
        </div>
    </nav>
</header>

<section style="padding:50px 8%;">

    <?php if ($flash): ?>
        <div style="background:#dcfce7;color:#166534;padding:14px 18px;border-radius:12px;margin-bottom:24px;">
            <?php echo htmlspecialchars($flash); ?>
        </div>
    <?php endif; ?>

    <div class="section-title" style="text-align:left; margin-bottom:20px;">
        <h2 style="color:var(--dark);">Manage Products</h2>
        <p><?php echo count($products); ?> product<?php echo count($products) === 1 ? '' : 's'; ?> across all sellers</p>
    </div>

    <div class="filter-tabs">
        <a href="products.php" class="<?php echo $categoryFilter === 0 ? 'active' : ''; ?>">All</a>
        <?php foreach ($categories as $cat): ?>
            <a href="products.php?category=<?php echo $cat['id']; ?>"
               class="<?php echo $categoryFilter === (int) $cat['id'] ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($cat['name']); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($products)): ?>

        <div style="background:white; border-radius:16px; padding:50px; text-align:center; color:var(--gray); box-shadow:0 10px 30px rgba(0,0,0,.06);">
            No products found.
        </div>

    <?php else: ?>

        <table class="listing-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Seller</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
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
                        <td style="font-size:13px;">
                            <?php echo $product['seller_name'] ? htmlspecialchars($product['seller_name']) : '<span style="color:var(--gray);">— unassigned —</span>'; ?>
                        </td>
                        <td style="font-size:13px; color:var(--gray);">
                            <?php echo htmlspecialchars($product['category_name'] ?? '—'); ?>
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
                        <td class="table-actions">
                            <form action="product_actions.php" method="post" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="redirect" value="products.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : ''; ?>">
                                <button type="submit" class="btn-delete"
                                        onclick="return confirm('Remove \'<?php echo htmlspecialchars(addslashes($product['title'])); ?>\' from the platform?');">
                                    Remove
                                </button>
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