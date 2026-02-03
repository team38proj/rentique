<?php
session_start();
require_once 'connectdb.php';

if (!isset($_SESSION['uid'])) {
    header("Location: login.php");
    exit;
}

$admin_uid = (int)$_SESSION['uid'];

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* Confirm admin role */
try {
    $stmt = $db->prepare("SELECT role FROM users WHERE uid = ? LIMIT 1");
    $stmt->execute([$admin_uid]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$r || ($r['role'] ?? '') !== 'admin') {
        die("Access denied.");
    }
} catch (PDOException $e) {
    die("Database error.");
}

/* View routing */
$view = trim($_GET['view'] ?? '');
$uidParam = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
$pidParam = isset($_GET['pid']) ? (int)$_GET['pid'] : 0;
$tidParam = isset($_GET['tid']) ? (int)$_GET['tid'] : 0;

/* Totals */
$totalUsers = 0;
$totalSellers = 0;
$totalItems = 0;
$totalTransactions = 0;
$supportOpenCount = 0;

try {
    $totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
} catch (PDOException $e) {
    $totalUsers = 0;
}

try {
    $totalSellers = (int)$db->query("SELECT COUNT(DISTINCT uid) FROM products WHERE uid IS NOT NULL")->fetchColumn();
} catch (PDOException $e) {
    $totalSellers = 0;
}

try {
    $totalItems = (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn();
} catch (PDOException $e) {
    $totalItems = 0;
}

try {
    $totalTransactions = (int)$db->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
} catch (PDOException $e) {
    $totalTransactions = 0;
}

try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM admin_conversations WHERE status = 'open'");
    $stmt->execute();
    $supportOpenCount = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    $supportOpenCount = 0;
}

/* Lists */
$users = [];
$sellers = [];
$items = [];
$transactions = [];
$supportConvs = [];

