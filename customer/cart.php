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
   FETCH CART ITEMS + PRODUCT DETAILS
========================================== */

$stmt = $pdo->prepare(
    "SELECT c.product_id, c.quantity, p.title, p.price, p.image, p.stock
     FROM cart c
     JOIN products p ON p.id = c.product_id
     WHERE c.user_id = ?
     ORDER BY c.added_at DESC"
);
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart | PustakMandi</title>

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
        <div class="profile">
            <div class="profile-icon"><i class="fa-solid fa-user"></i></div>
            <div class="profile-info">
                <strong><?php echo htmlspecialchars($fullName); ?></strong>
                <small>Customer</small>
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
        <h2>Your Cart</h2>
        <p><?php echo count($cartItems); ?> item<?php echo count($cartItems) === 1 ? '' : 's'; ?> in your cart</p>
    </div>

    <?php if (empty($cartItems)): ?>

        <div style="text-align:center; padding:60px 0;">
            <p style="color:var(--gray); margin-bottom:20px; font-size:18px;">Your cart is empty.</p>
            <a href="products.php" class="btn">Browse Products</a>
        </div>

    <?php else: ?>

        <div style="max-width:900px; margin:0 auto; display:flex; flex-direction:column; gap:20px;">

            <?php foreach ($cartItems as $item): ?>

                <div style="display:flex; align-items:center; gap:20px; background:white; border-radius:16px; padding:16px; box-shadow:0 10px 30px rgba(0,0,0,.06);">

                    <img src="<?php echo htmlspecialchars($item['image']); ?>"
                         alt="<?php echo htmlspecialchars($item['title']); ?>"
                         style="width:90px; height:90px; object-fit:cover; border-radius:12px; flex-shrink:0;">

                    <div style="flex:1;">
                        <h3 style="margin-bottom:6px;"><?php echo htmlspecialchars($item['title']); ?></h3>
                        <p style="color:var(--gray);">NPR <?php echo number_format($item['price'], 2); ?> each</p>
                    </div>

                    <!-- QUANTITY UPDATE -->
                    <form action="cart_actions.php" method="post" style="display:flex; align-items:center; gap:8px;">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>"
                               min="1" max="<?php echo $item['stock']; ?>"
                               style="width:60px; padding:8px; border-radius:8px; border:1px solid #ddd; text-align:center;">
                        <button type="submit" class="btn" style="padding:8px 16px; font-size:13px;">Update</button>
                    </form>

                    <div style="min-width:110px; text-align:right; font-weight:700; color:var(--primary);">
                        NPR <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                    </div>

                    <!-- REMOVE -->
                    <form action="cart_actions.php" method="post">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                        <button type="submit" style="border:none; background:none; color:#ef4444; font-size:18px; cursor:pointer;">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>

                </div>

            <?php endforeach; ?>

            <!-- SUMMARY -->
            <div style="background:white; border-radius:16px; padding:26px; box-shadow:0 10px 30px rgba(0,0,0,.06); text-align:right;">
                <p style="font-size:18px; margin-bottom:16px;">
                    Subtotal: <strong style="color:var(--primary);">NPR <?php echo number_format($subtotal, 2); ?></strong>
                </p>
                <a href="checkout.php" class="btn">Proceed to Checkout</a>
            </div>

        </div>

    <?php endif; ?>

</section>

<script src="../homepage/js/script.js"></script>

</body>

</html>
