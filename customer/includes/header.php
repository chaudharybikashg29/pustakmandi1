<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$name = $_SESSION['full_name'] ?? 'Customer';
?>

<div class="topbar">

    <div class="search">

        <input type="text" placeholder="Search products...">

        <button>

            <i class="fa fa-search"></i>

        </button>

    </div>

    <div class="user-info">

        <i class="fa-regular fa-bell notification"></i>

        <img src="../images/default-user.png" alt="Profile">

        <span><?php echo htmlspecialchars($name); ?></span>

    </div>

</div>