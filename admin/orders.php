<?php

session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../homepage/html/login.html");
    exit();
}

$fullName = $_SESSION['full_name'] ?? 'Admin';

/* ==========================================
   FILTER BY STATUS
========================================== */

$statusFilter = $_GET['status'] ?? 'all';
$allowedStatuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];

if ($statusFilter === 'refunds') {
    $stmt = $pdo->query(
        "SELECT o.*, u.full_name AS buyer_name, u.email AS buyer_email
         FROM orders o
         JOIN users u ON u.user_id = o.user_id
         WHERE o.refund_status = 'requested'
         ORDER BY o.created_at DESC"
    );
} elseif (in_array($statusFilter, $allowedStatuses, true)) {
    $stmt = $pdo->prepare(
        "SELECT o.*, u.full_name AS buyer_name, u.email AS buyer_email
         FROM orders o
         JOIN users u ON u.user_id = o.user_id
         WHERE o.status = ?
         ORDER BY o.created_at DESC"
    );
    $stmt->execute([$statusFilter]);
} else {
    $stmt = $pdo->query(
        "SELECT o.*, u.full_name AS buyer_name, u.email AS buyer_email
         FROM orders o
         JOIN users u ON u.user_id = o.user_id
         ORDER BY o.created_at DESC"
    );
}

$orders = $stmt->fetchAll();

