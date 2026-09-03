<?php

session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../homepage/html/login.html");
    exit();
}

$fullName = $_SESSION['full_name'] ?? 'Admin';
$adminId  = $_SESSION['user_id'];

/* ==========================================
   FILTER BY ROLE
========================================== */

$roleFilter = $_GET['role'] ?? 'all';
$allowedRoles = ['admin', 'seller', 'customer'];

if (in_array($roleFilter, $allowedRoles, true)) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = ? ORDER BY created_at DESC");
    $stmt->execute([$roleFilter]);
} else {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
}

$users = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | PustakMandi Admin</title>

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
        <h2 style="color:var(--dark);">Manage Users</h2>
        <p><?php echo count($users); ?> user<?php echo count($users) === 1 ? '' : 's'; ?></p>
    </div>

    <div class="filter-tabs">
        <a href="users.php" class="<?php echo $roleFilter === 'all' ? 'active' : ''; ?>">All</a>
        <a href="users.php?role=customer" class="<?php echo $roleFilter === 'customer' ? 'active' : ''; ?>">Customers</a>
        <a href="users.php?role=seller" class="<?php echo $roleFilter === 'seller' ? 'active' : ''; ?>">Sellers</a>
        <a href="users.php?role=admin" class="<?php echo $roleFilter === 'admin' ? 'active' : ''; ?>">Admins</a>
    </div>

    <?php if (empty($users)): ?>

        <div style="background:white; border-radius:16px; padding:50px; text-align:center; color:var(--gray); box-shadow:0 10px 30px rgba(0,0,0,.06);">
            No users found.
        </div>

    <?php else: ?>

        <table class="listing-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div style="font-weight:600;"><?php echo htmlspecialchars($user['full_name']); ?></div>
                            <div style="font-size:12px; color:var(--gray);">@<?php echo htmlspecialchars($user['username']); ?></div>
                        </td>
                        <td style="font-size:13px;">
                            <?php echo htmlspecialchars($user['email']); ?><br>
                            <span style="color:var(--gray);"><?php echo htmlspecialchars($user['phone'] ?? '—'); ?></span>
                        </td>
                        <td><span class="badge badge-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                        <td>
                            <span class="badge badge-<?php echo $user['status'] === 'active' ? 'active' : 'blocked'; ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                        <td class="table-actions">

                            <?php if ((int) $user['user_id'] === (int) $adminId): ?>

                                <span style="color:var(--gray); font-size:12px;">This is you</span>

                            <?php else: ?>

                                <form action="user_actions.php" method="post" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <input type="hidden" name="redirect" value="users.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : ''; ?>">

                                    <?php if ($user['status'] === 'active'): ?>
                                        <input type="hidden" name="action" value="block">
                                        <button type="submit" class="btn-block"
                                                onclick="return confirm('Block <?php echo htmlspecialchars(addslashes($user['full_name'])); ?>? They won\'t be able to log in.');">
                                            Block
                                        </button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="unblock">
                                        <button type="submit" class="btn-unblock">Unblock</button>
                                    <?php endif; ?>
                                </form>

                                <form action="user_actions.php" method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <input type="hidden" name="redirect" value="users.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : ''; ?>">
                                    <button type="submit" class="btn-delete"
                                            onclick="return confirm('Permanently delete <?php echo htmlspecialchars(addslashes($user['full_name'])); ?>? This also deletes their products, cart, orders, and wishlist. This cannot be undone.');">
                                        Delete
                                    </button>
                                </form>

                            <?php endif; ?>

                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>

</section>

</body>

</html>