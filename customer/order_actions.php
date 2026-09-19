<?php

session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header("Location: ../homepage/html/login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: orders.php");
    exit();
}

$userId  = $_SESSION['user_id'];
$orderId = (int) ($_POST['order_id'] ?? 0);
$action  = $_POST['action'] ?? '';

$stmt = $pdo->prepare("SELECT status, payment_status FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if ($order) {

    switch ($action) {

        case 'mark_received':
            if ($order['status'] === 'shipped') {
                $stmt = $pdo->prepare("UPDATE orders SET status = 'delivered' WHERE id = ? AND user_id = ?");
                $stmt->execute([$orderId, $userId]);
                $_SESSION['flash'] = "Order #{$orderId} marked as delivered. Thanks for confirming!";
            } else {
                $_SESSION['flash'] = "This order isn't marked as shipped yet.";
            }
            break;

        /* ---------------------------------
           CANCEL
           If this order was already paid for (eSewa), cancelling doesn't
           refund anything automatically — no gateway we use offers that
           as a simple API call. Instead it's flagged refund_status =
           'requested', which shows up for an admin to process the actual
           refund manually (bank transfer, eSewa merchant dashboard, etc.)
           and mark it done from admin/orders.php.
        --------------------------------- */
        case 'cancel':
            if (in_array($order['status'], ['pending', 'confirmed'], true)) {
                $reason = trim($_POST['reason'] ?? '') ?: 'Cancelled by buyer';

                $needsRefund = $order['payment_status'] === 'paid';

                $stmt = $pdo->prepare(
                    "UPDATE orders
                     SET status = 'cancelled', cancel_reason = ?, refund_status = ?
                     WHERE id = ? AND user_id = ?"
                );
                $stmt->execute([
                    $reason,
                    $needsRefund ? 'requested' : 'none',
                    $orderId, $userId
                ]);

                $_SESSION['flash'] = $needsRefund
                    ? "Order #{$orderId} cancelled. Since you already paid, a refund has been requested — this is processed manually, so allow some time."
                    : "Order #{$orderId} has been cancelled.";
            } else {
                $_SESSION['flash'] = "This order can no longer be cancelled.";
            }
            break;
    }
}

header("Location: orders.php");
exit();