try {
    $users = $db->query("SELECT uid, username, email, role FROM users ORDER BY uid DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
}

try {
    $sellers = $db->query("
        SELECT DISTINCT u.uid, u.username, u.email, u.role
        FROM users u
        JOIN products p ON p.uid = u.uid
        ORDER BY u.uid DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $sellers = [];
}

try {
    $items = $db->query("
        SELECT p.pid, p.title, p.product_type, p.price, p.uid AS seller_uid, u.username AS seller_name, p.is_available
        FROM products p
        LEFT JOIN users u ON u.uid = p.uid
        ORDER BY p.pid DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $items = [];
}

try {
    $transactions = $db->query("
        SELECT
            t.id,
            t.pid,
            t.paying_uid,
            t.receiving_uid,
            t.price,
            t.order_id,
            t.created_at,
            p.title AS item_title,
            ub.username AS buyer_name,
            us.username AS seller_name
        FROM transactions t
        LEFT JOIN products p ON p.pid = t.pid
        LEFT JOIN users ub ON ub.uid = t.paying_uid
        LEFT JOIN users us ON us.uid = t.receiving_uid
        ORDER BY t.created_at DESC
        LIMIT 100
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $transactions = [];
}

try {
    $stmt = $db->prepare("
        SELECT ac.id, ac.user_uid, ac.status, ac.created_at, u.username
        FROM admin_conversations ac
        JOIN users u ON u.uid = ac.user_uid
        ORDER BY ac.status ASC, ac.created_at DESC
        LIMIT 50
    ");
    $stmt->execute();
    $supportConvs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $supportConvs = [];
}

/* Detail panels */
$userDetail = null;
$sellerDetail = null;
$itemDetail = null;
$transactionDetail = null;

$userListings = [];
$userTransactionsAsBuyer = [];
$userTransactionsAsSeller = [];

$itemTransactions = [];

if ($view === 'user' && $uidParam > 0) {
    try {
        $stmt = $db->prepare("
            SELECT uid, username, email, address, billing_fullname, role
            FROM users
            WHERE uid = ?
            LIMIT 1
        ");
        $stmt->execute([$uidParam]);
        $userDetail = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        $userDetail = null;
    }

    try {
        $stmt = $db->prepare("
            SELECT t.id, t.pid, t.price, t.order_id, t.created_at, p.title
            FROM transactions t
            LEFT JOIN products p ON p.pid = t.pid
            WHERE t.paying_uid = ?
            ORDER BY t.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$uidParam]);
        $userTransactionsAsBuyer = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $userTransactionsAsBuyer = [];
    }

    try {
        $stmt = $db->prepare("
            SELECT t.id, t.pid, t.price, t.order_id, t.created_at, p.title
            FROM transactions t
            LEFT JOIN products p ON p.pid = t.pid
            WHERE t.receiving_uid = ?
            ORDER BY t.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$uidParam]);
        $userTransactionsAsSeller = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $userTransactionsAsSeller = [];
    }

    try {
        $stmt = $db->prepare("
            SELECT pid, title, product_type, price, is_available
            FROM products
            WHERE uid = ?
            ORDER BY pid DESC
            LIMIT 50
        ");
        $stmt->execute([$uidParam]);
        $userListings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $userListings = [];
    }
}

if ($view === 'seller' && $uidParam > 0) {
    try {
        $stmt = $db->prepare("
            SELECT uid, username, email, address, billing_fullname, role, pay_sortcode, pay_banknumber
            FROM users
            WHERE uid = ?
            LIMIT 1
        ");
        $stmt->execute([$uidParam]);
        $sellerDetail = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        $sellerDetail = null;
    }

    try {
        $stmt = $db->prepare("
            SELECT pid, title, product_type, price, is_available
            FROM products
            WHERE uid = ?
            ORDER BY pid DESC
            LIMIT 100
        ");
        $stmt->execute([$uidParam]);
        $userListings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $userListings = [];
    }

    try {
        $stmt = $db->prepare("
            SELECT t.id, t.pid, t.price, t.order_id, t.created_at, p.title, ub.username AS buyer_name
            FROM transactions t
            LEFT JOIN products p ON p.pid = t.pid
            LEFT JOIN users ub ON ub.uid = t.paying_uid
            WHERE t.receiving_uid = ?
            ORDER BY t.created_at DESC
            LIMIT 100
        ");
        $stmt->execute([$uidParam]);
        $userTransactionsAsSeller = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $userTransactionsAsSeller = [];
    }
}

if ($view === 'item' && $pidParam > 0) {
    try {
        $stmt = $db->prepare("
            SELECT
                p.pid, p.title, p.image, p.product_type, p.price, p.description, p.uid AS seller_uid,
                u.username AS seller_name, u.email AS seller_email, p.is_available
            FROM products p
            LEFT JOIN users u ON u.uid = p.uid
            WHERE p.pid = ?
            LIMIT 1
        ");
        $stmt->execute([$pidParam]);
        $itemDetail = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        $itemDetail = null;
    }

    try {
        $stmt = $db->prepare("
            SELECT
                t.id, t.price, t.order_id, t.created_at,
                ub.username AS buyer_name,
                us.username AS seller_name
            FROM transactions t
            LEFT JOIN users ub ON ub.uid = t.paying_uid
            LEFT JOIN users us ON us.uid = t.receiving_uid
            WHERE t.pid = ?
            ORDER BY t.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$pidParam]);
        $itemTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $itemTransactions = [];
    }
}

if ($view === 'transaction' && $tidParam > 0) {
    try {
        $stmt = $db->prepare("
            SELECT
                t.id,
                t.pid,
                t.paying_uid,
                t.receiving_uid,
                t.price,
                t.order_id,
                t.created_at,
                p.title AS item_title,
                p.product_type,
                p.image,
                ub.username AS buyer_name,
                ub.email AS buyer_email,
                us.username AS seller_name,
                us.email AS seller_email
            FROM transactions t
            LEFT JOIN products p ON p.pid = t.pid
            LEFT JOIN users ub ON ub.uid = t.paying_uid
            LEFT JOIN users us ON us.uid = t.receiving_uid
            WHERE t.id = ?
            LIMIT 1
        ");
        $stmt->execute([$tidParam]);
        $transactionDetail = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        $transactionDetail = null;
    }
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
            <img src="images/rentique_logo.png" alt="Rentique Logo">
            <span>rentique.</span>
        </div>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="productsPage.php">Shop</a></li>
            <li><a href="AboutUs.php">About</a></li>
            <li><a href="Contact.php">Contact</a></li>
            <button id="themeToggle">Theme</button>
            <li><a href="index.php?logout=1" class="btn logout">Logout</a></li>
        </ul>
    </nav>
</header>

<div class="dashboard-container">

    <aside class="sidebar">
        <h2>Admin Panel</h2>
        <a href="#overview" class="side-link">Dashboard Overview</a>
        <a href="#users" class="side-link">Users</a>
        <a href="#sellers" class="side-link">Sellers</a>
        <a href="#items" class="side-link">Items</a>
        <a href="#transactions" class="side-link">Transactions</a>
        <a href="report.php" class="side-link">Reports</a>
        <a href="#support" class="side-link">Support</a>
        <?php if ($view !== ''): ?>
            <a href="admin_dashboard.php" class="side-link">Clear details</a>
        <?php endif; ?>
    </aside>

    <section class="main-content">

        <div id="overview" class="section-block">
            <h2>Admin Dashboard</h2>

            <div class="overview-grid">
                <div class="overview-card">
                    <h3>Total Users</h3>
                    <p class="green"><?= (int)$totalUsers ?></p>
                </div>

                <div class="overview-card">
                    <h3>Total Sellers</h3>
                    <p class="green"><?= (int)$totalSellers ?></p>
                </div>

                <div class="overview-card">
                    <h3>Total Items</h3>
                    <p class="green"><?= (int)$totalItems ?></p>
                </div>

                <div class="overview-card">
                    <h3>Total Transactions</h3>
                    <p class="green"><?= (int)$totalTransactions ?></p>
                </div>

                <div class="overview-card">
                    <h3>Support Inbox</h3>
                    <p class="green"><?= (int)$supportOpenCount ?> Open</p>
                </div>
            </div>

            <div style="margin-top:12px;">
                <a href="report.php" class="btn primary">Open Reports</a>
            </div>
        </div>

        <?php if ($view === 'user' && $userDetail): ?>
            <div class="section-block">
                <h2>User details</h2>
                <div class="summaryBox">
                    <div class="summaryLine"><span>UID</span><span><?= (int)$userDetail['uid'] ?></span></div>
                    <div class="summaryLine"><span>Username</span><span><?= h($userDetail['username']) ?></span></div>
                    <div class="summaryLine"><span>Email</span><span><?= h($userDetail['email']) ?></span></div>
                    <div class="summaryLine"><span>Role</span><span><?= h($userDetail['role']) ?></span></div>
                    <div class="summaryLine"><span>Billing name</span><span><?= h($userDetail['billing_fullname']) ?></span></div>
                    <div class="summaryLine"><span>Address</span><span><?= h($userDetail['address']) ?></span></div>
                </div>

                <h3 style="margin-top:18px;">Listings</h3>
                <table class="main-table">
                    <thead>
                        <tr>
                            <th>PID</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Price</th>
                            <th>Available</th>
                            <th>Open</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$userListings): ?>
                            <tr><td colspan="6">No listings.</td></tr>
                        <?php else: ?>
                            <?php foreach ($userListings as $p): ?>
                                <tr>
                                    <td><?= (int)$p['pid'] ?></td>
                                    <td><?= h($p['title']) ?></td>
                                    <td><?= h($p['product_type']) ?></td>
                                    <td>£<?= number_format((float)$p['price'], 2) ?></td>
                                    <td><?= ((int)$p['is_available'] === 1) ? 'Yes' : 'No' ?></td>
                                    <td><a href="admin_dashboard.php?view=item&pid=<?= (int)$p['pid'] ?>">Open</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <h3 style="margin-top:18px;">Transactions as buyer</h3>
                <table class="main-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Item</th>
                            <th>Price</th>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Open</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$userTransactionsAsBuyer): ?>
                            <tr><td colspan="6">No transactions.</td></tr>
                        <?php else: ?>
                            <?php foreach ($userTransactionsAsBuyer as $t): ?>
                                <tr>
                                    <td><?= (int)$t['id'] ?></td>
                                    <td><?= h($t['title']) ?></td>
                                    <td>£<?= number_format((float)$t['price'], 2) ?></td>
                                    <td><?= h($t['order_id']) ?></td>
                                    <td><?= h($t['created_at']) ?></td>
                                    <td><a href="admin_dashboard.php?view=transaction&tid=<?= (int)$t['id'] ?>">Open</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <h3 style="margin-top:18px;">Transactions as seller</h3>
                <table class="main-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Item</th>
                            <th>Price</th>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Open</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$userTransactionsAsSeller): ?>
                            <tr><td colspan="6">No transactions.</td></tr>
                        <?php else: ?>
                            <?php foreach ($userTransactionsAsSeller as $t): ?>
                                <tr>
                                    <td><?= (int)$t['id'] ?></td>
                                    <td><?= h($t['title']) ?></td>
                                    <td>£<?= number_format((float)$t['price'], 2) ?></td>
                                    <td><?= h($t['order_id']) ?></td>
                                    <td><?= h($t['created_at']) ?></td>
                                    <td><a href="admin_dashboard.php?view=transaction&tid=<?= (int)$t['id'] ?>">Open</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($view === 'user' && $uidParam > 0): ?>
            <div class="section-block">
                <h2>User details</h2>
                <p>User not found.</p>
            </div>
        <?php endif; ?>

        <?php if ($view === 'seller' && $sellerDetail): ?>
            <div class="section-block">
                <h2>Seller details</h2>
                <div class="summaryBox">
                    <div class="summaryLine"><span>UID</span><span><?= (int)$sellerDetail['uid'] ?></span></div>
                    <div class="summaryLine"><span>Username</span><span><?= h($sellerDetail['username']) ?></span></div>
                    <div class="summaryLine"><span>Email</span><span><?= h($sellerDetail['email']) ?></span></div>
                    <div class="summaryLine"><span>Role</span><span><?= h($sellerDetail['role']) ?></span></div>
                    <div class="summaryLine"><span>Billing name</span><span><?= h($sellerDetail['billing_fullname']) ?></span></div>
                    <div class="summaryLine"><span>Address</span><span><?= h($sellerDetail['address']) ?></span></div>
                    <div class="summaryLine"><span>Sort code</span><span><?= h($sellerDetail['pay_sortcode']) ?></span></div>
                    <div class="summaryLine"><span>Account number</span><span><?= h($sellerDetail['pay_banknumber']) ?></span></div>
                </div>

                <h3 style="margin-top:18px;">Listings</h3>
                <table class="main-table">
                    <thead>
                        <tr>
                            <th>PID</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Price</th>
                            <th>Available</th>
                            <th>Open</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$userListings): ?>
                            <tr><td colspan="6">No listings.</td></tr>
                        <?php else: ?>
                            <?php foreach ($userListings as $p): ?>
                                <tr>
                                    <td><?= (int)$p['pid'] ?></td>
                                    <td><?= h($p['title']) ?></td>
                                    <td><?= h($p['product_type']) ?></td>
                                    <td>£<?= number_format((float)$p['price'], 2) ?></td>
                                    <td><?= ((int)$p['is_available'] === 1) ? 'Yes' : 'No' ?></td>
                                    <td><a href="admin_dashboard.php?view=item&pid=<?= (int)$p['pid'] ?>">Open</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <h3 style="margin-top:18px;">Transactions</h3>
                <table class="main-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Item</th>
                            <th>Buyer</th>
                            <th>Price</th>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Open</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$userTransactionsAsSeller): ?>
                            <tr><td colspan="7">No transactions.</td></tr>
                        <?php else: ?>
                            <?php foreach ($userTransactionsAsSeller as $t): ?>
                                <tr>
                                    <td><?= (int)$t['id'] ?></td>
                                    <td><?= h($t['title']) ?></td>
                                    <td><?= h($t['buyer_name']) ?></td>
                                    <td>£<?= number_format((float)$t['price'], 2) ?></td>
                                    <td><?= h($t['order_id']) ?></td>
                                    <td><?= h($t['created_at']) ?></td>
                                    <td><a href="admin_dashboard.php?view=transaction&tid=<?= (int)$t['id'] ?>">Open</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($view === 'seller' && $uidParam > 0): ?>
            <div class="section-block">
                <h2>Seller details</h2>
                <p>Seller not found.</p>
            </div>
        <?php endif; ?>

        <?php if ($view === 'item' && $itemDetail): ?>
            <div class="section-block">
                <h2>Item details</h2>

                <div style="display:flex; gap:14px; align-items:flex-start; flex-wrap:wrap;">
                    <div style="width:220px;">
                        <?php if (!empty($itemDetail['image'])): ?>
                            <img src="images/<?= h($itemDetail['image']) ?>" style="width:220px;height:220px;object-fit:cover;border-radius:12px;border:1px solid #ddd;">
                        <?php endif; ?>
                    </div>
                    <div style="flex:1; min-width:260px;">
                        <div class="summaryBox">
                            <div class="summaryLine"><span>PID</span><span><?= (int)$itemDetail['pid'] ?></span></div>
                            <div class="summaryLine"><span>Title</span><span><?= h($itemDetail['title']) ?></span></div>
                            <div class="summaryLine"><span>Type</span><span><?= h($itemDetail['product_type']) ?></span></div>
                            <div class="summaryLine"><span>Per day price</span><span>£<?= number_format((float)$itemDetail['price'], 2) ?></span></div>
                            <div class="summaryLine"><span>Available</span><span><?= ((int)$itemDetail['is_available'] === 1) ? 'Yes' : 'No' ?></span></div>
                            <div class="summaryLine"><span>Seller</span><span><?= h($itemDetail['seller_name']) ?> (<?= (int)$itemDetail['seller_uid'] ?>)</span></div>
                            <div class="summaryLine"><span>Seller email</span><span><?= h($itemDetail['seller_email']) ?></span></div>
                            <div class="summaryLine"><span>Description</span><span><?= h($itemDetail['description']) ?></span></div>
                        </div>
                        <div style="margin-top:10px;">
                            <a href="admin_dashboard.php?view=seller&uid=<?= (int)$itemDetail['seller_uid'] ?>">Open seller</a>
                        </div>
                    </div>
                </div>

                <h3 style="margin-top:18px;">Transactions for this item</h3>
                <table class="main-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Buyer</th>
                            <th>Seller</th>
                            <th>Price</th>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Open</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$itemTransactions): ?>
                            <tr><td colspan="7">No transactions.</td></tr>
                        <?php else: ?>
                            <?php foreach ($itemTransactions as $t): ?>
                                <tr>
                                    <td><?= (int)$t['id'] ?></td>
                                    <td><?= h($t['buyer_name']) ?></td>
                                    <td><?= h($t['seller_name']) ?></td>
                                    <td>£<?= number_format((float)$t['price'], 2) ?></td>
                                    <td><?= h($t['order_id']) ?></td>
                                    <td><?= h($t['created_at']) ?></td>
                                    <td><a href="admin_dashboard.php?view=transaction&tid=<?= (int)$t['id'] ?>">Open</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($view === 'item' && $pidParam > 0): ?>
            <div class="section-block">
                <h2>Item details</h2>
                <p>Item not found.</p>
            </div>
        <?php endif; ?>

        <?php if ($view === 'transaction' && $transactionDetail): ?>
            <div class="section-block">
                <h2>Transaction details</h2>

                <div class="summaryBox">
                    <div class="summaryLine"><span>ID</span><span><?= (int)$transactionDetail['id'] ?></span></div>
                    <div class="summaryLine"><span>Order ID</span><span><?= h($transactionDetail['order_id']) ?></span></div>
                    <div class="summaryLine"><span>Date</span><span><?= h($transactionDetail['created_at']) ?></span></div>
                    <div class="summaryLine"><span>Item</span><span><?= h($transactionDetail['item_title']) ?> (PID <?= (int)$transactionDetail['pid'] ?>)</span></div>
                    <div class="summaryLine"><span>Type</span><span><?= h($transactionDetail['product_type']) ?></span></div>
                    <div class="summaryLine"><span>Price</span><span>£<?= number_format((float)$transactionDetail['price'], 2) ?></span></div>
                    <div class="summaryLine"><span>Buyer</span><span><?= h($transactionDetail['buyer_name']) ?> (<?= (int)$transactionDetail['paying_uid'] ?>)</span></div>
                    <div class="summaryLine"><span>Buyer email</span><span><?= h($transactionDetail['buyer_email']) ?></span></div>
                    <div class="summaryLine"><span>Seller</span><span><?= h($transactionDetail['seller_name']) ?> (<?= (int)$transactionDetail['receiving_uid'] ?>)</span></div>
                    <div class="summaryLine"><span>Seller email</span><span><?= h($transactionDetail['seller_email']) ?></span></div>
                </div>

                <div style="margin-top:10px; display:flex; gap:12px; flex-wrap:wrap;">
                    <a href="admin_dashboard.php?view=item&pid=<?= (int)$transactionDetail['pid'] ?>">Open item</a>
                    <a href="admin_dashboard.php?view=user&uid=<?= (int)$transactionDetail['paying_uid'] ?>">Open buyer</a>
                    <a href="admin_dashboard.php?view=seller&uid=<?= (int)$transactionDetail['receiving_uid'] ?>">Open seller</a>
                </div>
            </div>
        <?php elseif ($view === 'transaction' && $tidParam > 0): ?>
            <div class="section-block">
                <h2>Transaction details</h2>
                <p>Transaction not found.</p>
            </div>
        <?php endif; ?>

        <div id="users" class="section-block">
            <h2>Users</h2>
            <table class="main-table">
                <thead>
                    <tr>
                        <th>UID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Open</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$users): ?>
                        <tr><td colspan="5">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= (int)$u['uid'] ?></td>
                                <td><?= h($u['username']) ?></td>
                                <td><?= h($u['email']) ?></td>
                                <td><?= h($u['role']) ?></td>
                                <td><a href="admin_dashboard.php?view=user&uid=<?= (int)$u['uid'] ?>">Open</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="sellers" class="section-block">
            <h2>Sellers</h2>
            <table class="main-table">
                <thead>
                    <tr>
                        <th>UID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Open</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$sellers): ?>
                        <tr><td colspan="5">No sellers found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($sellers as $s): ?>
                            <tr>
                                <td><?= (int)$s['uid'] ?></td>
                                <td><?= h($s['username']) ?></td>
                                <td><?= h($s['email']) ?></td>
                                <td><?= h($s['role']) ?></td>
                                <td><a href="admin_dashboard.php?view=seller&uid=<?= (int)$s['uid'] ?>">Open</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

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
                        <th>Available</th>
                        <th>Open</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$items): ?>
                        <tr><td colspan="7">No items found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($items as $i): ?>
                            <tr>
                                <td><?= (int)$i['pid'] ?></td>
                                <td><?= h($i['title']) ?></td>
                                <td><?= h($i['product_type']) ?></td>
                                <td>£<?= number_format((float)$i['price'], 2) ?></td>
                                <td><?= h($i['seller_name']) ?> (<?= (int)$i['seller_uid'] ?>)</td>
                                <td><?= ((int)$i['is_available'] === 1) ? 'Yes' : 'No' ?></td>
                                <td><a href="admin_dashboard.php?view=item&pid=<?= (int)$i['pid'] ?>">Open</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="transactions" class="section-block">
            <h2>Transactions</h2>
            <table class="main-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Item</th>
                        <th>Buyer</th>
                        <th>Seller</th>
                        <th>Price</th>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Open</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$transactions): ?>
                        <tr><td colspan="8">No transactions found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?= (int)$t['id'] ?></td>
                                <td><?= h($t['item_title']) ?></td>
                                <td><?= h($t['buyer_name']) ?> (<?= (int)$t['paying_uid'] ?>)</td>
                                <td><?= h($t['seller_name']) ?> (<?= (int)$t['receiving_uid'] ?>)</td>
                                <td>£<?= number_format((float)$t['price'], 2) ?></td>
                                <td><?= h($t['order_id']) ?></td>
                                <td><?= h($t['created_at']) ?></td>
                                <td><a href="admin_dashboard.php?view=transaction&tid=<?= (int)$t['id'] ?>">Open</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="support" class="section-block">
            <h2>Support</h2>

            <div style="margin:10px 0;">
                <a class="btn primary" href="admin_support.php">Open support inbox</a>
            </div>

            <?php if (!$supportConvs): ?>
                <p>No support conversations.</p>
            <?php else: ?>
                <table class="main-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Open</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($supportConvs as $c): ?>
                            <tr>
                                <td><?= (int)$c['id'] ?></td>
                                <td><?= h($c['username']) ?> (<?= (int)$c['user_uid'] ?>)</td>
                                <td><?= h($c['status']) ?></td>
                                <td><?= h($c['created_at']) ?></td>
                                <td><a href="admin_support.php?conv_id=<?= (int)$c['id'] ?>">Open</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </section>
</div>

</body>
</html>
