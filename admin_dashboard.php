<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();
require_once 'connectdb.php';

set_exception_handler(function($e) {
    error_log("Uncaught exception: " . $e->getMessage());
    http_response_code(500);
    echo "Server error. Check logs.";
    exit;
});

if (!isset($_SESSION['uid'])) {
    header("Location: login.php");
    exit;
}

$admin_uid = (int)$_SESSION['uid'];

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function tableExists(PDO $db, $tableName) {
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
        $stmt->execute([$tableName]);
        return ((int)$stmt->fetchColumn() > 0);
    } catch (PDOException $e) {
        error_log("tableExists error: " . $e->getMessage());
        return false;
    }
}

function columnExists(PDO $db, $tableName, $colName) {
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
        $stmt->execute([$tableName, $colName]);
        return ((int)$stmt->fetchColumn() > 0);
    } catch (PDOException $e) {
        error_log("columnExists error: " . $e->getMessage());
        return false;
    }
}

function getCount(PDO $db, $query, $params = []) {
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Count query error: " . $e->getMessage() . " SQL: " . $query);
        return 0;
    }
}

function getList(PDO $db, $query, $params = []) {
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("List query error: " . $e->getMessage() . " SQL: " . $query);
        return [];
    }
}

function getRow(PDO $db, $query, $params = []) {
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row : null;
    } catch (PDOException $e) {
        error_log("Row query error: " . $e->getMessage() . " SQL: " . $query);
        return null;
    }
}

function renderSummaryLine($label, $value) {
    return '<div class="summaryLine"><span>' . h($label) . '</span><span>' . h($value) . '</span></div>';
}

function renderTableRow($cells, $isHeader = false) {
    $tag = $isHeader ? 'th' : 'td';
    $out = '<tr>';
    for ($i = 0; $i < count($cells); $i++) {
        $out .= "<{$tag}>{$cells[$i]}</{$tag}>";
    }
    $out .= '</tr>';
    return $out;
}

function renderOverviewCard($title, $value) {
    return '<div class="overview-card"><h3>' . h($title) . '</h3><p class="green">' . h($value) . '</p></div>';
}

function renderEmptyRow($colspan, $message) {
    return '<tr><td colspan="' . (int)$colspan . '">' . h($message) . '</td></tr>';
}

$userRole = getRow($db, "SELECT role FROM users WHERE uid = ? LIMIT 1", [$admin_uid]);
if (!$userRole || ($userRole['role'] ?? '') !== 'admin') {
    die("Access denied.");
}

$view = trim($_GET['view'] ?? '');
$uidParam = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
$pidParam = isset($_GET['pid']) ? (int)$_GET['pid'] : 0;
$oiParam  = isset($_GET['oi'])  ? (int)$_GET['oi']  : 0;

error_log("Admin Dashboard - View: {$view}, UID: {$uidParam}, PID: {$pidParam}, OI: {$oiParam}");

$hasProducts = tableExists($db, 'products');
$hasUsers = tableExists($db, 'users');
$hasOrders = tableExists($db, 'orders');
$hasOrderItems = tableExists($db, 'order_items');
$hasShipments = tableExists($db, 'order_shipments');
$hasAdminConversations = tableExists($db, 'admin_conversations');

$productsHasIsAvailable = $hasProducts && columnExists($db, 'products', 'is_available');
$productsHasProductType = $hasProducts && columnExists($db, 'products', 'product_type');
$productsHasImage = $hasProducts && columnExists($db, 'products', 'image');
$productsHasDescription = $hasProducts && columnExists($db, 'products', 'description');

$usersHasPayout = $hasUsers && columnExists($db, 'users', 'pay_sortcode') && columnExists($db, 'users', 'pay_banknumber');
$usersHasAddress = $hasUsers && columnExists($db, 'users', 'address');
$usersHasBilling = $hasUsers && columnExists($db, 'users', 'billing_fullname');

$totalUsers = $hasUsers ? getCount($db, "SELECT COUNT(*) FROM users") : 0;
$totalItems = $hasProducts ? getCount($db, "SELECT COUNT(*) FROM products") : 0;
$totalSellers = 0;
if ($hasProducts) {
    $totalSellers = getCount($db, "SELECT COUNT(DISTINCT uid) FROM products WHERE uid IS NOT NULL");
}
$totalOrders = ($hasOrders ? getCount($db, "SELECT COUNT(*) FROM orders") : 0);
$totalOrderItems = ($hasOrderItems ? getCount($db, "SELECT COUNT(*) FROM order_items") : 0);
$supportOpenCount = 0;
if ($hasAdminConversations) {
    $supportOpenCount = getCount($db, "SELECT COUNT(*) FROM admin_conversations WHERE status = 'open'");
}

