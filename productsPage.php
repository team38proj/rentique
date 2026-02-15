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

/* ADD TO BASKET WITH RENTAL DAYS */
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
        SELECT pid, uid AS seller_uid, title, image, product_type, price, quantity
        FROM products
        WHERE pid = ? AND is_available = 1
        LIMIT 1
    ");
    $stmt->execute([$pid]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$p) {
        $addError = "Item not available.";
    }
    elseif ((int)$p['quantity'] <= 0) {
    $addError = "This item is out of stock.";
    } 
    else {
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

/* FILTERS */
$search = trim($_GET['search'] ?? "");
$category = $_GET['category'] ?? "All";
$price = $_GET['price'] ?? "";

$sql = "
    SELECT
        p.pid, p.title, p.image, p.product_type, p.price, p.description,
        u.username, p.uid AS seller_uid, p.quantity,
        COALESCE(AVG(r.stars), 0) AS avg_rating,
        COUNT(r.id) AS rating_count
    FROM products p
    JOIN users u ON p.uid = u.uid
    LEFT JOIN product_ratings r ON r.pid = p.pid
    WHERE p.is_available = 1
";

$params = [];

if ($search !== "") {
    $sql .= " AND (p.title LIKE ? OR p.product_type LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category !== "" && $category !== "All") {
    $sql .= " AND p.product_type = ?";
    $params[] = $category;
}

if ($price !== "") {
    if ($price === "10-30") $sql .= " AND p.price BETWEEN 10 AND 30";
    if ($price === "30-70") $sql .= " AND p.price BETWEEN 30 AND 70";
    if ($price === "70-150") $sql .= " AND p.price BETWEEN 70 AND 150";
    if ($price === "150+") $sql .= " AND p.price >= 150";
}
$sql .= " GROUP BY p.pid ";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <div style="max-width:1100px;margin:14px auto;color:#ff6b6b;text-align:center;">
        <?= h($addError) ?>
    </div>
<?php endif; ?>

<section class="search-section">
    <form class="search-container" method="GET" action="productsPage.php">
        <input type="text" name="search" placeholder="Search by name" value="<?= h($search) ?>">

        <select name="category">
            <option value="All" <?= $category === "All" ? "selected" : "" ?>>All Categories</option>
            <option value="Dresses" <?= $category === "Dresses" ? "selected" : "" ?>>Dresses</option>
            <option value="Shoes" <?= $category === "Shoes" ? "selected" : "" ?>>Shoes</option>
            <option value="Accessories" <?= $category === "Accessories" ? "selected" : "" ?>>Accessories</option>
            <option value="Suits" <?= $category === "Suits" ? "selected" : "" ?>>Suits</option>
            <option value="Outerwear" <?= $category === "Outerwear" ? "selected" : "" ?>>Outerwear</option>
        </select>

        <select name="price">
            <option value="" <?= $price === "" ? "selected" : "" ?>>All Prices</option>
            <option value="10-30" <?= $price === "10-30" ? "selected" : "" ?>>£10 - £30</option>
            <option value="30-70" <?= $price === "30-70" ? "selected" : "" ?>>£30 - £70</option>
            <option value="70-150" <?= $price === "70-150" ? "selected" : "" ?>>£70 - £150</option>
            <option value="150+" <?= $price === "150+" ? "selected" : "" ?>>£150+</option>
        </select>

        <button class="search-btn">Search</button>
    </form>
</section>

<section class="products-grid">

<?php if (!$products): ?>
    <p class="noResults">No products found.</p>
<?php else: ?>
    <?php foreach ($products as $p): ?>
        <?php
            $sellerUid = (int)($p['seller_uid'] ?? 0);
            $isOwnListing = $uid && ((int)$uid === $sellerUid);
        ?>
        <div class="product-card">
            <img src="images/<?= h($p['image']) ?>" alt="<?= h($p['title']) ?>">

            <h3><?= h($p['title']) ?></h3>
            <p><?= h($p['product_type']) ?></p>
            <p class="price">£<?= number_format((float)$p['price'], 2) ?> per day</p>
            
            <p style="margin-top:6px;">Stock: <?= (int)$p['quantity'] ?></p>

            <p style="margin-top:6px;">
        <?= number_format((float)$p['avg_rating'], 1) ?> ★ (<?= (int)$p['rating_count'] ?>)
            </p>

            <button type="button" class="btn secondary toggle-desc" data-target="desc-<?= (int)$p['pid'] ?>">
                PRODUCT INFO
            </button>

            <div id="desc-<?= (int)$p['pid'] ?>" class="product-description" style="display:none;">
                <p>Product Info:</p>
                <p><?= h($p['description']) ?></p>
                <br>
                <p>Product Listed by:</p>
                <p><?= h($p['username']) ?></p>
            </div>

            <form method="POST">
                <input type="hidden" name="pid" value="<?= (int)$p['pid'] ?>">

                <label style="display:block; margin-top:10px;">
                    Rental days
                    <select name="rental_days" class="inputbox" style="margin-top:6px;" <?= $isOwnListing ? "disabled" : "" ?>>
                        <?php for ($d = 1; $d <= 30; $d++): ?>
                            <option value="<?= $d ?>"><?= $d ?></option>
                        <?php endfor; ?>
                    </select>
                </label>

                <button type="submit" name="add_to_basket" class="btn primary" style="margin-top:10px;" <?= ($isOwnListing || (int)$p['quantity'] <= 0) ? "disabled" : "" ?>>
        <?= ((int)$p['quantity'] <= 0) ? "Out of Stock" : "Add to Basket" ?>
</button>

                <?php if ($isOwnListing): ?>
                    <div style="margin-top:8px;color:#ff6b6b;font-size:13px;">
                        This is your listing.
                    </div>
                <?php endif; ?>
            </form>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</section>

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
                btn.textContent = "HIDE";
            } else {
                desc.style.display = "none";
                btn.textContent = "PRODUCT INFO";
            }
        });
    });
});
</script>

</body>
</html>

