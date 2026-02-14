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

/* Krish's Updated Ver.*/
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
    <script src="js/theme.js" defer></script>
    <style>
 
        :root {
            --primary: #a3ff00;
            --primary-dark: #8cd900;
            --primary-light: #b3ff33;
            --primary-glow: rgba(163, 255, 0, 0.15);
            --bg-dark: #0a0a0a;
            --bg-card: #111111;
            --bg-hover: #1a1a1a;
            --bg-elevated: #222222;
            --border-color: #2a2a2a;
            --text-primary: #eaeaea;
            --text-secondary: #cccccc;
            --text-muted: #888888;
            --text-dim: #666666;
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.5);
            --transition-fast: 0.2s ease;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --radius-full: 9999px;
        }


        .shop-container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 30px;
        }


        .filters-sidebar {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: 24px;
            height: fit-content;
            position: sticky;
            top: 100px;
            box-shadow: var(--shadow-lg);
        }

        .filters-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }

        .filters-header h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .clear-filters {
            color: var(--text-muted);
            font-size: 0.85rem;
            text-decoration: none;
        }

        .clear-filters:hover {
            color: var(--primary);
        }

      
        .filter-section {
            margin-bottom: 25px;
        }

        .filter-section h4 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

      
        .category-list {
            list-style: none;
        }

        .category-item {
            margin-bottom: 10px;
        }

        .category-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 15px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 12px;
            background: var(--bg-hover);
            border: 1px solid var(--border-color);
            transition: var(--transition-fast);
        }

        .category-link:hover {
            border-color: var(--primary);
            transform: translateX(5px);
        }

        .category-link.active {
            background: rgba(163, 255, 0, 0.1);
            border-color: var(--primary);
            color: var(--primary);
        }

        .category-name {
            display: flex;
            align-items: center;
            gap: 8px;
        }


        .price-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .price-radio {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            background: var(--bg-hover);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            cursor: pointer;
        }

        .price-radio:hover {
            border-color: var(--primary);
        }

        .price-radio input[type="radio"] {
            accent-color: var(--primary);
            width: 16px;
            height: 16px;
        }


        .sidebar-search {
            margin-bottom: 20px;
        }

        .search-input-group {
            display: flex;
            align-items: center;
            background: var(--bg-hover);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-full);
            padding: 4px 4px 4px 16px;
        }

        .search-input-group:focus-within {
            border-color: var(--primary);
        }

        .search-input-group input {
            flex: 1;
            background: transparent;
            border: none;
            padding: 12px 0;
            color: var(--text-primary);
            outline: none;
        }

        .search-input-group button {
            background: var(--primary);
            border: none;
            border-radius: var(--radius-full);
            padding: 10px 20px;
            color: #000;
            font-weight: 600;
            cursor: pointer;
        }


        .sort-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        .sort-select {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg-hover);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            color: var(--text-primary);
            outline: none;
            cursor: pointer;
        }


        .products-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .products-header h2 {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .products-header h2 span {
            color: var(--primary);
        }

        .results-count {
            color: var(--text-muted);
        }

 
        .products-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
        }

        .product-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            overflow: hidden;
            transition: var(--transition-fast);
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid var(--border-color);
        }

        .product-details {
            padding: 16px;
        }

        .product-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 4px;
            color: var(--text-primary);
        }

        .product-category {
            color: var(--text-muted);
            font-size: 0.8rem;
            margin-bottom: 8px;
        }

        .product-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .product-price small {
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        .product-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 10px;
            font-size: 0.8rem;
        }

        .stars {
            color: #ffc107;
        }

        .rating-count {
            color: var(--text-muted);
        }

        .rental-select {
            width: 100%;
            padding: 8px;
            background: var(--bg-hover);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .add-to-basket-btn {
            width: 100%;
            padding: 10px;
            background: var(--primary);
            color: #000;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-fast);
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
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: var(--text-muted);
            padding: 4px 8px;
            border-radius: var(--radius-full);
            font-size: 0.7rem;
            border: 1px solid var(--border-color);
        }

        .info-toggle {
            width: 100%;
            padding: 8px;
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            margin-bottom: 10px;
        }

        .info-toggle:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .product-description {
            padding: 16px;
            background: var(--bg-hover);
            border-top: 1px solid var(--border-color);
            font-size: 0.9rem;
        }

        .no-results {
            grid-column: span 4;
            text-align: center;
            padding: 60px;
            color: var(--text-muted);
            border: 2px dashed var(--border-color);
            border-radius: 30px;
        }

        @media (max-width: 1200px) {
            .products-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            .no-results {
                grid-column: span 3;
            }
        }

        @media (max-width: 900px) {
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
            <li><a href="basketPage.php" class="cart-icon">Basket</a></li>
            <button id="themeToggle">Theme</button>

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
    <div style="max-width:1400px;margin:14px auto;color:#ff6b6b;text-align:center;padding:10px;background:rgba(255,107,107,0.1);border-radius:10px;">
        <i class="fas fa-exclamation-circle"></i> <?= h($addError) ?>
    </div>
<?php endif; ?>

<div class="shop-container">

    <aside class="filters-sidebar">
        <div class="filters-header">
            <h3><i class="fas fa-filter"></i> Filters</h3>
            <a href="productsPage.php" class="clear-filters"><i class="fas fa-times"></i> Clear</a>
        </div>

        <!-- Search Bar -->
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
                <?= count($products) ?> items found
            </div>
        </div>

        <div class="products-grid">
            <?php if (!$products): ?>
                <div class="no-results">
                    <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 15px;"></i>
                    <p>No products found matching your criteria.</p>
                    <a href="productsPage.php" style="color: var(--primary); text-decoration: none; margin-top: 10px; display: inline-block;">
                        Clear filters
                    </a>
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
                                        <option value="<?= $d ?>"><?= $d ?> day<?= $d > 1 ? 's' : '' ?></option>
                                    <?php endfor; ?>
                                </select>

                                <button type="submit" name="add_to_basket" class="add-to-basket-btn" <?= $isOwnListing ? "disabled" : "" ?>>
                                    <i class="fas fa-shopping-basket"></i> Add
                                </button>
                            </form>
                        </div>

                        <div id="desc-<?= (int)$p['pid'] ?>" class="product-description" style="display:none;">
                            <p><?= nl2br(h($p['description'] ?: 'No description available')) ?></p>
                            <p style="margin-top:8px;"><small>Listed by: <?= h($p['username']) ?></small></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<footer>
    <p>© 2025 Rentique. All rights reserved.</p>
</footer>

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