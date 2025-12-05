<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentique | About Us</title>

<!--Saja - backend (toggleable theme)-->
<script>
document.addEventListener("DOMContentLoaded", () => {
    const theme = localStorage.getItem("theme") || "light";
    if (theme === "dark") document.body.classList.add("dark-mode");
});
</script>
	
    <link rel="stylesheet" href="rentique.css">
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
	
</head>
	
<body id="aboutPage">
<header>
    <nav class="navbar">
            <div class="logo">
                <a href="Homepage.php">
                <img src="images/rentique_logo.png" alt="Rentique Logo">
                </a>
                <span>rentique.</span>
            </div>
        <ul class="nav-links">
                <li><a href="Homepage.php">Home</a></li>
                <li><a href="productsPage.php">Shop</a></li>
                <li><a href="AboutUs.php">About</a></li>
                <li><a href="Contact.php">Contact</a></li>
                <li><a href="login.php" class="btn login">Login</a></li>
                <li><a href="signup.php" class="btn signup">Sign Up</a></li>
                <li><a href="basketPage.php" class="cart-icon"><img src="images/basket.png" alt="Basket"></a></li>
                <li><button id="theme-toggle" class="black-btn">Light/Dark</button></li>
        </ul>
    </nav>
</header>

	<div class="full-banner">
		<img src="images/banner4.png" alt="Banner">
		</div>

	
<section class="mission-section right-section">
    <div class="container">
        <div class="mission-content">
            <h3>Our Beginning</h3>
            <p>Project Dakar: Rentique began as a simple concept to address the significance about the current direction fashion is going and its impact on the world. An example is Dakar, Senegal, where  we experienced first-hand the effects of pollution created by fast fashion. After seeing the extreme impact, members of Rentique felt that change was needed</p>
            <p>What began as an idea swiftly developed into a platform with a clear message to spread awareness, accessibility, and sustainability. Our mission was always clear and that is to produce fashion that helps people look good while doing good. </p>
        </div>
    </div>
</section>

<section class="mission-section left-section">
    <div class="container">
        <div class="mission-layout">

            <div class="mission-content">
                <h3>Our Mission</h3>
                <p>Rentique is an online fashion rental service with the goal to reduce waste by extending the life of clothing and accessories. Not only does this promote recycling of unwanted clothing but gives everyone access to high-end fashion.</p>
                <p>From shipment to expert cleaning, Rentique takes care of all processes ensuring that appearance is never at the expense of morality.</p>

                <div class="mission-stats">
                    <div class="stat-item">
                        <div class="stat-number">5%</div>
                        <div class="stat-label">Of Earnings Donated</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">6-10%</div>
                        <div class="stat-label">Fast Fashion Emissions</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">100%</div>
                        <div class="stat-label">Eco-Conscious</div>
                    </div>
                </div>
            </div>

            <div class="mission-image">
                <img src="images/map4.png" alt="Map">
            </div>

        </div>
    </div>
</section>



<section class="container">
    <div class="section-header">
        <h2>Our Values</h2>
        <p>At Rentique, we stand by our values, they influence every decision we make.</p>
    </div>
        
    <div class="values-section">
        <div class="value-card">
            <div class="value-icon">
                <i class="fas fa-recycle"></i>
            </div>
            <h4>Sustainable Fashion</h4>
            <p>We extend cloth lifecycles through our rental model, drastically lowering waste and the carbon footprint from fast fashion.</p>
        </div>
            
        <div class="value-card">
            <div class="value-icon">
                <i class="fas fa-hands-helping"></i>
            </div>
            <h4>Community Investment</h4>
            <p>5% of all earnings go towards charitable organisation who strive to eliminate waste and increase social welfare across communities.</p>
        </div>
            
        <div class="value-card">
            <div class="value-icon">
                <i class="fas fa-tshirt"></i>
            </div>
            <h4>Accessible Luxury</h4>
            <p>We think everyone should be able to afford high-end fashion, not just a select few. At Rentique, our approach makes designer pieces affordable from anyone and anywhere.</p>
        </div>
    </div>
</section>
	
<footer>
    <p>© 2025 Rentique. All rights reserved.</p>
</footer>

<!--Saja - backend (toggleable theme)-->
<script>
const toggleBtn = document.getElementById("theme-toggle");

if (toggleBtn) {
    toggleBtn.addEventListener("click", () => {
        document.body.classList.toggle("dark-mode");
        localStorage.setItem("theme", 
            document.body.classList.contains("dark-mode") ? "dark" : "light"
        );
    });
}
</script>

</body>
</html>
