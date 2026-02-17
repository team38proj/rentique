<?php 
session_start();
require_once 'connectdb.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

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

// Fetch featured products
$featuredProducts = [];

try {
    $stmt = $db->prepare("
        SELECT pid, title, image, product_type, price 
        FROM products 
        ORDER BY created_at DESC 
        LIMIT 8
    ");
    $stmt->execute();
    $featuredProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Featured fetch error: " . $e->getMessage());
}

// Search results
$searchResults = [];

if (isset($_GET['search']) || isset($_GET['category']) || isset($_GET['price_range'])) {

    $search = trim($_GET['search'] ?? '');
    $category = trim($_GET['category'] ?? '');
    $price = trim($_GET['price_range'] ?? '');

    $query = "SELECT pid, title, image, product_type, price FROM products WHERE 1";
    $params = [];

    if ($search !== '') {
        $query .= " AND title LIKE ?";
        $params[] = "%$search%";
    }

    if ($category !== '' && $category !== "All Categories") {
        $query .= " AND product_type = ?";
        $params[] = $category;
    }

    if ($price !== '' && $price !== "Price Range") {
        if ($price === "£10 - £30") $query .= " AND price BETWEEN 10 AND 30";
        if ($price === "£30 - £70") $query .= " AND price BETWEEN 30 AND 70";
        if ($price === "£70 - £150") $query .= " AND price BETWEEN 70 AND 150";
        if ($price === "£150+") $query .= " AND price >= 150";
    }

    $query .= " ORDER BY title LIMIT 20";

    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Search error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentique | Home</title>
    <link rel="stylesheet" href="css/rentique.css">
    <link rel="icon" type="image/png" href="/images/rentique_logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Cart icon & theme toggle styles -->
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
</head>

<script src="js/theme.js"></script>

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
            <li><a href="FAQTestimonials.php" class="active">FAQ</a></li>

            <!-- SVG Cart Icon -->
            <li><a href="basketPage.php" class="cart-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"></path>
                </svg>
            </a></li>

            <!-- Theme Toggle Button -->
            <li>
                <button id="themeToggle" onclick="toggleTheme()">🌙</button>
            </li>

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

    <!-- SEARCH SECTION
    <section class="search-section">
        <form method="GET" action="index.php" class="search-container">

            <input type="text" 
                   name="search" 
                   placeholder="Search dresses, suits, jackets..." 
                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">

            <select name="category">
                <option>All Categories</option>
                <option>Dresses</option>
                <option>Suits</option>
                <option>Accessories</option>
                <option>Jackets</option>
                <option>Shoes</option>
            </select>

            <select name="price_range">
                <option>Price Range</option>
                <option>£10 - £30</option>
                <option>£30 - £70</option>
                <option>£70 - £150</option>
                <option>£150+</option>
            </select>

            <button class="search-btn">Search</button>
        </form>
    </section> -->
</header>

<img src="images/rentiquebanner2.png" width="100%">

<section class="hero">
    <div class="hero-content">
        <h1>Style That Moves With You</h1>
        <p>Rent. Wear. Return. Fashion freedom. Sustainable choice.</p>
        <div class="hero-buttons">
            <a href="productsPage.php" class="btn primary">Explore Collection</a>
            <a href="#" class="btn secondary">Try Virtual Try-On</a>
        </div>
    </div>
</section>

<section class="features">
    <h2>Why Choose Rentique?</h2>
    <div class="feature-grid">
        <div class="feature-card enhanced">
            <h3>Virtual Try-On</h3>
            <p>Preview outfits with AI before renting.</p>
        </div>
        <div class="feature-card enhanced">
            <h3>Easy Rentals</h3>
            <p>Delivered to your doorstep with return labels.</p>
        </div>
        <div class="feature-card enhanced">
            <h3>Feedback System</h3>
            <p>Your opinions shape Rentique.</p>
        </div>
        <div class="feature-card enhanced">
            <h3>Secure Checkout</h3>
            <p>Fast and encrypted payments.</p>
        </div>
    </div>
</section>

<section id="shop"></section>

<!--Krish's Revamped Footer-->

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
                    <button type="button" id="subscribeBtn">→</button>
                </div>
                <div id="subscribeMessage" class="subscribe-message"></div>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <p>© 2025 Rentique. All Rights Reserved.</p>
    </div>
</footer>

<style>
.footer {
    background: #000;
    color: #fff;
    padding: 2.5rem 0 0;
    margin-top: 3rem;
    border-top: 3px solid #00FF00;
    width: 100%;
}

.footer-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 1rem;
    display: grid;
    grid-template-columns: 2fr 1fr 2fr;
    gap: 1rem;
    align-items: start;
}

.footer-column {
    display: flex;
    flex-direction: column;
}

.brand-column {
    align-items: flex-start;
}

.links-column {
    align-items: center;
    text-align: center;
}

.contact-column {
    align-items: flex-end;
    text-align: right;
}

.footer-logo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.8rem;
}

.footer-logo img {
    width: 40px;
    height: auto;
}

.footer-logo span {
    font-size: 1.8rem;
    font-weight: bold;
    color: #00FF00;
    text-transform: lowercase;
}

.footer-description {
    color: #b0b0b0;
    line-height: 1.5;
    margin-bottom: 1.2rem;
    font-size: 0.9rem;
    text-align: left;
}

.footer-social {
    display: flex;
    gap: 0.8rem;
}

