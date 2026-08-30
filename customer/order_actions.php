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

/* Ownership check — this order must belong to the logged-in customer */
$stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if ($order) {

    switch ($action) {

        /* ---------------------------------
           MARK AS RECEIVED
           Only valid coming from "shipped" — a buyer can't
           mark something delivered that was never shipped.
        --------------------------------- */
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
           Only allowed before it's shipped — once it's on
           its way, cancelling is between the buyer and seller directly.
        --------------------------------- */
        case 'cancel':
            if (in_array($order['status'], ['pending', 'confirmed'], true)) {
                $reason = trim($_POST['reason'] ?? '') ?: 'Cancelled by buyer';
                $stmt = $pdo->prepare(
                    "UPDATE orders SET status = 'cancelled', cancel_reason = ? WHERE id = ? AND user_id = ?"
                );
                $stmt->execute([$reason, $orderId, $userId]);
                $_SESSION['flash'] = "Order #{$orderId} has been cancelled.";
            } else {
                $_SESSION['flash'] = "This order can no longer be cancelled.";
            }
            break;
    }
}

header("Location: orders.php");
exit();