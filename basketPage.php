<?php
session_start();
require_once 'connectdb.php';

// Require login
$uid = $_SESSION['uid'] ?? null;
if (!$uid) {
    header("Location: login.php");
    exit;
}

/* ------------------------------------------------------
   REMOVE ITEM FROM BASKET
------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {

    $removeId = intval($_POST['remove_id']);

    try {
        $stmt = $db->prepare("DELETE FROM basket WHERE id = ? AND uid = ?");
        $stmt->execute([$removeId, $uid]);
    } catch (PDOException $e) {
        // Log error if needed
    }

    header("Location: BasketPage.php");
    exit;
}

/* ------------------------------------------------------
   LOAD BASKET ITEMS
------------------------------------------------------ */
try {
    $stmt = $db->prepare("SELECT id, pid, title, image, product_type, price, quantity 
                          FROM basket 
                          WHERE uid = ?");
    $stmt->execute([$uid]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $items = [];
}

/* ------------------------------------------------------
   CALCULATE TOTALS
------------------------------------------------------ */
$subtotal = 0;

foreach ($items as $item) {
    $qty = max(1, intval($item['quantity']));
    $subtotal += ($item['price'] * $qty);
}

$shipping = $items ? 4.99 : 0.00;
$total = $subtotal + $shipping;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentique Shopping Cart</title>

    <link rel="stylesheet" href="css/rentique.css">
    <link rel="icon" type="image/png" href="images/rentique_logo.png">
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

            <?php if ($uid): ?>
                <li><a href="user_dashboard.php" class="btn login">Dashboard</a></li>
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

        <!-- LEFT SIDE: ITEMS -->
        <div class="basketLeft">

            <?php if (!$items): ?>
                <p>Your basket is empty.</p>

            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <div class="basketItem">

                        <!-- REMOVE BUTTON -->
                        <form method="post" style="margin:0;">
                            <input type="hidden" name="remove_id" value="<?= $item['id'] ?>">
                            <button type="submit" class="removeBtn">×</button>
                        </form>

                        <!-- ITEM IMAGE -->
                        <div class="itemImage">
                            <img src="images/<?= htmlspecialchars($item['image']) ?>" 
                                 alt="<?= htmlspecialchars($item['title']) ?>">
                        </div>

                        <!-- ITEM DETAILS -->
                        <div class="itemDetails">
                            <p class="category"><?= htmlspecialchars($item['product_type']) ?></p>
                            <h3><?= htmlspecialchars($item['title']) ?></h3>
                            <p class="itemSize">Quantity: <?= max(1, intval($item['quantity'])) ?></p>
                        </div>

                        <!-- ITEM PRICE -->
                        <div class="itemTotal">
                            <?php
                                $qty = max(1, intval($item['quantity']));
                                $lineTotal = $item['price'] * $qty;
                            ?>
                            <p class="price">£<?= number_format($lineTotal, 2) ?></p>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>


        <!-- RIGHT SIDE: SUMMARY -->
        <div class="basketRight">
            <div class="summaryBox">
                <h2>Summary</h2>

                <div class="summaryLine">
                    <span>Subtotal</span>
                    <span>£<?= number_format($subtotal, 2) ?></span>
                </div>

                <div class="summaryLine">
                    <span>Shipping</span>
                    <span>£<?= number_format($shipping, 2) ?></span>
                </div>

                <div class="summaryTotal">
                    <span>Total</span>
                    <span class="price">£<?= number_format($total, 2) ?></span>
                </div>

                <button class="checkoutBtn" 
                        onclick="window.location.href='Checkout.php'">
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
