<?php

session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'seller') {
    header("Location: ../homepage/html/login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: products.php");
    exit();
}

$sellerId  = $_SESSION['user_id'];
$action    = $_POST['action'] ?? '';
$productId = (int) ($_POST['product_id'] ?? 0);

switch ($action) {

    /* ---------------------------------
       DELETE
       The "AND seller_id = ?" is the important part —
       without it, any seller could delete anyone's listing
       just by guessing a product_id in the form.
    --------------------------------- */
    case 'delete':
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
        $stmt->execute([$productId, $sellerId]);

        $_SESSION['flash'] = $stmt->rowCount() > 0
            ? "Listing deleted."
            : "That listing couldn't be deleted (it may not belong to you).";
        break;
}

header("Location: " . ($_POST['redirect'] ?? 'products.php'));
exit();