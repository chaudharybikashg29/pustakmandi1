<?php

session_start();
require_once '../config/db.php';
require_once '../includes/image_upload.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../homepage/html/login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: products.php");
    exit();
}

$action    = $_POST['action'] ?? '';
$productId = (int) ($_POST['product_id'] ?? 0);

switch ($action) {

    /* ---------------------------------
       DELETE
       Unlike a seller's own delete (which is scoped to their seller_id),
       an admin can remove any product. Still blocked from deleting one
       that has real order history, same as in user_actions.php — the
       order_items FK is RESTRICT, so it would fail anyway; better to
       give a clear message than an uncaught SQL error.
    --------------------------------- */
    case 'delete':
        $checkStmt = $pdo->prepare("SELECT 1 FROM order_items WHERE product_id = ? LIMIT 1");
        $checkStmt->execute([$productId]);

        if ($checkStmt->fetch()) {
            $_SESSION['flash'] = "This product has order history and can't be deleted — mark it out of stock instead if you want to stop new sales.";
        } else {
            $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();

            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$productId]);

            if ($stmt->rowCount() > 0 && $product) {
                deleteProductImage($product['image']);
                $_SESSION['flash'] = "Product removed.";
            } else {
                $_SESSION['flash'] = "That product no longer exists.";
            }
        }
        break;
}

header("Location: " . ($_POST['redirect'] ?? 'products.php'));
exit();