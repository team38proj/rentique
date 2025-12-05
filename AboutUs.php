<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentique | About Us</title>

    <link rel="stylesheet" href="css/rentique.css">
    <link rel="icon" type="image/png" href="images/rentique_logo.png">
</head>

<body id="aboutPage">

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

            <?php if (isset($_SESSION['uid'])): ?>
                <li><a href="user_dashboard.php" class="btn login">Dashboard</a></li>
                <li><a href="index.php?logout=1" class="btn login">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php" class="btn login">Login</a></li>
                <li><a href="signup.php" class="btn signup">Sign Up</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<img src="images/rentiquebanner2.png" width="100%">

<div class="about-us">
    <div class="about-text">
        <h2>About Us</h2>
        <h3>Welcome to Rentique</h3>

        <p>
            Rentique: Project Dakar
            <br><br>
            At Rentique we believe style should not damage the environment. Based in Dakar, Senegal, 
            one of the most polluted cities worldwide, our mission is to reduce the environmental 
            harm caused by fast fashion.
            <br><br>
            Rentique provides an eco-friendly fashion rental service. Items are cleaned, reused, and 
            shared, extending the lifecycle of every garment. Our customers rent premium clothing 
            without waste.
            <br><br>
            Our platform offers designer dresses, suits, accessories, jackets, footwear, and more. 
            Users enjoy personalised browsing through filters and user profiles.
            <br><br>
            Fast fashion contributes 8–10% of global carbon emissions. Dakar suffers this impact 
            directly. Our team chose the name Rentique to acknowledge this reality and support long-term sustainability.
            <br><br>
            We also give back. Rentique donates 5% of all revenue to local waste-reduction 
            and social-welfare organisations.
        </p>
    </div>

    <div class="about-image">
        <img src="images/map.png" alt="Map of Dakar">
    </div>
</div>

<footer>
    <p>© 2025 Rentique. All rights reserved.</p>
</footer>

</body>
</html>
