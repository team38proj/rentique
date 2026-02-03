<?php 
session_start();
require_once 'connectdb.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

// Check login state
$userData = null;

if (isset($_SESSION['uid'])) {
    try {
        $stmt = $db->prepare("SELECT uid, email, billing_fullname, role FROM users WHERE uid = ?");
        $stmt->execute([$_SESSION['uid']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Homepage user fetch error: " . $e->getMessage());
    }
}

// Fetch featured products
$featuredProducts = [];

try {
    $stmt = $db->prepare("
        SELECT pid, title, image, product_type, price 
        FROM products 
        ORDER BY created_at DESC 
        LIMIT 8
    ");
    $stmt->execute();
    $featuredProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Featured fetch error: " . $e->getMessage());
}

// Search results
$searchResults = [];

if (isset($_GET['search']) || isset($_GET['category']) || isset($_GET['price_range'])) {

    $search = trim($_GET['search'] ?? '');
    $category = trim($_GET['category'] ?? '');
    $price = trim($_GET['price_range'] ?? '');

    $query = "SELECT pid, title, image, product_type, price FROM products WHERE 1";
    $params = [];

    if ($search !== '') {
        $query .= " AND title LIKE ?";
        $params[] = "%$search%";
    }

    if ($category !== '' && $category !== "All Categories") {
        $query .= " AND product_type = ?";
        $params[] = $category;
    }

    if ($price !== '' && $price !== "Price Range") {
        if ($price === "£10 - £30") $query .= " AND price BETWEEN 10 AND 30";
        if ($price === "£30 - £70") $query .= " AND price BETWEEN 30 AND 70";
        if ($price === "£70 - £150") $query .= " AND price BETWEEN 70 AND 150";
        if ($price === "£150+") $query .= " AND price >= 150";
    }

    $query .= " ORDER BY title LIMIT 20";

    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Search error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentique | Home</title>
    <link rel="stylesheet" href="css/rentique.css">
    <link rel="icon" type="image/png" href="/images/rentique_logo.png">
</head>

<script src="js/theme.js"></script>


<body>

<header>
    <nav class="navbar">
        <div class="logo">
            <a href="index.php">
                <img src="images/rentique_logo.png" alt="Rentique Logo">
            </a>
            <span>rentique.</span>
        </div>

        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="productsPage.php">Shop</a></li>
            <li><a href="AboutUs.php">About</a></li>
            <li><a href="Contact.php">Contact</a></li>

            <li><a href="BasketPage.php" class="cart-icon">Basket</a></li>
             <!-- THEME TOGGLE BUTTON -->
            <li>
                <button id="themeToggle" class="btn small"> Theme</button>
            </li>

            <?php if (isset($userData['role']) && $userData['role'] === 'customer'): ?>
                <li><a href="seller_dashboard.php">Sell</a></li>
                <li><a href="user_dashboard.php"><?= htmlspecialchars($userData['billing_fullname'] ?? "Account") ?></a></li>
                <li><a href="index.php?logout=1" class="btn login">Logout</a></li>

            <?php elseif (isset($userData['role']) && $userData['role'] === 'admin'): ?>
            <!-- Admin logged in -->
                <li><a href="admin_dashboard.php">Admin</a></li>
                <li><a href="index.php?logout=1" class="btn login">Logout</a></li>

            <?php else: ?>
                <li><a href="login.php" class="btn login">Login</a></li>
                <li><a href="signup.php" class="btn signup">Sign Up</a></li>
            <?php endif; ?>

           
        </ul>
    </nav>

    <!-- SEARCH SECTION
    <section class="search-section">
        <form method="GET" action="index.php" class="search-container">

            <input type="text" 
                   name="search" 
                   placeholder="Search dresses, suits, jackets..." 
                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">

            <select name="category">
                <option>All Categories</option>
                <option>Dresses</option>
                <option>Suits</option>
                <option>Accessories</option>
                <option>Jackets</option>
                <option>Shoes</option>
            </select>

            <select name="price_range">
                <option>Price Range</option>
                <option>£10 - £30</option>
                <option>£30 - £70</option>
                <option>£70 - £150</option>
                <option>£150+</option>
            </select>

            <button class="search-btn">Search</button>
        </form>
    </section> -->
</header>

<img src="images/rentiquebanner2.png" width="100%">

<section class="hero">
    <div class="hero-content">
        <h1>Style That Moves With You</h1>
        <p>Rent. Wear. Return. Fashion freedom. Sustainable choice.</p>
        <div class="hero-buttons">
            <a href="productsPage.php" class="btn primary">Explore Collection</a>
            <a href="#" class="btn secondary">Try Virtual Try-On</a>
        </div>
    </div>
</section>

<section class="features">
    <h2>Why Choose Rentique?</h2>
    <div class="feature-grid">
        <div class="feature-card enhanced">
            <h3>Virtual Try-On</h3>
            <p>Preview outfits with AI before renting.</p>
        </div>
        <div class="feature-card enhanced">
            <h3>Easy Rentals</h3>
            <p>Delivered to your doorstep with return labels.</p>
        </div>
        <div class="feature-card enhanced">
            <h3>Feedback System</h3>
            <p>Your opinions shape Rentique.</p>
        </div>
        <div class="feature-card enhanced">
            <h3>Secure Checkout</h3>
            <p>Fast and encrypted payments.</p>
        </div>
    </div>
</section>

<section id="shop"></section>

<footer>
    <p>© 2025 Rentique. All rights reserved.</p>
</footer>


</body>
</html>
