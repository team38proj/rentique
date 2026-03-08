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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    if (params.get('celebrate') !== '1') return;

    for (let i = 0; i < 70; i++) {
        const sparkle = document.createElement('div');
        sparkle.style.position = 'fixed';
        sparkle.style.left = (window.innerWidth / 2) + 'px';
        sparkle.style.top = (window.innerHeight / 2) + 'px';
        sparkle.style.width = '8px';
        sparkle.style.height = '8px';
        sparkle.style.borderRadius = '50%';
        sparkle.style.background = i % 2 === 0 ? '#a3ff00' : '#ffffff';
        sparkle.style.boxShadow = '0 0 10px currentColor, 0 0 20px currentColor';
        sparkle.style.pointerEvents = 'none';
        sparkle.style.zIndex = '9999';

        const angle = Math.random() * Math.PI * 2;
        const distance = 60 + Math.random() * 240;
        const dx = Math.cos(angle) * distance;
        const dy = Math.sin(angle) * distance;

        sparkle.animate([
            { transform: 'translate(0, 0) scale(1)', opacity: 1 },
            { transform: `translate(${dx}px, ${dy}px) scale(0.15)`, opacity: 0 }
        ], {
            duration: 1000 + Math.random() * 700,
            easing: 'ease-out',
            fill: 'forwards'
        });

        document.body.appendChild(sparkle);
        setTimeout(() => sparkle.remove(), 1800);
    }
});
</script>

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

<?php
$orderNumber = 'RTQ-' . rand(100000, 999999);
$trackingNumber = 'TRK-' . rand(10000000, 99999999);
$estimatedDelivery = date('j M Y', strtotime('+3 days'));
$currentStep = 3;
?>

<p>Your items will be processed and prepared.</p>

<div class="trackingMetaSimple">
<p><strong>Order Number:</strong> <?= htmlspecialchars($orderNumber) ?></p>
<p><strong>Tracking Number:</strong> <?= htmlspecialchars($trackingNumber) ?></p>
<p><strong>Estimated Delivery:</strong> <?= htmlspecialchars($estimatedDelivery) ?></p>
</div>

<div class="trackingSimple">

<div class="trackingLine"></div>

<div class="trackingStepSimple <?= $currentStep >= 1 ? 'done' : '' ?>">
<div class="trackingCircle"></div>
<span>Placed</span>
</div>

<div class="trackingStepSimple <?= $currentStep >= 2 ? 'done' : '' ?>">
<div class="trackingCircle"></div>
<span>Confirmed</span>
</div>

<div class="trackingStepSimple <?= $currentStep >= 3 ? 'active' : '' ?>">
<div class="trackingCircle"></div>
<span>Preparing</span>
</div>

<div class="trackingStepSimple <?= $currentStep >= 4 ? 'done' : '' ?>">
<div class="trackingCircle"></div>
<span>Shipped</span>
</div>

<div class="trackingStepSimple <?= $currentStep >= 5 ? 'done' : '' ?>">
<div class="trackingCircle"></div>
<span>Delivered</span>
</div>

</div>

<p class="trackingHelpLink">
Need to update your delivery plans?
<a href="Contact.php">Contact our team</a>
</p>

<button id="backHomeBtn" onclick="window.location.href='index.php'">
Back to Home
</button>

</div>

</section>
</main>

</body>
</html>
