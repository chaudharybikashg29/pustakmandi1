<?php

session_start();
require_once '../config/db.php';

/* ==========================================
   ADMIN ONLY
========================================== */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../homepage/html/login.html");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$fullName = $_SESSION['full_name'] ?? 'Admin';

/* ==========================================
   PLATFORM STATS
========================================== */

$totalUsers = $pdo->query("SELECT COUNT(*) AS total FROM users")->fetch()['total'];

$stmt = $pdo->query("SELECT role, COUNT(*) AS total FROM users GROUP BY role");
$usersByRole = ['admin' => 0, 'seller' => 0, 'customer' => 0];
foreach ($stmt->fetchAll() as $row) {
    $usersByRole[$row['role']] = $row['total'];
}

$totalProducts = $pdo->query("SELECT COUNT(*) AS total FROM products")->fetch()['total'];
$totalOrders   = $pdo->query("SELECT COUNT(*) AS total FROM orders")->fetch()['total'];

$totalRevenue = $pdo->query(
    "SELECT COALESCE(SUM(total_amount), 0) AS total FROM orders WHERE status != 'cancelled'"
)->fetch()['total'];

/* ==========================================
   RECENT ORDERS (last 5)
========================================== */

$recentOrders = $pdo->query(
    "SELECT o.*, u.full_name AS buyer_name
     FROM orders o
     JOIN users u ON u.user_id = o.user_id
     ORDER BY o.created_at DESC
     LIMIT 5"
)->fetchAll();

/* ==========================================
   RECENT USERS (last 5)
========================================== */

$recentUsers = $pdo->query(
    "SELECT * FROM users ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

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
    <title>Admin Dashboard | PustakMandi</title>

    <link rel="stylesheet" href="../homepage/css/style.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

<div class="top-bar">
    <div class="top-left">📚 PustakMandi Admin</div>
    <div class="top-right">
        <a href="../logout.php">Logout</a>
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

<?php if ($flash): ?>
    <div style="max-width:1300px;margin:20px auto 0;padding:14px 8%;background:#dcfce7;color:#166534;border-radius:12px;">
        <?php echo htmlspecialchars($flash); ?>
    </div>
<?php endif; ?>

<section class="welcome">
    <div class="welcome-content">
        <h1>Admin Overview</h1>
        <p>Platform-wide stats and activity.</p>
    </div>
</section>

<div class="stats-grid">

    <div class="stat-card">
        <div class="stat-value"><?php echo $totalUsers; ?></div>
        <div class="stat-label">Total Users</div>
    </div>

    <div class="stat-card">
        <div class="stat-value"><?php echo $totalProducts; ?></div>
        <div class="stat-label">Total Products</div>
    </div>

    <div class="stat-card">
        <div class="stat-value"><?php echo $totalOrders; ?></div>
        <div class="stat-label">Total Orders</div>
    </div>

    <div class="stat-card">
        <div class="stat-value">NPR <?php echo number_format($totalRevenue, 0); ?></div>
        <div class="stat-label">Revenue (excl. cancelled)</div>
    </div>

</div>

<section style="padding:60px 8% 20px;">
    <div style="display:flex; gap:20px; flex-wrap:wrap;">
        <div style="background:white; border-radius:16px; padding:20px 26px; box-shadow:0 10px 30px rgba(0,0,0,.06); flex:1; min-width:180px;">
            <div style="font-size:13px; color:var(--gray);">Customers</div>
            <div style="font-size:22px; font-weight:700; color:var(--dark);"><?php echo $usersByRole['customer']; ?></div>
        </div>
        <div style="background:white; border-radius:16px; padding:20px 26px; box-shadow:0 10px 30px rgba(0,0,0,.06); flex:1; min-width:180px;">
            <div style="font-size:13px; color:var(--gray);">Sellers</div>
            <div style="font-size:22px; font-weight:700; color:var(--dark);"><?php echo $usersByRole['seller']; ?></div>
        </div>
        <div style="background:white; border-radius:16px; padding:20px 26px; box-shadow:0 10px 30px rgba(0,0,0,.06); flex:1; min-width:180px;">
            <div style="font-size:13px; color:var(--gray);">Admins</div>
            <div style="font-size:22px; font-weight:700; color:var(--dark);"><?php echo $usersByRole['admin']; ?></div>
        </div>
    </div>
</section>

<section style="padding:20px 8% 60px;">

    <div style="display:grid; grid-template-columns:1.3fr 1fr; gap:30px;">

        <!-- RECENT ORDERS -->
        <div>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h3 style="color:var(--dark);">Recent Orders</h3>
                <a href="orders.php" style="font-size:13px; color:var(--primary); font-weight:600;">View All</a>
            </div>

            <?php if (empty($recentOrders)): ?>
                <div style="background:white; border-radius:16px; padding:30px; text-align:center; color:var(--gray); box-shadow:0 10px 30px rgba(0,0,0,.06);">
                    No orders yet.
                </div>
            <?php else: ?>
                <table class="listing-table">
                    <thead>
                        <tr><th>Order</th><th>Buyer</th><th>Total</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <?php $color = $statusColors[$order['status']] ?? ['bg' => '#f1f5f9', 'text' => '#333']; ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                                <td>NPR <?php echo number_format($order['total_amount'], 2); ?></td>
                                <td><span class="badge" style="background:<?php echo $color['bg']; ?>; color:<?php echo $color['text']; ?>;"><?php echo ucfirst($order['status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- RECENT USERS -->
        <div>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h3 style="color:var(--dark);">Newest Users</h3>
                <a href="users.php" style="font-size:13px; color:var(--primary); font-weight:600;">View All</a>
            </div>

            <div style="background:white; border-radius:16px; box-shadow:0 10px 30px rgba(0,0,0,.06); overflow:hidden;">
                <?php foreach ($recentUsers as $user): ?>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:14px 20px; border-bottom:1px solid #f1f5f9;">
                        <div>
                            <div style="font-weight:600; font-size:14px;"><?php echo htmlspecialchars($user['full_name']); ?></div>
                            <div style="font-size:12px; color:var(--gray);">@<?php echo htmlspecialchars($user['username']); ?></div>
                        </div>
                        <span class="badge badge-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

</section>

</body>

</html>