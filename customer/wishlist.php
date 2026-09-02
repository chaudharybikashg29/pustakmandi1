<?php

session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header("Location: ../homepage/html/login.html");
    exit();
}

$userId   = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'Customer';

/* ==========================================
   FETCH WISHLIST ITEMS + PRODUCT DETAILS
========================================== */

$stmt = $pdo->prepare(
    "SELECT w.product_id, p.title, p.price, p.old_price, p.image, p.stock, p.rating
     FROM wishlist w
     JOIN products p ON p.id = w.product_id
     WHERE w.user_id = ?
     ORDER BY w.added_at DESC"
);
$stmt->execute([$userId]);
$wishlistItems = $stmt->fetchAll();
$wishlistCount = count($wishlistItems);

/* ==========================================
   CART COUNT (for the navbar icon)
========================================== */

$cartCountStmt = $pdo->prepare(
    "SELECT COALESCE(SUM(quantity), 0) AS total FROM cart WHERE user_id = ?"
);
$cartCountStmt->execute([$userId]);
$cartCount = $cartCountStmt->fetch()['total'];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Wishlist | PustakMandi</title>

    <link rel="stylesheet" href="../homepage/css/style.css">
    <link rel="stylesheet" href="dashboard.css">
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
            <li><a href="dashboard.php">Home</a></li>
            <li><a href="products.php">Products</a></li>
            <li><a href="cart.php">Cart</a></li>
        </ul>

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

<section style="padding:50px 8%;">

    <div class="section-title">
        <h2>Your Wishlist</h2>
        <p><?php echo $wishlistCount; ?> item<?php echo $wishlistCount === 1 ? '' : 's'; ?> saved</p>
    </div>

    <?php if (empty($wishlistItems)): ?>

        <div style="text-align:center; padding:60px 0;">
            <p style="color:var(--gray); margin-bottom:20px; font-size:18px;">
                Your wishlist is empty. Tap the heart on any product to save it here.
            </p>
            <a href="products.php" class="btn">Browse Products</a>
        </div>

    <?php else: ?>

        <div class="product-grid">

            <?php foreach ($wishlistItems as $item): ?>

                <div class="product-card">

                    <?php if ($item['old_price']): ?>
                        <span class="sale">SALE</span>
                    <?php endif; ?>

                    <!-- REMOVE FROM WISHLIST -->
                    <form action="wishlist_actions.php" method="post">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                        <input type="hidden" name="redirect" value="wishlist.php">
                        <button type="submit" class="wishlist-btn" title="Remove from wishlist">
                            <i class="fa-solid fa-heart" style="color:#ef4444;"></i>
                        </button>
                    </form>

                    <img src="<?php echo htmlspecialchars($item['image']); ?>"
                         alt="<?php echo htmlspecialchars($item['title']); ?>">

                    <div class="product-info">

                        <h3><?php echo htmlspecialchars($item['title']); ?></h3>

                        <div class="rating">
                            <?php echo str_repeat('⭐', (int) round($item['rating'])); ?>
                            <span>(<?php echo number_format($item['rating'], 1); ?>)</span>
                        </div>

                        <div class="price">
                            <span class="new-price">NPR <?php echo number_format($item['price'], 2); ?></span>
                            <?php if ($item['old_price']): ?>
                                <span class="old-price">NPR <?php echo number_format($item['old_price'], 2); ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if ($item['stock'] > 0): ?>
                            <form action="wishlist_actions.php" method="post">
                                <input type="hidden" name="action" value="move_to_cart">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <input type="hidden" name="redirect" value="wishlist.php">
                                <button type="submit" class="cart-btn">Move to Cart</button>
                            </form>
                        <?php else: ?>
                            <button class="cart-btn" disabled style="opacity:.5; cursor:not-allowed;">Out of Stock</button>
                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

</body>

</html>