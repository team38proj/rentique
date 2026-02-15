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
    <script src="js/theme.js" defer></script>
    <style>
   
        .faq-section, .testimonials-section {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 2rem;
        }

        .section-title {
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 2rem;
            color: var(--heading-color, #222);
            border-bottom: 3px solid var(--accent, #c7a97e);
            display: inline-block;
            padding-bottom: 0.5rem;
        }

        .faq-grid {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .faq-item {
            border: 1px solid var(--border-light, #ddd);
            border-radius: 12px;
            overflow: hidden;
            background: var(--card-bg, #fff);
            transition: box-shadow 0.2s;
        }
        .faq-item:hover {
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
        }

        .faq-question {
            background: var(--card-bg, #f9f9f9);
            padding: 1.2rem 2rem;
            font-weight: 600;
            font-size: 1.2rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid transparent;
            transition: background 0.2s;
        }
        .faq-question.active {
            border-bottom-color: var(--accent, #c7a97e);
            background: var(--light-accent, #f2ebe2);
        }
        .faq-question span {
            font-size: 1.6rem;
            user-select: none;
            color: var(--accent, #c7a97e);
        }

        .faq-answer {
            max-height: 0;
            padding: 0 2rem;
            background: var(--card-bg, #fff);
            line-height: 1.6;
            overflow: hidden;
            transition: max-height 0.35s ease, padding 0.2s ease;
        }
        .faq-answer.show {
            max-height: 300px; 
            padding: 1.5rem 2rem;
        }

        .testimonials-carousel {
            position: relative;
            background: var(--card-bg, #fafafa);
            border-radius: 28px;
            padding: 3rem 3rem 5rem;
            box-shadow: 0 20px 30px -10px rgba(0,0,0,0.15);
            border: 1px solid var(--border-light, #eee);
        }

        .carousel-container {
            overflow: hidden;
            min-height: 260px;
        }

        .carousel-track {
            display: flex;
            transition: transform 0.5s ease-in-out;
        }

        .testimonial-card {
            flex: 0 0 100%;
            padding: 1rem 2rem;
            box-sizing: border-box;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .testimonial-text {
            font-size: 1.5rem;
            font-style: italic;
            color: var(--text-color, #333);
            max-width: 700px;
            margin: 1rem auto;
            line-height: 1.6;
            position: relative;
        }
        .testimonial-text::before {
            content: "“";
            font-size: 4rem;
            position: absolute;
            left: -2rem;
            top: -1.5rem;
            opacity: 0.2;
            font-family: serif;
        }
        .testimonial-text::after {
            content: "”";
            font-size: 4rem;
            position: absolute;
            right: -2rem;
            bottom: -2rem;
            opacity: 0.2;
            font-family: serif;
        }

        .testimonial-author {
            font-weight: 600;
            font-size: 1.2rem;
            color: var(--accent, #c7a97e);
            margin-top: 1.5rem;
        }
        .testimonial-location {
            font-size: 0.9rem;
            color: #888;
        }

        .carousel-controls {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }
        .carousel-btn {
            background: var(--btn-bg, #fff);
            border: 2px solid var(--accent, #c7a97e);
            color: var(--accent, #c7a97e);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            font-size: 1.8rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }
        .carousel-btn:hover {
            background: var(--accent, #c7a97e);
            color: white;
        }

        .carousel-dots {
            display: flex;
            justify-content: center;
            gap: 0.6rem;
            margin: 1rem 0 2rem;
        }
        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #ccc;
            border: none;
            padding: 0;
            cursor: pointer;
            transition: 0.2s;
        }
        .dot.active {
            background: var(--accent, #c7a97e);
            transform: scale(1.3);
        }

        .faq-link-active {
            font-weight: bold;
            border-bottom: 2px solid var(--accent);
        }

        body {
            --heading-color: #1e1e2a;
            --text-color: #2d2d3a;
            --card-bg: #ffffff;
            --border-light: #eaeaea;
            --light-accent: #f7f2ec;
            --accent: #b38b5f;
            --btn-bg: #ffffff;
        }
        body.dark-mode {
            --heading-color: #f0f0f0;
            --text-color: #eaeaea;
            --card-bg: #2a2a35;
            --border-light: #3f3f4e;
            --light-accent: #3a3330;
            --accent: #d4a373;
            --btn-bg: #2a2a35;
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
            <li><a href="basketPage.php" class="cart-icon">Basket</a></li>
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

<main>
    <!-- FAQ SECTION -->
    <section class="faq-section">
        <h2 class="section-title">Frequently Asked Questions</h2>
        <div class="faq-grid">
            <div class="faq-item">
                <div class="faq-question">
                    How does renting work? <span>▼</span>
                </div>
                <div class="faq-answer">
                    Simply browse our collection, choose your favourite items, select rental dates, and proceed to checkout. We’ll deliver the pieces to your doorstep, and you return them after use — dry cleaning included.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">
                    What if the item doesn't fit? <span>▼</span>
                </div>
                <div class="faq-answer">
                    We offer one free size exchange per rental, subject to availability. Contact us within 24h of receiving your order to arrange a swap.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">
                    How are items cleaned? <span>▼</span>
                </div>
                <div class="faq-answer">
                    Every returned piece is professionally dry-cleaned and sanitised by our eco‑friendly partner. Quality and hygiene are our top priorities.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">
                    What if I damage an item? <span>▼</span>
                </div>
                <div class="faq-answer">
                    Minor wear is expected. For significant damage, a repair fee may apply — but we offer a damage waiver option at checkout for peace of mind.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">
                    Can I extend my rental period? <span>▼</span>
                </div>
                <div class="faq-answer">
                    Yes! Log in to your dashboard and request an extension before the return date. Extensions are subject to availability and an additional daily fee.
                </div>
            </div>
        </div>
    </section>

    <!-- TESTIMONIALS SECTION -->
    <section class="testimonials-section">
        <h2 class="section-title">What Our Renters Say</h2>

        <div class="testimonials-carousel">
            <div class="carousel-container">
                <div class="carousel-track" id="carouselTrack">
              
                    <div class="testimonial-card">
                        <div class="testimonial-text">Absolutely stunning trench coat! I felt like a movie star at the premiere. The process was seamless and the coat arrived immaculate.</div>
                        <div class="testimonial-author">— Navun M.</div>
                        <div class="testimonial-location">London</div>
                    </div>
                    <div class="testimonial-card">
                        <div class="testimonial-text">Rented a puffer jacket for a ski trip — warm, stylish, and saved me buying expensive gear I’d rarely use. Will definitely rent again!</div>
                        <div class="testimonial-author">— Harman S.</div>
                        <div class="testimonial-location">Manchester</div>
                    </div>
                    <div class="testimonial-card">
                        <div class="testimonial-text">As a bridesmaid, I needed a one-time elegant outfit. Rentique delivered perfection. So many compliments and zero commitment.</div>
                        <div class="testimonial-author">— Priya K.</div>
                        <div class="testimonial-location">Birmingham</div>
                    </div>
                    <div class="testimonial-card">
                        <div class="testimonial-text">The denim jacket was exactly as pictured. Quick delivery, easy return. Sustainable fashion at its best!</div>
                        <div class="testimonial-author">— Alex M.</div>
                        <div class="testimonial-location">Brighton</div>
                    </div>
                </div>
            </div>

            
            <div class="carousel-dots" id="carouselDots"></div>
            <div class="carousel-controls">
                <button class="carousel-btn" id="prevBtn" aria-label="Previous">‹</button>
                <button class="carousel-btn" id="nextBtn" aria-label="Next">›</button>
            </div>
        </div>
    </section>
</main>

<script>
    (function() {
    
        const faqItems = document.querySelectorAll('.faq-item');
        faqItems.forEach(item => {
            const question = item.querySelector('.faq-question');
            const answer = item.querySelector('.faq-answer');
            const arrow = question.querySelector('span');

            question.addEventListener('click', () => {
                const isActive = question.classList.contains('active');
        
                document.querySelectorAll('.faq-question').forEach(q => {
                    if (q !== question) {
                        q.classList.remove('active');
                        q.nextElementSibling.classList.remove('show');
                        const otherArrow = q.querySelector('span');
                        if (otherArrow) otherArrow.innerHTML = '▼';
                    }
                });

           
                if (!isActive) {
                    question.classList.add('active');
                    answer.classList.add('show');
                    arrow.innerHTML = '▲';
                } else {
                    question.classList.remove('active');
                    answer.classList.remove('show');
                    arrow.innerHTML = '▼';
                }
            });
        });

        const track = document.getElementById('carouselTrack');
        const cards = Array.from(document.querySelectorAll('.testimonial-card'));
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const dotsContainer = document.getElementById('carouselDots');

        if (track && cards.length) {
            let currentIndex = 0;
            const totalSlides = cards.length;

            // create dots
            cards.forEach((_, i) => {
                const dot = document.createElement('button');
                dot.classList.add('dot');
                if (i === 0) dot.classList.add('active');
                dot.setAttribute('data-index', i);
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

            // Rotates every 6 seconds
            let interval = setInterval(() => goToSlide(currentIndex + 1), 6000);

            // Pause on hover
            const carousel = document.querySelector('.testimonials-carousel');
            carousel.addEventListener('mouseenter', () => clearInterval(interval));
            carousel.addEventListener('mouseleave', () => {
                interval = setInterval(() => goToSlide(currentIndex + 1), 6000);
            });

         
            goToSlide(0);
        }

   
        const navLinks = document.querySelectorAll('.nav-links a');
        navLinks.forEach(link => {
            if (link.getAttribute('href') === 'FAQTestimonials.php') {
                link.classList.add('faq-link-active');
            }
        });
    })();
</script>

<!-- footer or extra content (optional) -->
<footer style="text-align: center; padding: 3rem 0; color: #777; border-top: 1px solid #ddd; margin-top: 4rem;">
    <p>© 2025 Rentique — All Rights Reserved.</p>
</footer>

</body>
</html>