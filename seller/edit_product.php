<?php

session_start();
require_once '../config/db.php';
require_once '../includes/image_upload.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'seller') {
    header("Location: ../homepage/html/login.html");
    exit();
}

$sellerId  = $_SESSION['user_id'];
$fullName  = $_SESSION['full_name'] ?? 'Seller';
$productId = (int) ($_GET['id'] ?? 0);
$error     = null;

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

/* ==========================================
   FETCH PRODUCT — MUST BELONG TO THIS SELLER
========================================== */

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
$stmt->execute([$productId, $sellerId]);
$product = $stmt->fetch();

if (!$product) {
    // Either it doesn't exist, or it belongs to someone else — don't leak either way
    header("Location: products.php");
    exit();
}

/* ==========================================
   HANDLE FORM SUBMISSION
========================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = (float) ($_POST['price'] ?? 0);
    $oldPrice    = trim($_POST['old_price'] ?? '');
    $categoryId  = (int) ($_POST['category_id'] ?? 0);
    $stock       = max(0, (int) ($_POST['stock'] ?? 0));

    $upload   = handleProductImageUpload($_FILES['image'] ?? []);
    $imageUrl = $product['image']; // keep existing unless a new file was chosen

    if ($title === '' || $price <= 0 || $categoryId === 0) {
        $error = "Please fill in title, price, and category.";
        $product = array_merge($product, $_POST);
    } elseif ($upload['error']) {
        $error = $upload['error'];
        $product = array_merge($product, $_POST);
    } else {

        if ($upload['success']) {
            deleteProductImage($product['image']); // remove the old file, only if it was our own upload
            $imageUrl = $upload['path'];
        }

        $oldPriceValue = ($oldPrice !== '' && (float) $oldPrice > $price) ? (float) $oldPrice : null;

        $stmt = $pdo->prepare(
            "UPDATE products
             SET title = ?, description = ?, price = ?, old_price = ?, category_id = ?, image = ?, stock = ?
             WHERE id = ? AND seller_id = ?"
        );
        $stmt->execute([
            $title, $description, $price, $oldPriceValue,
            $categoryId, $imageUrl, $stock, $productId, $sellerId
        ]);

        $_SESSION['flash'] = "\"{$title}\" was updated successfully.";
        header("Location: products.php");
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product | PustakMandi</title>

    <link rel="stylesheet" href="../homepage/css/style.css">
    <link rel="stylesheet" href="seller.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

<header>
    <nav class="navbar">
        <div class="logo">
            <img class="plogo" src="../homepage/images/pustakmandi.jpg" alt="PustakMandi Logo">
            <h2>PustakMandi</h2>
        </div>
        <ul class="nav-links">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="products.php">My Listings</a></li>
            <li><a href="orders.php">Orders</a></li>
            <li><a href="add_product.php">Add Product</a></li>
        </ul>
        <div class="icons">
            <a href="profile.php" class="profile" style="text-decoration:none; color:inherit;">
                <div class="profile-icon"><i class="fa-solid fa-store"></i></div>
                <div class="profile-info">
                    <strong><?php echo htmlspecialchars($fullName); ?></strong>
                    <small>Seller</small>
                </div>
            </a>
        </div>
    </nav>
</header>

<section style="padding:50px 8%;">

    <div class="section-title">
        <h2>Edit Product</h2>
        <p>Update details for "<?php echo htmlspecialchars($product['title']); ?>"</p>
    </div>

    <?php if ($error): ?>
        <div style="max-width:700px; margin:0 auto 24px; background:#fee2e2; color:#991b1b; padding:14px 18px; border-radius:12px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="form-card">

        <form action="edit_product.php?id=<?php echo $productId; ?>" method="post" enctype="multipart/form-data">

            <div class="form-group">
                <label>Product Title</label>
                <input type="text" name="title" required
                       value="<?php echo htmlspecialchars($product['title']); ?>">
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Price (NPR)</label>
                    <input type="number" name="price" step="0.01" min="0" required
                           value="<?php echo htmlspecialchars($product['price']); ?>">
                </div>
                <div class="form-group">
                    <label>Old Price (optional, for a sale badge)</label>
                    <input type="number" name="old_price" step="0.01" min="0"
                           value="<?php echo htmlspecialchars($product['old_price'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"
                                <?php echo ($product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Stock (quantity available)</label>
                    <input type="number" name="stock" min="0" required
                           value="<?php echo htmlspecialchars($product['stock']); ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Current Image</label>
                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt=""
                     style="width:100px; height:100px; object-fit:cover; border-radius:10px; display:block; margin-bottom:10px;">
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
                <small style="color:var(--gray);">
                    Leave empty to keep the current image. JPG, PNG, WEBP, or GIF, up to 2MB.
                </small>
            </div>

            <div style="display:flex; gap:14px;">
                <button type="submit" class="btn" style="border:none; cursor:pointer; flex:1;">
                    Save Changes
                </button>
                <a href="products.php" class="btn" style="background:#e2e8f0; color:#333; flex:1; text-align:center;">
                    Cancel
                </a>
            </div>

        </form>

    </div>

</section>

</body>

</html>