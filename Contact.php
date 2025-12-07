<?php
session_start();
require_once 'connectdb.php';

// Check login state
$userData = null;

if (isset($_SESSION['uid'])) {
    try {
        $stmt = $db->prepare("SELECT uid, email, billing_fullname, role FROM users WHERE uid = ?");
        $stmt->execute([$_SESSION['uid']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Homepage user fetch error: " . $e->getMessage());
    }
}

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentique Contact Us</title>

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
            <li>
                <a href="BasketPage.php" class="cart-icon">Basket</a>
            </li>
            <button id="themeToggle">Theme</button>
           
            <?php if (isset($userData['role']) && $userData['role'] === 'customer'): ?>
                <li><a href="seller_dashboard.php">Sell</a></li>
                <li><a href="user_dashboard.php"><?= htmlspecialchars($userData['billing_fullname'] ?? "Account") ?></a></li>
                <li><a href="index.php?logout=1" class="btn login">Logout</a></li>

            <?php elseif (isset($userData['role']) && $userData['role'] === 'admin'): ?>
            <!-- Admin logged in -->
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
        <h1>Get in Touch</h1>
        <p class="subtitle">We would like to hear from you</p>
        <p class="subtitle">If you have any inquiries please contact us here</p>

        <div class="contactForm">
            <div class="nameRow">
                <div>
                    <label for="firstName">First Name</label>
                    <input type="text" id="firstName" placeholder="First Name">
                </div>

                <div>
                    <label for="lastName">Last Name</label>
                    <input type="text" id="lastName" placeholder="Last Name">
                </div>
            </div>

            <div>
                <label for="email">Email *</label>
                <input type="email" id="email" placeholder="Email">
            </div>

            <div>
                <label for="message">Message</label>
                <textarea id="message" placeholder="Type your message here." rows="5"></textarea>
            </div>

            <button type="button" id="sendBtn">Send</button>

            <p id="responseMsg" style="text-align:center; margin-top:10px;"></p>
        </div>
    </section>
</main>

<footer>
    <p>© 2025 Rentique. All rights reserved.</p>
</footer>

<script>
// contact form submission
document.getElementById("sendBtn").addEventListener("click", function() {
    const firstName = document.getElementById("firstName").value.trim();
    const lastName = document.getElementById("lastName").value.trim();
    const email = document.getElementById("email").value.trim();
    const message = document.getElementById("message").value.trim();
    const res = document.getElementById("responseMsg");

    fetch("contactpage.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ firstName, lastName, email, message })
    })
    .then(r => r.json())
    .then(data => {
        res.textContent = data.message;
        res.style.color = data.success ? "green" : "red";
    })
    .catch(() => {
        res.textContent = "Error sending message";
        res.style.color = "red";
    });
});
</script>

</body>
</html>
