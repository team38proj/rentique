<?php
session_start();
require_once 'connectdb.php';

 //* >>>> reduce stock*//
$productId = $_SESSION['ordered_product_id'] ?? null;
$orderedQty = $_SESSION['ordered_quantity'] ?? 1;

if ($productId) {
    try {
        $stmt = $db->prepare("
            UPDATE products 
            SET quantity = quantity - ? 
            WHERE id = ? AND quantity >= ?
        ");
        $stmt->execute([$orderedQty, $productId, $orderedQty]);

        if ($stmt->rowCount() === 0) {
            die("Not enough stock available.");
        }

        // clear session so it doesnt reduce twice
        unset($_SESSION['ordered_product_id']);
        unset($_SESSION['ordered_quantity']);

    } catch (PDOException $e) {
        die("Stock update failed: " . $e->getMessage());
    }
}

//*-------*//

$uid = $_SESSION['uid'] ?? null;
if (!$uid) die("Not logged in.");

$cardLast4 = $_SESSION['last_card4'] ?? "XXXX";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Order Complete</title>
    <link rel="stylesheet" href="css/rentique.css">
</head>
<body>

<header>
    <nav class="navbar">
        <div class="logo">
            <img src="images/rentique_logo.png">
            <span>rentique.</span>
            <script src="js/theme.js" defer></script>

        </div>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <button id="themeToggle">Theme</button>
        </ul>
    </nav>
</header>

<main>
    <section class="intro">
        <div class="tickIcon">✓</div>

        <h1>Thank you for your order</h1>
        <p class="subtitle">Your payment was successful.</p>
        <p class="subtitle">Card ending in <?= htmlspecialchars($cardLast4) ?></p>

        <div class="orderSummary">
            <h2>Order Complete</h2>

            <p>Your items will be processed and prepared.</p>

            <button id="backHomeBtn" onclick="window.location.href='index.php'">
                Back to Home
            </button>
        </div>
    </section>
</main>

</body>
</html>
