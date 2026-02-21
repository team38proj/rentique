<?php
session_start();
require_once 'connectdb.php';

$uid = $_SESSION['uid'] ?? null;

$userData = null;
if (isset($_SESSION['uid'])) {
    try {
        $stmt = $db->prepare("SELECT uid, email, billing_fullname, role FROM users WHERE uid = ?");
        $stmt->execute([$_SESSION['uid']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Products page user fetch error: " . $e->getMessage());
    }
}
function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$addError = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_basket'])) {
    if (!$uid) {
        header("Location: login.php");
        exit;
    }

    $pid = intval($_POST['pid'] ?? 0);
    $rentalDays = intval($_POST['rental_days'] ?? 1);
    if ($rentalDays < 1) $rentalDays = 1;
    if ($rentalDays > 30) $rentalDays = 30;

    $stmt = $db->prepare("
        SELECT pid, uid AS seller_uid, title, image, product_type, price
        FROM products
        WHERE pid = ? AND is_available = 1
        LIMIT 1
    ");
    $stmt->execute([$pid]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$p) {
        $addError = "Item not available.";
    } else {
        $sellerUid = (int)$p['seller_uid'];

        if ($sellerUid === (int)$uid) {
            $addError = "You cannot add your own listing to your basket.";
        } else {
            $insert = $db->prepare("
                INSERT INTO basket (uid, pid, seller_uid, title, image, product_type, price, quantity, rental_days)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)
            ");
            $insert->execute([
                (int)$uid,
                (int)$p['pid'],
                (int)$sellerUid,
                $p['title'],
                $p['image'],
                $p['product_type'],
                $p['price'],
                (int)$rentalDays
            ]);

            header("Location: basketPage.php");
            exit;
        }
    }
}

$search = trim($_GET['search'] ?? "");
$category = $_GET['category'] ?? "All";
$price = $_GET['price'] ?? "";
$sort = $_GET['sort'] ?? "newest";

$sql = "
    SELECT
        p.pid, p.title, p.image, p.product_type, p.price, p.description,
        u.username, p.uid AS seller_uid,
        COALESCE(AVG(r.stars), 0) AS avg_rating,
        COUNT(r.id) AS rating_count
    FROM products p
    JOIN users u ON p.uid = u.uid
    LEFT JOIN product_ratings r ON r.pid = p.pid
    WHERE p.is_available = 1
";

$params = [];

if ($search !== "") {
    $sql .= " AND (p.title LIKE ? OR p.product_type LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category !== "" && $category !== "All") {
    $sql .= " AND p.product_type = ?";
    $params[] = $category;
}

if ($price !== "") {
    if ($price === "under-25") $sql .= " AND p.price < 25";
    if ($price === "25-50") $sql .= " AND p.price BETWEEN 25 AND 50";
    if ($price === "50-100") $sql .= " AND p.price BETWEEN 50 AND 100";
    if ($price === "100+") $sql .= " AND p.price >= 100";
}

$sql .= " GROUP BY p.pid ";

if ($sort === "price-low") $sql .= " ORDER BY p.price ASC";
elseif ($sort === "price-high") $sql .= " ORDER BY p.price DESC";
elseif ($sort === "rating") $sql .= " ORDER BY avg_rating DESC, rating_count DESC";
else $sql .= " ORDER BY p.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$catStmt = $db->query("SELECT DISTINCT product_type FROM products WHERE is_available = 1 ORDER BY product_type");
$categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentique | Shop</title>
    <link rel="stylesheet" href="css/rentique.css">
    <link rel="icon" type="image/png" href="images/rentique_logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="js/theme.js" defer></script>

    <style>
        .cart-icon {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cart-icon svg {
            width: 20px;
            height: 20px;
            stroke: #eaeaea;
            transition: all 0.3s ease;
        }
        html.light-mode .cart-icon svg {
            stroke: #000000;
        }
        .cart-icon:hover svg {
            stroke: #00FF00;
        }
        #themeToggle {
            background: transparent;
            border: 1px solid #00FF00;
            color: #ffffff;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }
        html.light-mode #themeToggle {
            color: #333333;
            border-color: #00FF00;
            background: transparent;
        }
        #themeToggle:hover {
            background: transparent;
            border-color: #d2ff4c;
            transform: scale(1.1);
        }
    </style>

    <style>
        :root {
            --primary: #a3ff00;
            --primary-dark: #8cd900;
            --primary-light: #b3ff33;
            --bg-dark: #0a0a0a;
            --bg-card: #111111;
            --bg-hover: #1a1a1a;
            --border-color: #2a2a2a;
            --text-primary: #eaeaea;
            --text-secondary: #cccccc;
            --text-muted: #888888;
            --shadow: 0 5px 15px rgba(0,0,0,0.3);
            --radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
        }

        .shop-container {
            max-width: 1400px;
            margin: 25px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 25px;
        }

        .filters-sidebar {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 18px;
            height: fit-content;
            position: sticky;
            top: 90px;
        }

        .filters-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
        }

        .filters-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary);
        }

        .clear-filters {
            color: var(--text-muted);
            font-size: 0.75rem;
            text-decoration: none;
        }

        .filter-section {
            margin-bottom: 20px;
        }

        .filter-section h4 {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .category-list {
            list-style: none;
        }

        .category-item {
            margin-bottom: 6px;
        }

        .category-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 10px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 6px;
            background: var(--bg-hover);
            border: 1px solid var(--border-color);
            font-size: 0.85rem;
        }

        .category-link:hover {
            border-color: var(--primary);
        }

        .category-link.active {
            border-color: var(--primary);
            color: var(--primary);
        }

        .category-name {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .price-options {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .price-radio {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            background: var(--bg-hover);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 0.85rem;
        }

        .price-radio label {
            color: var(--text-primary);
            font-size: 0.85rem;
            cursor: pointer;
        }

        .price-radio input[type="radio"] {
            accent-color: var(--primary);
            width: 14px;
            height: 14px;
        }

        .sidebar-search {
            margin-bottom: 15px;
        }

        .search-input-group {
            display: flex;
            align-items: center;
            background: var(--bg-hover);
            border: 1px solid var(--border-color);
            border-radius: 30px;
            padding: 2px 2px 2px 15px;
        }

        .search-input-group input {
            flex: 1;
            background: transparent;
            border: none;
            padding: 8px 0;
            color: var(--text-primary);
            font-size: 0.85rem;
            outline: none;
        }

        .search-input-group button {
            background: var(--primary);
            border: none;
            border-radius: 30px;
            padding: 6px 14px;
            color: #000;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
        }

        .sort-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        }

        .sort-select {
            width: 100%;
            padding: 8px 10px;
            background: var(--bg-hover);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 0.85rem;
            outline: none;
        }

        .products-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .products-header h2 {
            font-size: 1.4rem;
            font-weight: 600;
        }

        .products-header h2 span {
            color: var(--primary);
        }

        .results-count {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        .product-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }

        .product-card:hover {
            border-color: var(--primary);
        }

        .product-image {
            width: 100%;
            height: 160px;
            object-fit: cover;
            border-bottom: 1px solid var(--border-color);
        }

        .product-details {
            padding: 12px;
        }

        .product-title {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .product-category {
            color: var(--text-muted);
            font-size: 0.7rem;
            margin-bottom: 6px;
        }

        .product-price {
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 6px;
        }

        .product-price small {
            font-size: 0.65rem;
            color: var(--text-muted);
        }

        .product-rating {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-bottom: 8px;
            font-size: 0.7rem;
        }

        .stars {
            color: #ffc107;
            font-size: 0.7rem;
        }

        .rating-count {
            color: var(--text-muted);
        }

        .rental-select {
            width: 100%;
            padding: 6px;
            background: var(--bg-hover);
            border: 1px solid var(--border-color);
            border-radius: 5px;
            color: var(--text-primary);
            margin-bottom: 6px;
            font-size: 0.8rem;
        }

        .add-to-basket-btn {
            width: 100%;
            padding: 7px;
            background: var(--primary);
            color: #000;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
        }

        .add-to-basket-btn:hover:not(:disabled) {
            background: var(--primary-light);
        }

        .add-to-basket-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .own-listing-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(0,0,0,0.7);
            color: var(--text-muted);
            padding: 3px 6px;
            border-radius: 30px;
            font-size: 0.6rem;
            border: 1px solid var(--border-color);
        }

        .info-toggle {
            width: 100%;
            padding: 5px;
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            border-radius: 5px;
            font-size: 0.75rem;
            cursor: pointer;
            margin-bottom: 8px;
        }

        .info-toggle:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .product-description {
            background: #1a1a1a;
            color: #eaeaea;
            padding: 12px;
            border-top: 1px solid var(--border-color);
            font-size: 0.8rem;
            line-height: 1.4;
        }

        .product-description p {
            color: #eaeaea;
        }

        .product-description small {
            color: #aaaaaa;
        }

        .no-results {
            grid-column: span 4;
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
            border: 1px dashed var(--border-color);
            border-radius: 10px;
        }

			    html.light-mode .price-radio {
		    background: #f5f5f5;
		    border-color: #dddddd;
		}

        html.light-mode .product-description {
            background: #f0f0f0;
            color: #333333;
        }

        html.light-mode .product-description p {
            color: #333333;
        }

        html.light-mode .product-description small {
            color: #666666;
        }

        @media (max-width: 1100px) {
            .products-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            .no-results {
                grid-column: span 3;
            }
        }

        @media (max-width: 800px) {
            .shop-container {
                grid-template-columns: 1fr;
            }
            .filters-sidebar {
                position: static;
            }
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .no-results {
                grid-column: span 2;
            }
        }

        @media (max-width: 500px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
            .no-results {
                grid-column: span 1;
            }
        }
    </style>
</head>
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
            <li><a href="productsPage.php" class="active">Shop</a></li>
            <li><a href="AboutUs.php">About</a></li>
            <li><a href="Contact.php">Contact</a></li>
            <li><a href="FAQTestimonials.php">FAQ</a></li>

            <li><a href="basketPage.php" class="cart-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"></path>
                </svg>
            </a></li>

            <li>
                <button id="themeToggle" onclick="toggleTheme()">🌙</button>
            </li>

            <?php if (($userData['role'] ?? '') === 'customer'): ?>
                <li><a href="seller_dashboard.php">Sell</a></li>
                <li><a href="user_dashboard.php"><?= h($userData['billing_fullname'] ?? "Account") ?></a></li>
                <li><a href="index.php?logout=1" class="btn login">Logout</a></li>
            <?php elseif (($userData['role'] ?? '') === 'admin'): ?>
                <li><a href="admin_dashboard.php">Admin</a></li>
                <li><a href="index.php?logout=1" class="btn login">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php" class="btn login">Login</a></li>
                <li><a href="signup.php" class="btn signup">Sign Up</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<?php if ($addError !== ""): ?>
    <div style="max-width:1400px;margin:10px auto;color:#ff6b6b;text-align:center;padding:8px;background:rgba(255,107,107,0.1);border-radius:8px;font-size:0.9rem;">
        <i class="fas fa-exclamation-circle"></i> <?= h($addError) ?>
    </div>
<?php endif; ?>

<div class="shop-container">
    <aside class="filters-sidebar">
        <div class="filters-header">
            <h3><i class="fas fa-filter"></i> Filters</h3>
            <a href="productsPage.php" class="clear-filters"><i class="fas fa-times"></i> Clear</a>
        </div>

        <div class="sidebar-search">
            <form method="GET" action="productsPage.php">
                <div class="search-input-group">
                    <input type="text" name="search" placeholder="Search products..." value="<?= h($search) ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </div>
                <input type="hidden" name="category" value="<?= h($category) ?>">
                <input type="hidden" name="price" value="<?= h($price) ?>">
                <input type="hidden" name="sort" value="<?= h($sort) ?>">
            </form>
        </div>

        <div class="filter-section">
            <h4>Categories</h4>
            <ul class="category-list">
                <li class="category-item">
                    <a href="?category=All&search=<?= urlencode($search) ?>&price=<?= urlencode($price) ?>&sort=<?= urlencode($sort) ?>" 
                       class="category-link <?= $category === 'All' ? 'active' : '' ?>">
                        <span class="category-name"><i class="fas fa-tshirt"></i> All Items</span>
                    </a>
                </li>
                <?php foreach ($categories as $cat): ?>
                    <?php if (!empty($cat)): ?>
                    <li class="category-item">
                        <a href="?category=<?= urlencode($cat) ?>&search=<?= urlencode($search) ?>&price=<?= urlencode($price) ?>&sort=<?= urlencode($sort) ?>" 
                           class="category-link <?= $category === $cat ? 'active' : '' ?>">
                            <span class="category-name"><i class="fas fa-tag"></i> <?= h($cat) ?></span>
                        </a>
                    </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="filter-section">
            <h4>Price Range</h4>
            <div class="price-options">
                <?php 
                $priceRanges = [
                    '' => 'All Prices',
                    'under-25' => 'Under £25',
                    '25-50' => '£25 - £50',
                    '50-100' => '£50 - £100',
                    '100+' => '£100+'
                ];
                ?>
                <?php foreach ($priceRanges as $value => $label): ?>
                <div class="price-radio">
                    <input type="radio" name="price" id="price-<?= $value ?: 'all' ?>" 
                           value="<?= $value ?>" 
                           <?= $price === $value ? 'checked' : '' ?>
                           onchange="this.form.submit()">
                    <label for="price-<?= $value ?: 'all' ?>"><?= $label ?></label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="sort-section">
            <select name="sort" class="sort-select" onchange="window.location.href='?sort='+this.value+'&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&price=<?= urlencode($price) ?>'">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                <option value="price-low" <?= $sort === 'price-low' ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price-high" <?= $sort === 'price-high' ? 'selected' : '' ?>>Price: High to Low</option>
                <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Top Rated</option>
            </select>
        </div>

        <form id="filter-form" method="GET" action="productsPage.php">
            <input type="hidden" name="search" value="<?= h($search) ?>">
            <input type="hidden" name="category" value="<?= h($category) ?>">
            <input type="hidden" name="sort" value="<?= h($sort) ?>">
        </form>
    </aside>

    <main>
        <div class="products-header">
            <h2>Shop <span>Products</span></h2>
            <div class="results-count">
                <?= count($products) ?> items
            </div>
        </div>

        <div class="products-grid">
            <?php if (!$products): ?>
                <div class="no-results">
                    <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <p>No products found</p>
                    <a href="productsPage.php" style="color: var(--primary); font-size: 0.85rem;">Clear filters</a>
                </div>
            <?php else: ?>
                <?php foreach ($products as $p): ?>
                    <?php
                        $sellerUid = (int)($p['seller_uid'] ?? 0);
                        $isOwnListing = $uid && ((int)$uid === $sellerUid);
                    ?>
                    <div class="product-card">
                        <?php if ($isOwnListing): ?>
                            <div class="own-listing-badge">Your item</div>
                        <?php endif; ?>
                        
                        <img src="images/<?= h($p['image']) ?>" alt="<?= h($p['title']) ?>" class="product-image">

                        <div class="product-details">
                            <h3 class="product-title"><?= h($p['title']) ?></h3>
                            <div class="product-category">
                                <?= h($p['product_type']) ?>
                            </div>
                            
                            <div class="product-price">
                                £<?= number_format((float)$p['price'], 2) ?> <small>/day</small>
                            </div>

                            <div class="product-rating">
                                <span class="stars">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <?php if($i <= round($p['avg_rating'])): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </span>
                                <span class="rating-count">(<?= (int)$p['rating_count'] ?>)</span>
                            </div>

                            <button class="info-toggle toggle-desc" data-target="desc-<?= (int)$p['pid'] ?>">
                                <i class="fas fa-info-circle"></i> Info
                            </button>

                            <form method="POST">
                                <input type="hidden" name="pid" value="<?= (int)$p['pid'] ?>">
                                
                                <select name="rental_days" class="rental-select" <?= $isOwnListing ? "disabled" : "" ?>>
                                    <?php for ($d = 1; $d <= 30; $d++): ?>
                                        <option value="<?= $d ?>"><?= $d ?>d</option>
                                    <?php endfor; ?>
                                </select>

                                <button type="submit" name="add_to_basket" class="add-to-basket-btn" <?= $isOwnListing ? "disabled" : "" ?>>
                                    <i class="fas fa-shopping-basket"></i> Add
                                </button>
                            </form>
                        </div>

                        <div id="desc-<?= (int)$p['pid'] ?>" class="product-description" style="display:none;">
                            <p><?= nl2br(h($p['description'] ?: 'No description')) ?></p>
                            <p style="margin-top:6px;"><small>by <?= h($p['username']) ?></small></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-column brand-column">
            <div class="footer-logo">
                <img src="images/rentique_logo.png" alt="Rentique Logo">
                <span>rentique.</span>
            </div>
            <p class="footer-description">Rent. Wear. Return.<br>Fashion freedom. Sustainable choice.</p>
            <div class="footer-social">
                <a href="https://facebook.com" target="_blank"><i class="fab fa-facebook-f"></i></a>
                <a href="https://instagram.com" target="_blank"><i class="fab fa-instagram"></i></a>
                <a href="https://pinterest.com" target="_blank"><i class="fab fa-pinterest-p"></i></a>
            </div>
        </div>

        <div class="footer-column links-column">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="productsPage.php">Shop</a></li>
                <li><a href="AboutUs.php">About Us</a></li>
                <li><a href="Contact.php">Contact</a></li>
                <li><a href="FAQTestimonials.php">FAQ</a></li>
            </ul>
        </div>

        <div class="footer-column contact-column">
            <h4>Stay Connected</h4>
            <div class="contact-info">
                <p><i class="fas fa-envelope"></i> dtblations@gmail.com</p>
                <p><i class="fas fa-phone-alt"></i> 0121-875-3543</p>
                <p><i class="fas fa-map-marker-alt"></i> Aston University, Birmingham</p>
            </div>
            
            <div class="newsletter">
                <p>Subscribe for exclusive offers</p>
                <div class="newsletter-input">
                    <input type="email" id="subscribeEmail" placeholder="Your email address">
                    <button type="button" id="subscribeBtn">→</button>
                </div>
                <div id="subscribeMessage" class="subscribe-message"></div>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <p>© 2025 Rentique. All Rights Reserved.</p>
    </div>
</footer>

<style>
.footer {
    background: #000;
    color: #fff;
    padding: 2.5rem 0 0;
    margin-top: 3rem;
    border-top: 3px solid #00FF00;
    width: 100%;
}

.footer-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 1rem;
    display: grid;
    grid-template-columns: 2fr 1fr 2fr;
    gap: 1rem;
    align-items: start;
}

