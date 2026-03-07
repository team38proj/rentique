<?php
session_start();
require_once 'connectdb.php';

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

    <link rel="stylesheet" href="css/rentique.css?v=aboutFINAL1">
    <link rel="stylesheet" href="assets/global.css">
    <link rel="icon" type="image/png" href="images/rentique_logo.png">
    <script src="js/theme.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Cart icon & theme toggle styles (same as Contact page) -->
    <style>
        .cart-icon {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cart-icon svg {
            width: 20px;
            height: 20px;
            stroke: #eaeaea;
            transition: all 0.3s ease;
        }
        html.light-mode .cart-icon svg {
            stroke: #000000;
        }
        .cart-icon:hover svg {
            stroke: #00FF00;
        }
        #themeToggle {
            background: transparent;
            border: 1px solid #00FF00;
            color: #ffffff;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }
        html.light-mode #themeToggle {
            color: #333333;
            border-color: #00FF00;
            background: transparent;
        }
        #themeToggle:hover {
            background: transparent;
            border-color: #d2ff4c;
            transform: scale(1.1);
        }
    </style>

    <!-- Footer CSS (scoped ONLY to About page so it cannot break other pages) -->
    <style>
        #aboutPage .footer {
            background: #000;
            color: #fff;
            padding: 2.5rem 0 0;
            margin-top: 3rem;
            border-top: 3px solid #00FF00;
            width: 100%;
        }

        #aboutPage .footer-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 2fr 1fr 2fr;
            gap: 1.5rem;
            align-items: start;
        }

        #aboutPage .footer-column {
            display: flex;
            flex-direction: column;
        }

        #aboutPage .brand-column {
            align-items: flex-start;
            text-align: left;
        }

        #aboutPage .links-column {
            align-items: center;
            text-align: center;
        }

        #aboutPage .contact-column {
            align-items: flex-end;
            text-align: right;
        }

        #aboutPage .footer-logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.8rem;
        }

        #aboutPage .footer-logo img {
            width: 40px;
            height: auto;
        }

        #aboutPage .footer-logo span {
            font-size: 1.8rem;
            font-weight: bold;
            color: #00FF00;
            text-transform: lowercase;
        }

        #aboutPage .footer-description {
            color: #b0b0b0;
            line-height: 1.5;
            margin-bottom: 1.2rem;
            font-size: 0.9rem;
        }

        #aboutPage .footer-social {
            display: flex;
            gap: 0.8rem;
        }

        #aboutPage .footer-social a {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 1rem;
            border: 1px solid rgba(0, 255, 0, 0.2);
        }

        #aboutPage .footer-social a:hover {
            background: #00FF00;
            color: #000;
            transform: translateY(-3px);
            border-color: transparent;
        }

        #aboutPage .footer-column h4 {
            color: #00FF00;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            font-weight: 600;
            width: 100%;
        }

        #aboutPage .footer-column ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        #aboutPage .footer-column ul li {
            margin-bottom: 0.6rem;
        }

        #aboutPage .footer-column ul li a {
            color: #d0d0d0;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-block;
        }

        #aboutPage .footer-column ul li a:hover {
            color: #00FF00;
        }

        #aboutPage .contact-info {
            margin-bottom: 1.2rem;
            width: 100%;
        }

        #aboutPage .contact-info p {
            color: #d0d0d0;
            font-size: 0.9rem;
            margin-bottom: 0.6rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            justify-content: flex-end;
        }

        #aboutPage .contact-info i {
            color: #00FF00;
            width: 18px;
            text-align: center;
        }

        #aboutPage .newsletter {
            width: 100%;
        }

        #aboutPage .newsletter p {
            color: #d0d0d0;
            font-size: 0.9rem;
            margin-bottom: 0.6rem;
            text-align: right;
        }

        #aboutPage .newsletter-input {
            display: flex;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(0, 255, 0, 0.2);
            border-radius: 4px;
            overflow: hidden;
            width: 100%;
            max-width: 260px;
            margin-left: auto;
        }

        #aboutPage .newsletter-input input {
            flex: 1;
            padding: 0.7rem;
            background: transparent;
            border: none;
            color: #fff;
            font-size: 0.9rem;
        }

        #aboutPage .newsletter-input input:focus {
            outline: none;
        }

        #aboutPage .newsletter-input input::placeholder {
            color: #666;
        }

        #aboutPage .newsletter-input button {
            background: #00FF00;
            border: none;
            color: #000;
            padding: 0.7rem 1rem;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        #aboutPage .newsletter-input button:hover {
            background: #d2ff4c;
        }

        #aboutPage .subscribe-message {
            font-size: 0.8rem;
            margin-top: 0.5rem;
            min-height: 1.2rem;
            color: #00FF00;
            text-align: right;
        }

        #aboutPage .footer-bottom {
            margin-top: 2rem;
            padding: 1.2rem 0;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.3);
            width: 100%;
        }

        #aboutPage .footer-bottom p {
            color: #aaa;
            font-size: 0.85rem;
            margin: 0;
            line-height: 1.5;
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        html.light-mode #aboutPage .footer {
            background: #f8f8f8;
            color: #333;
        }
        html.light-mode #aboutPage .footer-description { color: #666; }
        html.light-mode #aboutPage .footer-social a { background: rgba(0,0,0,0.05); color: #333; }
        html.light-mode #aboutPage .footer-social a:hover { background: #00FF00; color: #000; }
        html.light-mode #aboutPage .footer-column ul li a { color: #555; }
        html.light-mode #aboutPage .contact-info p { color: #555; }
        html.light-mode #aboutPage .newsletter p { color: #555; }
        html.light-mode #aboutPage .newsletter-input { background: #fff; }
        html.light-mode #aboutPage .newsletter-input input { color: #333; }
        html.light-mode #aboutPage .newsletter-input input::placeholder { color: #999; }
        html.light-mode #aboutPage .subscribe-message { color: #00FF00; }
        html.light-mode #aboutPage .footer-bottom { background: rgba(0,0,0,0.02); }
        html.light-mode #aboutPage .footer-bottom p { color: #666; }

        @media (max-width: 900px) {
            #aboutPage .footer-container { grid-template-columns: 1fr 1fr; }
            #aboutPage .brand-column { grid-column: span 2; align-items: center; text-align: center; }
            #aboutPage .footer-description { text-align: center; }
            #aboutPage .footer-social { justify-content: center; }
            #aboutPage .contact-column { align-items: center; text-align: center; }
            #aboutPage .contact-info p { justify-content: center; }
            #aboutPage .newsletter p { text-align: center; }
            #aboutPage .newsletter-input { margin: 0 auto; }
            #aboutPage .subscribe-message { text-align: center; }
        }

        @media (max-width: 600px) {
            #aboutPage .footer-container { grid-template-columns: 1fr; }
            #aboutPage .brand-column { grid-column: span 1; }
        }
    </style>
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
            <li><a href="FAQTestimonials.php">FAQ</a></li>
            <li><a href="game.php" class="active">Game</a></li>

            <?php if (isset($userData)): ?>
                <li><a href="dashboard.php">Style Planner</a></li>
            <?php else: ?>
                <li><a href="login.php" class="active">Style Planner</a></li>
            <?php endif; ?>

            <li><a href="basketPage.php" class="cart-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"></path>
                </svg>
            </a></li>

            <li>
                <button id="themeToggle" onclick="toggleTheme()">&#127769;</button>
            </li>

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

