<?php
session_start();
require_once 'security.php';

// Database connection
$conn = new mysqli("localhost", "root", "", "simple_ecommerce");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get products
$category = isset($_GET['category']) ? sanitize($_GET['category']) : 'all';
$sql = "SELECT * FROM products WHERE stock > 0";
if ($category != 'all') {
    $sql .= " AND category = '$category'";
}
$sql .= " ORDER BY id DESC";
$products = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SimpleStore - Shoes & Clothes</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="logo">
                <h1>🛍️ SimpleStore</h1>
                <p>Quality Shoes & Fashion</p>
            </div>
            <nav class="nav">
                <a href="index.php">Home</a>
                <a href="index.php?category=shoes">👟 Shoes</a>
                <a href="index.php?category=clothes">👕 Clothes</a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="cart.php" class="cart-link">
                        🛒 Cart (<span id="cart-count">0</span>)
                    </a>
                    <a href="orders.php">📦 Orders</a>
                    <a href="logout.php">🚪 Logout</a>
                    <span class="welcome">Hi, <?php echo escape($_SESSION['username']); ?></span>
                <?php else: ?>
                    <a href="login.php">🔐 Login</a>
                    <a href="register.php">📝 Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h2>Welcome to SimpleStore</h2>
            <p>Discover the best shoes and clothes at amazing prices</p>
            <a href="#products" class="btn btn-primary">Shop Now</a>
        </div>
    </section>

    <!-- Products Section -->
    <section id="products" class="products">
        <div class="container">
            <h2 class="section-title">
                <?php 
                    if($category == 'shoes') echo "👟 Shoes Collection";
                    elseif($category == 'clothes') echo "👕 Clothing Collection";
                    else echo "✨ All Products";
                ?>
            </h2>
            
            <div class="products-grid" id="products-grid">
                <?php while($product = $products->fetch_assoc()): ?>
                <div class="product-card" data-product-id="<?php echo $product['id']; ?>">
                    <img src="<?php echo $product['image_url']; ?>" alt="<?php echo escape($product['name']); ?>">
                    <div class="product-info">
                        <h3><?php echo escape($product['name']); ?></h3>
                        <p class="product-desc"><?php echo escape(substr($product['description'], 0, 80)); ?>...</p>
                        <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                        <div class="product-stock <?php echo $product['stock'] > 0 ? 'in-stock' : 'out-stock'; ?>">
                            <?php echo $product['stock'] > 0 ? "✅ In Stock ({$product['stock']})" : "❌ Out of Stock"; ?>
                        </div>
                        
                        <?php if(isset($_SESSION['user_id']) && $product['stock'] > 0): ?>
                        <div class="add-to-cart-form">
                            <input type="number" class="qty-input" value="1" min="1" max="<?php echo $product['stock']; ?>">
                            <button class="btn-add-cart" data-id="<?php echo $product['id']; ?>">
                                🛒 Add to Cart
                            </button>
                        </div>
                        <?php elseif(!isset($_SESSION['user_id'])): ?>
                        <a href="login.php" class="btn-login-buy">🔐 Login to Buy</a>
                        <?php else: ?>
                        <button class="btn-disabled" disabled>Out of Stock</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 SimpleStore. All rights reserved.</p>
            <p>Quality products, secure shopping</p>
        </div>
    </footer>

    <script src="script.js"></script>
    <script>
        // Update cart count on page load
        updateCartCount();
    </script>
</body>
</html>