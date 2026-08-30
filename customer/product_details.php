<?php

session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../homepage/html/login.html");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

$fullName  = $_SESSION['full_name'] ?? 'Customer';
$productId = (int) ($_GET['id'] ?? 0);

/* ==========================================
   FETCH PRODUCT + CATEGORY + SELLER NAME
========================================== */

$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name, u.full_name AS seller_name
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     LEFT JOIN users u ON u.user_id = p.seller_id
     WHERE p.id = ?"
);
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: products.php");
    exit();
}

/* ==========================================
   IS THIS ALREADY WISHLISTED?
========================================== */

$wStmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
$wStmt->execute([$_SESSION['user_id'], $productId]);
$isWishlisted = (bool) $wStmt->fetch();

/* ==========================================
   RELATED PRODUCTS (same category, excluding this one)
========================================== */

$related = [];
if ($product['category_id']) {
    $relStmt = $pdo->prepare(
        "SELECT * FROM products WHERE category_id = ? AND id != ? ORDER BY created_at DESC LIMIT 4"
    );
    $relStmt->execute([$product['category_id'], $productId]);
    $related = $relStmt->fetchAll();
}

/* ==========================================
   CART COUNT
========================================== */

$cartCountStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) AS total FROM cart WHERE user_id = ?");
$cartCountStmt->execute([$_SESSION['user_id']]);
$cartCount = $cartCountStmt->fetch()['total'];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['title']); ?> | PustakMandi</title>

    <link rel="stylesheet" href="../homepage/css/style.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

<div class="top-bar">
    <div class="top-left">📚 Buy • Sell • Exchange Campus Essentials</div>
    <div class="top-right">
        <a href="#">Help</a>
        <a href="#">Become Seller</a>
    </div>
</div>

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
        <div class="icons">
            <a href="wishlist.php" class="icon">
                <i class="fa-solid fa-heart"></i>
                <span>Wishlist</span>
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

<section style="padding:40px 8%;">

    <a href="products.php" style="color:var(--gray); font-size:14px; display:inline-block; margin-bottom:24px;">
        <i class="fa-solid fa-arrow-left"></i> Back to Products
    </a>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:50px; max-width:1100px; margin:0 auto;">

        <!-- IMAGE -->
        <div>
            <img src="<?php echo htmlspecialchars($product['image']); ?>"
                 alt="<?php echo htmlspecialchars($product['title']); ?>"
                 style="width:100%; border-radius:20px; box-shadow:0 20px 40px rgba(0,0,0,.1);">
        </div>

        <!-- DETAILS -->
        <div>

            <?php if ($product['category_name']): ?>
                <span style="color:var(--primary); font-weight:600; font-size:13px; text-transform:uppercase; letter-spacing:.04em;">
                    <?php echo htmlspecialchars($product['category_name']); ?>
                </span>
            <?php endif; ?>

            <h1 style="font-size:32px; color:var(--dark); margin:10px 0 14px;">
                <?php echo htmlspecialchars($product['title']); ?>
            </h1>

            <div class="rating" style="margin-bottom:16px;">
                <?php echo str_repeat('⭐', (int) round($product['rating'])); ?>
                <span>(<?php echo number_format($product['rating'], 1); ?>)</span>
            </div>

            <div class="price" style="margin-bottom:20px;">
                <span class="new-price" style="font-size:30px;">NPR <?php echo number_format($product['price'], 2); ?></span>
                <?php if ($product['old_price']): ?>
                    <span class="old-price">NPR <?php echo number_format($product['old_price'], 2); ?></span>
                <?php endif; ?>
            </div>

            <p style="color:var(--gray); line-height:1.8; margin-bottom:24px;">
                <?php echo nl2br(htmlspecialchars($product['description'] ?: 'No description provided.')); ?>
            </p>

            <div style="display:flex; align-items:center; gap:10px; margin-bottom:24px; font-size:14px; color:var(--gray);">
                <i class="fa-solid fa-box"></i>
                <?php if ($product['stock'] > 0): ?>
                    <span style="color:#166534; font-weight:600;"><?php echo $product['stock']; ?> in stock</span>
                <?php else: ?>
                    <span style="color:#991b1b; font-weight:600;">Out of stock</span>
                <?php endif; ?>
            </div>

            <?php if ($product['seller_name']): ?>
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:30px; font-size:14px; color:var(--gray);">
                    <i class="fa-solid fa-store"></i>
                    Sold by <strong style="color:var(--dark);"><?php echo htmlspecialchars($product['seller_name']); ?></strong>
                </div>
            <?php endif; ?>

            <div style="display:flex; gap:14px;">

                <?php if ($product['stock'] > 0): ?>
                    <form action="cart_actions.php" method="post" style="flex:1;">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="redirect" value="product_details.php?id=<?php echo $product['id']; ?>">
                        <button type="submit" class="cart-btn">Add to Cart</button>
                    </form>
                <?php else: ?>
                    <button class="cart-btn" disabled style="flex:1; opacity:.5; cursor:not-allowed;">Out of Stock</button>
                <?php endif; ?>

                <form action="wishlist_actions.php" method="post">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <input type="hidden" name="redirect" value="product_details.php?id=<?php echo $product['id']; ?>">
                    <button type="submit" class="wishlist-btn" style="position:static; width:52px; height:52px; box-shadow:0 5px 15px rgba(0,0,0,.1);">
                        <i class="fa-<?php echo $isWishlisted ? 'solid' : 'regular'; ?> fa-heart"
                           style="<?php echo $isWishlisted ? 'color:#ef4444;' : ''; ?>"></i>
                    </button>
                </form>

            </div>

        </div>

    </div>

    <!-- RELATED PRODUCTS -->
    <?php if (!empty($related)): ?>
        <div style="max-width:1300px; margin:70px auto 0;">
            <h2 style="color:var(--dark); margin-bottom:24px;">You Might Also Like</h2>
            <div class="product-grid">
                <?php foreach ($related as $item): ?>
                    <a href="product_details.php?id=<?php echo $item['id']; ?>" class="product-card" style="display:block; color:inherit;">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                            <div class="price">
                                <span class="new-price">NPR <?php echo number_format($item['price'], 2); ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</section>

</body>

</html>