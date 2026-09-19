<?php

session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header("Location: ../homepage/html/login.html");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled | PustakMandi</title>
    <link rel="stylesheet" href="../homepage/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

<section style="padding:80px 8%; max-width:600px; margin:0 auto;">

    <div style="text-align:center; background:white; border-radius:20px; padding:50px 30px; box-shadow:0 10px 30px rgba(0,0,0,.08);">
        <i class="fa-solid fa-circle-xmark" style="font-size:60px; color:#ef4444; margin-bottom:20px;"></i>
        <h2 style="margin-bottom:12px;">Payment Cancelled</h2>
        <p style="color:var(--gray); margin-bottom:30px;">
            Your payment wasn't completed. The order was created but remains unpaid —
            you can find it under My Orders and it's still awaiting payment.
        </p>
        <a href="orders.php" class="btn">View My Orders</a>
    </div>

</section>

</body>

</html>