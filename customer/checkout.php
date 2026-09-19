<?php

session_start();
require_once '../config/db.php';
require_once '../includes/esewa.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header("Location: ../homepage/html/login.html");
    exit();
}

$userId   = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? '';

$orderPlaced   = false;
$esewaRedirect = null; // holds the auto-submit form fields when paying via eSewa
$orderId       = null;
$error         = null;

/* ==========================================
   HANDLE ORDER PLACEMENT
========================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $shipName      = trim($_POST['full_name'] ?? '');
    $shipPhone     = trim($_POST['phone'] ?? '');
    $shipAddr      = trim($_POST['address'] ?? '');
    $paymentMethod = ($_POST['payment_method'] ?? 'cod') === 'esewa' ? 'esewa' : 'cod';

    if ($shipName === '' || $shipPhone === '' || $shipAddr === '') {
        $error = "Please fill in all shipping details.";
    } else {

        $stmt = $pdo->prepare(
            "SELECT c.product_id, c.quantity, p.price, p.stock, p.title
             FROM cart c JOIN products p ON p.id = c.product_id
             WHERE c.user_id = ?"
        );
        $stmt->execute([$userId]);
        $cartItems = $stmt->fetchAll();

        if (empty($cartItems)) {
            $error = "Your cart is empty.";
        } else {

            foreach ($cartItems as $item) {
                if ($item['quantity'] > $item['stock']) {
                    $error = "\"{$item['title']}\" only has {$item['stock']} left in stock.";
                    break;
                }
            }

            if (!$error) {

                $total = 0;
                foreach ($cartItems as $item) {
                    $total += $item['price'] * $item['quantity'];
                }

                $transactionUuid = $paymentMethod === 'esewa'
                    ? date('Ymd-His') . '-' . uniqid()
                    : null;

                try {
                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare(
                        "INSERT INTO orders
                            (user_id, full_name, phone, address, total_amount, status,
                             payment_method, payment_status, transaction_uuid)
                         VALUES (?, ?, ?, ?, ?, 'pending', ?, 'unpaid', ?)"
                    );
                    $stmt->execute([
                        $userId, $shipName, $shipPhone, $shipAddr, $total,
                        $paymentMethod, $transactionUuid
                    ]);
                    $orderId = $pdo->lastInsertId();

                    $itemStmt  = $pdo->prepare(
                        "INSERT INTO order_items (order_id, product_id, quantity, price)
                         VALUES (?, ?, ?, ?)"
                    );
                    $stockStmt = $pdo->prepare(
                        "UPDATE products SET stock = stock - ? WHERE id = ?"
                    );

                    foreach ($cartItems as $item) {
                        $itemStmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
                        $stockStmt->execute([$item['quantity'], $item['product_id']]);
                    }

                    $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$userId]);

                    $pdo->commit();

                    if ($paymentMethod === 'esewa') {

                        // Don't show a confirmation yet — the order exists but is
                        // unpaid until eSewa's callback confirms the payment.
                        $fields = [
                            'amount'                 => number_format($total, 2, '.', ''),
                            'tax_amount'              => '0',
                            'total_amount'            => number_format($total, 2, '.', ''),
                            'transaction_uuid'        => $transactionUuid,
                            'product_code'            => ESEWA_PRODUCT_CODE,
                            'product_service_charge'  => '0',
                            'product_delivery_charge' => '0',
                            'success_url'             => (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/esewa_success.php',
                            'failure_url'             => (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/esewa_failure.php',
                            'signed_field_names'      => 'total_amount,transaction_uuid,product_code',
                        ];
                        $fields['signature'] = esewaGenerateSignature(
                            $fields,
                            ['total_amount', 'transaction_uuid', 'product_code'],
                            ESEWA_SECRET_KEY
                        );

                        $esewaRedirect = $fields;

                    } else {
                        $orderPlaced = true;
                    }

                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Something went wrong placing your order. Please try again.";
                }
            }
        }
    }
}

/* ==========================================
   FETCH CART FOR DISPLAY (if nothing happened above)
========================================== */

$cartItems = [];
$total     = 0;

