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
       ADD TO CART
       If the item is already in the cart, bump the quantity
       instead of creating a duplicate row.
    --------------------------------- */
    case 'add':
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            $_SESSION['flash'] = "That product no longer exists.";
            break;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO cart (user_id, product_id, quantity)
             VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + 1, ?)"
        );
        $stmt->execute([$userId, $productId, $product['stock']]);

        $_SESSION['flash'] = "Added to cart.";
        break;

    /* ---------------------------------
       UPDATE QUANTITY
    --------------------------------- */
    case 'update':
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));

        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if ($product) {
            $quantity = min($quantity, $product['stock']);

            $stmt = $pdo->prepare(
                "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?"
            );
            $stmt->execute([$quantity, $userId, $productId]);
        }
        break;

    /* ---------------------------------
       REMOVE ITEM
    --------------------------------- */
    case 'remove':
        $stmt = $pdo->prepare(
            "DELETE FROM cart WHERE user_id = ? AND product_id = ?"
        );
        $stmt->execute([$userId, $productId]);
        $_SESSION['flash'] = "Item removed from cart.";
        break;
}

/* Send the shopper back to wherever made sense */
$redirectTo = $_POST['redirect'] ?? 'cart.php';
header("Location: " . $redirectTo);
exit();
