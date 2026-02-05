<?php
session_start();
require_once 'connectdb.php';

$uid = $_SESSION['uid'] ?? null;
if (!$uid) {
    header("Location: login.php");
    exit;
}

$userData = null;
if (isset($_SESSION['uid'])) {
    try {
        $stmt = $db->prepare("SELECT uid, email, billing_fullname, role FROM users WHERE uid = ?");
        $stmt->execute([$_SESSION['uid']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Basket user fetch error: " . $e->getMessage());
    }
}

/* REMOVE ITEM */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {
    $removeId = intval($_POST['remove_id']);

    try {
        $stmt = $db->prepare("DELETE FROM basket WHERE id = ? AND uid = ?");
        $stmt->execute([$removeId, $uid]);
    } catch (PDOException $e) {
    }

    header("Location: BasketPage.php");
    exit;
}

/* LOAD ITEMS */
try {
    $stmt = $db->prepare("SELECT id, pid, title, image, product_type, price, quantity, rental_days FROM basket WHERE uid = ?");
    $stmt->execute([$uid]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $items = [];
}

/* TOTALS */
$platformFeePerItem = 4.99;
$subtotal = 0;
$itemCount = 0;

foreach ($items as $item) {
    $qty = max(1, intval($item['quantity'] ?? 1));
    $days = max(1, intval($item['rental_days'] ?? 1));
    $subtotal += (floatval($item['price']) * $days * $qty);
    $itemCount += $qty;
}

$platformFee = $itemCount * $platformFeePerItem;
$shipping = $items ? 4.99 : 0.00;
$total = $subtotal + $platformFee + $shipping;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentique Shopping Cart</title>

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
            <li><a href="productsPage.php">Shop</a></li>
            <li><a href="AboutUs.php">About</a></li>
            <li><a href="Contact.php">Contact</a></li>
            <button id="themeToggle">Theme</button>

            <?php if (($userData['role'] ?? '') === 'customer'): ?>
                <li><a href="seller_dashboard.php">Sell</a></li>
                <li><a href="user_dashboard.php"><?= htmlspecialchars($userData['billing_fullname'] ?? "Account") ?></a></li>
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

<main>
    <section class="intro">
        <h1>Shopping Cart</h1>
        <p class="subtitle">Review your items before checkout</p>
    </section>

    <div class="basketContainer">

        <div class="basketLeft">

            <?php if (!$items): ?>
                <p>Your basket is empty.</p>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <div class="basketItem">

                        <form method="post" style="margin:0;">
                            <input type="hidden" name="remove_id" value="<?= $item['id'] ?>">
                            <button type="submit" class="removeBtn">×</button>
                        </form>

                        <div class="itemImage">
                            <img src="images/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                        </div>

                        <div class="itemDetails">
                            <p class="category"><?= htmlspecialchars($item['product_type']) ?></p>
                            <h3><?= htmlspecialchars($item['title']) ?></h3>
                            <p class="itemSize">Days: <?= max(1, intval($item['rental_days'] ?? 1)) ?></p>
                            <p class="itemSize">Quantity: <?= max(1, intval($item['quantity'] ?? 1)) ?></p>
                            <p class="itemSize">£<?= number_format(floatval($item['price']), 2) ?> per day</p>
                        </div>

                        <div class="itemTotal">
                            <?php
                                $qty = max(1, intval($item['quantity'] ?? 1));
                                $days = max(1, intval($item['rental_days'] ?? 1));
                                $lineTotal = floatval($item['price']) * $days * $qty;
                            ?>
                            <p class="price">£<?= number_format($lineTotal, 2) ?></p>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>

        <div class="basketRight">
            <div class="summaryBox">
                <h2>Summary</h2>

                <div class="summaryLine">
                    <span>Subtotal</span>
                    <span>£<?= number_format($subtotal, 2) ?></span>
                </div>

                <div class="summaryLine">
                    <span>Platform fee (<?= $itemCount ?> items)</span>
                    <span>£<?= number_format($platformFee, 2) ?></span>
                </div>

                <div class="summaryLine">
                    <span>Shipping</span>
                    <span>£<?= number_format($shipping, 2) ?></span>
                </div>

                <div class="summaryTotal">
                    <span>Total</span>
                    <span class="price">£<?= number_format($total, 2) ?></span>
                </div>

                <button class="checkoutBtn" onclick="window.location.href='Checkout.php'">
                    Check Out
                </button>
            </div>
        </div>

    </div>
</main>

<footer>
    <p>© 2025 Rentique. All rights reserved.</p>
</footer>

</body>
</html>
