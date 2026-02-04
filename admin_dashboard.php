<?php
session_start();
require_once 'connectdb.php';

if (!isset($_SESSION['uid'])) {
    header("Location: login.php");
    exit;
}

$admin_uid = (int)$_SESSION['uid'];

/* ===== HELPER FUNCTIONS ===== */

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function getCount($db, $query, $params = []) {
    try {
        if ($params) {
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        }
        return (int)$db->query($query)->fetchColumn();
    } catch (PDOException $e) {
        error_log("Count query error: " . $e->getMessage());
        return 0;
    }
}

function getList($db, $query, $params = []) {
    try {
        if ($params) {
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("List query error: " . $e->getMessage());
        return [];
    }
}

function getRow($db, $query, $params = []) {
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        error_log("Row query error: " . $e->getMessage());
        return null;
    }
}

function renderSummaryLine($label, $value) {
    return '<div class="summaryLine"><span>' . h($label) . '</span><span>' . h($value) . '</span></div>';
}

function renderTableRow($cells, $isHeader = false) {
    $tag = $isHeader ? 'th' : 'td';
    $output = '<tr>';
    foreach ($cells as $cell) {
        $output .= "<{$tag}>{$cell}</{$tag}>";
    }
    $output .= '</tr>';
    return $output;
}

function renderOverviewCard($title, $value) {
    return '
        <div class="overview-card">
            <h3>' . h($title) . '</h3>
            <p class="green">' . h($value) . '</p>
        </div>';
}

function renderEmptyRow($colspan, $message) {
    return '<tr><td colspan="' . (int)$colspan . '">' . h($message) . '</td></tr>';
}

/* ===== AUTHENTICATION CHECK ===== */

$userRole = getRow($db, "SELECT role FROM users WHERE uid = ? LIMIT 1", [$admin_uid]);
if (!$userRole || ($userRole['role'] ?? '') !== 'admin') {
    die("Access denied.");
}

/* ===== VIEW ROUTING ===== */

$view = trim($_GET['view'] ?? '');
$uidParam = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
$pidParam = isset($_GET['pid']) ? (int)$_GET['pid'] : 0;
$tidParam = isset($_GET['tid']) ? (int)$_GET['tid'] : 0;

/* ===== DASHBOARD STATISTICS ===== */

$stats = [
    'totalUsers' => getCount($db, "SELECT COUNT(*) FROM users"),
    'totalSellers' => getCount($db, "SELECT COUNT(DISTINCT uid) FROM products WHERE uid IS NOT NULL"),
    'totalItems' => getCount($db, "SELECT COUNT(*) FROM products"),
    'totalTransactions' => getCount($db, "SELECT COUNT(*) FROM transactions"),
    'supportOpenCount' => getCount($db, "SELECT COUNT(*) FROM admin_conversations WHERE status = 'open'")
];

/* ===== MAIN DATA QUERIES ===== */

$queries = [
    'users' => "SELECT uid, username, email, role FROM users ORDER BY uid DESC",
    'sellers' => "SELECT DISTINCT u.uid, u.username, u.email, u.role 
                  FROM users u 
                  JOIN products p ON p.uid = u.uid 
                  ORDER BY u.uid DESC",
    'items' => "SELECT p.pid, p.title, p.product_type, p.price, p.uid AS seller_uid, 
                u.username AS seller_name, p.is_available 
                FROM products p 
                LEFT JOIN users u ON u.uid = p.uid 
                ORDER BY p.pid DESC",
    'transactions' => "SELECT t.id, t.pid, t.paying_uid, t.receiving_uid, t.price, t.order_id, t.created_at, 
                       p.title AS item_title, ub.username AS buyer_name, us.username AS seller_name 
                       FROM transactions t 
                       LEFT JOIN products p ON p.pid = t.pid 
                       LEFT JOIN users ub ON ub.uid = t.paying_uid 
                       LEFT JOIN users us ON us.uid = t.receiving_uid 
                       ORDER BY t.created_at DESC LIMIT 100",
    'supportConvs' => "SELECT ac.id, ac.user_uid, ac.status, ac.created_at, u.username 
                       FROM admin_conversations ac 
                       JOIN users u ON u.uid = ac.user_uid 
                       ORDER BY ac.status ASC, ac.created_at DESC LIMIT 50"
];

