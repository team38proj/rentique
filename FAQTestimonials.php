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
    <title>Rentique | FAQ & Testimonials</title>
    <link rel="stylesheet" href="css/rentique.css">
    <link rel="icon" type="image/png" href="images/rentique_logo.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #0a0a0a;
            min-height: 100vh;
            color: #ffffff;
            font-size: 14px;
            transition: background 0.3s ease, color 0.3s ease;
        }

        html.light-mode body {
            background: #f5f5f5;
            color: #333333;
        }

        header {
            width: 100%;
            background: transparent;
        }

        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 2rem;
            background: rgba(10, 10, 10, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 255, 0, 0.1);
            transition: all 0.3s ease;
            width: 100%;
        }

        html.light-mode .navbar {
            background: rgba(255, 255, 255, 0.95);
            border-bottom: 1px solid rgba(0, 255, 0, 0.2);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo img {
            height: 30px;
            width: auto;
        }

        .logo span {
            font-size: 1.2rem;
            font-weight: 600;
            background: linear-gradient(135deg, #00ff00 0%, #32cd32 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            list-style: none;
        }

        .nav-links a {
            color: #ffffff;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        html.light-mode .nav-links a {
            color: #333333;
        }

        .nav-links a:hover {
            background: linear-gradient(135deg, #00ff00 0%, #32cd32 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-links .active {
            background: linear-gradient(135deg, #00ff00 0%, #32cd32 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

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
            stroke: #333333;
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

        .btn {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
        }

        .btn.login {
            border: 1px solid #00FF00;
            color: #00FF00;
        }

        html.light-mode .btn.login {
            border-color: #00FF00;
            color: #333333;
        }

        .btn.login:hover {
            background-color: #00FF00;
            color: #000;
        }

        .btn.signup {
            background-color: #00FF00;
            color: #000;
        }

        .btn.signup:hover {
            background-color: #d2ff4c;
        }

        main {
            padding: 1rem;
            background: transparent;
        }

        .faq-section, .testimonials-section {
            max-width: 1000px;
            margin: 1.5rem auto;
            padding: 1.5rem;
            background: rgba(10, 10, 10, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 15px 30px -12px rgba(0, 255, 0, 0.15);
            border: 1px solid rgba(0, 255, 0, 0.1);
            transition: all 0.3s ease;
        }

        html.light-mode .faq-section, 
        html.light-mode .testimonials-section {
            background: rgba(255, 255, 255, 0.95);
            border-color: rgba(0, 255, 0, 0.2);
            box-shadow: 0 15px 30px -12px rgba(0, 255, 0, 0.2);
        }

        .section-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #00ff00 0%, #32cd32 50%, #00ff00 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
            text-shadow: 0 0 20px rgba(0, 255, 0, 0.3);
            animation: gradientShift 3s ease infinite;
            background-size: 200% 200%;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .section-subtitle {
            font-size: 0.9rem;
            color: #a0a0a0;
            max-width: 500px;
            margin: 0 auto;
            line-height: 1.5;
        }

        html.light-mode .section-subtitle {
            color: #666666;
        }

        .faq-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 0.8rem;
        }

        .faq-item {
            background: rgba(20, 20, 20, 0.95);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 15px -8px rgba(0, 255, 0, 0.15);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 255, 0, 0.2);
            backdrop-filter: blur(10px);
        }

        html.light-mode .faq-item {
            background: rgba(240, 240, 240, 0.95);
            border-color: rgba(0, 255, 0, 0.3);
        }

        .faq-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -12px rgba(0, 255, 0, 0.4);
            border-color: rgba(0, 255, 0, 0.4);
        }

        .faq-question {
            padding: 1rem 1.2rem;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(15, 15, 15, 0.95);
            transition: all 0.3s ease;
            color: #ffffff;
        }

        html.light-mode .faq-question {
            background: rgba(230, 230, 230, 0.95);
            color: #333333;
        }

        .faq-question.active {
            background: linear-gradient(135deg, #00ff00 0%, #32cd32 100%);
            color: #0a0a0a;
        }

        .faq-question.active span {
            color: #0a0a0a;
            transform: rotate(180deg);
        }

        .faq-question span {
            font-size: 1rem;
            color: #00ff00;
            transition: transform 0.3s ease;
        }

        html.light-mode .faq-question span {
            color: #00aa00;
        }

        .faq-answer {
            max-height: 0;
            padding: 0 1.2rem;
            background: rgba(20, 20, 20, 0.95);
            line-height: 1.5;
            color: #d0d0d0;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.85rem;
        }

        html.light-mode .faq-answer {
            background: rgba(240, 240, 240, 0.95);
            color: #444444;
        }

        .faq-answer.show {
            max-height: 200px;
            padding: 1rem 1.2rem;
        }

        .testimonials-carousel {
            position: relative;
            padding: 1rem 0;
            width: 100%;
            overflow: visible;
        }

        .carousel-container {
            overflow: hidden;
            border-radius: 20px;
            width: 100%;
        }

        .carousel-track {
            display: flex;
            transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            width: 100%;
        }

        .testimonial-card {
            flex: 0 0 100%;
            padding: 1.5rem;
            background: linear-gradient(135deg, rgba(0, 255, 0, 0.1) 0%, rgba(50, 205, 50, 0.1) 100%);
            color: white;
            text-align: center;
            border-radius: 20px;
            position: relative;
            isolation: isolate;
            border: 1px solid rgba(0, 255, 0, 0.2);
            backdrop-filter: blur(10px);
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        html.light-mode .testimonial-card {
            background: linear-gradient(135deg, rgba(0, 255, 0, 0.05) 0%, rgba(50, 205, 50, 0.05) 100%);
            color: #333333;
            border-color: rgba(0, 255, 0, 0.3);
        }

        .testimonial-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 50% 0%, rgba(0, 255, 0, 0.2), transparent 70%);
            z-index: -1;
        }

        .testimonial-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #00ff00 0%, #32cd32 100%);
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 600;
            color: #0a0a0a;
            border: 2px solid rgba(0, 255, 0, 0.3);
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.5);
        }

        .testimonial-text {
            font-size: 1rem;
            line-height: 1.5;
            max-width: 600px;
            margin: 0 auto 1rem;
            font-style: italic;
            font-weight: 400;
            color: #ffffff;
            text-shadow: 0 0 10px rgba(0, 255, 0, 0.3);
        }

        html.light-mode .testimonial-text {
            color: #333333;
            text-shadow: none;
        }

        .testimonial-author {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.2rem;
            background: linear-gradient(135deg, #00ff00 0%, #32cd32 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .testimonial-location {
            font-size: 0.8rem;
            color: #a0a0a0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
        }

        html.light-mode .testimonial-location {
            color: #666666;
        }

        .testimonial-location::before {
            content: '📍';
            font-size: 0.9rem;
        }

        .testimonial-rating {
            margin-top: 0.8rem;
            font-size: 1rem;
            letter-spacing: 2px;
            color: #00ff00;
            text-shadow: 0 0 10px #00ff00;
        }

        .carousel-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
        }

        .carousel-btn {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: none;
            background: rgba(20, 20, 20, 0.95);
            color: #00ff00;
            font-size: 1.3rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px -5px rgba(0, 255, 0, 0.3);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 255, 0, 0.3);
            backdrop-filter: blur(10px);
        }

        html.light-mode .carousel-btn {
            background: rgba(240, 240, 240, 0.95);
            color: #00aa00;
            border-color: rgba(0, 255, 0, 0.5);
        }

        .carousel-btn:hover {
            background: linear-gradient(135deg, #00ff00 0%, #32cd32 100%);
            color: #0a0a0a;
            transform: scale(1.1);
            box-shadow: 0 10px 20px -8px rgba(0, 255, 0, 0.6);
            border-color: transparent;
        }

        .carousel-dots {
            display: flex;
            justify-content: center;
            gap: 0.6rem;
            margin: 1rem 0;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(0, 255, 0, 0.2);
            border: 1px solid rgba(0, 255, 0, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 0;
        }

        .dot.active {
            background: linear-gradient(135deg, #00ff00 0%, #32cd32 100%);
            transform: scale(1.2);
            box-shadow: 0 0 15px #00ff00;
            border-color: transparent;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 2rem;
            padding: 1.2rem;
            background: rgba(15, 15, 15, 0.95);
            border-radius: 20px;
            border: 1px solid rgba(0, 255, 0, 0.2);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        html.light-mode .stats-container {
            background: rgba(240, 240, 240, 0.95);
            border-color: rgba(0, 255, 0, 0.3);
        }

        .stat-item {
            text-align: center;
            position: relative;
        }

        .stat-item::after {
            content: '';
            position: absolute;
            right: -0.5rem;
            top: 50%;
            transform: translateY(-50%);
            width: 1px;
            height: 25px;
            background: linear-gradient(135deg, #00ff00 0%, #32cd32 100%);
            opacity: 0.3;
        }

        .stat-item:last-child::after {
            display: none;
        }

        .stat-number {
            font-size: 1.6rem;
            font-weight: 700;
            background: linear-gradient(135deg, #00ff00 0%, #32cd32 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.2rem;
            text-shadow: 0 0 15px rgba(0, 255, 0, 0.3);
        }

        .stat-label {
            color: #a0a0a0;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        html.light-mode .stat-label {
            color: #666666;
        }

        .contact-prompt {
            text-align: center;
            margin: 2rem 0 1rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, rgba(0, 255, 0, 0.1) 0%, rgba(50, 205, 50, 0.1) 100%);
            border-radius: 20px;
            border: 1px solid rgba(0, 255, 0, 0.3);
            backdrop-filter: blur(10px);
            position: relative;
        }

        html.light-mode .contact-prompt {
            background: linear-gradient(135deg, rgba(0, 255, 0, 0.05) 0%, rgba(50, 205, 50, 0.05) 100%);
            border-color: rgba(0, 255, 0, 0.4);
        }

        .contact-prompt h3 {
            font-size: 1.4rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #00ff00 0%, #32cd32 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
        }

        .contact-prompt p {
            font-size: 0.9rem;
            margin-bottom: 1.2rem;
            color: #d0d0d0;
            position: relative;
        }

        html.light-mode .contact-prompt p {
            color: #444444;
        }

        .contact-btn {
            display: inline-block;
            padding: 0.6rem 1.8rem;
            background: linear-gradient(135deg, #00ff00 0%, #32cd32 100%);
            color: #0a0a0a;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px -5px rgba(0, 255, 0, 0.3);
            position: relative;
            border: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .contact-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -8px rgba(0, 255, 0, 0.5);
        }

        .faq-link-active {
            position: relative;
            background: linear-gradient(135deg, #00ff00 0%, #32cd32 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .faq-link-active::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 2px;
            background: linear-gradient(135deg, #00ff00 0%, #32cd32 100%);
            border-radius: 2px;
            box-shadow: 0 0 10px #00ff00;
        }

        footer {
            text-align: center;
            padding: 1.2rem;
            color: #a0a0a0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            margin-top: 2rem;
            border-top: 1px solid rgba(0, 255, 0, 0.1);
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        html.light-mode footer {
            background: rgba(240, 240, 240, 0.9);
            color: #666666;
            border-top-color: rgba(0, 255, 0, 0.2);
        }

        ::-webkit-scrollbar {
            width: 5px;
        }

        ::-webkit-scrollbar-track {
            background: #0a0a0a;
        }

        html.light-mode ::-webkit-scrollbar-track {
            background: #f0f0f0;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #00ff00 0%, #32cd32 100%);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #32cd32 0%, #00ff00 100%);
        }
    </style>
</head>
<body id="faqTestimonialsPage">

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
            <li><a href="basketPage.php" class="cart-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"></path>
                </svg>
            </a></li>
            <button id="themeToggle">🌙</button>

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

<main>
    <section class="faq-section">
        <div class="section-header">
            <h1 class="section-title">Frequently Asked Questions</h1>
            <p class="section-subtitle">Everything you need to know about renting with Rentique. Can't find what you're looking for? Feel free to contact our support team.</p>
        </div>
        
        <div class="faq-grid">
            <div class="faq-item">
                <div class="faq-question" id="q1">
                    How does renting work? <span id="s1">▼</span>
                </div>
                <div class="faq-answer" id="a1">
                    Simply browse our collection, choose your favourite items, select rental dates, and proceed to checkout. We'll deliver the pieces to your doorstep, and you return them after use — dry cleaning included. All rentals come with free shipping both ways and a 24-hour grace period for returns.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" id="q2">
                    What if the item doesn't fit? <span id="s2">▼</span>
                </div>
                <div class="faq-answer" id="a2">
                    We offer one free size exchange per rental, subject to availability. Contact us within 24h of receiving your order to arrange a swap. We'll ship the new size immediately and cover all return shipping costs. Our size guide and virtual fitting tool can help you choose the perfect fit first time.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" id="q3">
                    How are items cleaned? <span id="s3">▼</span>
                </div>
                <div class="faq-answer" id="a3">
                    Every returned piece is professionally dry-cleaned and sanitised by our eco-friendly partner using sustainable methods. Quality and hygiene are our top priorities. Each item undergoes a 15-point inspection checklist before being rented again, ensuring you receive nothing less than perfection.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" id="q4">
                    What if I damage an item? <span id="s4">▼</span>
                </div>
                <div class="faq-answer" id="a4">
                    Minor wear is expected. For significant damage, a repair fee may apply — but we offer a damage waiver option at checkout for complete peace of mind. With the waiver, you're covered for accidental damage up to £500. Without it, repair costs are capped at 40% of the retail value.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" id="q5">
                    Can I extend my rental period? <span id="s5">▼</span>
                </div>
                <div class="faq-answer" id="a5">
                    Yes! Log in to your dashboard and request an extension before the return date. Extensions are subject to availability and an additional daily fee at 30% of the original daily rate. You can extend up to twice per rental, with a maximum total rental period of 30 days.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" id="q6">
                    What about shipping times? <span id="s6">▼</span>
                </div>
                <div class="faq-answer" id="a6">
                    We offer free next-day delivery on all orders placed before 2pm Monday-Friday. Saturday delivery is available for a small fee. Returns are just as easy — use the prepaid label in your package, drop off at any Collect+ point, and we'll handle the rest.
                </div>
            </div>
        </div>
    </section>

    <section class="testimonials-section">
        <div class="section-header">
            <h1 class="section-title">What Our Renters Say</h1>
            <p class="section-subtitle">Join thousands of happy customers who've discovered the joy of renting with Rentique</p>
        </div>

        <div class="testimonials-carousel">
            <div class="carousel-container">
                <div class="carousel-track" id="carouselTrack">
                    <div class="testimonial-card">
                        <div class="testimonial-avatar">K</div>
                        <div class="testimonial-text">"Absolutely stunning trench coat! I felt like a movie star at the premiere. The process was seamless and the coat arrived immaculate. Already planning my next rental!"</div>
                        <div class="testimonial-author">Krish S.</div>
                        <div class="testimonial-location">Birmingham</div>
                        <div class="testimonial-rating">★★★★★</div>
                    </div>
                    
                    <div class="testimonial-card">
                        <div class="testimonial-avatar">J</div>
                        <div class="testimonial-text">"Rented a puffer jacket for a ski trip — warm, stylish, and saved me buying expensive gear I'd rarely use. Will definitely rent again! The quality exceeded my expectations."</div>
                        <div class="testimonial-author">James T.</div>
                        <div class="testimonial-location">Manchester</div>
                        <div class="testimonial-rating">★★★★★</div>
                    </div>
                    
                    <div class="testimonial-card">
                        <div class="testimonial-avatar">P</div>
                        <div class="testimonial-text">"As a bridesmaid, I needed a one-time elegant outfit. Rentique delivered perfection. So many compliments and zero commitment. The fit was perfect and the return was effortless."</div>
                        <div class="testimonial-author">Priya K.</div>
                        <div class="testimonial-location">Birmingham</div>
                        <div class="testimonial-rating">★★★★★</div>
                    </div>
                    
                    <div class="testimonial-card">
                        <div class="testimonial-avatar">A</div>
                        <div class="testimonial-text">"The denim jacket was exactly as pictured. Quick delivery, easy return. Sustainable fashion at its best! I love that I can wear designer pieces without the environmental guilt."</div>
                        <div class="testimonial-author">Alex M.</div>
                        <div class="testimonial-location">Brighton</div>
                        <div class="testimonial-rating">★★★★★</div>
                    </div>
                </div>
            </div>

            <div class="carousel-dots" id="carouselDots"></div>
            
            <div class="carousel-controls">
                <button class="carousel-btn" id="prevBtn" aria-label="Previous">←</button>
                <button class="carousel-btn" id="nextBtn" aria-label="Next">→</button>
            </div>
        </div>

        <div class="stats-container">
            <div class="stat-item">
                <div class="stat-number">67,000+</div>
                <div class="stat-label">Happy Renters</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">4.9/5</div>
                <div class="stat-label">Average Rating</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">98%</div>
                <div class="stat-label">Would Recommend</div>
            </div>
        </div>

        <div class="contact-prompt">
            <h3>Still have questions?</h3>
            <p>Our support team is here to help you 24/7</p>
            <a href="Contact.php" class="contact-btn">Contact Us →</a>
        </div>
    </section>
</main>

<footer>
    <p>© 2025 Rentique — All Rights Reserved.</p>
</footer>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const toggle = document.getElementById("themeToggle");
        if (!toggle) return;

        const saved = localStorage.getItem("rentique_theme");
        if (saved === "light") {
            document.documentElement.classList.add("light-mode");
            toggle.textContent = "☀️";
        } else {
            toggle.textContent = "🌙";
        }

        toggle.addEventListener("click", function () {
            document.documentElement.classList.toggle("light-mode");
            const isLight = document.documentElement.classList.contains("light-mode");
            localStorage.setItem("rentique_theme", isLight ? "light" : "dark");
            toggle.textContent = isLight ? "☀️" : "🌙";
        });

        const questions = [];
        for (let i = 1; i <= 6; i++) {
            questions.push({
                question: document.getElementById(`q${i}`),
                answer: document.getElementById(`a${i}`),
                span: document.getElementById(`s${i}`)
            });
        }
        
        questions.forEach((item, index) => {
            if (item.question) {
                item.question.addEventListener('click', function(e) {
                    e.stopPropagation();
                    
                    const isActive = this.classList.contains('active');
                    
                    questions.forEach((otherItem, otherIndex) => {
                        if (otherIndex !== index) {
                            if (otherItem.question) {
                                otherItem.question.classList.remove('active');
                                otherItem.answer.classList.remove('show');
                                if (otherItem.span) otherItem.span.innerHTML = '▼';
                            }
                        }
                    });
                    
                    if (!isActive) {
                        this.classList.add('active');
                        item.answer.classList.add('show');
                        if (item.span) item.span.innerHTML = '▲';
                    } else {
                        this.classList.remove('active');
                        item.answer.classList.remove('show');
                        if (item.span) item.span.innerHTML = '▼';
                    }
                });
            }
        });

        const track = document.getElementById('carouselTrack');
        const cards = Array.from(document.querySelectorAll('.testimonial-card'));
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const dotsContainer = document.getElementById('carouselDots');

        if (track && cards.length) {
            let currentIndex = 0;
            const totalSlides = cards.length;

            cards.forEach((_, i) => {
                const dot = document.createElement('button');
                dot.classList.add('dot');
                if (i === 0) dot.classList.add('active');
                dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
                dot.addEventListener('click', () => goToSlide(i));
                dotsContainer.appendChild(dot);
            });
            
            const dots = document.querySelectorAll('.dot');

            function updateDots() {
                dots.forEach((dot, i) => {
                    dot.classList.toggle('active', i === currentIndex);
                });
            }

            function goToSlide(index) {
                if (index < 0) index = totalSlides - 1;
                if (index >= totalSlides) index = 0;
                currentIndex = index;
                track.style.transform = `translateX(-${currentIndex * 100}%)`;
                updateDots();
            }

            prevBtn.addEventListener('click', () => {
                goToSlide(currentIndex - 1);
            });

            nextBtn.addEventListener('click', () => {
                goToSlide(currentIndex + 1);
            });

            let interval = setInterval(() => goToSlide(currentIndex + 1), 5000);

            const carousel = document.querySelector('.testimonials-carousel');
            carousel.addEventListener('mouseenter', () => clearInterval(interval));
            carousel.addEventListener('mouseleave', () => {
                clearInterval(interval);
                interval = setInterval(() => goToSlide(currentIndex + 1), 5000);
            });

            goToSlide(0);
        }

        const navLinks = document.querySelectorAll('.nav-links a');
        navLinks.forEach(link => {
            if (link.getAttribute('href') === 'FAQTestimonials.php') {
                link.classList.add('faq-link-active');
            }
        });
    });
</script>

</body>
</html>
