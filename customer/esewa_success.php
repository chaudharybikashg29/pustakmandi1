<?php

session_start();
require_once '../config/db.php';
require_once '../includes/esewa.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header("Location: ../homepage/html/login.html");
    exit();
}

$userId  = $_SESSION['user_id'];
$verified = false;
$message  = "We couldn't verify this payment.";
$orderId  = null;

/* ==========================================
   eSewa sends back ?data=<base64-encoded JSON>
   containing the transaction result AND its own signature.
   We must re-verify that signature ourselves — never trust
   the "status" field just because the browser was redirected here.
   Anyone could hand-craft a URL to this page otherwise.
========================================== */

$rawData = $_GET['data'] ?? '';

if ($rawData !== '') {

    $decoded  = base64_decode($rawData);
    $response = json_decode($decoded, true);

    if ($response && isset($response['signature'], $response['signed_field_names'])) {

        $signedFields = explode(',', $response['signed_field_names']);
        $expectedSignature = esewaGenerateSignature($response, $signedFields, ESEWA_SECRET_KEY);

        if (hash_equals($expectedSignature, $response['signature'])
            && ($response['status'] ?? '') === 'COMPLETE') {

            $transactionUuid = $response['transaction_uuid'] ?? '';

            /* Confirm this transaction belongs to an order owned by
               the logged-in user — never trust the callback alone. */
            $stmt = $pdo->prepare(
                "SELECT id, total_amount, payment_status FROM orders
                 WHERE transaction_uuid = ? AND user_id = ?"
            );
            $stmt->execute([$transactionUuid, $userId]);
            $order = $stmt->fetch();

            if ($order) {
                $orderId = $order['id'];

                // Amount must match exactly what we charged for — protects
                // against a tampered callback claiming a different amount.
                $expectedAmount = number_format($order['total_amount'], 2, '.', '');
                $paidAmount     = number_format((float) ($response['total_amount'] ?? 0), 2, '.', '');

                if ($expectedAmount === $paidAmount) {

                    if ($order['payment_status'] !== 'paid') {
                        $stmt = $pdo->prepare(
                            "UPDATE orders SET payment_status = 'paid', status = 'confirmed' WHERE id = ?"
                        );
                        $stmt->execute([$orderId]);
                    }

                    $verified = true;
                    $message  = "Payment confirmed. Thank you!";
                } else {
                    $message = "Payment amount didn't match this order. Please contact support.";
                }
            } else {
                $message = "Couldn't find a matching order for this payment.";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Result | PustakMandi</title>
    <link rel="stylesheet" href="../homepage/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

<section style="padding:80px 8%; max-width:600px; margin:0 auto;">

    <div style="text-align:center; background:white; border-radius:20px; padding:50px 30px; box-shadow:0 10px 30px rgba(0,0,0,.08);">

        <?php if ($verified): ?>
            <i class="fa-solid fa-circle-check" style="font-size:60px; color:#22c55e; margin-bottom:20px;"></i>
            <h2 style="margin-bottom:12px;">Payment Successful</h2>
            <p style="color:var(--gray); margin-bottom:30px;">
                <?php echo htmlspecialchars($message); ?>
                <?php if ($orderId): ?> Order #<?php echo $orderId; ?>.<?php endif; ?>
            </p>
        <?php else: ?>
            <i class="fa-solid fa-triangle-exclamation" style="font-size:60px; color:#f59e0b; margin-bottom:20px;"></i>
            <h2 style="margin-bottom:12px;">Payment Not Confirmed</h2>
            <p style="color:var(--gray); margin-bottom:30px;"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <a href="orders.php" class="btn">View My Orders</a>

    </div>

</section>

</body>

</html>