<section class="mission-section">
    <div class="container">
        <div class="mission-layout">
            <div class="mission-content">
                <h3>Our Beginning</h3>
                <p>
                    <span class="highlight">Project Dakar</span>: Rentique began as a simple concept to address the direction fashion is going and its impact on the world.
                    In <span class="highlight">Dakar, Senegal</span>, we experienced first-hand the effects of pollution driven by fast fashion.
                </p>
                <p>
                    What began as an idea quickly became a platform with a clear message:
                    <span class="highlight">awareness</span>, <span class="highlight">accessibility</span>, and <span class="highlight">sustainability</span>.
                    Our mission has always been simple &mdash; help people look good while <span class="highlight">doing good</span>.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="mission-section">
    <div class="container">
        <div class="mission-layout">
            <div class="mission-content">
                <h3>Our Mission</h3>
                <p>
                    Rentique is an online fashion rental service built to <span class="highlight">reduce waste</span> by extending the life of clothing and accessories.
                    It promotes re-use and gives people access to <span class="highlight">high-end fashion</span> without the cost of ownership.
                </p>
                <p>
                    From shipment to expert cleaning, Rentique takes care of the process &mdash; ensuring style is never at the expense of
                    <span class="highlight">people</span> or the <span class="highlight">planet</span>.
                </p>

                <div class="mission-stats">
                    <div class="stat-item">
                        <div class="stat-number">5%</div>
                        <div class="stat-label">Of Earnings Donated</div>
                        <div class="stat-note">Supporting clean-up and community projects.</div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-number">6&ndash;10%</div>
                        <div class="stat-label">Fast Fashion Emissions</div>
                        <div class="stat-note">Rental helps reduce repeat purchases.</div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-number">100%</div>
                        <div class="stat-label">Eco-Conscious Focus</div>
                        <div class="stat-note">Responsible handling and longer item lifecycles.</div>
                    </div>
                </div>
            </div>

            <div class="mission-image map-hover">
                <img src="images/map4.png" alt="Map of Dakar">
                <div class="map-caption">
                    Dakar, Senegal &mdash; a real-world example of how fast fashion waste can affect communities.
                </div>
            </div>
        </div>
    </div>