$users = [];
$sellers = [];
$items = [];
$recentOrders = [];
$supportConvs = [];

if ($hasUsers) {
    $users = getList($db, "SELECT uid, username, email, role FROM users ORDER BY uid DESC");
}

if ($hasUsers && $hasProducts) {
    $sellers = getList($db, "
        SELECT DISTINCT u.uid, u.username, u.email, u.role
        FROM users u
        JOIN products p ON p.uid = u.uid
        ORDER BY u.uid DESC
    ");
}

if ($hasProducts) {
    $items = getList($db, "
        SELECT
            p.pid,
            p.title,
            " . ($productsHasProductType ? "p.product_type" : "'' AS product_type") . ",
            p.price,
            p.uid AS seller_uid,
            u.username AS seller_name,
            " . ($productsHasIsAvailable ? "p.is_available" : "1") . " AS is_available
        FROM products p
        LEFT JOIN users u ON u.uid = p.uid
        ORDER BY p.pid DESC
    ");
}

if ($hasOrders && $hasOrderItems) {
    $recentOrders = getList($db, "
        SELECT
            oi.id AS order_item_id,
            o.order_id AS order_public_id,
            o.created_at,
            o.buyer_uid,
            ub.username AS buyer_name,
            oi.seller_uid,
            us.username AS seller_name,
            oi.pid,
            oi.title AS item_title,
            oi.rental_days,
            oi.per_day_price,
            oi.platform_fee,
            oi.line_total
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id_fk
        LEFT JOIN users ub ON ub.uid = o.buyer_uid
        LEFT JOIN users us ON us.uid = oi.seller_uid
        ORDER BY o.created_at DESC, oi.id DESC
        LIMIT 100
    ");
}

if ($hasAdminConversations && $hasUsers) {
    $supportConvs = getList($db, "
        SELECT ac.id, ac.user_uid, ac.status, ac.created_at, u.username
        FROM admin_conversations ac
        JOIN users u ON u.uid = ac.user_uid
        ORDER BY ac.status ASC, ac.created_at DESC
        LIMIT 50
    ");
}

$userDetail = null;
$sellerDetail = null;
$itemDetail = null;
$userListings = [];
$userOrdersAsBuyer = [];
$userOrdersAsSeller = [];
$itemOrderHistory = [];
$orderItemDetail = null;
$orderShipmentDetail = null;

if ($view === 'user' && $uidParam > 0 && $hasUsers) {
    $userDetail = getRow($db,
        "SELECT uid, username, email, role"
        . ($usersHasAddress ? ", address" : ", '' AS address")
        . ($usersHasBilling ? ", billing_fullname" : ", '' AS billing_fullname")
        . " FROM users WHERE uid = ? LIMIT 1",
        [$uidParam]
    );

    if ($hasProducts) {
        $userListings = getList($db,
            "SELECT pid, title, "
            . ($productsHasProductType ? "product_type" : "'' AS product_type") . ", "
            . "price, " . ($productsHasIsAvailable ? "is_available" : "1") . " AS is_available
             FROM products WHERE uid = ? ORDER BY pid DESC LIMIT 100",
            [$uidParam]
        );
    }

    if ($hasOrders && $hasOrderItems) {
        $userOrdersAsBuyer = getList($db, "
            SELECT oi.id AS order_item_id, o.order_id AS order_public_id, o.created_at,
                oi.title, oi.pid, oi.rental_days, oi.platform_fee, oi.line_total, us.username AS seller_name
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id_fk
            LEFT JOIN users us ON us.uid = oi.seller_uid
            WHERE o.buyer_uid = ?
            ORDER BY o.created_at DESC, oi.id DESC LIMIT 200
        ", [$uidParam]);

        $userOrdersAsSeller = getList($db, "
            SELECT oi.id AS order_item_id, o.order_id AS order_public_id, o.created_at,
                oi.title, oi.pid, oi.rental_days, oi.platform_fee, oi.line_total, ub.username AS buyer_name
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id_fk
            LEFT JOIN users ub ON ub.uid = o.buyer_uid
            WHERE oi.seller_uid = ?
            ORDER BY o.created_at DESC, oi.id DESC LIMIT 200
        ", [$uidParam]);
    }
}

if ($view === 'seller' && $uidParam > 0 && $hasUsers) {
    $sellerDetail = getRow($db,
        "SELECT uid, username, email, role"
        . ($usersHasAddress ? ", address" : ", '' AS address")
        . ($usersHasBilling ? ", billing_fullname" : ", '' AS billing_fullname")
        . ($usersHasPayout ? ", pay_sortcode, pay_banknumber" : ", '' AS pay_sortcode, '' AS pay_banknumber")
        . " FROM users WHERE uid = ? LIMIT 1",
        [$uidParam]
    );

    if ($hasProducts) {
        $userListings = getList($db,
            "SELECT pid, title, "
            . ($productsHasProductType ? "product_type" : "'' AS product_type") . ", "
            . "price, " . ($productsHasIsAvailable ? "is_available" : "1") . " AS is_available
             FROM products WHERE uid = ? ORDER BY pid DESC LIMIT 200",
            [$uidParam]
        );
    }

    if ($hasOrders && $hasOrderItems) {
        $userOrdersAsSeller = getList($db, "
            SELECT oi.id AS order_item_id, o.order_id AS order_public_id, o.created_at,
                oi.title, oi.pid, oi.rental_days, oi.platform_fee, oi.line_total, ub.username AS buyer_name
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id_fk
            LEFT JOIN users ub ON ub.uid = o.buyer_uid
            WHERE oi.seller_uid = ?
            ORDER BY o.created_at DESC, oi.id DESC LIMIT 200
        ", [$uidParam]);
    }
}

if ($view === 'item' && $pidParam > 0 && $hasProducts) {
    error_log("Fetching item details for PID: {$pidParam}");

    $itemDetail = getRow($db,
        "SELECT p.pid, p.title, "
        . ($productsHasImage ? "p.image" : "'' AS image") . ", "
        . ($productsHasProductType ? "p.product_type" : "'' AS product_type") . ", "
        . "p.price, "
        . ($productsHasDescription ? "p.description" : "'' AS description") . ",
            p.uid AS seller_uid, u.username AS seller_name, u.email AS seller_email,
            " . ($productsHasIsAvailable ? "p.is_available" : "1") . " AS is_available
         FROM products p
         LEFT JOIN users u ON u.uid = p.uid
         WHERE p.pid = ? LIMIT 1",
        [$pidParam]
    );

    error_log("Item detail fetched: " . ($itemDetail ? "YES (PID: {$itemDetail['pid']})" : "NO"));

    if ($itemDetail && $hasOrders && $hasOrderItems) {
        $itemOrderHistory = getList($db, "
            SELECT oi.id AS order_item_id, o.order_id AS order_public_id, o.created_at,
                ub.username AS buyer_name, us.username AS seller_name,
                oi.rental_days, oi.per_day_price, oi.platform_fee, oi.line_total
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id_fk
            LEFT JOIN users ub ON ub.uid = o.buyer_uid
            LEFT JOIN users us ON us.uid = oi.seller_uid
            WHERE oi.pid = ?
            ORDER BY o.created_at DESC, oi.id DESC LIMIT 200
        ", [$pidParam]);
    }
}

if ($view === 'order' && $oiParam > 0 && $hasOrders && $hasOrderItems) {
    $orderItemDetail = getRow($db, "
        SELECT oi.id AS order_item_id, o.order_id AS order_public_id, o.created_at,
            o.status AS order_status, o.buyer_uid, ub.username AS buyer_name, ub.email AS buyer_email,
            oi.seller_uid, us.username AS seller_name, us.email AS seller_email,
            oi.pid, oi.title AS item_title, oi.product_type, oi.image,
            oi.per_day_price, oi.rental_days, oi.platform_fee, oi.line_total
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id_fk
        LEFT JOIN users ub ON ub.uid = o.buyer_uid
        LEFT JOIN users us ON us.uid = oi.seller_uid
        WHERE oi.id = ? LIMIT 1
    ", [$oiParam]);

    if ($orderItemDetail && $hasShipments) {
        $orderShipmentDetail = getRow($db, "
            SELECT shipped_at, courier, tracking_number, buyer_marked_returned_at,
                buyer_return_courier, buyer_return_tracking, seller_marked_received_at
            FROM order_shipments WHERE order_item_id = ? LIMIT 1
        ", [$oiParam]);
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

    <!-- Theme toggle styles -->
    <style>
        #themeToggle {
            background: transparent;
            border: 1px solid #00FF00;
            color: #ffffff;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }
        html.light-mode #themeToggle {
            color: #333333;
            border-color: #00FF00;
            background: transparent;
        }
        #themeToggle:hover {
            background: transparent;
            border-color: #d2ff4c;
            transform: scale(1.1);
        }
    </style>
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
            <li><a href="FAQTestimonials.php">FAQ</a></li>

            <!-- Theme Toggle Button (no cart icon on admin page) -->
            <li>
                <button id="themeToggle" onclick="toggleTheme()">🌙</button>
            </li>

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
            ['#orders', 'Orders'],
            ['report.php', 'Reports'],
            ['#support', 'Support']
        ];
        for ($i = 0; $i < count($menuItems); $i++):
        ?>
            <a href="<?= h($menuItems[$i][0]) ?>" class="side-link"><?= h($menuItems[$i][1]) ?></a>
        <?php endfor; ?>
        <?php if ($view !== ''): ?>
            <a href="admin_dashboard.php" class="side-link">Clear details</a>
        <?php endif; ?>
    </aside>

    <section class="main-content">

        <div id="overview" class="section-block">
            <h2>Admin Dashboard</h2>
            <div class="overview-grid">
                <?php
                $overviewCards = [
                    ['Total Users', $totalUsers],
                    ['Total Sellers', $totalSellers],
                    ['Total Items', $totalItems],
                    ['Total Orders', $totalOrders],
                    ['Total Order Items', $totalOrderItems],
                    ['Support Inbox', $supportOpenCount . ' Open']
                ];
                for ($i = 0; $i < count($overviewCards); $i++) {
                    echo renderOverviewCard($overviewCards[$i][0], $overviewCards[$i][1]);
                }
                ?>
            </div>
            <div style="margin-top:12px;">
                <a href="report.php" class="btn primary">Open Reports</a>
            </div>
        </div>

        <?php if ($view === 'order' && $orderItemDetail): ?>
            <div class="section-block">
                <h2>Order Item Details</h2>
                <div class="summaryBox">
                    <?= renderSummaryLine('Order', $orderItemDetail['order_public_id']) ?>
                    <?= renderSummaryLine('Order item ID', $orderItemDetail['order_item_id']) ?>
                    <?= renderSummaryLine('Created', $orderItemDetail['created_at']) ?>
                    <?= renderSummaryLine('Status', $orderItemDetail['order_status']) ?>
                    <?= renderSummaryLine('Item', $orderItemDetail['item_title'] . ' (PID: ' . (int)$orderItemDetail['pid'] . ')') ?>
                    <?= renderSummaryLine('Seller', ($orderItemDetail['seller_name'] ?? '') . ' (UID: ' . (int)$orderItemDetail['seller_uid'] . ')') ?>
                    <?= renderSummaryLine('Buyer', ($orderItemDetail['buyer_name'] ?? '') . ' (UID: ' . (int)$orderItemDetail['buyer_uid'] . ')') ?>
                    <?= renderSummaryLine('Per day', '£' . number_format((float)$orderItemDetail['per_day_price'], 2)) ?>
                    <?= renderSummaryLine('Rental days', (int)$orderItemDetail['rental_days']) ?>
                    <?= renderSummaryLine('Platform fee', '£' . number_format((float)$orderItemDetail['platform_fee'], 2)) ?>
                    <?= renderSummaryLine('Total', '£' . number_format((float)$orderItemDetail['line_total'], 2)) ?>
                </div>

                <?php if ($orderShipmentDetail): ?>
                    <h3 style="margin-top:18px;">Shipping and Returns</h3>
                    <div class="summaryBox">
                        <?= renderSummaryLine('Shipped at', $orderShipmentDetail['shipped_at'] ?? '') ?>
                        <?= renderSummaryLine('Courier', $orderShipmentDetail['courier'] ?? '') ?>
                        <?= renderSummaryLine('Tracking', $orderShipmentDetail['tracking_number'] ?? '') ?>
                        <?= renderSummaryLine('Buyer marked returned at', $orderShipmentDetail['buyer_marked_returned_at'] ?? '') ?>
                        <?= renderSummaryLine('Return courier', $orderShipmentDetail['buyer_return_courier'] ?? '') ?>
                        <?= renderSummaryLine('Return tracking', $orderShipmentDetail['buyer_return_tracking'] ?? '') ?>
                        <?= renderSummaryLine('Seller marked received at', $orderShipmentDetail['seller_marked_received_at'] ?? '') ?>
                    </div>
                <?php endif; ?>

                <div style="margin-top:15px; display:flex; gap:12px; flex-wrap:wrap;">
                    <a href="admin_dashboard.php?view=item&pid=<?= (int)$orderItemDetail['pid'] ?>" class="btn primary">View Item</a>
                    <a href="admin_dashboard.php?view=user&uid=<?= (int)$orderItemDetail['buyer_uid'] ?>" class="btn primary">View Buyer</a>
                    <a href="admin_dashboard.php?view=seller&uid=<?= (int)$orderItemDetail['seller_uid'] ?>" class="btn primary">View Seller</a>
                </div>
            </div>
        <?php elseif ($view === 'order' && $oiParam > 0): ?>
            <div class="section-block">
                <h2>Order Item Details</h2>
                <p>Order item not found.</p>
            </div>
        <?php endif; ?>

        <?php if ($view === 'item' && $itemDetail): ?>
            <div class="section-block">
                <h2>Item Details</h2>
                <div class="summaryBox">
                    <?= renderSummaryLine('PID', $itemDetail['pid']) ?>
                    <?= renderSummaryLine('Title', $itemDetail['title']) ?>
                    <?php if (!empty($itemDetail['product_type'])): ?>
                        <?= renderSummaryLine('Type', $itemDetail['product_type']) ?>
                    <?php endif; ?>
                    <?= renderSummaryLine('Price per day', '£' . number_format((float)$itemDetail['price'], 2)) ?>
                    <?php if (!empty($itemDetail['description'])): ?>
                        <?= renderSummaryLine('Description', $itemDetail['description']) ?>
                    <?php endif; ?>
                    <?= renderSummaryLine('Available', ((int)$itemDetail['is_available'] === 1) ? 'Yes' : 'No') ?>
                    <?= renderSummaryLine('Seller', ($itemDetail['seller_name'] ?? 'Unknown') . ' (UID: ' . (int)$itemDetail['seller_uid'] . ')') ?>
                    <?= renderSummaryLine('Seller email', $itemDetail['seller_email'] ?? '') ?>
                </div>

                <?php if (!empty($itemDetail['image'])): ?>
                    <h3 style="margin-top:18px;">Image</h3>
                    <?php
                    $imagePath = $itemDetail['image'];
                    if (!preg_match('/^(uploads\/|images\/|products\/|assets\/|https?:\/\/)/i', $imagePath)) {
                        $imagePath = 'images/' . $imagePath;
                    }
                    ?>
                    <img src="<?= h($imagePath) ?>" alt="<?= h($itemDetail['title']) ?>" style="max-width: 400px; height: auto; border-radius: 8px; display: block; margin-top: 10px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <p style="display:none; color: #d9534f; background: #f8d7da; padding: 10px; border-radius: 4px; margin-top: 10px;">Image not found at path: <strong><?= h($imagePath) ?></strong><br>Original filename: <?= h($itemDetail['image']) ?></p>
                <?php endif; ?>

                <h3 style="margin-top:18px;">Order History</h3>
                <table class="main-table">
                    <thead><?= renderTableRow(['Order', 'Order item', 'Buyer', 'Seller', 'Days', 'Per day', 'Fee', 'Total', 'Date', 'Actions'], true) ?></thead>
                    <tbody>
                        <?php if (empty($itemOrderHistory)): ?>
                            <?= renderEmptyRow(10, 'No orders for this item.') ?>
                        <?php else: for ($i = 0; $i < count($itemOrderHistory); $i++): $oh = $itemOrderHistory[$i]; ?>
                            <?= renderTableRow([
                                h($oh['order_public_id']),
                                (int)$oh['order_item_id'],
                                h($oh['buyer_name'] ?? ''),
                                h($oh['seller_name'] ?? ''),
                                (int)$oh['rental_days'],
                                '£' . number_format((float)$oh['per_day_price'], 2),
                                '£' . number_format((float)$oh['platform_fee'], 2),
                                '£' . number_format((float)$oh['line_total'], 2),
                                h($oh['created_at']),
                                '<a href="admin_dashboard.php?view=order&oi=' . (int)$oh['order_item_id'] . '" class="btn small">View</a>'
                            ]) ?>
                        <?php endfor; endif; ?>
                    </tbody>
                </table>

                <div style="margin-top:15px;">
                    <a href="admin_dashboard.php?view=seller&uid=<?= (int)$itemDetail['seller_uid'] ?>" class="btn primary">View Seller</a>
                </div>
            </div>
        <?php elseif ($view === 'item' && $pidParam > 0): ?>
            <div class="section-block">
                <h2>Item Details</h2>
                <p>Item not found (PID: <?= (int)$pidParam ?>). This could mean:</p>
                <ul>
                    <li>The product doesn't exist in the database</li>
                    <li>There was a database error (check error logs)</li>
                    <li>The products table schema differs from expected</li>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($view === 'user' && $userDetail): ?>
            <div class="section-block">
                <h2>User Details</h2>
                <div class="summaryBox">
                    <?= renderSummaryLine('UID', $userDetail['uid']) ?>
                    <?= renderSummaryLine('Username', $userDetail['username']) ?>
                    <?= renderSummaryLine('Email', $userDetail['email']) ?>
                    <?= renderSummaryLine('Role', $userDetail['role']) ?>
                    <?= renderSummaryLine('Billing name', $userDetail['billing_fullname'] ?? '') ?>
                    <?= renderSummaryLine('Address', $userDetail['address'] ?? '') ?>
                </div>

                <h3 style="margin-top:18px;">Listings</h3>
                <table class="main-table">
                    <thead><?= renderTableRow(['PID', 'Title', 'Type', 'Price', 'Available', 'Actions'], true) ?></thead>
                    <tbody>
                        <?php if (empty($userListings)): ?>
                            <?= renderEmptyRow(6, 'No listings.') ?>
                        <?php else: for ($i = 0; $i < count($userListings); $i++): $p = $userListings[$i]; ?>
                            <?= renderTableRow([
                                (int)$p['pid'],
                                h($p['title']),
                                h($p['product_type']),
                                '£' . number_format((float)$p['price'], 2),
                                ((int)$p['is_available'] === 1) ? 'Yes' : 'No',
                                '<a href="admin_dashboard.php?view=item&pid=' . (int)$p['pid'] . '" class="btn small">View</a>'
                            ]) ?>
                        <?php endfor; endif; ?>
                    </tbody>
                </table>

                <h3 style="margin-top:18px;">Orders as Buyer</h3>
                <table class="main-table">
                    <thead><?= renderTableRow(['Order', 'Order item', 'Item', 'Seller', 'Total', 'Date', 'Actions'], true) ?></thead>
                    <tbody>
                        <?php if (empty($userOrdersAsBuyer)): ?>
                            <?= renderEmptyRow(7, 'No orders.') ?>
                        <?php else: for ($i = 0; $i < count($userOrdersAsBuyer); $i++): $t = $userOrdersAsBuyer[$i]; ?>
                            <?= renderTableRow([
                                h($t['order_public_id']),
                                (int)$t['order_item_id'],
                                h($t['title']) . ' (PID: ' . (int)$t['pid'] . ')',
                                h($t['seller_name'] ?? ''),
                                '£' . number_format((float)$t['line_total'], 2),
                                h($t['created_at']),
                                '<a href="admin_dashboard.php?view=order&oi=' . (int)$t['order_item_id'] . '" class="btn small">View</a>'
                            ]) ?>
                        <?php endfor; endif; ?>
                    </tbody>
                </table>

                <h3 style="margin-top:18px;">Orders as Seller</h3>
                <table class="main-table">
                    <thead><?= renderTableRow(['Order', 'Order item', 'Item', 'Buyer', 'Total', 'Date', 'Actions'], true) ?></thead>
                    <tbody>
                        <?php if (empty($userOrdersAsSeller)): ?>
                            <?= renderEmptyRow(7, 'No orders.') ?>
                        <?php else: for ($i = 0; $i < count($userOrdersAsSeller); $i++): $t = $userOrdersAsSeller[$i]; ?>
                            <?= renderTableRow([
                                h($t['order_public_id']),
                                (int)$t['order_item_id'],
                                h($t['title']) . ' (PID: ' . (int)$t['pid'] . ')',
                                h($t['buyer_name'] ?? ''),
                                '£' . number_format((float)$t['line_total'], 2),
                                h($t['created_at']),
                                '<a href="admin_dashboard.php?view=order&oi=' . (int)$t['order_item_id'] . '" class="btn small">View</a>'
                            ]) ?>
                        <?php endfor; endif; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($view === 'user' && $uidParam > 0): ?>
            <div class="section-block">
                <h2>User Details</h2>
                <p>User not found.</p>
            </div>
        <?php endif; ?>

        <?php if ($view === 'seller' && $sellerDetail): ?>
            <div class="section-block">
                <h2>Seller Details</h2>
                <div class="summaryBox">
                    <?= renderSummaryLine('UID', $sellerDetail['uid']) ?>
                    <?= renderSummaryLine('Username', $sellerDetail['username']) ?>
                    <?= renderSummaryLine('Email', $sellerDetail['email']) ?>
                    <?= renderSummaryLine('Role', $sellerDetail['role']) ?>
                    <?= renderSummaryLine('Billing name', $sellerDetail['billing_fullname'] ?? '') ?>
                    <?= renderSummaryLine('Address', $sellerDetail['address'] ?? '') ?>
                    <?= renderSummaryLine('Sort code', $sellerDetail['pay_sortcode'] ?? '') ?>
                    <?= renderSummaryLine('Account number', $sellerDetail['pay_banknumber'] ?? '') ?>
                </div>

                <h3 style="margin-top:18px;">Listings</h3>
                <table class="main-table">
                    <thead><?= renderTableRow(['PID', 'Title', 'Type', 'Price', 'Available', 'Actions'], true) ?></thead>
                    <tbody>
                        <?php if (empty($userListings)): ?>
                            <?= renderEmptyRow(6, 'No listings.') ?>
                        <?php else: for ($i = 0; $i < count($userListings); $i++): $p = $userListings[$i]; ?>
                            <?= renderTableRow([
                                (int)$p['pid'],
                                h($p['title']),
                                h($p['product_type']),
                                '£' . number_format((float)$p['price'], 2),
                                ((int)$p['is_available'] === 1) ? 'Yes' : 'No',
                                '<a href="admin_dashboard.php?view=item&pid=' . (int)$p['pid'] . '" class="btn small">View</a>'
                            ]) ?>
                        <?php endfor; endif; ?>
                    </tbody>
                </table>

                <h3 style="margin-top:18px;">Orders as Seller</h3>
                <table class="main-table">
                    <thead><?= renderTableRow(['Order', 'Order item', 'Item', 'Buyer', 'Total', 'Date', 'Actions'], true) ?></thead>
                    <tbody>
                        <?php if (empty($userOrdersAsSeller)): ?>
                            <?= renderEmptyRow(7, 'No orders.') ?>
                        <?php else: for ($i = 0; $i < count($userOrdersAsSeller); $i++): $t = $userOrdersAsSeller[$i]; ?>
                            <?= renderTableRow([
                                h($t['order_public_id']),
                                (int)$t['order_item_id'],
                                h($t['title']) . ' (PID: ' . (int)$t['pid'] . ')',
                                h($t['buyer_name'] ?? ''),
                                '£' . number_format((float)$t['line_total'], 2),
                                h($t['created_at']),
                                '<a href="admin_dashboard.php?view=order&oi=' . (int)$t['order_item_id'] . '" class="btn small">View</a>'
                            ]) ?>
                        <?php endfor; endif; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($view === 'seller' && $uidParam > 0): ?>
            <div class="section-block">
                <h2>Seller Details</h2>
                <p>Seller not found.</p>
            </div>
        <?php endif; ?>

        <div id="users" class="section-block">
            <h2>All Users</h2>
            <table class="main-table">
                <thead><?= renderTableRow(['UID', 'Username', 'Email', 'Role', 'Actions'], true) ?></thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <?= renderEmptyRow(5, 'No users found.') ?>
                    <?php else: for ($i = 0; $i < count($users); $i++): $u = $users[$i]; ?>
                        <?= renderTableRow([
                            (int)$u['uid'],
                            h($u['username']),
                            h($u['email']),
                            h($u['role']),
                            '<a href="admin_dashboard.php?view=user&uid=' . (int)$u['uid'] . '" class="btn small">View</a>'
                        ]) ?>
                    <?php endfor; endif; ?>
                </tbody>
            </table>
        </div>

        <div id="sellers" class="section-block">
            <h2>All Sellers</h2>
            <table class="main-table">
                <thead><?= renderTableRow(['UID', 'Username', 'Email', 'Role', 'Actions'], true) ?></thead>
                <tbody>
                    <?php if (empty($sellers)): ?>
                        <?= renderEmptyRow(5, 'No sellers found.') ?>
                    <?php else: for ($i = 0; $i < count($sellers); $i++): $s = $sellers[$i]; ?>
                        <?= renderTableRow([
                            (int)$s['uid'],
                            h($s['username']),
                            h($s['email']),
                            h($s['role']),
                            '<a href="admin_dashboard.php?view=seller&uid=' . (int)$s['uid'] . '" class="btn small">View</a>'
                        ]) ?>
                    <?php endfor; endif; ?>
                </tbody>
            </table>
        </div>

        <div id="items" class="section-block">
            <h2>All Items</h2>
            <table class="main-table">
                <thead><?= renderTableRow(['PID', 'Title', 'Type', 'Price', 'Seller', 'Available', 'Actions'], true) ?></thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <?= renderEmptyRow(7, 'No items found.') ?>
                    <?php else: for ($i = 0; $i < count($items); $i++): $it = $items[$i]; ?>
                        <?= renderTableRow([
                            (int)$it['pid'],
                            h($it['title']),
                            h($it['product_type']),
                            '£' . number_format((float)$it['price'], 2),
                            h($it['seller_name'] ?? '') . ' (UID: ' . (int)$it['seller_uid'] . ')',
                            ((int)$it['is_available'] === 1) ? 'Yes' : 'No',
                            '<a href="admin_dashboard.php?view=item&pid=' . (int)$it['pid'] . '" class="btn small">View</a>'
                        ]) ?>
                    <?php endfor; endif; ?>
                </tbody>
            </table>
        </div>

        <div id="orders" class="section-block">
            <h2>Recent Orders</h2>
            <p style="margin-bottom: 15px; color: #666;">One row per order item</p>

            <?php if (!$hasOrders || !$hasOrderItems): ?>
                <p>Orders tables not found.</p>
            <?php else: ?>
                <table class="main-table">
                    <thead><?= renderTableRow(['Order', 'Order item', 'Item', 'Buyer', 'Seller', 'Total', 'Date', 'Actions'], true) ?></thead>
                    <tbody>
                        <?php if (empty($recentOrders)): ?>
                            <?= renderEmptyRow(8, 'No orders found.') ?>
                        <?php else: for ($i = 0; $i < count($recentOrders); $i++): $o = $recentOrders[$i]; ?>
                            <?= renderTableRow([
                                h($o['order_public_id']),
                                (int)$o['order_item_id'],
                                h($o['item_title']) . ' (PID: ' . (int)$o['pid'] . ')',
                                h($o['buyer_name'] ?? '') . ' (' . (int)$o['buyer_uid'] . ')',
                                h($o['seller_name'] ?? '') . ' (' . (int)$o['seller_uid'] . ')',
                                '£' . number_format((float)$o['line_total'], 2),
                                h($o['created_at']),
                                '<a href="admin_dashboard.php?view=order&oi=' . (int)$o['order_item_id'] . '" class="btn small">View</a>'
                            ]) ?>
                        <?php endfor; endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div id="support" class="section-block">
            <h2>Support Conversations</h2>
            <div style="margin:10px 0;">
                <a class="btn primary" href="admin_support.php">Open Support Inbox</a>
            </div>

            <?php if (!$hasAdminConversations): ?>
                <p>Support tables not found.</p>
            <?php elseif (empty($supportConvs)): ?>
                <p>No support conversations.</p>
            <?php else: ?>
                <table class="main-table">
                    <thead><?= renderTableRow(['ID', 'User', 'Status', 'Created', 'Actions'], true) ?></thead>
                    <tbody>
                        <?php for ($i = 0; $i < count($supportConvs); $i++): $c = $supportConvs[$i]; ?>
                            <?= renderTableRow([
                                (int)$c['id'],
                                h($c['username']) . ' (UID: ' . (int)$c['user_uid'] . ')',
                                h($c['status']),
                                h($c['created_at']),
                                '<a href="admin_support.php?conv_id=' . (int)$c['id'] . '" class="btn small">View</a>'
                            ]) ?>
                        <?php endfor; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </section>
</div>

<!-- Theme toggle script -->
<script>
    function toggleTheme() {
        const body = document.body;
        const themeToggle = document.getElementById('themeToggle');
        if (body.classList.contains('light-mode')) {
            body.classList.remove('light-mode');
            themeToggle.textContent = '🌙';
            localStorage.setItem('theme', 'dark');
        } else {
            body.classList.add('light-mode');
            themeToggle.textContent = '☀️';
            localStorage.setItem('theme', 'light');
        }
    }
    document.addEventListener('DOMContentLoaded', function () {
        const savedTheme = localStorage.getItem('theme');
        const themeToggle = document.getElementById('themeToggle');
        if (savedTheme === 'light') {
            document.body.classList.add('light-mode');
            themeToggle.textContent = '☀️';
        } else {
            themeToggle.textContent = '🌙';
        }
    });
</script>

</body>
</html>
