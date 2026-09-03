<?php

session_start();
require_once '../config/db.php';
require_once '../includes/image_upload.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../homepage/html/login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: users.php");
    exit();
}

$adminId = $_SESSION['user_id'];
$action  = $_POST['action'] ?? '';
$userId  = (int) ($_POST['user_id'] ?? 0);

/* An admin can never block or delete their own account through this form —
   that would be an easy way to accidentally lock yourself out. */
if ($userId === (int) $adminId) {
    $_SESSION['flash'] = "You can't perform this action on your own account.";
    header("Location: " . ($_POST['redirect'] ?? 'users.php'));
    exit();
}

switch ($action) {

    case 'block':
        $stmt = $pdo->prepare("UPDATE users SET status = 'blocked' WHERE user_id = ?");
        $stmt->execute([$userId]);
        $_SESSION['flash'] = "User blocked.";
        break;

    case 'unblock':
        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE user_id = ?");
        $stmt->execute([$userId]);
        $_SESSION['flash'] = "User unblocked.";
        break;

    /* ---------------------------------
       DELETE
       products.seller_id is ON DELETE SET NULL — so simply deleting the
       user would leave their listings behind as orphaned rows with broken
       image links. We'd rather remove them entirely.

       BUT order_items.product_id has no cascade rule (defaults to RESTRICT),
       so a product that was ever actually ordered can't be hard-deleted —
       doing so would throw a foreign key violation. Those are left alone
       (they'll just lose their seller_id automatically) so order history
       stays intact for whoever bought them.
    --------------------------------- */
    case 'delete':
        $stmt = $pdo->prepare("SELECT id, image FROM products WHERE seller_id = ?");
        $stmt->execute([$userId]);
        $ownedProducts = $stmt->fetchAll();

        $deletableIds = [];
        foreach ($ownedProducts as $product) {
            $checkStmt = $pdo->prepare("SELECT 1 FROM order_items WHERE product_id = ? LIMIT 1");
            $checkStmt->execute([$product['id']]);

            if (!$checkStmt->fetch()) {
                // Never ordered — safe to fully remove, image included
                deleteProductImage($product['image']);
                $deletableIds[] = $product['id'];
            }
            // else: leave it — it'll become seller_id = NULL when the user is deleted below
        }

        if (!empty($deletableIds)) {
            $placeholders = implode(',', array_fill(0, count($deletableIds), '?'));
            $pdo->prepare("DELETE FROM products WHERE id IN ({$placeholders})")->execute($deletableIds);
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);

        $_SESSION['flash'] = "User deleted" . (count($ownedProducts) > count($deletableIds)
            ? ", though some previously-sold listings were kept for order history."
            : " along with all their data.");
        break;
}

header("Location: " . ($_POST['redirect'] ?? 'users.php'));
exit();