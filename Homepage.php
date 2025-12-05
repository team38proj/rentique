<?php
session_start();
require_once 'connectdb.php';

// Rentique Homepage [Krish Backend] checks if user's logged in and obtains their data
$userData = null;
if (isset($_SESSION['uid'])) {
    try {
        $stmt = $db->prepare("SELECT uid, email, first_name, last_name FROM users WHERE uid = ?");
        $stmt->execute([$_SESSION['uid']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log error but don't break the page
        error_log("Database error in homepage: " . $e->getMessage());
    }
}

// Rentique Homepage [Krish Backend] fetches featured products in the shop section
$featuredProducts = [];
try {
    $stmt = $db->prepare("SELECT id, name, description, category, rental_price, image_url, size, color FROM products WHERE featured = 1 AND available = 1 LIMIT 8");
    $stmt->execute();
    $featuredProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error fetching featured products: " . $e->getMessage());
}

// Rentique Homepage [Krish Backend] search functionality
$searchResults = [];
if (isset($_GET['search']) || isset($_GET['category']) || isset($_GET['price_range'])) {
    $search = trim($_GET['search'] ?? '');
    $category = $_GET['category'] ?? '';
    $price_range = $_GET['price_range'] ?? '';
    
    try {
        $query = "SELECT id, name, description, category, rental_price, image_url FROM products WHERE available = 1";
        $params = [];
        
        if (!empty($search)) {
            $query .= " AND (name LIKE ? OR description LIKE ? OR category LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($category) && $category != 'All Categories') {
            $query .= " AND category = ?";
            $params[] = $category;
        }
        
        if (!empty($price_range) && $price_range != 'Price Range') {
            switch($price_range) {
                case '£10 - £30':
                    $query .= " AND rental_price BETWEEN 10 AND 30";
                    break;
                case '£30 - £70':
                    $query .= " AND rental_price BETWEEN 30 AND 70";
                    break;
                case '£70 - £150':
                    $query .= " AND rental_price BETWEEN 70 AND 150";
                    break;
                case '£150+':
                    $query .= " AND rental_price >= 150";
                    break;
            }
        }
        
        $query .= " ORDER BY name LIMIT 20";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Database error during search: " . $e->getMessage());
    }
}

// Rentique Homepage[Krish Backend] login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    try {
        $stmt = $db->prepare("SELECT uid, email, password, first_name, last_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['uid'] = $user['uid'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            
            // redirection to prevent resubmission 
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $loginError = "Invalid email or password";
        }
    } catch (PDOException $e) {
        $loginError = "Database error: " . $e->getMessage();
    }
}

// Rentique Homepage [Krish Backend] signup form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    
    try {
        // checks if email already exists
        $stmt = $db->prepare("SELECT uid FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $signupError = "Email already registered";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (email, password, first_name, last_name, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$email, $hashed_password, $first_name, $last_name]);
            
            $newUserId = $db->lastInsertId();
            $_SESSION['uid'] = $newUserId;
            $_SESSION['email'] = $email;
            $_SESSION['first_name'] = $first_name;
            
            // redirection to prevent resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    } catch (PDOException $e) {
        $signupError = "Database error: " . $e->getMessage();
    }
}

// Rentique Homepage [Krish Backend] manages logout 
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Rentique Homepage [Krish Backend] data sends to frontend
?>
<script>
    window.userData = <?php echo json_encode($userData); ?>;
    window.featuredProducts = <?php echo json_encode($featuredProducts); ?>;
    window.searchResults = <?php echo json_encode($searchResults); ?>;
</script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentique | Home</title>
    <link rel="stylesheet" href="rentique.css">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="rentique_logo.png">

<!--Saja - backend (toggleable theme)-->
<script>
document.addEventListener("DOMContentLoaded", () => {
    const currentTheme = localStorage.getItem("theme") || "light";
    if (currentTheme === "dark") {
        document.body.classList.add("dark-mode");
    }
});
</script>
    
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
                <li><a href="Homepage.php">Home</a></li>
                <li><a href="productsPage.php">Shop</a></li>
                <li><a href="AboutUs.php">About</a></li>
                <li><a href="Contact.php">Contact</a></li>
                <li><a href="login.php" class="btn login">Login</a></li>
                <li><a href="signup.php" class="btn signup">Sign Up</a></li>
                <li><a href="basketPage.php" class="cart-icon"><img src="basket.png" alt="Basket"></a></li>
                <li><button id="theme-toggle" class="black-btn">Light/Dark</button></li>
            </ul>
        </nav>

     
        <section class="search-section">
            <div class="search-container">
                <input type="text" placeholder="Search for dresses, suits, jackets, accessories...">

                <select>
                    <option>All Categories</option>
                    <option>Dresses</option>
                    <option>Menswear</option>
                    <option>Accessories</option>
                    <option>Formal Wear</option>
                    <option>Casual</option>
                </select>

                <select>
                    <option>Price Range</option>
                    <option>£10 - £30</option>
                    <option>£30 - £70</option>
                    <option>£70 - £150</option>
                    <option>£150+</option>
                </select>

                <button class="search-btn">Search</button>
            </div>
        </section>

    </header>

    <img src="rentiquebanner2.png" width= "1255">

    <section class="hero">
        <div class="hero-content">
            <h1>Style That Moves With You</h1>
            <p>Rent. Wear. Return. Experience fashion freedom with Rentique – the modern way to shop sustainably.</p>
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
                <p>See how outfits look on you using AI-powered virtual fitting before renting or purchasing.</p>
            </div>
            <div class="feature-card enhanced">
                <h3>Easy Rentals</h3>
                <p>Browse, rent, and enjoy designer outfits delivered right to your doorstep.</p>
            </div>
            <div class="feature-card enhanced">
                <h3>Feedback System</h3>
                <p>Share your experience and help us improve – your opinion shapes Rentique.</p>
            </div>
            <div class="feature-card enhanced">
                <h3>Secure Purchases</h3>
                <p>Enjoy a smooth and secure checkout with trusted payment options.</p>
            </div>
        </div>
    </section>

    <section id="shop"></section>

    <footer>
        <p>© 2025 Rentique. All rights reserved.</p>
    </footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
        const toggleBtn = document.getElementById('theme-toggle');
        const body = document.body;

        // If button not present, nothing to do
        if (!toggleBtn) return;

        // Initialize state from localStorage
        const saved = localStorage.getItem('theme');
        if (saved === 'dark') {
            body.classList.add('dark-mode');
            toggleBtn.setAttribute('aria-pressed', 'true');
        } else {
            toggleBtn.setAttribute('aria-pressed', 'false');
        }

        toggleBtn.addEventListener('click', function () {
            const isDark = body.classList.toggle('dark-mode');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            toggleBtn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        });
    });
    </script>
    
</body>
</html>




