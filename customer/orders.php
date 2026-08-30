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
   FETCH THIS CUSTOMER'S ORDERS
========================================== */

$stmt = $pdo->prepare(
    "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC"
);
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

/* Fetch line items for each order in one extra query, grouped by order_id */
$itemsByOrder = [];
if (!empty($orders)) {
    $orderIds = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));

    $itemStmt = $pdo->prepare(
        "SELECT oi.order_id, oi.quantity, oi.price, p.title, p.image
         FROM order_items oi
         JOIN products p ON p.id = oi.product_id
         WHERE oi.order_id IN ({$placeholders})"
    );
    $itemStmt->execute($orderIds);

    foreach ($itemStmt->fetchAll() as $row) {
        $itemsByOrder[$row['order_id']][] = $row;
    }
}

$cartCountStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) AS total FROM cart WHERE user_id = ?");
$cartCountStmt->execute([$userId]);
$cartCount = $cartCountStmt->fetch()['total'];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$statusColors = [
    'pending'   => ['bg' => '#fef9c3', 'text' => '#854d0e'],
    'confirmed' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
    'shipped'   => ['bg' => '#e0e7ff', 'text' => '#4338ca'],
    'delivered' => ['bg' => '#dcfce7', 'text' => '#166534'],
    'cancelled' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | PustakMandi</title>

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
            <a href="profile.php" class="profile" style="text-decoration:none; color:inherit;">
                <div class="profile-icon"><i class="fa-solid fa-user"></i></div>
                <div class="profile-info">
                    <strong><?php echo htmlspecialchars($fullName); ?></strong>
                    <small>Customer</small>
                </div>
            </a>
        </div>
    </nav>
</header>

<section style="padding:50px 8%;">

    <?php if ($flash): ?>
        <div style="max-width:900px;margin:0 auto 24px;padding:14px 18px;background:#dcfce7;color:#166534;border-radius:12px;">
            <?php echo htmlspecialchars($flash); ?>
        </div>
    <?php endif; ?>

    <div class="section-title">
        <h2>My Orders</h2>
        <p><?php echo count($orders); ?> order<?php echo count($orders) === 1 ? '' : 's'; ?> placed</p>
    </div>

    <?php if (empty($orders)): ?>

        <div style="text-align:center; padding:60px 0;">
            <p style="color:var(--gray); margin-bottom:20px; font-size:18px;">You haven't placed any orders yet.</p>
            <a href="products.php" class="btn">Browse Products</a>
        </div>

    <?php else: ?>

        <div style="max-width:900px; margin:0 auto; display:flex; flex-direction:column; gap:24px;">

            <?php foreach ($orders as $order): ?>

                <?php $color = $statusColors[$order['status']] ?? ['bg' => '#f1f5f9', 'text' => '#333']; ?>

                <div style="background:white; border-radius:16px; padding:24px; box-shadow:0 10px 30px rgba(0,0,0,.06);">

                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
                        <div>
                            <strong>Order #<?php echo $order['id']; ?></strong>
                            <span style="color:var(--gray); font-size:13px; margin-left:10px;">
                                <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                            </span>
                        </div>
                        <span class="badge" style="background:<?php echo $color['bg']; ?>; color:<?php echo $color['text']; ?>;">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>

                    <?php if ($order['status'] === 'cancelled' && !empty($order['cancel_reason'])): ?>
                        <div style="font-size:13px; color:#991b1b; background:#fee2e2; padding:10px 14px; border-radius:8px; margin-bottom:16px;">
                            Cancelled: <?php echo htmlspecialchars($order['cancel_reason']); ?>
                        </div>
                    <?php endif; ?>

                    <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:16px;">
                        <?php foreach ($itemsByOrder[$order['id']] ?? [] as $item): ?>
                            <div style="display:flex; align-items:center; gap:14px;">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt=""
                                     style="width:50px; height:50px; object-fit:cover; border-radius:8px;">
                                <div style="flex:1; font-size:14px;">
                                    <?php echo htmlspecialchars($item['title']); ?> × <?php echo $item['quantity']; ?>
                                </div>
                                <div style="font-weight:600; color:var(--primary); font-size:14px;">
                                    NPR <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; padding-top:14px; border-top:1px solid #f1f5f9;">

                        <strong>Total: NPR <?php echo number_format($order['total_amount'], 2); ?></strong>

                        <div style="display:flex; gap:10px;">

                            <?php if ($order['status'] === 'shipped'): ?>
                                <form action="order_actions.php" method="post">
                                    <input type="hidden" name="action" value="mark_received">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <button type="submit" class="btn" style="border:none; cursor:pointer; padding:10px 20px; font-size:13px;">
                                        Mark as Received
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if (in_array($order['status'], ['pending', 'confirmed'], true)): ?>
                                <form action="order_actions.php" method="post" onsubmit="return setReason(this);">
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <input type="hidden" name="reason" class="cancel-reason-field">
                                    <button type="submit" style="border:none; background:#fee2e2; color:#991b1b; cursor:pointer; padding:10px 20px; border-radius:999px; font-size:13px; font-weight:600;">
                                        Cancel Order
                                    </button>
                                </form>
                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<script>
function setReason(form) {
    const reason = prompt("Why are you cancelling this order?");
    if (reason === null) return false;
    form.querySelector('.cancel-reason-field').value = reason;
    return true;
}
</script>

</body>

</html>