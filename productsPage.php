<?php
session_start();
require_once 'connectdb.php';

$uid = $_SESSION['uid'] ?? null;

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


/* FETCH FILTERS */
$search = trim($_GET['search'] ?? "");
$category = $_GET['category'] ?? "";
$price = $_GET['price'] ?? "";

$query = "SELECT pid, title, image, product_type, price FROM products WHERE 1";
$params = [];

/* SEARCH FILTER */
if ($search !== "") {
    $query .= " AND (title LIKE ? OR product_type LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

/* CATEGORY FILTER */
if ($category !== "" && $category !== "All") {
    $query .= " AND product_type = ?";
    $params[] = $category;
}

/* PRICE FILTER */
if ($price !== "") {
    if ($price === "10-30") {
        $query .= " AND price BETWEEN 10 AND 30";
    } elseif ($price === "30-70") {
        $query .= " AND price BETWEEN 30 AND 70";
    } elseif ($price === "70-150") {
        $query .= " AND price BETWEEN 70 AND 150";
    } elseif ($price === "150+") {
        $query .= " AND price >= 150";
    }
}

/* RUN QUERY */
$stmt = $db->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* HANDLE ADD TO BASKET */
if (isset($_POST['add_to_basket'])) {
    if (!$uid) {
        header("Location: login.php");
        exit;
    }

    $pid = intval($_POST['pid']);

    /* Retrieve product info */
    $stmt = $db->prepare("SELECT pid, title, image, product_type, price FROM products WHERE pid = ?");
    $stmt->execute([$pid]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($p) {
        $insert = $db->prepare("
            INSERT INTO basket (uid, pid, title, image, product_type, price, quantity)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        $insert->execute([
            $uid,
            $p['pid'],
            $p['title'],
            $p['image'],
            $p['product_type'],
            $p['price']
        ]);

        header("Location: BasketPage.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rentique | Products</title>
    <link rel="stylesheet" href="css/rentique.css">
    <link rel="icon" type="image/png" href="images/rentique_logo.png">
    <script src="js/theme.js" defer></script>
</head>

<body>

<header>
    <nav class="navbar">
        <div class="logo">
            <a href="index.php">
                <img src="images/rentique_logo.png">
            </a>
            <span>rentique.</span>
        </div>

        
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="productsPage.php" class="active">Shop</a></li>
            <li><a href="AboutUs.php">About</a></li>
            <li><a href="Contact.php">Contact</a></li>
            <li>
                <a href="BasketPage.php" class="cart-icon">Basket</a>
            </li>
            <button id="themeToggle">Theme</button>

           
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
</header>


<section class="search-section">
    <form class="search-container" method="GET" action="productsPage.php">

        <input type="text" name="search" placeholder="Search outfits, jackets, accessories..."
               value="<?= htmlspecialchars($search) ?>">

        <select name="category">
            <option value="All">All Categories</option>
            <option value="Dresses" <?= $category === "Dresses" ? "selected" : "" ?>>Dresses</option>
            <option value="Menswear" <?= $category === "Menswear" ? "selected" : "" ?>>Menswear</option>
            <option value="Accessories" <?= $category === "Accessories" ? "selected" : "" ?>>Accessories</option>
            <option value="Formal" <?= $category === "Formal" ? "selected" : "" ?>>Formal Wear</option>
        </select>

        <select name="price">
            <option value="">Price Range</option>
            <option value="10-30" <?= $price === "10-30" ? "selected" : "" ?>>£10 - £30</option>
            <option value="30-70" <?= $price === "30-70" ? "selected" : "" ?>>£30 - £70</option>
            <option value="70-150" <?= $price === "70-150" ? "selected" : "" ?>>£70 - £150</option>
            <option value="150+" <?= $price === "150+" ? "selected" : "" ?>>£150+</option>
        </select>

        <button class="search-btn">Search</button>

    </form>
</section>


<section class="products-grid">

    <?php if (empty($products)): ?>
        <p class="noResults">No products found.</p>
    <?php else: ?>

        <?php foreach ($products as $p): ?>
            <div class="product-card">
                <img src="images/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['title']) ?>">

                <h3><?= htmlspecialchars($p['title']) ?></h3>
                <p><?= htmlspecialchars($p['product_type']) ?></p>
                <p class="price">£<?= number_format($p['price'], 2) ?></p>

                <form method="POST">
                    <input type="hidden" name="pid" value="<?= $p['pid'] ?>">
                    <button type="submit" name="add_to_basket" class="btn primary">
                        Add to Basket
                    </button>
                </form>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>

</section>

<footer>
    <p>© 2025 Rentique. All rights reserved.</p>
</footer>

</body>
</html>