</section>

<section>
    <div class="container">
        <div class="section-header">
            <h2>Our Values</h2>
            <p>At Rentique, we stand by our values &mdash; they influence every decision we make.</p>
        </div>

        <div class="values-section">
            <div class="value-card">
                <h4>Sustainable Fashion</h4>
                <p>
                    We extend clothing lifecycles through our rental model, lowering waste and reducing the
                    <span class="highlight">carbon footprint</span> of fast fashion.
                </p>
            </div>

            <div class="value-card">
                <h4>Community Investment</h4>
                <p>
                    <span class="highlight">5% of all earnings</span> go towards charitable organisations working to reduce waste
                    and improve social welfare.
                </p>
            </div>

            <div class="value-card">
                <h4>Accessible Luxury</h4>
                <p>
                    Everyone deserves access to premium fashion. We make <span class="highlight">designer style</span> more affordable
                    for anyone, anywhere.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Krish's Revamped Footer -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-column brand-column">
            <div class="footer-logo">
                <img src="images/rentique_logo.png" alt="Rentique Logo">
                <span>rentique.</span>
            </div>
            <p class="footer-description">Rent. Wear. Return.<br>Fashion freedom. Sustainable choice.</p>
            <div class="footer-social">
                <a href="https://facebook.com" target="_blank"><i class="fab fa-facebook-f"></i></a>
                <a href="https://instagram.com" target="_blank"><i class="fab fa-instagram"></i></a>
                <a href="https://pinterest.com" target="_blank"><i class="fab fa-pinterest-p"></i></a>
            </div>
        </div>

        <div class="footer-column links-column">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="productsPage.php">Shop</a></li>
                <li><a href="AboutUs.php">About Us</a></li>
                <li><a href="Contact.php">Contact</a></li>
                <li><a href="FAQTestimonials.php">FAQ</a></li>
            </ul>
        </div>

        <div class="footer-column contact-column">
            <h4>Stay Connected</h4>
            <div class="contact-info">
                <p><i class="fas fa-envelope"></i> dtblations@gmail.com</p>
                <p><i class="fas fa-phone-alt"></i> 0121-875-3543</p>
                <p><i class="fas fa-map-marker-alt"></i> Aston University, Birmingham</p>
            </div>

            <div class="newsletter">
                <p>Subscribe for exclusive offers</p>
                <div class="newsletter-input">
                    <input type="email" id="subscribeEmail" placeholder="Your email address">
                    <button type="button" id="subscribeBtn">&rarr;</button>
                </div>
                <div id="subscribeMessage" class="subscribe-message"></div>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <p>© 2025 Rentique. All Rights Reserved.</p>
    </div>
</footer>

<!-- Subscribe script (same behaviour as other pages) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const subscribeBtn = document.getElementById('subscribeBtn');
    const subscribeEmail = document.getElementById('subscribeEmail');
    const subscribeMessage = document.getElementById('subscribeMessage');

    if (!subscribeBtn || !subscribeEmail || !subscribeMessage) return;

    subscribeBtn.addEventListener('click', function() {
        const email = subscribeEmail.value.trim();

        if (!email) {
            showMessage('Please enter your email address', 'error');
            return;
        }

        if (!isValidEmail(email)) {
            showMessage('Please enter a valid email address', 'error');
            return;
        }

        showMessage('Thank you for subscribing!', 'success');
        subscribeEmail.value = '';

        setTimeout(() => {
            subscribeMessage.innerHTML = '';
        }, 3000);
    });

    subscribeEmail.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            subscribeBtn.click();
        }
    });

    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function showMessage(text, type) {
        subscribeMessage.innerHTML = text;
        subscribeMessage.style.color = type === 'success' ? '#00FF00' : '#ff4444';
    }
});
</script>

<!-- AI Chat (KEEP) -->
<button id="aiChatBtn">&#128172; AI Help</button>

<div id="chatBox">
    <div class="chat-header">
        AI Assistant
        <span id="closeChat">&#10006;</span>
    </div>

    <div id="chatMessages"></div>

    <div class="chat-input">
        <input type="text" id="userMessage" placeholder="Ask something..." />
        <button id="sendMessage">Send</button>
    </div>
</div>

<script src="assets/global.js"></script>
</body>
</html>