/* Fetch line items for every order in one extra query */
$itemsByOrder = [];
if (!empty($orders)) {
    $orderIds = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));

    $itemStmt = $pdo->prepare(
        "SELECT oi.order_id, oi.quantity, oi.price, p.title, p.image, u.full_name AS seller_name
         FROM order_items oi
         JOIN products p ON p.id = oi.product_id
         LEFT JOIN users u ON u.user_id = p.seller_id
         WHERE oi.order_id IN ({$placeholders})"
    );
    $itemStmt->execute($orderIds);

    foreach ($itemStmt->fetchAll() as $row) {
        $itemsByOrder[$row['order_id']][] = $row;
    }
}

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
    <title>Manage Orders | PustakMandi Admin</title>

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
        <div style="max-width:1300px;margin:0 auto 24px;padding:14px 18px;background:#dcfce7;color:#166534;border-radius:12px;">
            <?php echo htmlspecialchars($flash); ?>
        </div>
    <?php endif; ?>

    <div class="section-title" style="text-align:left; margin-bottom:20px;">
        <h2 style="color:var(--dark);">Manage Orders</h2>
        <p><?php echo count($orders); ?> order<?php echo count($orders) === 1 ? '' : 's'; ?></p>
    </div>

    <div class="filter-tabs">
        <a href="orders.php" class="<?php echo $statusFilter === 'all' ? 'active' : ''; ?>">All</a>
        <a href="orders.php?status=refunds" class="<?php echo $statusFilter === 'refunds' ? 'active' : ''; ?>" style="<?php echo $statusFilter === 'refunds' ? '' : 'background:#fef9c3; color:#854d0e;'; ?>">
            Refunds Needed
        </a>
        <?php foreach ($allowedStatuses as $status): ?>
            <a href="orders.php?status=<?php echo $status; ?>"
               class="<?php echo $statusFilter === $status ? 'active' : ''; ?>">
                <?php echo ucfirst($status); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($orders)): ?>

        <div style="background:white; border-radius:16px; padding:50px; text-align:center; color:var(--gray); box-shadow:0 10px 30px rgba(0,0,0,.06);">
            No orders found.
        </div>

    <?php else: ?>

        <div style="display:flex; flex-direction:column; gap:20px;">

            <?php foreach ($orders as $order): ?>

                <?php
                    $color    = $statusColors[$order['status']] ?? ['bg' => '#f1f5f9', 'text' => '#333'];
                    $isLocked = in_array($order['status'], ['delivered', 'cancelled'], true);
                ?>

                <div style="background:white; border-radius:16px; padding:24px; box-shadow:0 10px 30px rgba(0,0,0,.06);">

                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px; flex-wrap:wrap; gap:14px;">

                        <div>
                            <strong>Order #<?php echo $order['id']; ?></strong>
                            <span style="color:var(--gray); font-size:13px; margin-left:10px;">
                                <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                            </span>
                            <div style="font-size:13px; color:var(--gray); margin-top:4px;">
                                <?php echo htmlspecialchars($order['buyer_name']); ?> · <?php echo htmlspecialchars($order['buyer_email']); ?><br>
                                <?php echo htmlspecialchars($order['phone']); ?> — <?php echo htmlspecialchars($order['address']); ?>
                            </div>
                        </div>

                        <?php if ($isLocked): ?>

                            <span class="badge" style="background:<?php echo $color['bg']; ?>; color:<?php echo $color['text']; ?>;">
                                <?php echo ucfirst($order['status']); ?>
                            </span>

                        <?php else: ?>

                            <div style="display:flex; flex-direction:column; gap:6px; align-items:flex-end;">

                                <form action="order_status_actions.php" method="post" style="display:flex; gap:6px;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <input type="hidden" name="redirect" value="orders.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : ''; ?>">
                                    <select name="status"
                                            style="background:<?php echo $color['bg']; ?>; color:<?php echo $color['text']; ?>; border:none; border-radius:20px; padding:6px 10px; font-size:12px; font-weight:600; cursor:pointer;">
                                        <?php foreach ($allowedStatuses as $option): ?>
                                            <option value="<?php echo $option; ?>" <?php echo $order['status'] === $option ? 'selected' : ''; ?>>
                                                <?php echo ucfirst($option); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" style="border:none; background:none; color:var(--primary); cursor:pointer;" title="Update status">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </form>

                            </div>

                        <?php endif; ?>

                    </div>

                    <?php if ($order['status'] === 'cancelled' && !empty($order['cancel_reason'])): ?>
                        <div style="font-size:13px; color:#991b1b; background:#fee2e2; padding:10px 14px; border-radius:8px; margin-bottom:16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                            <span>
                                Cancelled: <?php echo htmlspecialchars($order['cancel_reason']); ?>
                                <?php if ($order['refund_status'] === 'requested'): ?>
                                    <br><strong style="color:#854d0e;">Refund requested — money not yet returned.</strong>
                                <?php elseif ($order['refund_status'] === 'refunded'): ?>
                                    <br><strong style="color:#166534;">Refunded.</strong>
                                <?php endif; ?>
                            </span>
                            <?php if ($order['refund_status'] === 'requested'): ?>
                                <form action="refund_actions.php" method="post">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <input type="hidden" name="redirect" value="orders.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : ''; ?>">
                                    <button type="submit" style="border:none; background:#166534; color:white; padding:8px 16px; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer;"
                                            onclick="return confirm('Only click this after you\'ve actually sent the refund manually (bank transfer / eSewa dashboard). This just marks it as done here — it does not move any money.');">
                                        Mark as Refunded
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:16px;">
                        <?php foreach ($itemsByOrder[$order['id']] ?? [] as $item): ?>
                            <div style="display:flex; align-items:center; gap:14px;">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt=""
                                     style="width:44px; height:44px; object-fit:cover; border-radius:8px;">
                                <div style="flex:1; font-size:14px;">
                                    <?php echo htmlspecialchars($item['title']); ?> × <?php echo $item['quantity']; ?>
                                    <div style="font-size:11px; color:var(--gray);">
                                        Sold by <?php echo $item['seller_name'] ? htmlspecialchars($item['seller_name']) : '— unassigned —'; ?>
                                    </div>
                                </div>
                                <div style="font-weight:600; color:var(--primary); font-size:14px;">
                                    NPR <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="padding-top:14px; border-top:1px solid #f1f5f9; text-align:right;">
                        <strong>Total: NPR <?php echo number_format($order['total_amount'], 2); ?></strong>
                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

</body>

</html>