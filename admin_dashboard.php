<?php
session_start();
require_once 'connectdb.php';

// Admin access check
if (!isset($_SESSION['uid'])) {
    header("Location: login.php");
    exit;
}

// Fetch totals
try {
    // Total Users
    $totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

    // Total Sellers (users with at least 1 product)
    $totalSellers = $db->query("
        SELECT COUNT(DISTINCT uid) FROM products WHERE uid IS NOT NULL
    ")->fetchColumn();

    // Total Items
    $totalItems = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();

    // Total Transactions
    $totalTransactions = $db->query("SELECT COUNT(*) FROM transactions")->fetchColumn();

    // Users list
    $users = $db->query("SELECT uid, username, email FROM users ORDER BY uid DESC")->fetchAll(PDO::FETCH_ASSOC);

    // Sellers list
    $sellers = $db->query("
        SELECT DISTINCT u.uid, u.username, u.email
        FROM users u
        JOIN products p ON p.uid = u.uid
        ORDER BY u.uid DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Items list
    $items = $db->query("
        SELECT p.pid, p.title, p.product_type, p.price, u.username AS seller
        FROM products p
        LEFT JOIN users u ON p.uid = u.uid
        ORDER BY p.pid DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Transactions list
    $transactions = $db->query("
        SELECT t.id, t.pid, t.price, t.created_at, 
               u1.username AS buyer, 
               u2.username AS seller,
               p.title
        FROM transactions t
        JOIN users u1 ON t.paying_uid = u1.uid
        JOIN users u2 ON t.receiving_uid = u2.uid
        JOIN products p ON p.pid = t.pid
        ORDER BY t.created_at DESC
        LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Rentique | Admin Dashboard</title>
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
            <button id="themeToggle">Theme</button>
            <li><a href="login.php" class="btn logout">Logout</a></li>
        </ul>
    </nav>
</header>

<div class="dashboard-container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <h2>Admin Panel</h2>

        <a href="#overview" class="side-link">Dashboard Overview</a>
        <a href="#users" class="side-link">Manage Users</a>
        <a href="#sellers" class="side-link">Manage Sellers</a>
        <a href="#items" class="side-link">Manage Items</a>
        <a href="#transactions" class="side-link">Transactions</a>
    </aside>

    <!-- MAIN CONTENT -->
    <section class="main-content">

        <!-- OVERVIEW -->
        <div id="overview" class="section-block">
            <h2>Admin Dashboard</h2>

            <div class="overview-grid">
                <div class="overview-card">
                    <h3>Total Users</h3>
                    <p class="green"><?= $totalUsers ?></p>
                </div>

                <div class="overview-card">
                    <h3>Total Sellers</h3>
                    <p class="green"><?= $totalSellers ?></p>
                </div>

                <div class="overview-card">
                    <h3>Total Items</h3>
                    <p class="green"><?= $totalItems ?></p>
                </div>

                <div class="overview-card">
                    <h3>Total Transactions</h3>
                    <p class="green"><?= $totalTransactions ?></p>
                </div>
            </div>
        </div>

        <!-- MANAGE USERS -->
        <div id="users" class="section-block">
            <h2>Manage Users</h2>

            <table class="main-table">
                <thead>
                    <tr>
                        <th>UID</th>
                        <th>Username</th>
                        <th>Email</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['uid'] ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- MANAGE SELLERS -->
        <div id="sellers" class="section-block">
            <h2>Sellers</h2>

            <table class="main-table">
                <thead>
                    <tr>
                        <th>Seller UID</th>
                        <th>Username</th>
                        <th>Email</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($sellers as $s): ?>
                        <tr>
                            <td><?= $s['uid'] ?></td>
                            <td><?= htmlspecialchars($s['username']) ?></td>
                            <td><?= htmlspecialchars($s['email']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- MANAGE ITEMS -->
        <div id="items" class="section-block">
            <h2>Items</h2>

            <table class="main-table">
                <thead>
                    <tr>
                        <th>PID</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th>Seller</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($items as $i): ?>
                        <tr>
                            <td><?= $i['pid'] ?></td>
                            <td><?= htmlspecialchars($i['title']) ?></td>
                            <td><?= htmlspecialchars($i['product_type']) ?></td>
                            <td>£<?= number_format($i['price'], 2) ?></td>
                            <td><?= htmlspecialchars($i['seller']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- TRANSACTIONS -->
        <div id="transactions" class="section-block">
            <h2>Recent Transactions</h2>

            <table class="main-table">
                <thead>
                    <tr>
                        <th>TID</th>
                        <th>Item</th>
                        <th>Buyer</th>
                        <th>Seller</th>
                        <th>Price</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>
                <?php foreach ($transactions as $t): ?>
                    <tr>
                        <td><?= $t['tid'] ?></td>
                        <td><?= htmlspecialchars($t['title']) ?></td>
                        <td><?= htmlspecialchars($t['buyer']) ?></td>
                        <td><?= htmlspecialchars($t['seller']) ?></td>
                        <td>£<?= number_format($t['price'], 2) ?></td>
                        <td><?= $t['created_at'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </section>
</div>

</body>
</html>
