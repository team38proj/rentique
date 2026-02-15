<?php
session_start();
require_once 'connectdb.php';

$uid = $_SESSION['uid'] ?? null;
if (!$uid) die("Not logged in.");

$cardLast4 = $_SESSION['last_card4'] ?? "XXXX";

 //* >>>> reduce stock*//
try {
    // Get all items in this user's basket
    $stmt = $db->prepare("SELECT pid, quantity FROM basket WHERE uid = ?");
    $stmt->execute([$uid]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        $update = $db->prepare("
            UPDATE products 
            SET quantity = quantity - ?
            WHERE pid = ? AND quantity >= ?
        ");
        $update->execute([
            (int)$item['quantity'],
            (int)$item['pid'],
            (int)$item['quantity']
        ]);

        if ($update->rowCount() === 0) {
            die("Not enough stock available.");
        }
    }

    // Clear basket after successful stock update
    $clear = $db->prepare("DELETE FROM basket WHERE uid = ?");
    $clear->execute([$uid]);

} catch (PDOException $e) {
    die("Stock update failed: " . $e->getMessage());
}
?>
 //*------------*//

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
