<?php
session_start();
require_once 'connectdb.php';

// Rentique Homepage [Krish Backend] checks if user's logged in and obtains their data
$userData = null;
if (isset($_SESSION['uid'])) {
    try {
        $stmt = $db->prepare("SELECT uid, email, first_name, last_name FROM users WHERE uid = ?");
        $stmt->execute([$_SESSION['uid']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log error but don't break the page
        error_log("Database error in homepage: " . $e->getMessage());
    }
}

// Rentique Homepage [Krish Backend] fetches featured products in the shop section
$featuredProducts = [];
try {
    $stmt = $db->prepare("SELECT id, name, description, category, rental_price, image_url, size, color FROM products WHERE featured = 1 AND available = 1 LIMIT 8");
    $stmt->execute();
    $featuredProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error fetching featured products: " . $e->getMessage());
}

// Rentique Homepage [Krish Backend] search functionality
$searchResults = [];
$search = trim($_GET['search'] ?? '');
$category = $_GET['category'] ?? '';
$price_range = $_GET['price_range'] ?? '';

if ($search !== '' || $category !== '' || $price_range !== '') {
    try {
        $query = "SELECT id, name, description, category, rental_price, image_url FROM products WHERE available = 1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (name LIKE ? OR description LIKE ? OR category LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($category) && $category !== 'All Categories') {
            $query .= " AND category = ?";
            $params[] = $category;
        }

        if (!empty($price_range) && $price_range !== 'Price Range') {
            switch ($price_range) {
                case '£10 - £30':
                    $query .= " AND rental_price BETWEEN 10 AND 30";
                    break;
                case '£30 - £70':
                    $query .= " AND rental_price BETWEEN 30 AND 70";
                    break;
                case '£70 - £150':
                    $query .= " AND rental_price BETWEEN 70 AND 150";
                    break;
                case '£150+':
                    $query .= " AND rental_price >= 150";
                    break;
            }
        }

        $query .= " ORDER BY name LIMIT 20";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error during search: " . $e->getMessage());
    }
}

// Rentique Homepage [Krish Backend] login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    try {
        $stmt = $db->prepare("SELECT uid, email, password, first_name, last_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['uid'] = $user['uid'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];

            // redirection to prevent resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $loginError = "Invalid email or password";
        }
    } catch (PDOException $e) {
        $loginError = "Database error: " . $e->getMessage();
    }
}

// Rentique Homepage [Krish Backend] signup form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');

    try {
        // checks if email already exists
        $stmt = $db->prepare("SELECT uid FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $signupError = "Email already registered";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (email, password, first_name, last_name, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$email, $hashed_password, $first_name, $last_name]);

            $newUserId = $db->lastInsertId();
            $_SESSION['uid'] = $newUserId;
            $_SESSION['email'] = $email;
            $_SESSION['first_name'] = $first_name;

            // redirection to prevent resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    } catch (PDOException $e) {
        $signupError = "Database error: " . $e->getMessage();
    }
}