.footer-column {
    display: flex;
    flex-direction: column;
}

.brand-column {
    align-items: flex-start;
}

.links-column {
    align-items: center;
    text-align: center;
}

.contact-column {
    align-items: flex-end;
    text-align: right;
}

.footer-logo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.8rem;
}

.footer-logo img {
    width: 40px;
    height: auto;
}

.footer-logo span {
    font-size: 1.8rem;
    font-weight: bold;
    color: #00FF00;
    text-transform: lowercase;
}

.footer-description {
    color: #b0b0b0;
    line-height: 1.5;
    margin-bottom: 1.2rem;
    font-size: 0.9rem;
    text-align: left;
}

.footer-social {
    display: flex;
    gap: 0.8rem;
}

.footer-social a {
    color: #fff;
    background: rgba(255, 255, 255, 0.1);
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    text-decoration: none;
    font-size: 1rem;
    border: 1px solid rgba(0, 255, 0, 0.2);
}

.footer-social a:hover {
    background: #00FF00;
    color: #000;
    transform: translateY(-3px);
    border-color: transparent;
}

.footer-column h4 {
    color: #00FF00;
    font-size: 1.1rem;
    margin-bottom: 1rem;
    font-weight: 600;
    width: 100%;
}

.links-column h4 {
    text-align: center;
}

