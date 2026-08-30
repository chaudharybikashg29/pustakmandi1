<?php

session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'seller') {
    header("Location: ../homepage/html/login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: orders.php");
    exit();
}

$sellerId = $_SESSION['user_id'];
$orderId  = (int) ($_POST['order_id'] ?? 0);
$status   = $_POST['status'] ?? '';
$reason   = trim($_POST['reason'] ?? '');

// Sellers can move an order forward or cancel it — but "delivered" is the
// customer's call to make (they're the only one who actually received it).
$allowedStatuses = ['pending', 'confirmed', 'shipped', 'cancelled'];

if (in_array($status, $allowedStatuses, true)) {

    $checkStmt = $pdo->prepare(
        "SELECT 1 FROM order_items oi
         JOIN products p ON p.id = oi.product_id
         WHERE oi.order_id = ? AND p.seller_id = ?
         LIMIT 1"
    );
    $checkStmt->execute([$orderId, $sellerId]);

    if ($checkStmt->fetch()) {
        if ($status === 'cancelled') {
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, cancel_reason = ? WHERE id = ?");
            $stmt->execute([$status, $reason !== '' ? $reason : 'Cancelled by seller', $orderId]);
        } else {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $orderId]);
        }
        $_SESSION['flash'] = "Order #{$orderId} marked as " . ucfirst($status) . ".";
    } else {
        $_SESSION['flash'] = "You don't have permission to update that order.";
    }
}

header("Location: orders.php");
exit();