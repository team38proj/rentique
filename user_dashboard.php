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
        SELECT username, email, address
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
$newMessages = 0;
$balance = 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Rentique | User Dashboard</title>
    <link rel="stylesheet" href="css/rentique.css">
    <script src="js/theme.js" defer></script>
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
            <li><a href="BasketPage.php" class="cart-icon">Basket</a></li>
            <button id="themeToggle">Theme</button>

            <li><a href="user_dashboard.php"><?= htmlspecialchars($userData['username']) ?></a></li>
            <li><a href="index.php?logout=1" class="btn login">Logout</a></li>
        </ul>
    </nav>
</header>

<div class="dashboard-container">

    <aside class="sidebar">
        <h2>User Menu</h2>
        <a href="#overview" class="side-link">Dashboard Overview</a>
        <a href="#orders" class="side-link">My Orders</a>
        <a href="#messages" class="side-link">Messages</a>
        <a href="#settings" class="side-link">Settings</a>
    </aside>

    <section class="main-content">

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
                        <tr><td colspan="4">You have no orders yet.</td></tr>
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

        <div id="messages" class="section-block">
            <h2>Messages</h2>
            <p>No messages at the moment.</p>
        </div>

        <div id="settings" class="section-block">
            <h2>Settings</h2>

            <form class="settings-form" method="post" action="update_user.php">
                <label>Username</label>
                <input type="text" name="username"
                       value="<?= htmlspecialchars($userData['username']) ?>" required>

                <label>Email</label>
                <input type="email" name="email"
                       value="<?= htmlspecialchars($userData['email']) ?>" required>

                <label>Address</label>
                <input type="text" name="address"
                       value="<?= htmlspecialchars($userData['address']) ?>" required>

                <button class="btn primary" type="submit">Save Changes</button>
            </form>
        </div>

    </section>
</div>

</body>
</html>