.contact-column h4 {
    text-align: right;
}

.footer-column ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.links-column ul {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.footer-column ul li {
    margin-bottom: 0.6rem;
}

.footer-column ul li a {
    color: #d0d0d0;
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    display: inline-block;
}

.footer-column ul li a:hover {
    color: #00FF00;
}

.contact-info {
    margin-bottom: 1.2rem;
    width: 100%;
}

.contact-info p {
    color: #d0d0d0;
    font-size: 0.9rem;
    margin-bottom: 0.6rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    justify-content: flex-end;
}

.contact-info i {
    color: #00FF00;
    width: 18px;
    text-align: center;
}

.newsletter {
    width: 100%;
}

.newsletter p {
    color: #d0d0d0;
    font-size: 0.9rem;
    margin-bottom: 0.6rem;
    text-align: right;
}

.newsletter-input {
    display: flex;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(0, 255, 0, 0.2);
    border-radius: 4px;
    overflow: hidden;
    width: 100%;
    max-width: 260px;
    margin-left: auto;
}

.newsletter-input input {
    flex: 1;
    padding: 0.7rem;
    background: transparent;
    border: none;
    color: #fff;
    font-size: 0.9rem;
}

.newsletter-input input:focus {
    outline: none;
}

.newsletter-input input::placeholder {
    color: #666;
}

.newsletter-input button {
    background: #00FF00;
    border: none;
    color: #000;
    padding: 0.7rem 1rem;
    cursor: pointer;
    font-size: 1.1rem;
    font-weight: bold;
    transition: background 0.3s ease;
}

.newsletter-input button:hover {
    background: #d2ff4c;
}

.subscribe-message {
    font-size: 0.8rem;
    margin-top: 0.5rem;
    min-height: 1.2rem;
    color: #00FF00;
    text-align: right;
}

.footer-bottom {
    margin-top: 2rem;
    padding: 1.2rem 0;
    text-align: center;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.3);
    width: 100%;
}

