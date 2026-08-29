<?php

session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header("Location: ../homepage/html/login.html");
    exit();
}

$userId = $_SESSION['user_id'];
$error  = null;
$success = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/* ==========================================
   HANDLE FORM SUBMISSIONS
========================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $form = $_POST['form'] ?? '';

    /* ---------------------------------
       UPDATE PROFILE INFO
    --------------------------------- */
    if ($form === 'profile') {

        $fullName = trim($_POST['full_name'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        $college  = trim($_POST['college'] ?? '');
        $bio      = trim($_POST['bio'] ?? '');

        if ($fullName === '') {
            $error = "Full name can't be empty.";
        } else {
            $stmt = $pdo->prepare(
                "UPDATE users
                 SET full_name = ?, phone = ?, address = ?, college = ?, bio = ?
                 WHERE user_id = ?"
            );
            $stmt->execute([$fullName, $phone, $address, $college, $bio, $userId]);

            // Keep the navbar's greeting in sync for the rest of the session
            $_SESSION['full_name'] = $fullName;

            $_SESSION['flash'] = "Profile updated successfully.";
            header("Location: profile.php");
            exit();
        }
    }

    /* ---------------------------------
       CHANGE PASSWORD
    --------------------------------- */
    if ($form === 'password') {

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($currentPassword, $row['password'])) {
            $error = "Current password is incorrect.";
        } elseif (strlen($newPassword) < 6) {
            $error = "New password must be at least 6 characters.";
        } elseif ($newPassword !== $confirmPassword) {
            $error = "New password and confirmation don't match.";
        } else {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$hashed, $userId]);

            $_SESSION['flash'] = "Password changed successfully.";
            header("Location: profile.php");
            exit();
        }
    }
}

/* ==========================================
   FETCH CURRENT USER DATA
========================================== */

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$cartCountStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) AS total FROM cart WHERE user_id = ?");
$cartCountStmt->execute([$userId]);
$cartCount = $cartCountStmt->fetch()['total'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | PustakMandi</title>

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
                    <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                    <small>Customer</small>
                </div>
            </div>
        </div>
    </nav>
</header>

<section style="padding:50px 8%; max-width:800px; margin:0 auto;">

    <div class="section-title">
        <h2>My Profile</h2>
        <p>Manage your account details and password.</p>
    </div>

    <?php if ($success): ?>
        <div style="background:#dcfce7;color:#166534;padding:14px 18px;border-radius:12px;margin-bottom:24px;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background:#fee2e2;color:#991b1b;padding:14px 18px;border-radius:12px;margin-bottom:24px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- ACCOUNT SUMMARY -->
    <div style="background:white; border-radius:16px; padding:26px; box-shadow:0 10px 30px rgba(0,0,0,.06); margin-bottom:30px; display:flex; align-items:center; gap:20px;">
        <div style="width:70px; height:70px; border-radius:50%; background:var(--primary); color:white; display:flex; align-items:center; justify-content:center; font-size:28px; flex-shrink:0;">
            <i class="fa-solid fa-user"></i>
        </div>
        <div>
            <h3 style="margin-bottom:4px;"><?php echo htmlspecialchars($user['full_name']); ?></h3>
            <p style="color:var(--gray); font-size:14px;">@<?php echo htmlspecialchars($user['username']); ?> · <?php echo htmlspecialchars($user['email']); ?></p>
        </div>
    </div>

    <!-- PROFILE INFO FORM -->
    <div style="background:white; border-radius:16px; padding:26px; box-shadow:0 10px 30px rgba(0,0,0,.06); margin-bottom:30px;">

        <h3 style="margin-bottom:20px;">Profile Details</h3>

        <form action="profile.php" method="post">
            <input type="hidden" name="form" value="profile">

            <div style="margin-bottom:18px;">
                <label style="display:block; margin-bottom:6px; font-weight:600;">Full Name</label>
                <input type="text" name="full_name" required
                       value="<?php echo htmlspecialchars($user['full_name']); ?>"
                       style="width:100%; padding:12px; border-radius:10px; border:1px solid #ddd;">
            </div>

            <div style="margin-bottom:18px;">
                <label style="display:block; margin-bottom:6px; font-weight:600;">Phone Number</label>
                <input type="tel" name="phone"
                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                       style="width:100%; padding:12px; border-radius:10px; border:1px solid #ddd;">
            </div>

            <div style="margin-bottom:18px;">
                <label style="display:block; margin-bottom:6px; font-weight:600;">College</label>
                <input type="text" name="college"
                       value="<?php echo htmlspecialchars($user['college'] ?? ''); ?>"
                       style="width:100%; padding:12px; border-radius:10px; border:1px solid #ddd;">
            </div>

            <div style="margin-bottom:18px;">
                <label style="display:block; margin-bottom:6px; font-weight:600;">Address</label>
                <input type="text" name="address"
                       value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>"
                       style="width:100%; padding:12px; border-radius:10px; border:1px solid #ddd;">
            </div>

            <div style="margin-bottom:24px;">
                <label style="display:block; margin-bottom:6px; font-weight:600;">Bio</label>
                <textarea name="bio" rows="3"
                          style="width:100%; padding:12px; border-radius:10px; border:1px solid #ddd; font-family:inherit;"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn" style="border:none; cursor:pointer;">Save Changes</button>
        </form>

    </div>

    <!-- CHANGE PASSWORD FORM -->
    <div style="background:white; border-radius:16px; padding:26px; box-shadow:0 10px 30px rgba(0,0,0,.06);">

        <h3 style="margin-bottom:20px;">Change Password</h3>

        <form action="profile.php" method="post">
            <input type="hidden" name="form" value="password">

            <div style="margin-bottom:18px;">
                <label style="display:block; margin-bottom:6px; font-weight:600;">Current Password</label>
                <input type="password" name="current_password" required
                       style="width:100%; padding:12px; border-radius:10px; border:1px solid #ddd;">
            </div>

            <div style="margin-bottom:18px;">
                <label style="display:block; margin-bottom:6px; font-weight:600;">New Password</label>
                <input type="password" name="new_password" required minlength="6"
                       style="width:100%; padding:12px; border-radius:10px; border:1px solid #ddd;">
            </div>

            <div style="margin-bottom:24px;">
                <label style="display:block; margin-bottom:6px; font-weight:600;">Confirm New Password</label>
                <input type="password" name="confirm_password" required minlength="6"
                       style="width:100%; padding:12px; border-radius:10px; border:1px solid #ddd;">
            </div>

            <button type="submit" class="btn" style="border:none; cursor:pointer;">Update Password</button>
        </form>

    </div>

</section>

</body>

</html>