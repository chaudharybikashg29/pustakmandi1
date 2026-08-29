<?php

session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header("Location: ../homepage/html/login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: products.php");
    exit();
}

$userId    = $_SESSION['user_id'];
$action    = $_POST['action'] ?? '';
$productId = (int) ($_POST['product_id'] ?? 0);

switch ($action) {

    /* ---------------------------------
       ADD
    --------------------------------- */
    case 'add':
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)"
        );
        $stmt->execute([$userId, $productId]);
        $_SESSION['flash'] = "Added to wishlist.";
        break;

    /* ---------------------------------
       REMOVE
    --------------------------------- */
    case 'remove':
        $stmt = $pdo->prepare(
            "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?"
        );
        $stmt->execute([$userId, $productId]);
        $_SESSION['flash'] = "Removed from wishlist.";
        break;

    /* ---------------------------------
       TOGGLE  (used by the heart button on product cards)
    --------------------------------- */
    case 'toggle':
        $checkStmt = $pdo->prepare(
            "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?"
        );
        $checkStmt->execute([$userId, $productId]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            $stmt = $pdo->prepare(
                "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?"
            );
            $stmt->execute([$userId, $productId]);
            $_SESSION['flash'] = "Removed from wishlist.";
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)"
            );
            $stmt->execute([$userId, $productId]);
            $_SESSION['flash'] = "Added to wishlist.";
        }
        break;

    /* ---------------------------------
       MOVE TO CART  (used from the wishlist page itself)
    --------------------------------- */
    case 'move_to_cart':
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if ($product && $product['stock'] > 0) {
            $stmt = $pdo->prepare(
                "INSERT INTO cart (user_id, product_id, quantity)
                 VALUES (?, ?, 1)
                 ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + 1, ?)"
            );
            $stmt->execute([$userId, $productId, $product['stock']]);

            $stmt = $pdo->prepare(
                "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?"
            );
            $stmt->execute([$userId, $productId]);

            $_SESSION['flash'] = "Moved to cart.";
        } else {
            $_SESSION['flash'] = "That item is out of stock.";
        }
        break;
}

$redirectTo = $_POST['redirect'] ?? 'wishlist.php';
header("Location: " . $redirectTo);
exit();