.footer-bottom p {
    color: #aaa;
    font-size: 0.85rem;
    margin: 0;
    line-height: 1.5;
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 1rem;
}

html.light-mode .footer {
    background: #f8f8f8;
    color: #333;
}

html.light-mode .footer-description {
    color: #666;
}

html.light-mode .footer-social a {
    background: rgba(0, 0, 0, 0.05);
    color: #333;
}

html.light-mode .footer-social a:hover {
    background: #00FF00;
    color: #000;
}

html.light-mode .footer-column ul li a {
    color: #555;
}

html.light-mode .contact-info p {
    color: #555;
}

html.light-mode .newsletter p {
    color: #555;
}

html.light-mode .newsletter-input {
    background: #fff;
}

html.light-mode .newsletter-input input {
    color: #333;
}

html.light-mode .newsletter-input input::placeholder {
    color: #999;
}

html.light-mode .subscribe-message {
    color: #00FF00;
}

html.light-mode .footer-bottom {
    background: rgba(0, 0, 0, 0.02);
}

html.light-mode .footer-bottom p {
    color: #666;
}

@media (max-width: 900px) {
    .footer-container {
        grid-template-columns: 1fr 1fr;
    }
    
    .brand-column {
        grid-column: span 2;
        align-items: center;
        text-align: center;
    }
    
    .footer-description {
        text-align: center;
    }
    
    .footer-social {
        justify-content: center;
    }
    
    .contact-column {
        align-items: center;
        text-align: center;
    }
    
    .contact-column h4 {
        text-align: center;
    }
    
    .contact-info p {
        justify-content: center;
    }
    
    .newsletter p {
        text-align: center;
    }
    
    .newsletter-input {
        margin: 0 auto;
    }
    
    .subscribe-message {
        text-align: center;
    }
}

