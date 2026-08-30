<?php

session_start();

/* ==========================================
   CHECK LOGIN
========================================== */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../homepage/html/login.html");
    exit();
}

/* ==========================================
   CUSTOMER ONLY
========================================== */

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

/* ==========================================
   USER INFORMATION
========================================== */

$fullName = $_SESSION['full_name'] ?? 'Customer';
$username = $_SESSION['username'] ?? '';
$email = $_SESSION['email'] ?? '';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Customer Dashboard | PustakMandi</title>

    <!-- Site-wide stylesheet (same one the homepage uses) -->
    <link rel="stylesheet"
          href="../homepage/css/style.css">

    <!-- Dashboard-only additions (welcome banner, profile card, quick actions) -->
    <link rel="stylesheet"
          href="css/dashboard.css">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>


<body class="customer-page">


<!-- ==========================================
     TOP BAR
========================================== -->

<div class="top-bar">

    <div class="top-left">
        📚 Buy • Sell • Exchange Campus Essentials
    </div>

    <div class="top-right">
        <a href="#">Help</a>
        <a href="../seller/dashboard.php">Become Seller</a>
    </div>

</div>


<!-- ==========================================
     NAVBAR
========================================== -->

<header>

    <nav class="navbar">

        <!-- LOGO -->
        <div class="logo">
            <img class="plogo" src="../homepage/images/pustakmandi.jpg" alt="PustakMandi Logo">
            <h2>PustakMandi</h2>
        </div>

        <!-- NAVIGATION -->
        <ul class="nav-links">
            <li><a href="dashboard.php">Home</a></li>
            <li><a href="products.php">Products</a></li>
            <li><a href="products.php#categories">Categories</a></li>
            <li><a href="orders.php">My Orders</a></li>
        </ul>

        <!-- SEARCH -->
        <div class="search-box">
            <form action="products.php" method="get" style="display:flex; width:100%;">
                <input type="text" name="search" placeholder="Search books, calculators...">
                <button type="submit"><i class="fa-solid fa-search"></i></button>
            </form>
        </div>

        <!-- CUSTOMER ACTIONS -->
        <div class="icons">

            <a href="wishlist.php" class="icon">
                <i class="fa-regular fa-heart"></i>
                <span>Wishlist</span>
            </a>

            <a href="cart.php" class="icon">
                <i class="fa-solid fa-cart-shopping"></i>
                <span>Cart</span>
            </a>

            <!-- PROFILE -->
            <div class="profile">
                <div class="profile-icon">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="profile-info">
                    <strong><?php echo htmlspecialchars($fullName); ?></strong>
                    <small>Customer</small>
                </div>
            </div>

        </div>

    </nav>

</header>


<!-- ==========================================
     WELCOME SECTION
========================================== -->

<section class="welcome" id="home">

    <div class="welcome-content">

        <div>
            <p class="welcome-small">Welcome back 👋</p>
            <h1>Hello, <?php echo htmlspecialchars($fullName); ?>!</h1>
            <p>Find books, notes, electronics and other campus essentials at affordable prices.</p>
            <a href="products.php" class="btn">Explore Products</a>
        </div>

        <!-- QUICK PROFILE CARD -->
        <div class="user-card">
            <div class="user-avatar"><i class="fa-solid fa-user"></i></div>
            <h3><?php echo htmlspecialchars($fullName); ?></h3>
            <p>@<?php echo htmlspecialchars($username); ?></p>
            <a href="profile.php">View Profile</a>
        </div>

    </div>

</section>


<!-- ==========================================
     QUICK ACTIONS
========================================== -->

<section class="quick-actions">

    <a href="cart.php" class="action-card">
        <i class="fa-solid fa-cart-shopping"></i>
        <div><h3>My Cart</h3><p>View your selected products</p></div>
    </a>

    <a href="wishlist.php" class="action-card">
        <i class="fa-regular fa-heart"></i>
        <div><h3>Wishlist</h3><p>Products you saved</p></div>
    </a>

    <a href="orders.php" class="action-card">
        <i class="fa-solid fa-box"></i>
        <div><h3>My Orders</h3><p>Track your purchases</p></div>
    </a>

    <a href="profile.php" class="action-card">
        <i class="fa-solid fa-user"></i>
        <div><h3>My Profile</h3><p>Manage your account</p></div>
    </a>

</section>


<!-- ==========================================
     CATEGORIES
========================================== -->

