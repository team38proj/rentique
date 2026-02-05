<?php
session_start();
require_once 'connectdb.php';

if (!isset($_SESSION['uid'])) {
    header("Location: login.php");
    exit;
}

$user_uid = (int)$_SESSION['uid'];

$view = trim($_GET['view'] ?? '');
$order_item_id = isset($_GET['order_item_id']) ? (int)$_GET['order_item_id'] : 0;
$sale_item_id = isset($_GET['sale_item_id']) ? (int)$_GET['sale_item_id'] : 0;

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* Helper functions */
function getRow($db, $query, $params = []) {
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        error_log("Query error: " . $e->getMessage());
        return null;
    }
}

function getList($db, $query, $params = []) {
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Query error: " . $e->getMessage());
        return [];
    }
}

function truncateText($text, $length = 80) {
    return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
}

/* Fetch user information */
$userData = getRow($db, "SELECT username, email, address FROM users WHERE uid = ?", [$user_uid]);
if (!$userData) {
    die("User not found.");
}

/* Buying orders (buyer view) from orders + order_items */
$buyingOrders = getList($db, "
    SELECT
        oi.id AS order_item_id,
        o.order_id AS order_public_id,
        o.created_at,
        oi.rental_days,
        oi.title,
        oi.pid,
        oi.image,
        oi.seller_uid,
        us.username AS seller_name
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id_fk
    LEFT JOIN users us ON us.uid = oi.seller_uid
    WHERE o.buyer_uid = ?
    ORDER BY o.created_at DESC, oi.id DESC
", [$user_uid]);

/* Selling orders (seller view) from orders + order_items */
$sellingOrders = getList($db, "
    SELECT
        oi.id AS sale_item_id,
        o.order_id AS order_public_id,
        o.created_at,
        oi.rental_days,
        oi.title,
        oi.pid,
        oi.image,
        o.buyer_uid,
        ub.username AS buyer_name
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id_fk
    LEFT JOIN users ub ON ub.uid = o.buyer_uid
    WHERE oi.seller_uid = ?
    ORDER BY o.created_at DESC, oi.id DESC
", [$user_uid]);

/* Order detail (buyer) */
$orderDetail = null;
if ($view === 'order' && $order_item_id > 0) {
    $orderDetail = getRow($db, "
        SELECT
            oi.id AS order_item_id,
            o.order_id AS order_public_id,
            o.created_at AS purchase_date,
            oi.title,
            oi.pid,
            oi.image,
            p.description,
            p.product_type,
            oi.rental_days,
            oi.per_day_price,
            oi.platform_fee,
            oi.line_total,
            us.username AS seller_name,
            us.email AS seller_email,
            us.address AS seller_address
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id_fk
        JOIN products p ON p.pid = oi.pid
        LEFT JOIN users us ON us.uid = oi.seller_uid
        WHERE oi.id = ? AND o.buyer_uid = ?
        LIMIT 1
    ", [$order_item_id, $user_uid]);

    if ($orderDetail) {
        $purchaseDate = new DateTime($orderDetail['purchase_date']);
        $returnDate = clone $purchaseDate;
        $returnDate->modify('+' . (int)$orderDetail['rental_days'] . ' days');
        $orderDetail['return_date'] = $returnDate->format('Y-m-d H:i:s');

        $now = new DateTime();
        $daysRemaining = (int)$now->diff($returnDate)->format('%r%a');
        $orderDetail['days_until_return'] = max(0, $daysRemaining);
    }
}

/* Sale detail (seller) */
$saleDetail = null;
if ($view === 'sale' && $sale_item_id > 0) {
    $saleDetail = getRow($db, "
        SELECT
            oi.id AS sale_item_id,
            o.order_id AS order_public_id,
            o.created_at AS sale_date,
            oi.title,
            oi.pid,
            oi.image,
            p.description,
            p.product_type,
            oi.rental_days,
            oi.per_day_price,
            oi.platform_fee,
            oi.line_total,
            ub.username AS buyer_name,
            ub.email AS buyer_email,
            ub.address AS buyer_address
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id_fk
        JOIN products p ON p.pid = oi.pid
        LEFT JOIN users ub ON ub.uid = o.buyer_uid
        WHERE oi.id = ? AND oi.seller_uid = ?
        LIMIT 1
    ", [$sale_item_id, $user_uid]);

    if ($saleDetail) {
        $saleDate = new DateTime($saleDetail['sale_date']);
        $returnDate = clone $saleDate;
        $returnDate->modify('+' . (int)$saleDetail['rental_days'] . ' days');
        $saleDetail['expected_return_date'] = $returnDate->format('Y-m-d H:i:s');

        $now = new DateTime();
        $daysRemaining = (int)$now->diff($returnDate)->format('%r%a');
        $saleDetail['days_until_return'] = max(0, $daysRemaining);

        $saleDetail['your_earnings'] = (float)$saleDetail['line_total'] - (float)$saleDetail['platform_fee'];
    }
}

/* Load chats */
$chats = getList($db, "
    SELECT
        c.id AS conversation_id,
        c.buyer_uid,
        c.seller_uid,
        o.order_id AS order_public_id,
        o.created_at AS order_created_at,
        ub.username AS buyer_name,
        us.username AS seller_name
    FROM conversations c
    JOIN orders o ON o.id = c.order_id_fk
    JOIN users ub ON ub.uid = c.buyer_uid
    JOIN users us ON us.uid = c.seller_uid
    WHERE c.buyer_uid = ? OR c.seller_uid = ?
    ORDER BY o.created_at DESC, c.id DESC
", [$user_uid, $user_uid]);

/* Get chat previews */
$chatPreviews = [];
foreach ($chats as $c) {
    $cid = (int)$c['conversation_id'];
    $chatPreviews[$cid] = getRow(
        $db,
        "SELECT sender_role, sender_uid, body, created_at FROM messages WHERE conversation_id = ? ORDER BY created_at DESC, id DESC LIMIT 1",
        [$cid]
    );
}

/* Count new messages */
$msgCount = getRow($db, "
    SELECT COUNT(*) AS cnt
    FROM messages m
    JOIN conversations c ON c.id = m.conversation_id
    WHERE (c.buyer_uid = ? OR c.seller_uid = ?)
      AND m.sender_uid IS NOT NULL
      AND m.sender_uid <> ?
", [$user_uid, $user_uid, $user_uid]);
$newMessages = (int)($msgCount['cnt'] ?? 0);

$activeBuyingOrders = count($buyingOrders);
$activeSellingOrders = count($sellingOrders);
$balance = 0.00;

$menuItems = [
    ['#overview', 'Dashboard Overview'],
    ['#buying-orders', 'My Purchases'],
    ['#selling-orders', 'My Sales'],
    ['#messages', 'Messages'],
    ['#settings', 'Settings']
];
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
            <img src="images/rentique_logo.png" alt="Rentique Logo">
            <span>rentique.</span>
        </div>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="productsPage.php">Shop</a></li>
            <li><a href="AboutUs.php">About</a></li>
            <li><a href="Contact.php">Contact</a></li>
            <li><a href="basketPage.php" class="cart-icon">Basket</a></li>
            <button id="themeToggle">Theme</button>
            <li><a href="user_dashboard.php"><?= h($userData['username']) ?></a></li>
            <li><a href="index.php?logout=1" class="btn login">Logout</a></li>
        </ul>
    </nav>
</header>

<div class="dashboard-container">

    <aside class="sidebar">
        <h2>User Menu</h2>
        <?php foreach ($menuItems as $mi): ?>
            <a href="<?= h($mi[0]) ?>" class="side-link"><?= h($mi[1]) ?></a>
        <?php endforeach; ?>
        <?php if ($view === 'order' || $view === 'sale'): ?>
            <a href="user_dashboard.php" class="side-link">Back to Dashboard</a>
        <?php endif; ?>
    </aside>

    <section class="main-content">

        <?php if ($view === 'order' && $orderDetail): ?>
            <div class="section-block">
                <h2>Order Details</h2>

                <div style="display:flex; gap:20px; align-items:flex-start; flex-wrap:wrap; margin-bottom:20px;">
                    <?php if (!empty($orderDetail['image'])): ?>
                        <div style="width:200px;">
                            <img src="images/<?= h($orderDetail['image']) ?>" style="width:200px;height:200px;object-fit:cover;border-radius:12px;border:1px solid #ddd;">
                        </div>
                    <?php endif; ?>

                    <div style="flex:1; min-width:300px;">
                        <div class="summaryBox">
                            <div class="summaryLine"><span>Item</span><span><?= h($orderDetail['title']) ?></span></div>
                            <div class="summaryLine"><span>Category</span><span><?= h($orderDetail['product_type']) ?></span></div>
                            <div class="summaryLine"><span>Order</span><span><?= h($orderDetail['order_public_id']) ?></span></div>
                            <div class="summaryLine"><span>Per day</span><span>£<?= number_format((float)$orderDetail['per_day_price'], 2) ?></span></div>
                            <div class="summaryLine"><span>Rental days</span><span><?= (int)$orderDetail['rental_days'] ?> days</span></div>
                            <div class="summaryLine"><span>Platform fee</span><span>£<?= number_format((float)$orderDetail['platform_fee'], 2) ?></span></div>
                            <div class="summaryLine"><span>Total paid</span><span>£<?= number_format((float)$orderDetail['line_total'], 2) ?></span></div>
                            <div class="summaryLine"><span>Purchase Date</span><span><?= h($orderDetail['purchase_date']) ?></span></div>
                            <div class="summaryLine"><span>Expected Return Date</span><span><?= h($orderDetail['return_date']) ?></span></div>
                            <div class="summaryLine"><span>Days Until Return</span><span><?= (int)$orderDetail['days_until_return'] ?> days</span></div>
                            <div class="summaryLine"><span>Status</span><span class="green">Active Rental</span></div>
                        </div>

                        <h3 style="margin-top:18px;">Seller Information</h3>
                        <div class="summaryBox">
                            <div class="summaryLine"><span>Seller Name</span><span><?= h($orderDetail['seller_name']) ?></span></div>
                            <div class="summaryLine"><span>Seller Email</span><span><?= h($orderDetail['seller_email']) ?></span></div>
                            <div class="summaryLine"><span>Return Address</span><span><?= h($orderDetail['seller_address'] ?? '') ?></span></div>
                        </div>

                        <div style="margin-top:15px;">
                            <a href="buyer_returns.php?order_item_id=<?= (int)$orderDetail['order_item_id'] ?>" class="btn primary">Initiate Return</a>
                        </div>
                    </div>
                </div>

                <h3>Item Description</h3>
                <p style="padding:15px; background:rgba(255,255,255,0.05); border-radius:8px; margin-top:10px;">
                    <?= h($orderDetail['description']) ?>
                </p>
            </div>

        <?php elseif ($view === 'sale' && $saleDetail): ?>
            <div class="section-block">
                <h2>Sale Details</h2>

                <div style="display:flex; gap:20px; align-items:flex-start; flex-wrap:wrap; margin-bottom:20px;">
                    <?php if (!empty($saleDetail['image'])): ?>
                        <div style="width:200px;">
                            <img src="images/<?= h($saleDetail['image']) ?>" style="width:200px;height:200px;object-fit:cover;border-radius:12px;border:1px solid #ddd;">
                        </div>
                    <?php endif; ?>

                    <div style="flex:1; min-width:300px;">
                        <div class="summaryBox">
                            <div class="summaryLine"><span>Item</span><span><?= h($saleDetail['title']) ?></span></div>
                            <div class="summaryLine"><span>Category</span><span><?= h($saleDetail['product_type']) ?></span></div>
                            <div class="summaryLine"><span>Order</span><span><?= h($saleDetail['order_public_id']) ?></span></div>
                            <div class="summaryLine"><span>Per day</span><span>£<?= number_format((float)$saleDetail['per_day_price'], 2) ?></span></div>
                            <div class="summaryLine"><span>Rental days</span><span><?= (int)$saleDetail['rental_days'] ?> days</span></div>
                            <div class="summaryLine"><span>Platform fee</span><span>£<?= number_format((float)$saleDetail['platform_fee'], 2) ?></span></div>
                            <div class="summaryLine"><span>Total charged</span><span>£<?= number_format((float)$saleDetail['line_total'], 2) ?></span></div>
                            <div class="summaryLine"><span>Your Earnings</span><span class="green">£<?= number_format((float)$saleDetail['your_earnings'], 2) ?></span></div>
                            <div class="summaryLine"><span>Sale Date</span><span><?= h($saleDetail['sale_date']) ?></span></div>
                            <div class="summaryLine"><span>Expected Return Date</span><span><?= h($saleDetail['expected_return_date']) ?></span></div>
                            <div class="summaryLine"><span>Days Until Return</span><span><?= (int)$saleDetail['days_until_return'] ?> days</span></div>
                            <div class="summaryLine"><span>Status</span><span class="green">Active Rental</span></div>
                        </div>

                        <h3 style="margin-top:18px;">Buyer Information</h3>
                        <div class="summaryBox">
                            <div class="summaryLine"><span>Buyer Name</span><span><?= h($saleDetail['buyer_name']) ?></span></div>
                            <div class="summaryLine"><span>Buyer Email</span><span><?= h($saleDetail['buyer_email']) ?></span></div>
                            <div class="summaryLine"><span>Delivery Address</span><span><?= h($saleDetail['buyer_address'] ?? '') ?></span></div>
                        </div>

                        <div style="margin-top:15px;">
                            <a href="seller_orders.php" class="btn primary">Manage Shipping</a>
                        </div>
                    </div>
                </div>

                <h3>Item Description</h3>
                <p style="padding:15px; background:rgba(255,255,255,0.05); border-radius:8px; margin-top:10px;">
                    <?= h($saleDetail['description']) ?>
                </p>
            </div>

        <?php elseif ($view === 'order' && $order_item_id > 0): ?>
            <div class="section-block">
                <h2>Order Details</h2>
                <p>Order not found or you don't have permission to view it.</p>
            </div>

        <?php elseif ($view === 'sale' && $sale_item_id > 0): ?>
            <div class="section-block">
                <h2>Sale Details</h2>
                <p>Sale not found or you don't have permission to view it.</p>
            </div>

        <?php else: ?>
            <div id="overview" class="section-block">
                <h2>Welcome Back, <?= h($userData['username']) ?>!</h2>

                <div class="overview-grid">
                    <?php
                    $stats = [
                        ['Active Purchases', $activeBuyingOrders],
                        ['Active Sales', $activeSellingOrders],
                        ['Messages', $newMessages],
                        ['Balance', '£' . number_format($balance, 2)]
                    ];
                    foreach ($stats as $st):
                    ?>
                        <div class="overview-card">
                            <h3><?= h($st[0]) ?></h3>
                            <p class="green"><?= h($st[1]) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="buying-orders" class="section-block">
                <h2>My Purchases</h2>
                <p style="margin-bottom: 15px; color: #666;">Items you have rented from others</p>

                <table class="main-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Seller</th>
                            <th>Order</th>
                            <th>Order Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($buyingOrders)): ?>
                            <tr><td colspan="6">You haven't purchased any items yet.</td></tr>
                        <?php else: foreach ($buyingOrders as $order): ?>
                            <tr>
                                <td><?= h($order['title']) ?></td>
                                <td><?= h($order['seller_name'] ?? '') ?></td>
                                <td><?= h($order['order_public_id']) ?></td>
                                <td><?= h($order['created_at']) ?></td>
                                <td><span class="green">Active</span></td>
                                <td>
                                    <a href="user_dashboard.php?view=order&order_item_id=<?= (int)$order['order_item_id'] ?>" class="btn small">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="selling-orders" class="section-block">
                <h2>My Sales</h2>
                <p style="margin-bottom: 15px; color: #666;">Items you have rented to others</p>

                <table class="main-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Buyer</th>
                            <th>Order</th>
                            <th>Order Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($sellingOrders)): ?>
                            <tr><td colspan="6">You haven't sold any items yet.</td></tr>
                        <?php else: foreach ($sellingOrders as $order): ?>
                            <tr>
                                <td><?= h($order['title']) ?></td>
                                <td><?= h($order['buyer_name'] ?? '') ?></td>
                                <td><?= h($order['order_public_id']) ?></td>
                                <td><?= h($order['created_at']) ?></td>
                                <td><span class="green">Active</span></td>
                                <td>
                                    <a href="user_dashboard.php?view=sale&sale_item_id=<?= (int)$order['sale_item_id'] ?>" class="btn small">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="messages" class="section-block">
                <h2>Messages</h2>

                <div style="margin:10px 0; display:flex; gap:12px; flex-wrap:wrap;">
                    <a class="btn primary" href="admin_support.php">Message admin support</a>
                    <a class="btn primary" href="buyer_returns.php">Returns</a>
                </div>

                <?php if (empty($chats)): ?>
                    <p>No order chats yet.</p>
                <?php else: ?>
                    <table class="main-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>With</th>
                                <th>Last message</th>
                                <th>When</th>
                                <th>Open</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($chats as $c):
                                $cid = (int)$c['conversation_id'];
                                $isBuyer = ((int)$c['buyer_uid'] === $user_uid);
                                $withName = $isBuyer ? ($c['seller_name'] ?? 'Seller') : ($c['buyer_name'] ?? 'Buyer');
                                $orderId = (string)$c['order_public_id'];

                                $p = $chatPreviews[$cid] ?? null;
                                $txt = $p ? truncateText((string)$p['body']) : '';
                                $when = $p ? (string)$p['created_at'] : (string)$c['order_created_at'];
                                $sellerUidLink = (int)$c['seller_uid'];
                            ?>
                                <tr>
                                    <td><?= h($orderId) ?></td>
                                    <td><?= h($withName) ?></td>
                                    <td><?= h($txt) ?></td>
                                    <td><?= h($when) ?></td>
                                    <td><a href="chat.php?order_id=<?= urlencode($orderId) ?>&seller_uid=<?= (int)$sellerUidLink ?>">Open</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div id="settings" class="section-block">
                <h2>Settings</h2>

                <form class="settings-form" method="post" action="update_user.php">
                    <label>Username</label>
                    <input type="text" name="username" value="<?= h($userData['username']) ?>" required>

                    <label>Email</label>
                    <input type="email" name="email" value="<?= h($userData['email']) ?>" required>

                    <label>Address</label>
                    <input type="text" name="address" value="<?= h($userData['address'] ?? '') ?>" required>

                    <button class="btn primary" type="submit">Save Changes</button>
                </form>
            </div>
        <?php endif; ?>

    </section>
</div>

</body>
</html>
