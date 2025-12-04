<?php
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Rentique | Seller Dashboard</title>
    <link rel="stylesheet" href="rentique.css">
</head>

<body>

    <!-- ROLE ACCESS PROTECTION -->
    <script>
        if (localStorage.getItem("rentique_role") !== "seller") {
            window.location.href = "login.html";
        }
    </script>

    <header>
        <nav class="navbar">
            <div class="logo">
                <img src="rentique_logo.png">
                <span>rentique.</span>
            </div>
            <ul class="nav-links">
                <li><a href="#">Home</a></li>
                <li><a href="#shop">Shop</a></li>
                <li><a href="Homepage.html">Home</a></li>
                <li><a href="productsPage.php">Shop</a></li>
                <li><a href="AboutUs.php">About</a></li>
                <li><a href="Contact.php">Contact</a></li>
                <li><a href="auth_login.html" class="btn logout">Logout</a></li>
                <li><a href="basketPage.php" class="cart-icon"><img src="basket.png" alt="Basket"></a></li> 
            </ul>
        </nav>
    </header>

    <div class="dashboard-container">

        <!-- SIDEBAR -->
        <aside class="sidebar">
            <h2>Seller Menu</h2>

            <a href="#overview" class="side-link">Dashboard Overview</a>
            <a href="#listings" class="side-link">My Listings</a>
            <a href="#additem" class="side-link">Add New Item</a>
            <a href="#earnings" class="side-link">Earnings</a>
            <a href="#messages" class="side-link">Messages</a>
            <a href="#settings" class="side-link">Settings</a>
            <a href="#payout" class="side-link">Payout Details</a>
            <a href="../user/dashboard.html" class="side-link">Switch to Buyer Mode</a>
        </aside>

        <!-- MAIN CONTENT -->
        <section class="main-content">

            <!-- OVERVIEW -->
            <div id="overview" class="section-block">
                <h2>Welcome, Seller!</h2>

                <div class="overview-grid">
                    <div class="overview-card">
                        <h3>Active Listings</h3>
                        <p class="green">14</p>
                    </div>

                    <div class="overview-card">
                        <h3>Items Rented Out</h3>
                        <p class="green">5</p>
                    </div>

                    <div class="overview-card">
                        <h3>Earnings</h3>
                        <p class="green">£312.80</p>
                    </div>
                </div>
            </div>

            <!-- LISTINGS -->
            <div id="listings" class="section-block">
                <h2>My Listings</h2>

                <table class="main-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Rental Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Black Satin Evening Dress</td>
                            <td>Dresses</td>
                            <td class="green">Available</td>
                            <td>£22/day</td>
                        </tr>

                        <tr>
                            <td>Men's Slim-Fit Blazer</td>
                            <td>Menswear</td>
                            <td class="green">Rented Out</td>
                            <td>£18/day</td>
                        </tr>

                        <tr>
                            <td>Gold Bracelet</td>
                            <td>Accessories</td>
                            <td class="green">Available</td>
                            <td>£10/day</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ADD ITEM -->
            <div id="additem" class="section-block">
                <h2>Add New Item</h2>

                <form class="settings-form">
                    <label>Item Name</label>
                    <input type="text" placeholder="e.g., Blue Satin Dress">

                    <label>Category</label>
                    <select>
                        <option>Dresses</option>
                        <option>Menswear</option>
                        <option>Accessories</option>
                        <option>Jewelry</option>
                        <option>Footwear</option>
                    </select>

                    <label>Price Per Day (£)</label>
                    <input type="number" placeholder="20">

                    <label>Upload Images</label>
                    <input type="file">

                    <label>Description</label>
                    <textarea placeholder="Describe your item..."></textarea>

                    <button class="btn primary">Upload Item</button>
                </form>
            </div>

            <!-- EARNINGS -->
            <div id="earnings" class="section-block">
                <h2>Earnings</h2>

                <div class="earning-card">
                    <h3>Total Earned</h3>
                    <p class="green">£312.80</p>
                </div>

                <div class="earning-card">
                    <h3>Pending Earnings</h3>
                    <p class="green">£110.00</p>
                </div>

                <div class="earning-card">
                    <h3>Withdrawn</h3>
                    <p class="green">£202.80</p>
                </div>
            </div>

            <!-- MESSAGES -->
            <div id="messages" class="section-block">
                <h2>Messages</h2>

                <div class="message-card">
                    <h3>User: Sarah W.</h3>
                    <p>Is the dress available for next weekend?</p>
                    <button class="btn primary small">Reply</button>
                </div>

                <div class="message-card">
                    <h3>Admin</h3>
                    <p>Your listing has been approved.</p>
                    <button class="btn primary small">Open</button>
                </div>
            </div>

            <!-- SETTINGS -->
            <div id="settings" class="section-block">
                <h2>Settings</h2>

                <form class="settings-form">
                    <h3>Seller Information</h3>

                    <label>Full Name</label>
                    <input type="text" placeholder="John Doe">

                    <label>Email</label>
                    <input type="email" placeholder="seller@example.com">

                    <label>Pickup / Return Address</label>
                    <input type="text" placeholder="123 Rentique Street">

                    <button class="btn primary">Save Changes</button>
                </form>
            </div>

            <!-- PAYOUT DETAILS -->
            <div id="payout" class="section-block">
                <h2>Payout Details</h2>

                <form class="settings-form">
                    <label>Account Holder Name</label>
                    <input type="text" placeholder="John Doe">

                    <label>Bank Sort Code</label>
                    <input type="text" placeholder="00-00-00">

                    <label>Account Number</label>
                    <input type="text" placeholder="12345678">

                    <button class="btn primary">Save Bank Details</button>
                </form>
            </div>

        </section>

    </div>

    <script src="../js/auth.js"></script>

</body>

</html>
