<?php
session_start();
require_once 'connectdb.php';   // FIX: ensure $db exists before using it

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
    <title>Rentique | About Us</title>

    <link rel="stylesheet" href="css/rentique.css">
    <link rel="icon" type="image/png" href="images/rentique_logo.png">
    <script src="js/theme.js" defer></script>
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
            <button id="themeToggle">Theme</button>

            <?php if (isset($userData['role']) && $userData['role'] === 'customer'): ?>
                <li><a href="seller_dashboard.php">Sell</a></li>
                <li><a href="user_dashboard.php"><?= htmlspecialchars($userData['billing_fullname'] ?? "Account") ?></a></li>
                <li><a href="index.php?logout=1" class="btn login">Logout</a></li>

            <?php elseif (isset($userData['role']) && $userData['role'] === 'admin'): ?>
                <li><a href="admin_dashboard.php">Admin</a></li>
                <li><a href="index.php?logout=1" class="btn login">Logout</a></li>

            <?php else: ?>
                <li><a href="login.php" class="btn login">Login</a></li>
                <li><a href="signup.php" class="btn signup">Sign Up</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<div class="full-banner">
    <img src="images/Dakar1.png" alt="Banner">
</div>

<section class="mission-section right-section">
    <div class="container">
        <div class="mission-content">
            <h3>Our Beginning</h3>
            <p>
                Project Dakar: Rentique began as a simple concept to address the significance about the 
                current direction fashion is going and its impact on the world. An example is Dakar, Senegal, 
                where we experienced first-hand the effects of pollution created by fast fashion.
            </p>
            <p>
                What began as an idea swiftly developed into a platform with a clear message to spread 
                awareness, accessibility, and sustainability. Our mission was always clear — produce fashion 
                that helps people look good while doing good.
            </p>
        </div>
    </div>
</section>

<section class="mission-section left-section">
    <div class="container">
        <div class="mission-layout">

            <div class="mission-content">
                <h3>Our Mission</h3>
                <p>
                    Rentique is an online fashion rental service with the goal to reduce waste by extending the 
                    life of clothing and accessories. Not only does this promote recycling of unwanted clothing 
                    but gives everyone access to high-end fashion.
                </p>
                <p>
                    From shipment to expert cleaning, Rentique takes care of all processes ensuring that 
                    appearance is never at the expense of morality.
                </p>

                <div class="mission-stats">
                    <div class="stat-item">
                        <div class="stat-number">5%</div>
                        <div class="stat-label">Of Earnings Donated</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">6–10%</div>
                        <div class="stat-label">Fast Fashion Emissions</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">100%</div>
                        <div class="stat-label">Eco-Conscious</div>
                    </div>
                </div>
            </div>

            <div class="mission-image">
                <img src="images/map4.png" alt="Map of Dakar">
            </div>

        </div>
    </div>
</section>

<section class="container">
    <div class="section-header">
        <h2>Our Values</h2>
        <p>At Rentique, we stand by our values — they influence every decision we make.</p>
    </div>

    <div class="values-section">

        <div class="value-card">
            <div class="value-icon">♻</div>
            <h4>Sustainable Fashion</h4>
            <p>
                We extend clothing lifecycles through our rental model, drastically lowering waste 
                and the carbon footprint from fast fashion.
            </p>
        </div>

        <div class="value-card">
            <div class="value-icon">🤝</div>
            <h4>Community Investment</h4>
            <p>
                5% of all earnings go towards charitable organisations striving to eliminate waste 
                and improve social welfare.
            </p>
        </div>

        <div class="value-card">
            <div class="value-icon">👕</div>
            <h4>Accessible Luxury</h4>
            <p>
                Everyone deserves access to premium fashion. We make designer clothing affordable 
                for anyone, anywhere.
            </p>
        </div>

    </div>
</section>


<footer>
    <p>© 2025 Rentique. All rights reserved.</p>
</footer>

</body>
</html>