<section class="categories" id="categories">

    <div class="section-title">

        <h2>Shop by Category</h2>

        <p>
            Everything you need for your campus life.
        </p>

    </div>


    <div class="category-grid">

        <a href="products.php?category=1" class="category-card">
            <i class="fa-solid fa-book"></i>
            <h3>Books</h3>
            <p>Textbooks & References</p>
        </a>

        <a href="products.php?category=2" class="category-card">
            <i class="fa-solid fa-file-lines"></i>
            <h3>Notes</h3>
            <p>Handwritten & Printed Notes</p>
        </a>

        <a href="products.php?category=3" class="category-card">
            <i class="fa-solid fa-laptop"></i>
            <h3>Laptops</h3>
            <p>Used & New Devices</p>
        </a>

        <a href="products.php?category=4" class="category-card">
            <i class="fa-solid fa-calculator"></i>
            <h3>Calculators</h3>
            <p>Scientific & Graphical</p>
        </a>

        <a href="products.php?category=5" class="category-card">
            <i class="fa-solid fa-mobile-screen-button"></i>
            <h3>Mobiles</h3>
            <p>Smartphones & Accessories</p>
        </a>

        <a href="products.php?category=6" class="category-card">
            <i class="fa-solid fa-couch"></i>
            <h3>Hostel Items</h3>
            <p>Furniture & Daily Essentials</p>
        </a>

    </div>

</section>


<!-- ==========================================
     PRODUCTS
========================================== -->

<section class="products" id="products">

    <div class="section-title">

        <h2>Recommended Products</h2>

        <p>
            Products you may be interested in.
        </p>

    </div>


    <div class="product-grid">


        <!-- PRODUCT 1 -->

        <div class="product-card">

            <button class="wishlist-btn">

                <i class="fa-regular fa-heart"></i>

            </button>

            <img
                src="https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=500"
                alt="Operating System Book">

            <div class="product-info">

                <h3>Operating System Book</h3>

                <div class="rating">
                    ⭐⭐⭐⭐⭐
                    <span>(4.9)</span>
                </div>

                <div class="price">

                    <span class="new-price">
                        NPR 850
                    </span>

                </div>

                <a href="products.php" class="cart-btn" style="display:block; text-align:center;">
                    View in Products
                </a>

            </div>

        </div>


        <!-- PRODUCT 2 -->

        <div class="product-card">

            <button class="wishlist-btn">

                <i class="fa-regular fa-heart"></i>

            </button>

            <img
                src="https://images.unsplash.com/photo-1517336714739-489689fd1ca8?w=500"
                alt="Laptop">

            <div class="product-info">

                <h3>HP Pavilion Laptop</h3>

                <div class="rating">
                    ⭐⭐⭐⭐☆
                    <span>(4.6)</span>
                </div>

                <div class="price">

                    <span class="new-price">
                        NPR 52,000
                    </span>

                </div>

                <a href="products.php" class="cart-btn" style="display:block; text-align:center;">
                    View in Products
                </a>

            </div>

        </div>


        <!-- PRODUCT 3 -->

        <div class="product-card">

            <button class="wishlist-btn">

                <i class="fa-regular fa-heart"></i>

            </button>

            <img
                src="https://images.unsplash.com/photo-1580910051074-3eb694886505?w=500"
                alt="Calculator">

            <div class="product-info">

                <h3>Casio FX-991ES Plus</h3>

                <div class="rating">
                    ⭐⭐⭐⭐⭐
                    <span>(5.0)</span>
                </div>

                <div class="price">

                    <span class="new-price">
                        NPR 1,800
                    </span>

                </div>

                <a href="products.php" class="cart-btn" style="display:block; text-align:center;">
                    View in Products
                </a>

            </div>

        </div>


        <!-- PRODUCT 4 -->

        <div class="product-card">

            <button class="wishlist-btn">

                <i class="fa-regular fa-heart"></i>

            </button>

            <img
                src="https://images.unsplash.com/photo-1512436991641-6745cdb1723f?w=500"
                alt="Backpack">

            <div class="product-info">

                <h3>College Backpack</h3>

                <div class="rating">
                    ⭐⭐⭐⭐☆
                    <span>(4.5)</span>
                </div>

                <div class="price">

                    <span class="new-price">
                        NPR 1,500
                    </span>

                </div>

                <a href="products.php" class="cart-btn" style="display:block; text-align:center;">
                    View in Products
                </a>

            </div>

        </div>


    </div>

</section>

<!-- ==========================================
     FOOTER
========================================== -->

<footer>

    <div class="footer-container">

        <div class="footer-col">
            <h2>PustakMandi</h2>
            <p>Nepal's trusted campus marketplace where students buy, sell and exchange college essentials.</p>
        </div>

        <div class="footer-col">
            <h3>Customer</h3>
            <ul>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="orders.php">My Orders</a></li>
                <li><a href="wishlist.php">Wishlist</a></li>
                <li><a href="cart.php">Cart</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h3>Support</h3>
            <ul>
                <li><a href="#">Help Center</a></li>
                <li><a href="#">Privacy Policy</a></li>
                <li><a href="#">Terms</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h3>Account</h3>
            <ul>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>

    </div>

    <div class="copyright">© 2026 PustakMandi | All Rights Reserved</div>

</footer>



<script src="js\dashboard.js"></script>

</body>

</html>