// Rentique Homepage [Krish Backend] manages logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentique | Home</title>
    <link rel="stylesheet" href="rentique.css">
    <link rel="icon" type="image/png" href="rentique_logo.png">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <img src="rentique_logo.png" alt="Rentique logo">
                <span>rentique.</span>
            </div>
            <ul class="nav-links">
                <li><a href="homepage.php">Home</a></li>
                <li><a href="productsPage.php">Shop</a></li>
                <li><a href="AboutUs.php">About</a></li>
                <li><a href="Contact.php">Contact</a></li>
                <?php if ($userData): ?>
                    <li><span class="welcome">Hi, <?= htmlspecialchars($userData['first_name']) ?></span></li>
                    <li><a href="?logout=1" class="btn login">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.html" class="btn login">Login</a></li>
                    <li><a href="signup.html" class="btn signup">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <section class="search-section">
            <form class="search-container" method="GET" action="homepage.php">
                <input type="text" name="search" placeholder="Search for dresses, suits, jackets, accessories..." value="<?= htmlspecialchars($search) ?>">

                <select name="category">
                    <option <?= $category === 'All Categories' ? 'selected' : '' ?>>All Categories</option>
                    <option value="Dresses" <?= $category === 'Dresses' ? 'selected' : '' ?>>Dresses</option>
                    <option value="Menswear" <?= $category === 'Menswear' ? 'selected' : '' ?>>Menswear</option>
                    <option value="Accessories" <?= $category === 'Accessories' ? 'selected' : '' ?>>Accessories</option>
                    <option value="Formal Wear" <?= $category === 'Formal Wear' ? 'selected' : '' ?>>Formal Wear</option>
                    <option value="Casual" <?= $category === 'Casual' ? 'selected' : '' ?>>Casual</option>
                </select>

                <select name="price_range">
                    <option <?= $price_range === 'Price Range' ? 'selected' : '' ?>>Price Range</option>
                    <option value="£10 - £30" <?= $price_range === '£10 - £30' ? 'selected' : '' ?>>£10 - £30</option>
                    <option value="£30 - £70" <?= $price_range === '£30 - £70' ? 'selected' : '' ?>>£30 - £70</option>
                    <option value="£70 - £150" <?= $price_range === '£70 - £150' ? 'selected' : '' ?>>£70 - £150</option>
                    <option value="£150+" <?= $price_range === '£150+' ? 'selected' : '' ?>>£150+</option>
                </select>

                <button class="search-btn" type="submit">Search</button>
            </form>
        </section>

    </header>

    <img src="rentiquebanner2.png" width="1255" alt="Rentique banner">

    <section class="hero">
        <div class="hero-content">
            <h1>Style That Moves With You</h1>
            <p>Rent. Wear. Return. Experience fashion freedom with Rentique – the modern way to shop sustainably.</p>
            <div class="hero-buttons">
                <a href="productsPage.php" class="btn primary">Explore Collection</a>
                <a href="#featured" class="btn secondary">Try Virtual Try-On</a>
            </div>
        </div>
    </section>

    <section class="features">
        <h2>Why Choose Rentique?</h2>
        <div class="feature-grid">
            <div class="feature-card enhanced">
                <h3>Virtual Try-On</h3>
                <p>See how outfits look on you using AI-powered virtual fitting before renting or purchasing.</p>
            </div>
            <div class="feature-card enhanced">
                <h3>Easy Rentals</h3>
                <p>Browse, rent, and enjoy designer outfits delivered right to your doorstep.</p>
            </div>
            <div class="feature-card enhanced">
                <h3>Feedback System</h3>
                <p>Share your experience and help us improve – your opinion shapes Rentique.</p>
            </div>
            <div class="feature-card enhanced">
                <h3>Secure Purchases</h3>
                <p>Enjoy a smooth and secure checkout with trusted payment options.</p>
            </div>
        </div>
    </section>

    <section id="shop" class="shop">
        <h2 id="featured">Featured Products</h2>
        <div class="productGrid">
            <?php if (!empty($featuredProducts)): ?>
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="product">
                        <div class="category"><?= htmlspecialchars($product['category']) ?></div>
                        <div class="title"><h3><?= htmlspecialchars($product['name']) ?></h3></div>
                        <?php if (!empty($product['image_url'])): ?>
                            <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" width="250" height="200">
                        <?php endif; ?>
                        <div class="price">£<?= htmlspecialchars($product['rental_price']) ?></div>
                        <a class="rentBtn" href="productsPage.php">View Details</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No featured products available right now.</p>
            <?php endif; ?>
        </div>
    </section>

    <?php if (!empty($searchResults)): ?>
        <section class="shop search-results">
            <h2>Search Results</h2>
            <div class="productGrid">
                <?php foreach ($searchResults as $product): ?>
                    <div class="product">
                        <div class="category"><?= htmlspecialchars($product['category']) ?></div>
                        <div class="title"><h3><?= htmlspecialchars($product['name']) ?></h3></div>
                        <?php if (!empty($product['image_url'])): ?>
                            <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" width="250" height="200">
                        <?php endif; ?>
                        <div class="price">£<?= htmlspecialchars($product['rental_price']) ?></div>
                        <a class="rentBtn" href="productsPage.php">View Details</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php elseif ($search !== '' || $category !== '' || $price_range !== ''): ?>
        <section class="shop search-results">
            <h2>Search Results</h2>
            <p>No products matched your search.</p>
        </section>
    <?php endif; ?>

    <footer>
        <p>© 2025 Rentique. All rights reserved.</p>
    </footer>
</body>
</html>