$data = array_map(fn($query) => getList($db, $query), $queries);
extract($data); // Creates $users, $sellers, $items, $transactions, $supportConvs

/* ===== DETAIL VIEWS ===== */

$details = [
    'userDetail' => null,
    'sellerDetail' => null,
    'itemDetail' => null,
    'transactionDetail' => null,
    'userListings' => [],
    'userTransactionsAsBuyer' => [],
    'userTransactionsAsSeller' => [],
    'itemTransactions' => []
];

// User detail view
if ($view === 'user' && $uidParam > 0) {
    $details['userDetail'] = getRow($db, 
        "SELECT uid, username, email, address, billing_fullname, role FROM users WHERE uid = ? LIMIT 1", 
        [$uidParam]
    );
    $details['userTransactionsAsBuyer'] = getList($db, 
        "SELECT t.id, t.pid, t.price, t.order_id, t.created_at, p.title 
         FROM transactions t 
         LEFT JOIN products p ON p.pid = t.pid 
         WHERE t.paying_uid = ? 
         ORDER BY t.created_at DESC LIMIT 50", 
        [$uidParam]
    );
    $details['userTransactionsAsSeller'] = getList($db, 
        "SELECT t.id, t.pid, t.price, t.order_id, t.created_at, p.title 
         FROM transactions t 
         LEFT JOIN products p ON p.pid = t.pid 
         WHERE t.receiving_uid = ? 
         ORDER BY t.created_at DESC LIMIT 50", 
        [$uidParam]
    );
    $details['userListings'] = getList($db, 
        "SELECT pid, title, product_type, price, is_available 
         FROM products 
         WHERE uid = ? 
         ORDER BY pid DESC LIMIT 50", 
        [$uidParam]
    );
}

// Seller detail view
if ($view === 'seller' && $uidParam > 0) {
    $details['sellerDetail'] = getRow($db, 
        "SELECT uid, username, email, address, billing_fullname, role, pay_sortcode, pay_banknumber 
         FROM users 
         WHERE uid = ? LIMIT 1", 
        [$uidParam]
    );
    $details['userListings'] = getList($db, 
        "SELECT pid, title, product_type, price, is_available 
         FROM products 
         WHERE uid = ? 
         ORDER BY pid DESC LIMIT 100", 
        [$uidParam]
    );
    $details['userTransactionsAsSeller'] = getList($db, 
        "SELECT t.id, t.pid, t.price, t.order_id, t.created_at, p.title, ub.username AS buyer_name 
         FROM transactions t 
         LEFT JOIN products p ON p.pid = t.pid 
         LEFT JOIN users ub ON ub.uid = t.paying_uid 
         WHERE t.receiving_uid = ? 
         ORDER BY t.created_at DESC LIMIT 100", 
        [$uidParam]
    );
}

// Item detail view
if ($view === 'item' && $pidParam > 0) {
    $details['itemDetail'] = getRow($db, 
        "SELECT p.pid, p.title, p.image, p.product_type, p.price, p.description, p.uid AS seller_uid, 
         u.username AS seller_name, u.email AS seller_email, p.is_available 
         FROM products p 
         LEFT JOIN users u ON u.uid = p.uid 
         WHERE p.pid = ? LIMIT 1", 
        [$pidParam]
    );
    $details['itemTransactions'] = getList($db, 
        "SELECT t.id, t.price, t.order_id, t.created_at, ub.username AS buyer_name, us.username AS seller_name 
         FROM transactions t 
         LEFT JOIN users ub ON ub.uid = t.paying_uid 
         LEFT JOIN users us ON us.uid = t.receiving_uid 
         WHERE t.pid = ? 
         ORDER BY t.created_at DESC LIMIT 50", 
        [$pidParam]
    );
}

