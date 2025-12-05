<?php
session_start();
require_once 'connectdb.php';

// Validate user login
if (!isset($_SESSION['uid'])) {
    header("Location: login.php");
    exit;
}

$user_uid = $_SESSION['uid'];

// Fetch user information
try {
    $stmt = $db->prepare("
        SELECT username, email, address, pay_sortcode, pay_banknumber
        FROM users
        WHERE uid = ?
    ");
    $stmt->execute([$user_uid]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        die("User not found.");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Fetch user orders
try {
    $stmt = $db->prepare("
        SELECT t.price, t.created_at, p.title
        FROM transactions t
        JOIN products p ON p.pid = t.pid
        WHERE t.paying_uid = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$user_uid]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $orders = [];
}

$activeOrders = count($orders);
$newMessages = 0; // No message table implemented
$balance = 0;     // Buyers do not earn money
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Rentique | User Dashboard</title>
    <link rel="stylesheet" href="css/rentique.css">
</head>

<body>

<header>
    <nav class="navbar">
        <div class="logo">
            <img src="images/rentique_logo.png">
            <span>rentique.</span>
        </div>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="productsPage.php">Shop</a></li>
            <li><a href="AboutUs.php">About</a></li>
            <li><a href="Contact.php">Contact</a></li>

           <li>
                <a href="BasketPage.php" class="cart-icon">Basket</a>
            </li>

            <?php if ($userData): ?>
                <li><a href="seller_dashboard.php">Sell</a></li>
                <li><a href="user_dashboard.php"><?= htmlspecialchars($userData['billing_fullname'] ?? "Account") ?></a></li>
                <li><a href="index.php?logout=1" class="btn login">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php" class="btn login">Login</a></li>
                <li><a href="signup.php" class="btn signup">Sign Up</a></li>
            <?php endif; ?>
         </ul>

    </nav>
</header>

<div class="dashboard-container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <h2>User Menu</h2>

        <a href="#overview" class="side-link">Dashboard Overview</a>
        <a href="#orders" class="side-link">My Orders</a>
        <a href="#messages" class="side-link">Messages</a>
        <a href="#settings" class="side-link">Settings</a>
        <a href="#cashout" class="side-link">Cash Out</a>
    </aside>

    <!-- MAIN CONTENT -->
    <section class="main-content">

        <!-- OVERVIEW -->
        <div id="overview" class="section-block">
            <h2>Welcome Back, <?= htmlspecialchars($userData['username']) ?>!</h2>

            <div class="overview-grid">
                <div class="overview-card">
                    <h3>Active Orders</h3>
                    <p class="green"><?= $activeOrders ?></p>
                </div>

                <div class="overview-card">
                    <h3>Messages</h3>
                    <p class="green"><?= $newMessages ?> New</p>
                </div>

                <div class="overview-card">
                    <h3>Balance</h3>
                    <p class="green">£<?= number_format($balance, 2) ?></p>
                </div>
            </div>
        </div>

        <!-- ORDERS -->
        <div id="orders" class="section-block">
            <h2>My Orders</h2>

            <table class="main-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Order Type</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="4">You have no orders yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['title']) ?></td>
                            <td>Purchase</td>
                            <td class="green">Completed</td>
                            <td><?= $order['created_at'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- MESSAGES -->
        <div id="messages" class="section-block">
            <h2>Messages</h2>
            <p>No messages at the moment.</p>
        </div>

        <!-- SETTINGS -->
        <div id="settings" class="section-block">
            <h2>Settings</h2>

            <form class="settings-form">
                <h3>Personal Information</h3>

                <label>Username</label>
                <input type="text" value="<?= htmlspecialchars($userData['username']) ?>" disabled>

                <label>Email</label>
                <input type="email" value="<?= htmlspecialchars($userData['email']) ?>" disabled>

                <label>Address</label>
                <input type="text" value="<?= htmlspecialchars($userData['address']) ?>" disabled>
            </form>
        </div>

        <!-- CASH OUT -->
        <div id="cashout" class="section-block">
            <h2>Cash Out</h2>

            <p>You have no earnings to withdraw.</p>

            <form class="settings-form">
                <label>Bank Sort Code</label>
                <input type="text" value="<?= htmlspecialchars($userData['pay_sortcode']) ?>" disabled>

                <label>Bank Account Number</label>
                <input type="text" value="<?= htmlspecialchars($userData['pay_banknumber']) ?>" disabled>

                <button class="btn primary" disabled>Withdraw</button>
            </form>
        </div>

    </section>
</div>

</body>
</html>
