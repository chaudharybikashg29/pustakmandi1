<?php

session_start();
require_once '../config/db.php';

/* ==========================================
   CUSTOMER ONLY
========================================== */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../homepage/html/login.html");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

$fullName = $_SESSION['full_name'] ?? 'Customer';

/* ==========================================
   FETCH CATEGORIES (for the filter bar)
========================================== */

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

/* ==========================================
   FETCH PRODUCTS (optionally filtered by category)
========================================== */

$selectedCategory = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$searchTerm       = trim($_GET['search'] ?? '');

$where  = [];
$params = [];

if ($selectedCategory > 0) {
    $where[]  = "category_id = ?";
    $params[] = $selectedCategory;
}

if ($searchTerm !== '') {
    $where[]  = "title LIKE ?";
    $params[] = "%{$searchTerm}%";
}

$sql = "SELECT * FROM products";
if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

/* ==========================================
   WISHLIST PRODUCT IDS (to fill the heart for saved items)
========================================== */

$wishlistStmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
$wishlistStmt->execute([$_SESSION['user_id']]);
$wishlistedIds = array_column($wishlistStmt->fetchAll(), 'product_id');
$wishlistCount = count($wishlistedIds);

/* ==========================================
   CART COUNT (for the navbar icon)
========================================== */

$cartCountStmt = $pdo->prepare(
    "SELECT COALESCE(SUM(quantity), 0) AS total FROM cart WHERE user_id = ?"
);
$cartCountStmt->execute([$_SESSION['user_id']]);
$cartCount = $cartCountStmt->fetch()['total'];

/* ==========================================
   FLASH MESSAGE (set by cart_actions.php / wishlist_actions.php)
========================================== */

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Products | PustakMandi</title>

    <link rel="stylesheet" href="../homepage/css/style.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>

<body>

<!-- TOP BAR -->
<div class="top-bar">
    <div class="top-left">📚 Buy • Sell • Exchange Campus Essentials</div>
    <div class="top-right">
        <a href="#">Help</a>
        <a href="../seller/dashboard.php">Become Seller</a>
    </div>
</div>

<!-- NAVBAR -->
<header>
    <nav class="navbar">

        <div class="logo">
            <img class="plogo" src="../homepage/images/pustakmandi.jpg" alt="PustakMandi Logo">
            <h2>PustakMandi</h2>
        </div>

        <ul class="nav-links">
            <li><a href="dashboard.php">Home</a></li>
            <li><a href="products.php">Products</a></li>
            <li><a href="cart.php">Cart</a></li>
        </ul>

        <div class="search-box">
            <form action="products.php" method="get" style="display:flex; width:100%;">
                <input type="text" name="search" placeholder="Search books, calculators..."
                       value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                <button type="submit"><i class="fa-solid fa-search"></i></button>
            </form>
        </div>

        <div class="icons">

            <a href="wishlist.php" class="icon">
                <i class="fa-solid fa-heart"></i>
                <span>Wishlist (<?php echo (int) $wishlistCount; ?>)</span>
            </a>

            <a href="cart.php" class="icon">
                <i class="fa-solid fa-cart-shopping"></i>
                <span>Cart (<?php echo (int) $cartCount; ?>)</span>
            </a>

            <div class="profile">
                <div class="profile-icon"><i class="fa-solid fa-user"></i></div>
                <div class="profile-info">
                    <strong><?php echo htmlspecialchars($fullName); ?></strong>
                    <small>Customer</small>
                </div>
            </div>

        </div>

    </nav>
</header>

<?php if ($flash): ?>
    <div style="max-width:1300px;margin:20px auto 0;padding:14px 8%;background:#dcfce7;color:#166534;border-radius:12px;">
        <?php echo htmlspecialchars($flash); ?>
    </div>
<?php endif; ?>

<!-- CATEGORY FILTER -->
<section style="padding:50px 8% 0;">

    <div class="section-title">
        <h2>Browse Products</h2>
        <p>Find books, notes, electronics and other campus essentials.</p>
    </div>

    <div style="display:flex; gap:12px; flex-wrap:wrap; justify-content:center; margin-bottom:40px;">

        <a href="products.php"
           class="btn"
           style="<?php echo $selectedCategory === 0 ? '' : 'background:#e2e8f0;color:#333;'; ?> padding:10px 22px; font-size:14px;">
            All
        </a>

        <?php foreach ($categories as $cat): ?>
            <a href="products.php?category=<?php echo $cat['id']; ?>"
               class="btn"
               style="<?php echo $selectedCategory === (int)$cat['id'] ? '' : 'background:#e2e8f0;color:#333;'; ?> padding:10px 22px; font-size:14px;">
                <i class="<?php echo htmlspecialchars($cat['icon']); ?>"></i>
                <?php echo htmlspecialchars($cat['name']); ?>
            </a>
        <?php endforeach; ?>

    </div>

</section>

<!-- PRODUCT GRID -->
<section class="products">

    <div class="product-grid">

        <?php if (empty($products)): ?>

            <p style="grid-column:1/-1; text-align:center; color:var(--gray);">
                No products found in this category yet.
            </p>

        <?php else: ?>

            <?php foreach ($products as $product): ?>

                <?php $isWishlisted = in_array($product['id'], $wishlistedIds); ?>

                <div class="product-card">

                    <?php if ($product['old_price']): ?>
                        <span class="sale">SALE</span>
                    <?php endif; ?>

                    <!-- WISHLIST TOGGLE -->
                    <form action="wishlist_actions.php" method="post">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="redirect"
                               value="products.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : ''; ?>">
                        <button type="submit" class="wishlist-btn" title="<?php echo $isWishlisted ? 'Remove from wishlist' : 'Save to wishlist'; ?>">
                            <i class="fa-<?php echo $isWishlisted ? 'solid' : 'regular'; ?> fa-heart"
                               style="<?php echo $isWishlisted ? 'color:#ef4444;' : ''; ?>"></i>
                        </button>
                    </form>

                    <a href="product_details.php?id=<?php echo $product['id']; ?>">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>"
                             alt="<?php echo htmlspecialchars($product['title']); ?>">
                    </a>

                    <div class="product-info">

                        <h3><a href="product_details.php?id=<?php echo $product['id']; ?>" style="color:inherit;"><?php echo htmlspecialchars($product['title']); ?></a></h3>

                        <div class="rating">
                            <?php echo str_repeat('⭐', (int) round($product['rating'])); ?>
                            <span>(<?php echo number_format($product['rating'], 1); ?>)</span>
                        </div>

                        <div class="price">
                            <span class="new-price">NPR <?php echo number_format($product['price'], 2); ?></span>
                            <?php if ($product['old_price']): ?>
                                <span class="old-price">NPR <?php echo number_format($product['old_price'], 2); ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if ($product['stock'] > 0): ?>
                            <form action="cart_actions.php" method="post">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="redirect"
                                       value="products.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : ''; ?>">
                                <button type="submit" class="cart-btn">Add to Cart</button>
                            </form>
                        <?php else: ?>
                            <button class="cart-btn" disabled style="opacity:.5; cursor:not-allowed;">Out of Stock</button>
                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</section>

<script src="../homepage/js/script.js"></script>

</body>

</html>