if (!$orderPlaced && !$esewaRedirect) {
    $stmt = $pdo->prepare(
        "SELECT c.quantity, p.title, p.price
         FROM cart c JOIN products p ON p.id = c.product_id
         WHERE c.user_id = ?"
    );
    $stmt->execute([$userId]);
    $cartItems = $stmt->fetchAll();

    foreach ($cartItems as $item) {
        $total += $item['price'] * $item['quantity'];
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | PustakMandi</title>

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
    </nav>
</header>

<section style="padding:60px 8%; max-width:700px; margin:0 auto;">

    <?php if ($esewaRedirect): ?>

        <!-- AUTO-SUBMITTING FORM — redirects to eSewa's sandbox payment page -->
        <div style="text-align:center; background:white; border-radius:20px; padding:50px 30px; box-shadow:0 10px 30px rgba(0,0,0,.08);">
            <i class="fa-solid fa-circle-notch fa-spin" style="font-size:40px; color:var(--primary); margin-bottom:20px;"></i>
            <h2 style="margin-bottom:12px;">Redirecting to eSewa…</h2>
            <p style="color:var(--gray);">Order #<?php echo $orderId; ?> created. Please wait, do not close this page.</p>
        </div>

        <form id="esewaForm" action="<?php echo ESEWA_FORM_URL; ?>" method="POST">
            <?php foreach ($esewaRedirect as $name => $value): ?>
                <input type="hidden" name="<?php echo htmlspecialchars($name); ?>" value="<?php echo htmlspecialchars($value); ?>">
            <?php endforeach; ?>
        </form>
        <script>document.getElementById('esewaForm').submit();</script>

    <?php elseif ($orderPlaced): ?>

        <div style="text-align:center; background:white; border-radius:20px; padding:50px 30px; box-shadow:0 10px 30px rgba(0,0,0,.08);">
            <i class="fa-solid fa-circle-check" style="font-size:60px; color:#22c55e; margin-bottom:20px;"></i>
            <h2 style="margin-bottom:12px;">Order Placed Successfully!</h2>
            <p style="color:var(--gray); margin-bottom:30px;">
                Your order <strong>#<?php echo $orderId; ?></strong> has been received and is pending confirmation.
            </p>
            <a href="dashboard.php" class="btn">Back to Dashboard</a>
        </div>

    <?php elseif (empty($cartItems)): ?>

        <div style="text-align:center;">
            <p style="color:var(--gray); margin-bottom:20px; font-size:18px;">Your cart is empty.</p>
            <a href="products.php" class="btn">Browse Products</a>
        </div>

    <?php else: ?>

        <div class="section-title">
            <h2>Checkout</h2>
            <p>Confirm your details and choose how to pay.</p>
        </div>

        <?php if ($error): ?>
            <div style="background:#fee2e2; color:#991b1b; padding:14px 18px; border-radius:12px; margin-bottom:24px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div style="background:white; border-radius:16px; padding:24px; box-shadow:0 10px 30px rgba(0,0,0,.06); margin-bottom:30px;">
            <?php foreach ($cartItems as $item): ?>
                <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f1f5f9;">
                    <span><?php echo htmlspecialchars($item['title']); ?> × <?php echo $item['quantity']; ?></span>
                    <span>NPR <?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                </div>
            <?php endforeach; ?>
            <div style="display:flex; justify-content:space-between; padding-top:14px; font-weight:700; font-size:18px;">
                <span>Total</span>
                <span style="color:var(--primary);">NPR <?php echo number_format($total, 2); ?></span>
            </div>
        </div>

        <form action="checkout.php" method="post" style="background:white; border-radius:16px; padding:26px; box-shadow:0 10px 30px rgba(0,0,0,.06);">

            <div style="margin-bottom:18px;">
                <label style="display:block; margin-bottom:6px; font-weight:600;">Full Name</label>
                <input type="text" name="full_name" required
                       value="<?php echo htmlspecialchars($fullName); ?>"
                       style="width:100%; padding:12px; border-radius:10px; border:1px solid #ddd;">
            </div>

            <div style="margin-bottom:18px;">
                <label style="display:block; margin-bottom:6px; font-weight:600;">Phone Number</label>
                <input type="tel" name="phone" required
                       style="width:100%; padding:12px; border-radius:10px; border:1px solid #ddd;">
            </div>

            <div style="margin-bottom:24px;">
                <label style="display:block; margin-bottom:6px; font-weight:600;">Delivery Address</label>
                <textarea name="address" required rows="3"
                          style="width:100%; padding:12px; border-radius:10px; border:1px solid #ddd; font-family:inherit;"></textarea>
            </div>

            <div style="margin-bottom:24px;">
                <label style="display:block; margin-bottom:10px; font-weight:600;">Payment Method</label>

                <label style="display:flex; align-items:center; gap:10px; padding:14px; border:1px solid #ddd; border-radius:10px; margin-bottom:10px; cursor:pointer;">
                    <input type="radio" name="payment_method" value="cod" checked>
                    <span>Cash on Delivery</span>
                </label>

                <label style="display:flex; align-items:center; gap:10px; padding:14px; border:1px solid #ddd; border-radius:10px; cursor:pointer;">
                    <input type="radio" name="payment_method" value="esewa">
                    <span>Pay with eSewa (test sandbox)</span>
                </label>
            </div>

            <button type="submit" class="btn" style="width:100%; border:none; cursor:pointer;">
                Place Order
            </button>

        </form>

    <?php endif; ?>

</section>

</body>

</html>