<?php

session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'seller') {
    header("Location: ../homepage/html/login.html");
    exit();
}

$sellerId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'Seller';

/* ==========================================
   FETCH ORDERS CONTAINING THIS SELLER'S PRODUCTS
   One row per order_item — if a buyer ordered 3 of your
   products in one checkout, that's 3 rows here, one per product.
========================================== */

$stmt = $pdo->prepare(
    "SELECT
        o.id AS order_id,
        o.full_name AS buyer_name,
        o.phone AS buyer_phone,
        o.address AS buyer_address,
        o.status,
        o.cancel_reason,
        o.created_at,
        oi.quantity,
        oi.price,
        p.title AS product_title,
        p.image AS product_image
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     JOIN products p ON p.id = oi.product_id
     WHERE p.seller_id = ?
     ORDER BY o.created_at DESC"
);
$stmt->execute([$sellerId]);
$orderItems = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders | PustakMandi Seller</title>

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

<section style="padding:50px 8%;">

    <?php if ($flash): ?>
        <div style="background:#dcfce7;color:#166534;padding:14px 18px;border-radius:12px;margin-bottom:24px;">
            <?php echo htmlspecialchars($flash); ?>
        </div>
    <?php endif; ?>

    <div class="section-title" style="text-align:left; margin-bottom:30px;">
        <h2 style="color:var(--dark);">Orders</h2>
        <p><?php echo count($orderItems); ?> order item<?php echo count($orderItems) === 1 ? '' : 's'; ?> across your listings</p>
    </div>

    <?php if (empty($orderItems)): ?>

        <div style="background:white; border-radius:16px; padding:60px; text-align:center; box-shadow:0 10px 30px rgba(0,0,0,.06);">
            <p style="color:var(--gray); font-size:18px;">No one has ordered your products yet.</p>
        </div>

    <?php else: ?>

        <table class="listing-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Total</th>
                    <th>Buyer</th>
                    <th>Delivery Details</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderItems as $item): ?>
                    <tr>
                        <td>#<?php echo $item['order_id']; ?></td>
                        <td style="display:flex; align-items:center; gap:12px;">
                            <img src="<?php echo htmlspecialchars($item['product_image']); ?>" alt="">
                            <?php echo htmlspecialchars($item['product_title']); ?>
                        </td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>NPR <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        <td><?php echo htmlspecialchars($item['buyer_name']); ?></td>
                        <td style="font-size:13px; color:var(--gray);">
                            <?php echo htmlspecialchars($item['buyer_phone']); ?><br>
                            <?php echo htmlspecialchars($item['buyer_address']); ?>
                        </td>
                        <td>
                            <?php
                                $statusColors = [
                                    'pending'   => ['bg' => '#fef9c3', 'text' => '#854d0e'],
                                    'confirmed' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                                    'shipped'   => ['bg' => '#e0e7ff', 'text' => '#4338ca'],
                                    'delivered' => ['bg' => '#dcfce7', 'text' => '#166534'],
                                    'cancelled' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                                ];
                                $color = $statusColors[$item['status']] ?? ['bg' => '#f1f5f9', 'text' => '#333'];
                                $isLocked = in_array($item['status'], ['delivered', 'cancelled'], true);
                            ?>

                            <?php if ($isLocked): ?>

                                <span class="badge" style="background:<?php echo $color['bg']; ?>; color:<?php echo $color['text']; ?>;">
                                    <?php echo ucfirst($item['status']); ?>
                                </span>
                                <?php if ($item['status'] === 'cancelled' && !empty($item['cancel_reason'])): ?>
                                    <div style="font-size:11px; color:var(--gray); margin-top:4px; max-width:160px;">
                                        <?php echo htmlspecialchars($item['cancel_reason']); ?>
                                    </div>
                                <?php endif; ?>

                            <?php else: ?>

                                <div style="display:flex; flex-direction:column; gap:6px;">

                                    <form action="order_status_actions.php" method="post" style="display:flex; gap:6px; align-items:center;">
                                        <input type="hidden" name="order_id" value="<?php echo $item['order_id']; ?>">
                                        <select name="status"
                                                style="background:<?php echo $color['bg']; ?>; color:<?php echo $color['text']; ?>; border:none; border-radius:20px; padding:6px 10px; font-size:12px; font-weight:600; cursor:pointer;">
                                            <?php foreach (['pending', 'confirmed', 'shipped'] as $option): ?>
                                                <option value="<?php echo $option; ?>" <?php echo $item['status'] === $option ? 'selected' : ''; ?>>
                                                    <?php echo ucfirst($option); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" style="border:none; background:none; color:var(--primary); cursor:pointer; font-size:13px;" title="Update status">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                    </form>

                                    <form action="order_status_actions.php" method="post"
                                          onsubmit="return setCancelReason(this);">
                                        <input type="hidden" name="order_id" value="<?php echo $item['order_id']; ?>">
                                        <input type="hidden" name="status" value="cancelled">
                                        <input type="hidden" name="reason" class="cancel-reason-field">
                                        <button type="submit" style="border:none; background:none; color:#991b1b; cursor:pointer; font-size:11px; text-decoration:underline;">
                                            Cancel Order
                                        </button>
                                    </form>

                                </div>

                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($item['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p style="color:var(--gray); font-size:13px; margin-top:16px;">
            Changing status here updates the whole order (#) — if a buyer ordered from
            multiple sellers in one checkout, they'll all see whichever status is set last.
        </p>

    <?php endif; ?>

</section>

<script>
function setCancelReason(form) {
    const reason = prompt("Reason for cancelling this order (shown to the buyer):");
    if (reason === null) return false; // they hit Cancel on the prompt itself
    form.querySelector('.cancel-reason-field').value = reason;
    return true;
}
</script>

</body>

</html>