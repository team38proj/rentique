<?php
// Victor Backend – Start session and load database connection
session_start();
require_once 'connectdb.php';

// Ensure user is logged in
$uid = $_SESSION['uid'] ?? null;
if (!$uid) die("Access denied.");

// Fetch the last transactions for this user (most recent order)
try {
    $stmt = $db->prepare("
        SELECT t.pid, t.price, p.title, p.image 
        FROM transactions t
        JOIN products p ON t.pid = p.pid
        WHERE t.paying_uid = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$uid]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total
    $total = 0;
    foreach ($transactions as $item) {
        $total += $item['price'];
    }
} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}

// Get card info from query string
$cardLast4 = $_GET['card'] ?? 'XXXX';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Complete</title>
    <link rel="stylesheet" href="rentique.css">
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>
<body>
<header>
    <nav class="navbar">
        <div class="logo">
            <img src="images/rentique_logo.png">
            <span>rentique.</span>
        </div>
        <ul class="nav-links">
            <li><a href="Homepage.php">Home</a></li>
            <li><a href="productsPage.php">Shop</a></li>
            <li><a href="About.php">About</a></li>
            <li><a href="Contact.php">Contact</a></li>
            <li><a href="userdashboard.php">Dashboard</a></li>
        </ul>
    </nav>
</header>

<main>
    <section class="intro">
        <div class="tickIcon">✓</div>
        
        <h1>Thank you for your purchase</h1>
        <p class="subtitle">We've received your order and will ship it in 5-7 business days.</p>
        <p class="subtitle">Paid with card ending in: **<?= htmlspecialchars($cardLast4) ?></p>

        <div class="orderSummary">
            <h2>Order Summary</h2>

            <?php if (!empty($transactions)): ?>
                <?php foreach ($transactions as $item): ?>
                    <div class="orderItem">
                        <h3><?= htmlspecialchars($item['title']) ?></h3>
                        <div class="itemPrice">£<?= number_format($item['price'], 2) ?></div>
                    </div>
                <?php endforeach; ?>
                <div class="orderTotal">
                    <span>Total</span>
                    <span class="totalPrice">£<?= number_format($total, 2) ?></span>
                </div>
            <?php else: ?>
                <p>No items found in your order.</p>
            <?php endif; ?>

            <button type="button" id="backHomeBtn" onclick="window.location.href='Homepage.php'">Back to Home</button>
        </div>
    </section>
</main>

<footer>
    <p>© 2025 Rentique. All rights reserved.</p>
</footer>
</body>
</html>