.footer-social a {
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

.footer-social a:hover {
    background: #00FF00;
    color: #000;
    transform: translateY(-3px);
    border-color: transparent;
}

.footer-column h4 {
    color: #00FF00;
    font-size: 1.1rem;
    margin-bottom: 1rem;
    font-weight: 600;
    width: 100%;
}

.links-column h4 {
    text-align: center;
}

.contact-column h4 {
    text-align: right;
}

.footer-column ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.links-column ul {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.footer-column ul li {
    margin-bottom: 0.6rem;
}

.footer-column ul li a {
    color: #d0d0d0;
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    display: inline-block;
}

.footer-column ul li a:hover {
    color: #00FF00;
}

.contact-info {
    margin-bottom: 1.2rem;
    width: 100%;
}

.contact-info p {
    color: #d0d0d0;
    font-size: 0.9rem;
    margin-bottom: 0.6rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    justify-content: flex-end;
}

.contact-info i {
    color: #00FF00;
    width: 18px;
    text-align: center;
}

.newsletter {
    width: 100%;
}

.newsletter p {
    color: #d0d0d0;
    font-size: 0.9rem;
    margin-bottom: 0.6rem;
    text-align: right;
}

.newsletter-input {
    display: flex;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(0, 255, 0, 0.2);
    border-radius: 4px;
    overflow: hidden;
    width: 100%;
    max-width: 260px;
    margin-left: auto;
}

.newsletter-input input {
    flex: 1;
    padding: 0.7rem;
    background: transparent;
    border: none;
    color: #fff;
    font-size: 0.9rem;
}

.newsletter-input input:focus {
    outline: none;
}

.newsletter-input input::placeholder {
    color: #666;
}

.newsletter-input button {
    background: #00FF00;
    border: none;
    color: #000;
    padding: 0.7rem 1rem;
    cursor: pointer;
    font-size: 1.1rem;
    font-weight: bold;
    transition: background 0.3s ease;
}

.newsletter-input button:hover {
    background: #d2ff4c;
}

.subscribe-message {
    font-size: 0.8rem;
    margin-top: 0.5rem;
    min-height: 1.2rem;
    color: #00FF00;
    text-align: right;
}

.footer-bottom {
    margin-top: 2rem;
    padding: 1.2rem 0;
    text-align: center;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.3);
    width: 100%;
}

.footer-bottom p {
    color: #aaa;
    font-size: 0.85rem;
    margin: 0;
    line-height: 1.5;
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 1rem;
}

html.light-mode .footer {
    background: #f8f8f8;
    color: #333;
}

html.light-mode .footer-description {
    color: #666;
}

html.light-mode .footer-social a {
    background: rgba(0, 0, 0, 0.05);
    color: #333;
}

html.light-mode .footer-social a:hover {
    background: #00FF00;
    color: #000;
}

html.light-mode .footer-column ul li a {
    color: #555;
}

html.light-mode .contact-info p {
    color: #555;
}

html.light-mode .newsletter p {
    color: #555;
}

html.light-mode .newsletter-input {
    background: #fff;
}

html.light-mode .newsletter-input input {
    color: #333;
}

html.light-mode .newsletter-input input::placeholder {
    color: #999;
}

html.light-mode .subscribe-message {
    color: #00FF00;
}

html.light-mode .footer-bottom {
    background: rgba(0, 0, 0, 0.02);
}

html.light-mode .footer-bottom p {
    color: #666;
}

@media (max-width: 900px) {
    .footer-container {
        grid-template-columns: 1fr 1fr;
    }
    
    .brand-column {
        grid-column: span 2;
        align-items: center;
        text-align: center;
    }
    
    .footer-description {
        text-align: center;
    }
    
    .footer-social {
        justify-content: center;
    }
    
    .contact-column {
        align-items: center;
        text-align: center;
    }
    
    .contact-column h4 {
        text-align: center;
    }
    
    .contact-info p {
        justify-content: center;
    }
    
    .newsletter p {
        text-align: center;
    }
    
    .newsletter-input {
        margin: 0 auto;
    }
    
    .subscribe-message {
        text-align: center;
    }
}

@media (max-width: 600px) {
    .footer-container {
        grid-template-columns: 1fr;
    }
    
    .brand-column {
        grid-column: span 1;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const subscribeBtn = document.getElementById('subscribeBtn');
    const subscribeEmail = document.getElementById('subscribeEmail');
    const subscribeMessage = document.getElementById('subscribeMessage');
    
    if (subscribeBtn) {
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
                subscribeBtn.click();
            }
        });
    }
    
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

<!-- Theme toggle script (replaces theme.js) -->
<script>
    function toggleTheme() {
        const body = document.body;
        const themeToggle = document.getElementById('themeToggle');
        if (body.classList.contains('light-mode')) {
            body.classList.remove('light-mode');
            themeToggle.textContent = '🌙';
            localStorage.setItem('theme', 'dark');
        } else {
            body.classList.add('light-mode');
            themeToggle.textContent = '☀️';
            localStorage.setItem('theme', 'light');
        }
    }
    document.addEventListener('DOMContentLoaded', function () {
        const savedTheme = localStorage.getItem('theme');
        const themeToggle = document.getElementById('themeToggle');
        if (savedTheme === 'light') {
            document.body.classList.add('light-mode');
            themeToggle.textContent = '☀️';
        } else {
            themeToggle.textContent = '🌙';
        }
    });
</script>

</body>
</html>
