<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentique Shopping Cart</title>
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
                <li><a href="Homepage.php">Home</a></li>
                <li><a href="productsPage.php">Shop</a></li>
                <li><a href="#">About</a></li>
                <li><a href="Contact.php">Contact</a></li>
                <li><a href="#" class="btn login">Login</a></li>
                <li><a href="#" class="btn signup">Sign Up</a></li>
                <li><a href="basketPage.php" class="cart-icon"><img src="basket.png" alt="Basket"></a></li>
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
                

                <div class="basketItem">
                    <button class="removeBtn">×</button>
                    <div class="itemImage">
                        <img src="" alt="Product">
                    </div>
                    <div class="itemDetails">
                        <p class="category">example</p>
                        <h3>example</h3>
                        <p class="itemSize">example</p>
                    </div>
                    <div class="itemQuantity">
                        <button class="qtyBtn">-</button>
                        <input type="text" value="1" readonly>
                        <button class="qtyBtn">+</button>
                    </div>
                    <div class="itemTotal">
                        <p class="price">example</p>
                    </div>
                </div>

                <div class="basketItem">
                    <button class="removeBtn">×</button>
                    <div class="itemImage">
                        <img src="" alt="Product">
                    </div>
                    <div class="itemDetails">
                        <p class="category">example</p>
                        <h3>example</h3>
                        <p class="itemSize">example</p>
                    </div>
                    <div class="itemQuantity">
                        <button class="qtyBtn">-</button>
                        <input type="text" value="1" readonly>
                        <button class="qtyBtn">+</button>
                    </div>
                    <div class="itemTotal">
                        <p class="price">example</p>
                    </div>
                </div>
            </div>

            <div class="basketRight">
                <div class="summaryBox">
                    <h2>Summary</h2>

                    <div class="summaryLine">
                        <span>Subtotal</span>
                        <span>example</span>
                    </div>

                    <div class="summaryLine">
                        <span>Shipping</span>
                        <span>example</span>
                    </div>

                    <div class="summaryTotal">
                        <span>Total</span>
                        <span class="price">example</span>
                    </div>

                    <button class="checkoutBtn" onclick="window.location.href='Checkout.php'">Check Out</button>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>© 2025 Rentique. All rights reserved.</p>
    </footer>
</body>
</html>
