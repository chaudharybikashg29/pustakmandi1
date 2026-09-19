    <?php

session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../homepage/html/login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: orders.php");
    exit();
}

$orderId = (int) ($_POST['order_id'] ?? 0);

/* ---------------------------------
   Marking "refunded" here does NOT move any money — there's no
   automated refund API for either payment method in use. This is
   a record-keeping action: the admin has already sent the money
   back manually (bank transfer, eSewa merchant dashboard, etc.)
   and is confirming that here so it stops showing as outstanding.
--------------------------------- */
$stmt = $pdo->prepare(
    "UPDATE orders SET refund_status = 'refunded' WHERE id = ? AND refund_status = 'requested'"
);
$stmt->execute([$orderId]);

$_SESSION['flash'] = $stmt->rowCount() > 0
    ? "Order #{$orderId} marked as refunded."
    : "That order isn't awaiting a refund.";

header("Location: " . ($_POST['redirect'] ?? 'orders.php'));
exit();