<?php
session_start();
require_once 'connectdb.php';

$uid = $_SESSION['uid'] ?? null;
if (!$uid) {
    header("Location: login.php");
    exit;
}

$userData = null;
if (isset($_SESSION['uid'])) {
    try {
        $stmt = $db->prepare("SELECT uid, email, billing_fullname, role FROM users WHERE uid = ?");
        $stmt->execute([$_SESSION['uid']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Basket user fetch error: " . $e->getMessage());
    }
}

/* REMOVE ITEM */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {
    $removeId = intval($_POST['remove_id']);

    try {
        $stmt = $db->prepare("DELETE FROM basket WHERE id = ? AND uid = ?");
        $stmt->execute([$removeId, $uid]);
    } catch (PDOException $e) {
    }

    header("Location: basketPage.php");
    exit;
}

/* LOAD ITEMS */
try {
    $stmt = $db->prepare("SELECT id, pid, title, image, product_type, price, quantity, rental_days FROM basket WHERE uid = ?");
    $stmt->execute([$uid]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $items = [];
}

/* TOTALS */
$platformFeePerItem = 4.99;
$subtotal = 0;
$itemCount = 0;

foreach ($items as $item) {
    $qty = max(1, intval($item['quantity'] ?? 1));
    $days = max(1, intval($item['rental_days'] ?? 1));
    $subtotal += (floatval($item['price']) * $days * $qty);
    $itemCount += $qty;
}

$platformFee = $itemCount * $platformFeePerItem;
$shipping = $items ? 4.99 : 0.00;
$total = $subtotal + $platformFee + $shipping;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentique Shopping Cart</title>
    <link rel="stylesheet" href="css/rentique.css">
    <link rel="icon" type="image/png" href="images/rentique_logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="js/theme.js" defer></script>

    <!-- Theme toggle styles -->
    <style>
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
            <li><a href="FAQTestimonials.php">FAQ</a></li>

            <!-- Theme Toggle Button (no cart icon on basket page) -->
            <li>
                <button id="themeToggle" onclick="toggleTheme()">🌙</button>
            </li>

            <?php if (($userData['role'] ?? '') === 'customer'): ?>
                <li><a href="seller_dashboard.php">Sell</a></li>
                <li><a href="user_dashboard.php"><?= htmlspecialchars($userData['billing_fullname'] ?? "Account") ?></a></li>
                <li><a href="index.php?logout=1" class="btn login">Logout</a></li>
            <?php elseif (($userData['role'] ?? '') === 'admin'): ?>
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
        <h1>Shopping Cart</h1>
        <p class="subtitle">Review your items before checkout</p>
    </section>

    <div class="basketContainer">

        <div class="basketLeft">

            <?php if (!$items): ?>
                <p>Your basket is empty.</p>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <div class="basketItem">

                        <form method="post" style="margin:0;">
                            <input type="hidden" name="remove_id" value="<?= $item['id'] ?>">
                            <button type="submit" class="removeBtn">×</button>
                        </form>

                        <div class="itemImage">
                            <img src="images/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                        </div>

                        <div class="itemDetails">
                            <p class="category"><?= htmlspecialchars($item['product_type']) ?></p>
                            <h3><?= htmlspecialchars($item['title']) ?></h3>
                            <p class="itemSize">Days: <?= max(1, intval($item['rental_days'] ?? 1)) ?></p>
                            <p class="itemSize">Quantity: <?= max(1, intval($item['quantity'] ?? 1)) ?></p>
                            <p class="itemSize">£<?= number_format(floatval($item['price']), 2) ?> per day</p>
                        </div>

                        <div class="itemTotal">
                            <?php
                                $qty = max(1, intval($item['quantity'] ?? 1));
                                $days = max(1, intval($item['rental_days'] ?? 1));
                                $lineTotal = floatval($item['price']) * $days * $qty;
                            ?>
                            <p class="price">£<?= number_format($lineTotal, 2) ?></p>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>

        <div class="basketRight">
            <div class="summaryBox">
                <h2>Summary</h2>

                <div class="summaryLine">
                    <span>Subtotal</span>
                    <span>£<?= number_format($subtotal, 2) ?></span>
                </div>

                <div class="summaryLine">
                    <span>Platform fee (<?= $itemCount ?> items)</span>
                    <span>£<?= number_format($platformFee, 2) ?></span>
                </div>

                <div class="summaryLine">
                    <span>Shipping</span>
                    <span>£<?= number_format($shipping, 2) ?></span>
                </div>

                <div class="summaryTotal">
                    <span>Total</span>
                    <span class="price">£<?= number_format($total, 2) ?></span>
                </div>

                <button class="checkoutBtn" onclick="window.location.href='Checkout.php'">
                    Check Out
                </button>
            </div>
        </div>

    </div>
</main>

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

<!-- Theme toggle script -->
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
