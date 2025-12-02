<?php
// connects to database
require_once ("connectdb.php");
$search = htmlspecialchars($_GET['search'] ?? '');
$typesearch = htmlspecialchars($_GET['typesearch'] ?? '');

try {
    if ($search && $typesearch) {
        $query = $db->prepare("SELECT * FROM products WHERE title like ? and product_type = ?");
        $query->execute(["%$search%",$typesearch]);
    } else if ($search) {
        $query = $db->prepare("SELECT * FROM products WHERE title like ?");
        $query->execute(["%$search%"]);
    } else if ($typesearch) {
        $query = $db->prepare("SELECT * FROM products WHERE product_type = ?");
        $query->execute([$typesearch]);
    } else {
    //Query DB to find all products.
    $query = $db->prepare("SELECT * FROM products");
    $query->execute();
    }
}
catch(PDOException $ex) {
    echo("Failed to connect to the database.<br>");
    echo("error reported: " . $ex->getMessage());
    exit;
}

// fetch the results row 
if ($query->rowCount()>0){  // matching products
    $rows=$query->fetchAll();
} else {
    $rows  = NULL;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>rentique - Browse Attire</title>
    <link rel="stylesheet" href="rentique.css">
    <link rel="icon" type="image/png" href="rentique_logo.png">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <img src="rentique_logo.png">
                <span>rentique.</span>
            </div>
            <ul class="nav-links">
                <li><a href="homepage.php">Home</a></li>
                <li><a href="productsPage.php">Shop</a></li>
                <li><a href="AboutUs.php">About</a></li>
                <li><a href="Contact.php">Contact</a></li>
                <li><a href="login.html" class="btn login">Login</a></li>
                <li><a href="signup.html" class="btn signup">Sign Up</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="intro">
            <h1>Browse Attire</h1>
            <p class="subtitle">browse our collection of attire</p>

            <!-- filters n search -->
            <div class="filters">
                <div class="filters">
                    <form method="GET" action="productsPage.php">
                    <!-- Filter by name field -->
                    
                    <!-- Filter by type field -->
                    <select id="dropdown" name="typesearch" placeholder="Search by product type">
                    <option value="">All Categories</option>
                    <option value="Dresses">Dresses</option>
                    <option value="Suits">Suits</option>
                    <option value="Accessories">Accessories</option>
                    <option value="Jackets">Jackets</option>
                    <option value="Shoes">Shoes</option>
                    </select>
                    <input type="text" name="search" placeholder="Search by product name"><br>
                    <button type="submit">Search</button>
                    </form>
                </div>
            </div>
        </section>

        <!--products -->
        <div id="productGrid" class="productGrid">
                <?php
                    if ($rows) {
                        foreach ($rows as $row) {
                            echo "<div class='product'>";
                            echo "<div class='category'>" . htmlspecialchars($row['product_type']) . "</div>";
                            echo "<div class='title'> <h3>" . htmlspecialchars($row['title']) . "</h3></div>";
                            echo '<img src="images/' . htmlspecialchars($row['image']) . '" alt="Product Image" width="250" height="200">';
                            echo "<div class='price'> £" . htmlspecialchars($row['price']) . "</h3></div>";
                            echo "<button>View Details</button>";
                            echo "</div>";
                        }
                    } else {
                        echo "<p> No products fit the criteria! </p>";
                    }
                ?>
            </div>

        <!-- products view -->
        <div id="productView" class="hiddenProducts">
            <button class="backBtn" onclick="goBack()">← Back</button>
            
            <div class="productContent">
                <div class="productImage">
                    <img id="productImg" src="" alt="product image">
                </div>
                
                <div class="productInfo">
                    <div id="productCategory" class="category">example</div>
                    <h2 id="productName">example</h2>
                    <div id="productPrice" class="price">£example</div>
                    
                    <!-- product details -->
                    <div class="productSpec">
                        <div class="productLine">
                            <span class="productLabel">Condition:</span>
                            <span id="productCondition" class="productValue">example</span>
                        </div>
                        <div class="productLine">
                            <span class="productLabel">Size:</span>
                            <span id="productSize" class="productValue">example</span>
                        </div>
                        <div class="productLine">
                            <span class="productLabel">Material:</span>
                            <span id="productMaterial" class="productValue">example</span>
                        </div>
                    </div>
                    
                    <button class="rentBtn" onclick="alert('coming soon')">Rent Now</button>
                </div>
            </div>
        </div>
    </main>

    <script src="script.js"></script>
</body>
</html>