// Transaction detail view
if ($view === 'transaction' && $tidParam > 0) {
    $details['transactionDetail'] = getRow($db, 
        "SELECT t.id, t.pid, t.paying_uid, t.receiving_uid, t.price, t.order_id, t.created_at, 
         p.title AS item_title, p.product_type, p.image, 
         ub.username AS buyer_name, ub.email AS buyer_email, 
         us.username AS seller_name, us.email AS seller_email 
         FROM transactions t 
         LEFT JOIN products p ON p.pid = t.pid 
         LEFT JOIN users ub ON ub.uid = t.paying_uid 
         LEFT JOIN users us ON us.uid = t.receiving_uid 
         WHERE t.id = ? LIMIT 1", 
        [$tidParam]
    );
}

extract($details);
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
        <?php
        $menuItems = [
            ['#overview', 'Dashboard Overview'],
            ['#users', 'Users'],
            ['#sellers', 'Sellers'],
            ['#items', 'Items'],
            ['#transactions', 'Transactions'],
            ['report.php', 'Reports'],
            ['#support', 'Support']
        ];
        foreach ($menuItems as [$href, $label]):
        ?>
            <a href="<?= h($href) ?>" class="side-link"><?= h($label) ?></a>
        <?php endforeach; ?>
        <?php if ($view !== ''): ?>
            <a href="admin_dashboard.php" class="side-link">Clear details</a>
        <?php endif; ?>
    </aside>

    <section class="main-content">

        <!-- Dashboard Overview -->
        <div id="overview" class="section-block">
            <h2>Admin Dashboard</h2>
            <div class="overview-grid">
                <?php
                $overviewCards = [
                    ['Total Users', $stats['totalUsers']],
                    ['Total Sellers', $stats['totalSellers']],
                    ['Total Items', $stats['totalItems']],
                    ['Total Transactions', $stats['totalTransactions']],
                    ['Support Inbox', $stats['supportOpenCount'] . ' Open']
                ];
                foreach ($overviewCards as [$title, $value]):
                    echo renderOverviewCard($title, $value);
                endforeach;
                ?>
            </div>
            <div style="margin-top:12px;">
                <a href="report.php" class="btn primary">Open Reports</a>
            </div>
        </div>

        <!-- User Detail View -->
        <?php if ($view === 'user' && $userDetail): ?>
            <div class="section-block">
                <h2>User Details</h2>
                <div class="summaryBox">
                    <?= renderSummaryLine('UID', $userDetail['uid']) ?>
                    <?= renderSummaryLine('Username', $userDetail['username']) ?>
                    <?= renderSummaryLine('Email', $userDetail['email']) ?>
                    <?= renderSummaryLine('Role', $userDetail['role']) ?>
                    <?= renderSummaryLine('Billing name', $userDetail['billing_fullname']) ?>
                    <?= renderSummaryLine('Address', $userDetail['address']) ?>
                </div>

                <!-- User Listings -->
                <h3 style="margin-top:18px;">Listings</h3>
                <table class="main-table">
                    <thead><?= renderTableRow(['PID', 'Title', 'Type', 'Price', 'Available', 'Actions'], true) ?></thead>
                    <tbody>
                        <?php if (empty($userListings)): ?>
                            <?= renderEmptyRow(6, 'No listings.') ?>
                        <?php else: foreach ($userListings as $p): ?>
                            <?= renderTableRow([
                                (int)$p['pid'],
                                h($p['title']),
                                h($p['product_type']),
                                '£' . number_format((float)$p['price'], 2),
                                ((int)$p['is_available'] === 1) ? 'Yes' : 'No',
                                '<a href="admin_dashboard.php?view=item&pid=' . (int)$p['pid'] . '" class="btn small">View</a>'
                            ]) ?>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>

                <!-- User Transactions -->
                <?php
                $transactionSections = [
                    ['Purchases (as Buyer)', $userTransactionsAsBuyer],
                    ['Sales (as Seller)', $userTransactionsAsSeller]
                ];
                foreach ($transactionSections as [$sectionTitle, $transactionList]):
                ?>
                    <h3 style="margin-top:18px;"><?= h($sectionTitle) ?></h3>
                    <table class="main-table">
                        <thead><?= renderTableRow(['ID', 'Item', 'Price', 'Order ID', 'Date', 'Actions'], true) ?></thead>
                        <tbody>
                            <?php if (empty($transactionList)): ?>
                                <?= renderEmptyRow(6, 'No transactions.') ?>
                            <?php else: foreach ($transactionList as $t): ?>
                                <?= renderTableRow([
                                    (int)$t['id'],
                                    h($t['title']),
                                    '£' . number_format((float)$t['price'], 2),
                                    h($t['order_id']),
                                    h($t['created_at']),
                                    '<a href="admin_dashboard.php?view=transaction&tid=' . (int)$t['id'] . '" class="btn small">View</a>'
                                ]) ?>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            </div>
        <?php elseif ($view === 'user' && $uidParam > 0): ?>
            <div class="section-block">
                <h2>User Details</h2>
                <p>User not found.</p>
            </div>
        <?php endif; ?>

        <!-- Seller Detail View -->
        <?php if ($view === 'seller' && $sellerDetail): ?>
            <div class="section-block">
                <h2>Seller Details</h2>
                <div class="summaryBox">
                    <?= renderSummaryLine('UID', $sellerDetail['uid']) ?>
                    <?= renderSummaryLine('Username', $sellerDetail['username']) ?>
                    <?= renderSummaryLine('Email', $sellerDetail['email']) ?>
                    <?= renderSummaryLine('Role', $sellerDetail['role']) ?>
                    <?= renderSummaryLine('Billing name', $sellerDetail['billing_fullname']) ?>
                    <?= renderSummaryLine('Address', $sellerDetail['address']) ?>
                    <?= renderSummaryLine('Sort code', $sellerDetail['pay_sortcode']) ?>
                    <?= renderSummaryLine('Account number', $sellerDetail['pay_banknumber']) ?>
                </div>

                <h3 style="margin-top:18px;">Listings</h3>
                <table class="main-table">
                    <thead><?= renderTableRow(['PID', 'Title', 'Type', 'Price', 'Available', 'Actions'], true) ?></thead>
                    <tbody>
                        <?php if (empty($userListings)): ?>
                            <?= renderEmptyRow(6, 'No listings.') ?>
                        <?php else: foreach ($userListings as $p): ?>
                            <?= renderTableRow([
                                (int)$p['pid'],
                                h($p['title']),
                                h($p['product_type']),
                                '£' . number_format((float)$p['price'], 2),
                                ((int)$p['is_available'] === 1) ? 'Yes' : 'No',
                                '<a href="admin_dashboard.php?view=item&pid=' . (int)$p['pid'] . '" class="btn small">View</a>'
                            ]) ?>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>

                <h3 style="margin-top:18px;">Sales Transactions</h3>
                <table class="main-table">
                    <thead><?= renderTableRow(['ID', 'Item', 'Buyer', 'Price', 'Order ID', 'Date', 'Actions'], true) ?></thead>
                    <tbody>
                        <?php if (empty($userTransactionsAsSeller)): ?>
                            <?= renderEmptyRow(7, 'No transactions.') ?>
                        <?php else: foreach ($userTransactionsAsSeller as $t): ?>
                            <?= renderTableRow([
                                (int)$t['id'],
                                h($t['title']),
                                h($t['buyer_name']),
                                '£' . number_format((float)$t['price'], 2),
                                h($t['order_id']),
                                h($t['created_at']),
                                '<a href="admin_dashboard.php?view=transaction&tid=' . (int)$t['id'] . '" class="btn small">View</a>'
                            ]) ?>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($view === 'seller' && $uidParam > 0): ?>
            <div class="section-block">
                <h2>Seller Details</h2>
                <p>Seller not found.</p>
            </div>
        <?php endif; ?>

        <!-- Item Detail View -->
        <?php if ($view === 'item' && $itemDetail): ?>
            <div class="section-block">
                <h2>Item Details</h2>
                <div style="display:flex; gap:20px; align-items:flex-start; flex-wrap:wrap;">
                    <?php if (!empty($itemDetail['image'])): ?>
                        <div style="width:220px;">
                            <img src="images/<?= h($itemDetail['image']) ?>" style="width:220px;height:220px;object-fit:cover;border-radius:12px;border:1px solid #ddd;">
                        </div>
                    <?php endif; ?>
                    <div style="flex:1; min-width:300px;">
                        <div class="summaryBox">
                            <?= renderSummaryLine('PID', $itemDetail['pid']) ?>
                            <?= renderSummaryLine('Title', $itemDetail['title']) ?>
                            <?= renderSummaryLine('Type', $itemDetail['product_type']) ?>
                            <?= renderSummaryLine('Price per day', '£' . number_format((float)$itemDetail['price'], 2)) ?>
                            <?= renderSummaryLine('Available', ((int)$itemDetail['is_available'] === 1) ? 'Yes' : 'No') ?>
                            <?= renderSummaryLine('Seller', $itemDetail['seller_name'] . ' (UID: ' . (int)$itemDetail['seller_uid'] . ')') ?>
                            <?= renderSummaryLine('Seller email', $itemDetail['seller_email']) ?>
                            <?= renderSummaryLine('Description', $itemDetail['description']) ?>
                        </div>
                        <div style="margin-top:15px;">
                            <a href="admin_dashboard.php?view=seller&uid=<?= (int)$itemDetail['seller_uid'] ?>" class="btn primary">View Seller</a>
                        </div>
                    </div>
                </div>

                <h3 style="margin-top:18px;">Transaction History</h3>
                <table class="main-table">
                    <thead><?= renderTableRow(['ID', 'Buyer', 'Seller', 'Price', 'Order ID', 'Date', 'Actions'], true) ?></thead>
                    <tbody>
                        <?php if (empty($itemTransactions)): ?>
                            <?= renderEmptyRow(7, 'No transactions.') ?>
                        <?php else: foreach ($itemTransactions as $t): ?>
                            <?= renderTableRow([
                                (int)$t['id'],
                                h($t['buyer_name']),
                                h($t['seller_name']),
                                '£' . number_format((float)$t['price'], 2),
                                h($t['order_id']),
                                h($t['created_at']),
                                '<a href="admin_dashboard.php?view=transaction&tid=' . (int)$t['id'] . '" class="btn small">View</a>'
                            ]) ?>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($view === 'item' && $pidParam > 0): ?>
            <div class="section-block">
                <h2>Item Details</h2>
                <p>Item not found.</p>
            </div>
        <?php endif; ?>

        <!-- Transaction Detail View -->
        <?php if ($view === 'transaction' && $transactionDetail): ?>
            <div class="section-block">
                <h2>Transaction Details</h2>
                <div class="summaryBox">
                    <?= renderSummaryLine('Transaction ID', $transactionDetail['id']) ?>
                    <?= renderSummaryLine('Order ID', $transactionDetail['order_id']) ?>
                    <?= renderSummaryLine('Date', $transactionDetail['created_at']) ?>
                    <?= renderSummaryLine('Item', $transactionDetail['item_title'] . ' (PID: ' . (int)$transactionDetail['pid'] . ')') ?>
                    <?= renderSummaryLine('Category', $transactionDetail['product_type']) ?>
                    <?= renderSummaryLine('Price', '£' . number_format((float)$transactionDetail['price'], 2)) ?>
                    <?= renderSummaryLine('Buyer', $transactionDetail['buyer_name'] . ' (UID: ' . (int)$transactionDetail['paying_uid'] . ')') ?>
                    <?= renderSummaryLine('Buyer email', $transactionDetail['buyer_email']) ?>
                    <?= renderSummaryLine('Seller', $transactionDetail['seller_name'] . ' (UID: ' . (int)$transactionDetail['receiving_uid'] . ')') ?>
                    <?= renderSummaryLine('Seller email', $transactionDetail['seller_email']) ?>
                </div>
                <div style="margin-top:15px; display:flex; gap:12px; flex-wrap:wrap;">
                    <a href="admin_dashboard.php?view=item&pid=<?= (int)$transactionDetail['pid'] ?>" class="btn primary">View Item</a>
                    <a href="admin_dashboard.php?view=user&uid=<?= (int)$transactionDetail['paying_uid'] ?>" class="btn primary">View Buyer</a>
                    <a href="admin_dashboard.php?view=seller&uid=<?= (int)$transactionDetail['receiving_uid'] ?>" class="btn primary">View Seller</a>
                </div>
            </div>
        <?php elseif ($view === 'transaction' && $tidParam > 0): ?>
            <div class="section-block">
                <h2>Transaction Details</h2>
                <p>Transaction not found.</p>
            </div>
        <?php endif; ?>

        <!-- Users List -->
        <div id="users" class="section-block">
            <h2>All Users</h2>
            <table class="main-table">
                <thead><?= renderTableRow(['UID', 'Username', 'Email', 'Role', 'Actions'], true) ?></thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <?= renderEmptyRow(5, 'No users found.') ?>
                    <?php else: foreach ($users as $u): ?>
                        <?= renderTableRow([
                            (int)$u['uid'],
                            h($u['username']),
                            h($u['email']),
                            h($u['role']),
                            '<a href="admin_dashboard.php?view=user&uid=' . (int)$u['uid'] . '" class="btn small">View</a>'
                        ]) ?>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Sellers List -->
        <div id="sellers" class="section-block">
            <h2>All Sellers</h2>
            <table class="main-table">
                <thead><?= renderTableRow(['UID', 'Username', 'Email', 'Role', 'Actions'], true) ?></thead>
                <tbody>
                    <?php if (empty($sellers)): ?>
                        <?= renderEmptyRow(5, 'No sellers found.') ?>
                    <?php else: foreach ($sellers as $s): ?>
                        <?= renderTableRow([
                            (int)$s['uid'],
                            h($s['username']),
                            h($s['email']),
                            h($s['role']),
                            '<a href="admin_dashboard.php?view=seller&uid=' . (int)$s['uid'] . '" class="btn small">View</a>'
                        ]) ?>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Items List -->
        <div id="items" class="section-block">
            <h2>All Items</h2>
            <table class="main-table">
                <thead><?= renderTableRow(['PID', 'Title', 'Type', 'Price', 'Seller', 'Available', 'Actions'], true) ?></thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <?= renderEmptyRow(7, 'No items found.') ?>
                    <?php else: foreach ($items as $i): ?>
                        <?= renderTableRow([
                            (int)$i['pid'],
                            h($i['title']),
                            h($i['product_type']),
                            '£' . number_format((float)$i['price'], 2),
                            h($i['seller_name']) . ' (UID: ' . (int)$i['seller_uid'] . ')',
                            ((int)$i['is_available'] === 1) ? 'Yes' : 'No',
                            '<a href="admin_dashboard.php?view=item&pid=' . (int)$i['pid'] . '" class="btn small">View</a>'
                        ]) ?>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Transactions List -->
        <div id="transactions" class="section-block">
            <h2>Recent Transactions</h2>
            <p style="margin-bottom: 15px; color: #666;">Showing last 100 transactions</p>
            <table class="main-table">
                <thead><?= renderTableRow(['ID', 'Item', 'Buyer', 'Seller', 'Price', 'Order ID', 'Date', 'Actions'], true) ?></thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <?= renderEmptyRow(8, 'No transactions found.') ?>
                    <?php else: foreach ($transactions as $t): ?>
                        <?= renderTableRow([
                            (int)$t['id'],
                            h($t['item_title']),
                            h($t['buyer_name']) . ' (' . (int)$t['paying_uid'] . ')',
                            h($t['seller_name']) . ' (' . (int)$t['receiving_uid'] . ')',
                            '£' . number_format((float)$t['price'], 2),
                            h($t['order_id']),
                            h($t['created_at']),
                            '<a href="admin_dashboard.php?view=transaction&tid=' . (int)$t['id'] . '" class="btn small">View</a>'
                        ]) ?>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Support Section -->
        <div id="support" class="section-block">
            <h2>Support Conversations</h2>
            <div style="margin:10px 0;">
                <a class="btn primary" href="admin_support.php">Open Support Inbox</a>
            </div>
            <?php if (empty($supportConvs)): ?>
                <p>No support conversations.</p>
            <?php else: ?>
                <table class="main-table">
                    <thead><?= renderTableRow(['ID', 'User', 'Status', 'Created', 'Actions'], true) ?></thead>
                    <tbody>
                        <?php foreach ($supportConvs as $c): ?>
                            <?= renderTableRow([
                                (int)$c['id'],
                                h($c['username']) . ' (UID: ' . (int)$c['user_uid'] . ')',
                                h($c['status']),
                                h($c['created_at']),
                                '<a href="admin_support.php?conv_id=' . (int)$c['id'] . '" class="btn small">View</a>'
                            ]) ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </section>
</div>

</body>
</html>
