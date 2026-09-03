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
$status  = $_POST['status'] ?? '';
$reason  = trim($_POST['reason'] ?? '');

/* Unlike seller/order_status_actions.php, admin is allowed to set
   'delivered' directly — the referee role covers correcting mistakes
   (e.g. a buyer forgot to click "Mark as Received") without needing
   to impersonate the buyer's account. */
$allowedStatuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];

if (in_array($status, $allowedStatuses, true)) {

    if ($status === 'cancelled') {
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, cancel_reason = ? WHERE id = ?");
        $stmt->execute([$status, $reason !== '' ? $reason : 'Cancelled by admin', $orderId]);
    } else {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $orderId]);
    }

    $_SESSION['flash'] = "Order #{$orderId} marked as " . ucfirst($status) . ".";
}

header("Location: " . ($_POST['redirect'] ?? 'orders.php'));
exit();