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

/* Helper to safely query counts */
function getCount($db, $query) {
    try {
        return (int)$db->query($query)->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/* Helper to safely query lists */
function getList($db, $query, $params = []) {
    try {
        if ($params) {
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/* Helper to safely query single row */
function getRow($db, $query, $params = []) {
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

/* Totals */
$totalUsers = getCount($db, "SELECT COUNT(*) FROM users");
$totalSellers = getCount($db, "SELECT COUNT(DISTINCT uid) FROM products WHERE uid IS NOT NULL");
$totalItems = getCount($db, "SELECT COUNT(*) FROM products");
$totalTransactions = getCount($db, "SELECT COUNT(*) FROM transactions");
$supportOpenCount = getCount($db, "SELECT COUNT(*) FROM admin_conversations WHERE status = 'open'");

/* Lists */
$users = getList($db, "SELECT uid, username, email, role FROM users ORDER BY uid DESC");
$sellers = getList($db, "SELECT DISTINCT u.uid, u.username, u.email, u.role FROM users u JOIN products p ON p.uid = u.uid ORDER BY u.uid DESC");
$items = getList($db, "SELECT p.pid, p.title, p.product_type, p.price, p.uid AS seller_uid, u.username AS seller_name, p.is_available FROM products p LEFT JOIN users u ON u.uid = p.uid ORDER BY p.pid DESC");
$transactions = getList($db, "SELECT t.id, t.pid, t.paying_uid, t.receiving_uid, t.price, t.order_id, t.created_at, p.title AS item_title, ub.username AS buyer_name, us.username AS seller_name FROM transactions t LEFT JOIN products p ON p.pid = t.pid LEFT JOIN users ub ON ub.uid = t.paying_uid LEFT JOIN users us ON us.uid = t.receiving_uid ORDER BY t.created_at DESC LIMIT 100");
$supportConvs = getList($db, "SELECT ac.id, ac.user_uid, ac.status, ac.created_at, u.username FROM admin_conversations ac JOIN users u ON u.uid = ac.user_uid ORDER BY ac.status ASC, ac.created_at DESC LIMIT 50");

/* Detail panels */
$userDetail = $sellerDetail = $itemDetail = $transactionDetail = null;
$userListings = $userTransactionsAsBuyer = $userTransactionsAsSeller = $itemTransactions = [];

if ($view === 'user' && $uidParam > 0) {
    $userDetail = getRow($db, "SELECT uid, username, email, address, billing_fullname, role FROM users WHERE uid = ? LIMIT 1", [$uidParam]);
    $userTransactionsAsBuyer = getList($db, "SELECT t.id, t.pid, t.price, t.order_id, t.created_at, p.title FROM transactions t LEFT JOIN products p ON p.pid = t.pid WHERE t.paying_uid = ? ORDER BY t.created_at DESC LIMIT 50", [$uidParam]);
    $userTransactionsAsSeller = getList($db, "SELECT t.id, t.pid, t.price, t.order_id, t.created_at, p.title FROM transactions t LEFT JOIN products p ON p.pid = t.pid WHERE t.receiving_uid = ? ORDER BY t.created_at DESC LIMIT 50", [$uidParam]);
    $userListings = getList($db, "SELECT pid, title, product_type, price, is_available FROM products WHERE uid = ? ORDER BY pid DESC LIMIT 50", [$uidParam]);
}

if ($view === 'seller' && $uidParam > 0) {
    $sellerDetail = getRow($db, "SELECT uid, username, email, address, billing_fullname, role, pay_sortcode, pay_banknumber FROM users WHERE uid = ? LIMIT 1", [$uidParam]);
    $userListings = getList($db, "SELECT pid, title, product_type, price, is_available FROM products WHERE uid = ? ORDER BY pid DESC LIMIT 100", [$uidParam]);
    $userTransactionsAsSeller = getList($db, "SELECT t.id, t.pid, t.price, t.order_id, t.created_at, p.title, ub.username AS buyer_name FROM transactions t LEFT JOIN products p ON p.pid = t.pid LEFT JOIN users ub ON ub.uid = t.paying_uid WHERE t.receiving_uid = ? ORDER BY t.created_at DESC LIMIT 100", [$uidParam]);
}

if ($view === 'item' && $pidParam > 0) {
    $itemDetail = getRow($db, "SELECT p.pid, p.title, p.image, p.product_type, p.price, p.description, p.uid AS seller_uid, u.username AS seller_name, u.email AS seller_email, p.is_available FROM products p LEFT JOIN users u ON u.uid = p.uid WHERE p.pid = ? LIMIT 1", [$pidParam]);
    $itemTransactions = getList($db, "SELECT t.id, t.price, t.order_id, t.created_at, ub.username AS buyer_name, us.username AS seller_name FROM transactions t LEFT JOIN users ub ON ub.uid = t.paying_uid LEFT JOIN users us ON us.uid = t.receiving_uid WHERE t.pid = ? ORDER BY t.created_at DESC LIMIT 50", [$pidParam]);
}

if ($view === 'transaction' && $tidParam > 0) {
    $transactionDetail = getRow($db, "SELECT t.id, t.pid, t.paying_uid, t.receiving_uid, t.price, t.order_id, t.created_at, p.title AS item_title, p.product_type, p.image, ub.username AS buyer_name, ub.email AS buyer_email, us.username AS seller_name, us.email AS seller_email FROM transactions t LEFT JOIN products p ON p.pid = t.pid LEFT JOIN users ub ON ub.uid = t.paying_uid LEFT JOIN users us ON us.uid = t.receiving_uid WHERE t.id = ? LIMIT 1", [$tidParam]);
}

/* Helper functions for rendering */
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
                <?php
                $cards = [
                    ['Total Users', $totalUsers],
                    ['Total Sellers', $totalSellers],
                    ['Total Items', $totalItems],
                    ['Total Transactions', $totalTransactions],
                    ['Support Inbox', $supportOpenCount . ' Open']
                ];
                foreach ($cards as [$title, $value]):
                ?>
                    <div class="overview-card">
                        <h3><?= h($title) ?></h3>
                        <p class="green"><?= h($value) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:12px;">
                <a href="report.php" class="btn primary">Open Reports</a>
            </div>
        </div>

        <?php if ($view === 'user' && $userDetail): ?>
            <div class="section-block">
                <h2>User details</h2>
                <div class="summaryBox">
                    <?= renderSummaryLine('UID', $userDetail['uid']) ?>
                    <?= renderSummaryLine('Username', $userDetail['username']) ?>
                    <?= renderSummaryLine('Email', $userDetail['email']) ?>
                    <?= renderSummaryLine('Role', $userDetail['role']) ?>
                    <?= renderSummaryLine('Billing name', $userDetail['billing_fullname']) ?>
                    <?= renderSummaryLine('Address', $userDetail['address']) ?>
                </div>

                <h3 style="margin-top:18px;">Listings</h3>
                <table class="main-table">
                    <thead><?= renderTableRow(['PID', 'Title', 'Type', 'Price', 'Available', 'Open'], true) ?></thead>
                    <tbody>
                        <?php if (!$userListings): ?>
                            <tr><td colspan="6">No listings.</td></tr>
                        <?php else: foreach ($userListings as $p): ?>
                            <?= renderTableRow([
                                (int)$p['pid'],
                                h($p['title']),
                                h($p['product_type']),
                                '£' . number_format((float)$p['price'], 2),
                                ((int)$p['is_available'] === 1) ? 'Yes' : 'No',
                                '<a href="admin_dashboard.php?view=item&pid=' . (int)$p['pid'] . '">Open</a>'
                            ]) ?>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>

                <?php
                $transactionSections = [
                    ['Transactions as buyer', $userTransactionsAsBuyer],
                    ['Transactions as seller', $userTransactionsAsSeller]
                ];
                foreach ($transactionSections as [$sectionTitle, $transactionList]):
                ?>
                    <h3 style="margin-top:18px;"><?= h($sectionTitle) ?></h3>
                    <table class="main-table">
                        <thead><?= renderTableRow(['ID', 'Item', 'Price', 'Order ID', 'Date', 'Open'], true) ?></thead>
                        <tbody>
                            <?php if (!$transactionList): ?>
                                <tr><td colspan="6">No transactions.</td></tr>
                            <?php else: foreach ($transactionList as $t): ?>
                                <?= renderTableRow([
                                    (int)$t['id'],
                                    h($t['title']),
                                    '£' . number_format((float)$t['price'], 2),
                                    h($t['order_id']),
                                    h($t['created_at']),
                                    '<a href="admin_dashboard.php?view=transaction&tid=' . (int)$t['id'] . '">Open</a>'
                                ]) ?>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            </div>
        <?php elseif ($view === 'user' && $uidParam > 0): ?>
            <div class="section-block"><h2>User details</h2><p>User not found.</p></div>
        <?php endif; ?>

        <?php if ($view === 'seller' && $sellerDetail): ?>
            <div class="section-block">
                <h2>Seller details</h2>
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
                    <thead><?= renderTableRow(['PID', 'Title', 'Type', 'Price', 'Available', 'Open'], true) ?></thead>
                    <tbody>
                        <?php if (!$userListings): ?>
                            <tr><td colspan="6">No listings.</td></tr>
                        <?php else: foreach ($userListings as $p): ?>
                            <?= renderTableRow([
                                (int)$p['pid'],
                                h($p['title']),
                                h($p['product_type']),
                                '£' . number_format((float)$p['price'], 2),
                                ((int)$p['is_available'] === 1) ? 'Yes' : 'No',
                                '<a href="admin_dashboard.php?view=item&pid=' . (int)$p['pid'] . '">Open</a>'
                            ]) ?>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>

                <h3 style="margin-top:18px;">Transactions</h3>
                <table class="main-table">
                    <thead><?= renderTableRow(['ID', 'Item', 'Buyer', 'Price', 'Order ID', 'Date', 'Open'], true) ?></thead>
                    <tbody>
                        <?php if (!$userTransactionsAsSeller): ?>
                            <tr><td colspan="7">No transactions.</td></tr>
                        <?php else: foreach ($userTransactionsAsSeller as $t): ?>
                            <?= renderTableRow([
                                (int)$t['id'],
                                h($t['title']),
                                h($t['buyer_name']),
                                '£' . number_format((float)$t['price'], 2),
                                h($t['order_id']),
                                h($t['created_at']),
                                '<a href="admin_dashboard.php?view=transaction&tid=' . (int)$t['id'] . '">Open</a>'
                            ]) ?>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($view === 'seller' && $uidParam > 0): ?>
            <div class="section-block"><h2>Seller details</h2><p>Seller not found.</p></div>
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
                            <?= renderSummaryLine('PID', $itemDetail['pid']) ?>
                            <?= renderSummaryLine('Title', $itemDetail['title']) ?>
                            <?= renderSummaryLine('Type', $itemDetail['product_type']) ?>
                            <?= renderSummaryLine('Per day price', '£' . number_format((float)$itemDetail['price'], 2)) ?>
                            <?= renderSummaryLine('Available', ((int)$itemDetail['is_available'] === 1) ? 'Yes' : 'No') ?>
                            <?= renderSummaryLine('Seller', $itemDetail['seller_name'] . ' (' . (int)$itemDetail['seller_uid'] . ')') ?>
                            <?= renderSummaryLine('Seller email', $itemDetail['seller_email']) ?>
                            <?= renderSummaryLine('Description', $itemDetail['description']) ?>
                        </div>
                        <div style="margin-top:10px;">
                            <a href="admin_dashboard.php?view=seller&uid=<?= (int)$itemDetail['seller_uid'] ?>">Open seller</a>
                        </div>
                    </div>
                </div>

                <h3 style="margin-top:18px;">Transactions for this item</h3>
                <table class="main-table">
                    <thead><?= renderTableRow(['ID', 'Buyer', 'Seller', 'Price', 'Order ID', 'Date', 'Open'], true) ?></thead>
                    <tbody>
                        <?php if (!$itemTransactions): ?>
                            <tr><td colspan="7">No transactions.</td></tr>
                        <?php else: foreach ($itemTransactions as $t): ?>
                            <?= renderTableRow([
                                (int)$t['id'],
                                h($t['buyer_name']),
                                h($t['seller_name']),
                                '£' . number_format((float)$t['price'], 2),
                                h($t['order_id']),
                                h($t['created_at']),
                                '<a href="admin_dashboard.php?view=transaction&tid=' . (int)$t['id'] . '">Open</a>'
                            ]) ?>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($view === 'item' && $pidParam > 0): ?>
            <div class="section-block"><h2>Item details</h2><p>Item not found.</p></div>
        <?php endif; ?>

        <?php if ($view === 'transaction' && $transactionDetail): ?>
            <div class="section-block">
                <h2>Transaction details</h2>
                <div class="summaryBox">
                    <?= renderSummaryLine('ID', $transactionDetail['id']) ?>
                    <?= renderSummaryLine('Order ID', $transactionDetail['order_id']) ?>
                    <?= renderSummaryLine('Date', $transactionDetail['created_at']) ?>
                    <?= renderSummaryLine('Item', $transactionDetail['item_title'] . ' (PID ' . (int)$transactionDetail['pid'] . ')') ?>
                    <?= renderSummaryLine('Type', $transactionDetail['product_type']) ?>
                    <?= renderSummaryLine('Price', '£' . number_format((float)$transactionDetail['price'], 2)) ?>
                    <?= renderSummaryLine('Buyer', $transactionDetail['buyer_name'] . ' (' . (int)$transactionDetail['paying_uid'] . ')') ?>
                    <?= renderSummaryLine('Buyer email', $transactionDetail['buyer_email']) ?>
                    <?= renderSummaryLine('Seller', $transactionDetail['seller_name'] . ' (' . (int)$transactionDetail['receiving_uid'] . ')') ?>
                    <?= renderSummaryLine('Seller email', $transactionDetail['seller_email']) ?>
                </div>
                <div style="margin-top:10px; display:flex; gap:12px; flex-wrap:wrap;">
                    <a href="admin_dashboard.php?view=item&pid=<?= (int)$transactionDetail['pid'] ?>">Open item</a>
                    <a href="admin_dashboard.php?view=user&uid=<?= (int)$transactionDetail['paying_uid'] ?>">Open buyer</a>
                    <a href="admin_dashboard.php?view=seller&uid=<?= (int)$transactionDetail['receiving_uid'] ?>">Open seller</a>
                </div>
            </div>
        <?php elseif ($view === 'transaction' && $tidParam > 0): ?>
            <div class="section-block"><h2>Transaction details</h2><p>Transaction not found.</p></div>
        <?php endif; ?>

        <div id="users" class="section-block">
            <h2>Users</h2>
            <table class="main-table">
                <thead><?= renderTableRow(['UID', 'Username', 'Email', 'Role', 'Open'], true) ?></thead>
                <tbody>
                    <?php if (!$users): ?>
                        <tr><td colspan="5">No users found.</td></tr>
                    <?php else: foreach ($users as $u): ?>
                        <?= renderTableRow([
                            (int)$u['uid'],
                            h($u['username']),
                            h($u['email']),
                            h($u['role']),
                            '<a href="admin_dashboard.php?view=user&uid=' . (int)$u['uid'] . '">Open</a>'
                        ]) ?>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <div id="sellers" class="section-block">
            <h2>Sellers</h2>
            <table class="main-table">
                <thead><?= renderTableRow(['UID', 'Username', 'Email', 'Role', 'Open'], true) ?></thead>
                <tbody>
                    <?php if (!$sellers): ?>
                        <tr><td colspan="5">No sellers found.</td></tr>
                    <?php else: foreach ($sellers as $s): ?>
                        <?= renderTableRow([
                            (int)$s['uid'],
                            h($s['username']),
                            h($s['email']),
                            h($s['role']),
                            '<a href="admin_dashboard.php?view=seller&uid=' . (int)$s['uid'] . '">Open</a>'
                        ]) ?>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <div id="items" class="section-block">
            <h2>Items</h2>
            <table class="main-table">
                <thead><?= renderTableRow(['PID', 'Title', 'Type', 'Price', 'Seller', 'Available', 'Open'], true) ?></thead>
                <tbody>
                    <?php if (!$items): ?>
                        <tr><td colspan="7">No items found.</td></tr>
                    <?php else: foreach ($items as $i): ?>
                        <?= renderTableRow([
                            (int)$i['pid'],
                            h($i['title']),
                            h($i['product_type']),
                            '£' . number_format((float)$i['price'], 2),
                            h($i['seller_name']) . ' (' . (int)$i['seller_uid'] . ')',
                            ((int)$i['is_available'] === 1) ? 'Yes' : 'No',
                            '<a href="admin_dashboard.php?view=item&pid=' . (int)$i['pid'] . '">Open</a>'
                        ]) ?>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <div id="transactions" class="section-block">
            <h2>Transactions</h2>
            <table class="main-table">
                <thead><?= renderTableRow(['ID', 'Item', 'Buyer', 'Seller', 'Price', 'Order ID', 'Date', 'Open'], true) ?></thead>
                <tbody>
                    <?php if (!$transactions): ?>
                        <tr><td colspan="8">No transactions found.</td></tr>
                    <?php else: foreach ($transactions as $t): ?>
                        <?= renderTableRow([
                            (int)$t['id'],
                            h($t['item_title']),
                            h($t['buyer_name']) . ' (' . (int)$t['paying_uid'] . ')',
                            h($t['seller_name']) . ' (' . (int)$t['receiving_uid'] . ')',
                            '£' . number_format((float)$t['price'], 2),
                            h($t['order_id']),
                            h($t['created_at']),
                            '<a href="admin_dashboard.php?view=transaction&tid=' . (int)$t['id'] . '">Open</a>'
                        ]) ?>
                    <?php endforeach; endif; ?>
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
                    <thead><?= renderTableRow(['ID', 'User', 'Status', 'Created', 'Open'], true) ?></thead>
                    <tbody>
                        <?php foreach ($supportConvs as $c): ?>
                            <?= renderTableRow([
                                (int)$c['id'],
                                h($c['username']) . ' (' . (int)$c['user_uid'] . ')',
                                h($c['status']),
                                h($c['created_at']),
                                '<a href="admin_support.php?conv_id=' . (int)$c['id'] . '">Open</a>'
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
