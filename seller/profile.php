<?php

session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'seller') {
    header("Location: ../homepage/html/login.html");
    exit();
}

$sellerId = $_SESSION['user_id'];
$error    = null;
$success  = $_SESSION['flash'] ?? null;
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
            $stmt->execute([$fullName, $phone, $address, $college, $bio, $sellerId]);

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
        $stmt->execute([$sellerId]);
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
            $stmt->execute([$hashed, $sellerId]);

            $_SESSION['flash'] = "Password changed successfully.";
            header("Location: profile.php");
            exit();
        }
    }
}

/* ==========================================
   FETCH CURRENT SELLER DATA
========================================== */

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$sellerId]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM products WHERE seller_id = ?");
$stmt->execute([$sellerId]);
$totalListings = $stmt->fetch()['total'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | PustakMandi Seller</title>

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
            <div class="profile">
                <div class="profile-icon"><i class="fa-solid fa-store"></i></div>
                <div class="profile-info">
                    <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                    <small>Seller</small>
                </div>
            </div>
        </div>
    </nav>
</header>

<section style="padding:50px 8%; max-width:800px; margin:0 auto;">

    <div class="section-title" style="text-align:left;">
        <h2 style="color:var(--dark);">My Profile</h2>
        <p>Manage your seller account details and password.</p>
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
            <i class="fa-solid fa-store"></i>
        </div>
        <div>
            <h3 style="margin-bottom:4px;"><?php echo htmlspecialchars($user['full_name']); ?></h3>
            <p style="color:var(--gray); font-size:14px;">
                @<?php echo htmlspecialchars($user['username']); ?> · <?php echo htmlspecialchars($user['email']); ?>
            </p>
            <p style="color:var(--primary); font-size:13px; font-weight:600; margin-top:4px;">
                <?php echo $totalListings; ?> product<?php echo $totalListings === 1 ? '' : 's'; ?> listed
            </p>
        </div>
    </div>

    <!-- PROFILE INFO FORM -->
    <div class="form-card" style="margin-bottom:30px;">

        <h3 style="margin-bottom:20px;">Profile Details</h3>

        <form action="profile.php" method="post">
            <input type="hidden" name="form" value="profile">

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required
                       value="<?php echo htmlspecialchars($user['full_name']); ?>">
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone"
                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>College</label>
                <input type="text" name="college"
                       value="<?php echo htmlspecialchars($user['college'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Address</label>
                <input type="text" name="address"
                       value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Bio</label>
                <textarea name="bio" rows="3"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn" style="border:none; cursor:pointer;">Save Changes</button>
        </form>

    </div>

    <!-- CHANGE PASSWORD FORM -->
    <div class="form-card">

        <h3 style="margin-bottom:20px;">Change Password</h3>

        <form action="profile.php" method="post">
            <input type="hidden" name="form" value="password">

            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" required>
            </div>

            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" required minlength="6">
            </div>

            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required minlength="6">
            </div>

            <button type="submit" class="btn" style="border:none; cursor:pointer;">Update Password</button>
        </form>

    </div>

</section>

</body>

</html>