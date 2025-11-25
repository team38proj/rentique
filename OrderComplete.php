<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Complete</title>
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
            </ul>
        </nav>
    </header>

    <main>
        <section class="intro">
            <div class="tickIcon">✓</div>
            
            <h1>Thank you for your purchase</h1>
            <p class="subtitle">We've received your order and will ship it in 5-7 business days.</p>
            <p class="subtitle">Your order number is #ABC123</p>

            <div class="orderSummary">
                <h2>Order Summary</h2>

                <div class="orderItem">
                    <h3>example</h3>
                    <div class="itemPrice">£example</div>
                </div>

                <div class="orderItem">
                    <h3>example</h3>
                    <div class="itemPrice">£example</div>
                </div>

                <div class="orderTotal">
                    <span>Total</span>
                    <span class="totalPrice">£example</span>
                </div>

                <button type="button" id="backHomeBtn" onclick="window.location.href='Homepage.php'">Back to Home</button>
            </div>
        </section>
    </main>

    <footer>
        <p>© 2025 Rentique. All rights reserved.</p>
    </footer>
</body>
</html>