@media (max-width: 600px) {
    .footer-container {
        grid-template-columns: 1fr;
    }
    
    .brand-column {
        grid-column: span 1;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const subscribeBtn = document.getElementById('subscribeBtn');
    const subscribeEmail = document.getElementById('subscribeEmail');
    const subscribeMessage = document.getElementById('subscribeMessage');
    
    if (subscribeBtn) {
        subscribeBtn.addEventListener('click', function() {
            const email = subscribeEmail.value.trim();
            
            if (!email) {
                showMessage('Please enter your email address', 'error');
                return;
            }
            
            if (!isValidEmail(email)) {
                showMessage('Please enter a valid email address', 'error');
                return;
            }
            
            showMessage('Thank you for subscribing!', 'success');
            subscribeEmail.value = '';
            
            setTimeout(() => {
                subscribeMessage.innerHTML = '';
            }, 3000);
        });
        
        subscribeEmail.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                subscribeBtn.click();
            }
        });
    }
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    function showMessage(text, type) {
        subscribeMessage.innerHTML = text;
        subscribeMessage.style.color = type === 'success' ? '#00FF00' : '#ff4444';
    }
});
</script>

<script>
    function toggleTheme() {
        const body = document.body;
        const themeToggle = document.getElementById('themeToggle');
        if (body.classList.contains('light-mode')) {
            body.classList.remove('light-mode');
            themeToggle.textContent = '🌙';
            localStorage.setItem('theme', 'dark');
        } else {
            body.classList.add('light-mode');
            themeToggle.textContent = '☀️';
            localStorage.setItem('theme', 'light');
        }
    }
    document.addEventListener('DOMContentLoaded', function () {
        const savedTheme = localStorage.getItem('theme');
        const themeToggle = document.getElementById('themeToggle');
        if (savedTheme === 'light') {
            document.body.classList.add('light-mode');
            themeToggle.textContent = '☀️';
        } else {
            themeToggle.textContent = '🌙';
        }
    });
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".toggle-desc").forEach(function(btn) {
        btn.addEventListener("click", function() {
            const targetId = btn.getAttribute("data-target");
            const desc = document.getElementById(targetId);
            if (!desc) return;
            if (desc.style.display === "none") {
                desc.style.display = "block";
                btn.innerHTML = '<i class="fas fa-chevron-up"></i> Hide';
            } else {
                desc.style.display = "none";
                btn.innerHTML = '<i class="fas fa-info-circle"></i> Info';
            }
        });
    });
    document.querySelectorAll('input[name="price"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('filter-form').submit();
        });
    });
});
</script>

</body>
</html>

