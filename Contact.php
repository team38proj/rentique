<?php
session_start();
require_once 'connectdb.php';
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentique Contact Us</title>
    <link rel="stylesheet" href="rentique.css">
    <link rel="icon" type="image/png" href="rentique_logo.png">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="productsPage.php">
                <img src="rentique_logo.png" alt="Rentique Logo">
                </a>
                <span>rentique.</span>
            </div>
            <ul class="nav-links">
                <li><a href="Homepage.html">Home</a></li>
                <li><a href="productsPage.php">Shop</a></li>
                <li><a href="AboutUs.php">About</a></li>
                <li><a href="Contact.php">Contact</a></li>
                <li><a href="login.php" class="btn login">Login</a></li>
                <li><a href="signup.html" class="btn signup">Sign Up</a></li>
                <li><a href="basketPage.php" class="cart-icon"><img src="basket.png" alt="Basket"></a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="intro">
            <h1>Get in Touch</h1>
            <p class="subtitle">We would like to hear from you!</p>
            <p class="subtitle">If you have any inquiries please contact us here.</p>

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
            </div>
        </section>
    </main>

    <footer>
        <p>© 2025 Rentique. All rights reserved.</p>
    </footer>
</body>
</html>
