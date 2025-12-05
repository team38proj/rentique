<?php
session_start();
require_once 'connectdb.php';

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
        </div>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
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
