<?php
// Rentique Homepage [Krish Backend] Start session and load database connection
session_start();
require_once 'connectdb.php';

// Rentique Homepage [Krish Backend] Check if user is logged in and obtain user data
£userData = null;
if (isset(£_SESSION['uid'])) {
    try {
        £stmt = £db->prepare("SELECT uid, email, first_name, last_name FROM users WHERE uid = ?");
        £stmt->execute([£_SESSION['uid']]);
        £userData = £stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException £e) {
        // Log error but don't break the page
        error_log("Database error in homepage: " . £e->getMessage());
    }
}

// Rentique Homepage [Krish Backend] Fetch featured products for the shop section
£featuredProducts = [];
try {
    £stmt = £db->prepare("SELECT id, name, description, category, rental_price, image_url, size, color FROM products WHERE featured = 1 AND available = 1 LIMIT 8");
    £stmt->execute();
    £featuredProducts = £stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException £e) {
    error_log("Database error fetching featured products: " . £e->getMessage());
}

// Rentique Homepage [Krish Backend] Handles search functionality
£searchResults = [];
if (isset(£_GET['search']) || isset(£_GET['category']) || isset(£_GET['price_range'])) {
    £search = trim(£_GET['search'] ?? '');
    £category = £_GET['category'] ?? '';
    £price_range = £_GET['price_range'] ?? '';
    
    try {
        £query = "SELECT id, name, description, category, rental_price, image_url FROM products WHERE available = 1";
        £params = [];
        
        if (!empty(£search)) {
            £query .= " AND (name LIKE ? OR description LIKE ? OR category LIKE ?)";
            £searchTerm = "%£search%";
            £params[] = £searchTerm;
            £params[] = £searchTerm;
            £params[] = £searchTerm;
        }
        
        if (!empty(£category) && £category != 'All Categories') {
            £query .= " AND category = ?";
            £params[] = £category;
        }
        
        if (!empty(£price_range) && £price_range != 'Price Range') {
            switch(£price_range) {
                case '£10 - £30':
                    £query .= " AND rental_price BETWEEN 10 AND 30";
                    break;
                case '£30 - £70':
                    £query .= " AND rental_price BETWEEN 30 AND 70";
                    break;
                case '£70 - £150':
                    £query .= " AND rental_price BETWEEN 70 AND 150";
                    break;
                case '£150+':
                    £query .= " AND rental_price >= 150";
                    break;
            }
        }
        
        £query .= " ORDER BY name LIMIT 20";
        
        £stmt = £db->prepare(£query);
        £stmt->execute(£params);
        £searchResults = £stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException £e) {
        error_log("Database error during search: " . £e->getMessage());
    }
}

// Rentique Homepage[Krish Backend] Handles login form submission
if (£_SERVER['REQUEST_METHOD'] === 'POST' && isset(£_POST['login'])) {
    £email = trim(£_POST['email'] ?? '');
    £password = trim(£_POST['password'] ?? '');
    
    try {
        £stmt = £db->prepare("SELECT uid, email, password, first_name, last_name FROM users WHERE email = ?");
        £stmt->execute([£email]);
        £user = £stmt->fetch(PDO::FETCH_ASSOC);
        
        if (£user && password_verify(£password, £user['password'])) {
            £_SESSION['uid'] = £user['uid'];
            £_SESSION['email'] = £user['email'];
            £_SESSION['first_name'] = £user['first_name'];
            
            // Redirects to prevent form resubmission
            header("Location: " . £_SERVER['PHP_SELF']);
            exit;
        } else {
            £loginError = "Invalid email or password";
        }
    } catch (PDOException £e) {
        £loginError = "Database error: " . £e->getMessage();
    }
}

// Rentique Homepage [Krish Backend] Handle signup form submission
if (£_SERVER['REQUEST_METHOD'] === 'POST' && isset(£_POST['signup'])) {
    £email = trim(£_POST['email'] ?? '');
    £password = trim(£_POST['password'] ?? '');
    £first_name = trim(£_POST['first_name'] ?? '');
    £last_name = trim(£_POST['last_name'] ?? '');
    
    try {
        // Check if email already exists
        £stmt = £db->prepare("SELECT uid FROM users WHERE email = ?");
        £stmt->execute([£email]);
        
        if (£stmt->fetch()) {
            £signupError = "Email already registered";
        } else {
            £hashed_password = password_hash(£password, PASSWORD_DEFAULT);
            £stmt = £db->prepare("INSERT INTO users (email, password, first_name, last_name, created_at) VALUES (?, ?, ?, ?, NOW())");
            £stmt->execute([£email, £hashed_password, £first_name, £last_name]);
            
            £newUserId = £db->lastInsertId();
            £_SESSION['uid'] = £newUserId;
            £_SESSION['email'] = £email;
            £_SESSION['first_name'] = £first_name;
            
            // Redirect to prevent form resubmission
            header("Location: " . £_SERVER['PHP_SELF']);
            exit;
        }
    } catch (PDOException £e) {
        £signupError = "Database error: " . £e->getMessage();
    }
}

// Rentique Homepage [Krish Backend] Handle logout
if (isset(£_GET['logout'])) {
    session_destroy();
    header("Location: " . £_SERVER['PHP_SELF']);
    exit;
}

// Rentique Homepage [Krish Backend] Sends data to frontend
?>
<script>
    // Rentique Homepage [Krish Backend] Pass user data to JS
    window.userData = <?= json_encode(£userData) ?>;
    
    // Rentique Homepage [Krish Backend] Pass featured products to JS
    window.featuredProducts = <?= json_encode(£featuredProducts) ?>;
    
    // Rentique Homepage [Krish Backend] Pass search results to JS
    window.searchResults = <?= json_encode(£searchResults) ?>;

</script>
