<?php
session_start();
require_once 'connectdb.php';

if (!isset($_SESSION['uid'])) {
    header("Location: login.php");
    exit;
}

$user_uid = (int)$_SESSION['uid'];
$view = trim($_GET['view'] ?? '');
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$sale_id = isset($_GET['sale_id']) ? (int)$_GET['sale_id'] : 0;

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
if (!$userData) die("User not found.");

/* Fetch buying orders (where user is the buyer AND NOT the seller) */
$buyingOrders = getList($db, "
    SELECT 
        t.id,
        t.price,
        t.created_at,
        t.order_id as tracking_number,
        p.title,
        p.pid,
        p.image,
        p.uid as product_owner_uid,
        u.username as seller_name,
        u.uid as seller_uid
    FROM transactions t 
    JOIN products p ON p.pid = t.pid 
    LEFT JOIN users u ON u.uid = p.uid
    WHERE t.paying_uid = ? AND p.uid != ?
    ORDER BY t.created_at DESC
", [$user_uid, $user_uid]);

/* Fetch selling orders (where user is the seller AND NOT the buyer) */
$sellingOrders = getList($db, "
    SELECT 
        t.id,
        t.price,
        t.created_at,
        t.order_id as tracking_number,
        p.title,
        p.pid,
        p.image,
        u.username as buyer_name,
        u.uid as buyer_uid
    FROM transactions t 
    JOIN products p ON p.pid = t.pid 
    LEFT JOIN users u ON u.uid = t.paying_uid
    WHERE p.uid = ? AND t.paying_uid != ?
    ORDER BY t.created_at DESC
", [$user_uid, $user_uid]);

/* Fetch specific order details if viewing */
$orderDetail = null;
if ($view === 'order' && $order_id > 0) {
    $orderDetail = getRow($db, "
        SELECT 
            t.id,
            t.price,
            t.created_at as purchase_date,
            t.order_id as tracking_number,
            p.title,
            p.pid,
            p.image,
            p.description,
            p.product_type,
            u.username as seller_name,
            u.email as seller_email,
            u.uid as seller_uid,
            u.address as seller_address
        FROM transactions t 
        JOIN products p ON p.pid = t.pid 
        LEFT JOIN users u ON u.uid = p.uid
        WHERE t.id = ? AND t.paying_uid = ?
        LIMIT 1
    ", [$order_id, $user_uid]);
    
    /* Calculate return date (assuming 7-day rental period) */
    if ($orderDetail) {
        $purchaseDate = new DateTime($orderDetail['purchase_date']);
        $returnDate = clone $purchaseDate;
        $returnDate->modify('+7 days');
        $orderDetail['return_date'] = $returnDate->format('Y-m-d H:i:s');
        
        // Calculate days until return (from now to return date)
        $now = new DateTime();
        $daysRemaining = (int)$now->diff($returnDate)->format('%r%a');
        $orderDetail['days_until_return'] = max(0, $daysRemaining);
    }
}

/* Fetch specific sale details if viewing */
$saleDetail = null;
if ($view === 'sale' && $sale_id > 0) {
    $saleDetail = getRow($db, "
        SELECT 
            t.id,
            t.price,
            t.created_at as sale_date,
            t.order_id as tracking_number,
            t.rental_days,
            t.platform_fee,
            p.title,
            p.pid,
            p.image,
            p.description,
            p.product_type,
            u.username as buyer_name,
            u.email as buyer_email,
            u.uid as buyer_uid,
            u.address as buyer_address
        FROM transactions t 
        JOIN products p ON p.pid = t.pid 
        LEFT JOIN users u ON u.uid = t.paying_uid
        WHERE t.id = ? AND p.uid = ?
        LIMIT 1
    ", [$sale_id, $user_uid]);
    
    /* Calculate rental period */
    if ($saleDetail) {
        $saleDate = new DateTime($saleDetail['sale_date']);
        $returnDate = clone $saleDate;
        $returnDate->modify('+' . (int)$saleDetail['rental_days'] . ' days');
        $saleDetail['expected_return_date'] = $returnDate->format('Y-m-d H:i:s');
        
        // Calculate days until return
        $now = new DateTime();
        $daysRemaining = (int)$now->diff($returnDate)->format('%r%a');
        $saleDetail['days_until_return'] = max(0, $daysRemaining);
        
        // Calculate your earnings (price minus platform fee)
        $saleDetail['your_earnings'] = (float)$saleDetail['price'] - (float)$saleDetail['platform_fee'];
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
    $chatPreviews[$cid] = getRow($db, "SELECT sender_role, sender_uid, body, created_at FROM messages WHERE conversation_id = ? ORDER BY created_at DESC, id DESC LIMIT 1", [$cid]);
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
            <li><a href="BasketPage.php" class="cart-icon">Basket</a></li>
            <button id="themeToggle">Theme</button>
            <li><a href="user_dashboard.php"><?= h($userData['username']) ?></a></li>
            <li><a href="index.php?logout=1" class="btn login">Logout</a></li>
        </ul>
    </nav>
</header>

<div class="dashboard-container">

    <aside class="sidebar">
        <h2>User Menu</h2>
        <?php foreach ($menuItems as [$href, $label]): ?>
            <a href="<?= $href ?>" class="side-link"><?= h($label) ?></a>
        <?php endforeach; ?>
        <?php if ($view === 'order' || $view === 'sale'): ?>
            <a href="user_dashboard.php" class="side-link">Back to Dashboard</a>
        <?php endif; ?>
    </aside>

    <section class="main-content">

        <?php if ($view === 'order' && $orderDetail): ?>
            <!-- Order Detail View -->
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
                            <div class="summaryLine"><span>Order ID</span><span><?= h($orderDetail['id']) ?></span></div>
                            <div class="summaryLine"><span>Tracking Number</span><span><?= h($orderDetail['tracking_number']) ?></span></div>
                            <div class="summaryLine"><span>Price Paid</span><span>£<?= number_format((float)$orderDetail['price'], 2) ?></span></div>
                            <div class="summaryLine"><span>Purchase Date</span><span><?= h($orderDetail['purchase_date']) ?></span></div>
                            <div class="summaryLine"><span>Expected Return Date</span><span><?= h($orderDetail['return_date']) ?></span></div>
                            <div class="summaryLine"><span>Days Until Return</span><span><?= (int)$orderDetail['days_until_return'] ?> days</span></div>
                            <div class="summaryLine"><span>Status</span><span class="green">Active Rental</span></div>
                        </div>
                        
                        <h3 style="margin-top:18px;">Seller Information</h3>
                        <div class="summaryBox">
                            <div class="summaryLine"><span>Seller Name</span><span><?= h($orderDetail['seller_name']) ?></span></div>
                            <div class="summaryLine"><span>Seller Email</span><span><?= h($orderDetail['seller_email']) ?></span></div>
                            <div class="summaryLine"><span>Return Address</span><span><?= h($orderDetail['seller_address']) ?></span></div>
                        </div>
                        
                        <div style="margin-top:15px;">
                            <a href="buyer_returns.php?order_id=<?= (int)$orderDetail['id'] ?>" class="btn primary">Initiate Return</a>
                        </div>
                    </div>
                </div>
                
                <h3>Item Description</h3>
                <p style="padding:15px; background:rgba(255,255,255,0.05); border-radius:8px; margin-top:10px;">
                    <?= h($orderDetail['description']) ?>
                </p>
            </div>
            
        <?php elseif ($view === 'sale' && $saleDetail): ?>
            <!-- Sale Detail View -->
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
                            <div class="summaryLine"><span>Transaction ID</span><span><?= h($saleDetail['id']) ?></span></div>
                            <div class="summaryLine"><span>Tracking Number</span><span><?= h($saleDetail['tracking_number']) ?></span></div>
                            <div class="summaryLine"><span>Rental Price</span><span>£<?= number_format((float)$saleDetail['price'], 2) ?></span></div>
                            <div class="summaryLine"><span>Platform Fee</span><span>£<?= number_format((float)$saleDetail['platform_fee'], 2) ?></span></div>
                            <div class="summaryLine"><span>Your Earnings</span><span class="green">£<?= number_format((float)$saleDetail['your_earnings'], 2) ?></span></div>
                            <div class="summaryLine"><span>Sale Date</span><span><?= h($saleDetail['sale_date']) ?></span></div>
                            <div class="summaryLine"><span>Rental Period</span><span><?= (int)$saleDetail['rental_days'] ?> days</span></div>
                            <div class="summaryLine"><span>Expected Return Date</span><span><?= h($saleDetail['expected_return_date']) ?></span></div>
                            <div class="summaryLine"><span>Days Until Return</span><span><?= (int)$saleDetail['days_until_return'] ?> days</span></div>
                            <div class="summaryLine"><span>Status</span><span class="green">Active Rental</span></div>
                        </div>
                        
                        <h3 style="margin-top:18px;">Buyer Information</h3>
                        <div class="summaryBox">
                            <div class="summaryLine"><span>Buyer Name</span><span><?= h($saleDetail['buyer_name']) ?></span></div>
                            <div class="summaryLine"><span>Buyer Email</span><span><?= h($saleDetail['buyer_email']) ?></span></div>
                            <div class="summaryLine"><span>Delivery Address</span><span><?= h($saleDetail['buyer_address']) ?></span></div>
                        </div>
                        
                        <div style="margin-top:15px;">
                            <a href="seller_orders.php?transaction_id=<?= (int)$saleDetail['id'] ?>" class="btn primary">Manage Shipping</a>
                        </div>
                    </div>
                </div>
                
                <h3>Item Description</h3>
                <p style="padding:15px; background:rgba(255,255,255,0.05); border-radius:8px; margin-top:10px;">
                    <?= h($saleDetail['description']) ?>
                </p>
            </div>
            
        <?php elseif ($view === 'order' && $order_id > 0): ?>
            <div class="section-block">
                <h2>Order Details</h2>
                <p>Order not found or you don't have permission to view it.</p>
            </div>
            
        <?php elseif ($view === 'sale' && $sale_id > 0): ?>
            <div class="section-block">
                <h2>Sale Details</h2>
                <p>Sale not found or you don't have permission to view it.</p>
            </div>
            
        <?php else: ?>
            <!-- Dashboard Overview -->
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
                    foreach ($stats as [$label, $value]):
                    ?>
                        <div class="overview-card">
                            <h3><?= h($label) ?></h3>
                            <p class="green"><?= h($value) ?></p>
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
                            <?php foreach (['Item', 'Seller', 'Tracking Number', 'Order Date', 'Status', 'Actions'] as $header): ?>
                                <th><?= h($header) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($buyingOrders)): ?>
                            <tr><td colspan="6">You haven't purchased any items yet.</td></tr>
                        <?php else: foreach ($buyingOrders as $order): ?>
                            <tr>
                                <td><?= h($order['title']) ?></td>
                                <td><?= h($order['seller_name']) ?></td>
                                <td><?= h($order['tracking_number']) ?></td>
                                <td><?= h($order['created_at']) ?></td>
                                <td><span class="green">Active</span></td>
                                <td>
                                    <a href="user_dashboard.php?view=order&order_id=<?= (int)$order['id'] ?>" class="btn small">View Details</a>
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
                            <?php foreach (['Item', 'Buyer', 'Tracking Number', 'Order Date', 'Amount', 'Status', 'Actions'] as $header): ?>
                                <th><?= h($header) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($sellingOrders)): ?>
                            <tr><td colspan="7">You haven't sold any items yet.</td></tr>
                        <?php else: foreach ($sellingOrders as $order): ?>
                            <tr>
                                <td><?= h($order['title']) ?></td>
                                <td><?= h($order['buyer_name']) ?></td>
                                <td><?= h($order['tracking_number']) ?></td>
                                <td><?= h($order['created_at']) ?></td>
                                <td>£<?= number_format((float)$order['price'], 2) ?></td>
                                <td><span class="green">Active</span></td>
                                <td>
                                    <a href="user_dashboard.php?view=sale&sale_id=<?= (int)$order['id'] ?>" class="btn small">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="messages" class="section-block">
                <h2>Messages</h2>

                <div style="margin:10px 0; display:flex; gap:12px; flex-wrap:wrap;">
                    <?php
                    $buttons = [
                        ['admin_support.php', 'Message admin support'],
                        ['buyer_returns.php', 'Returns']
                    ];
                    foreach ($buttons as [$href, $label]):
                    ?>
                        <a class="btn primary" href="<?= h($href) ?>"><?= h($label) ?></a>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($chats)): ?>
                    <p>No order chats yet.</p>
                <?php else: ?>
                    <table class="main-table">
                        <thead>
                            <tr>
                                <?php foreach (['Order', 'With', 'Last message', 'When', 'Open'] as $header): ?>
                                    <th><?= h($header) ?></th>
                                <?php endforeach; ?>
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
                                    <td><a href="chat.php?order_id=<?= urlencode($orderId) ?>&seller_uid=<?= $sellerUidLink ?>">Open</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div id="settings" class="section-block">
                <h2>Settings</h2>

                <form class="settings-form" method="post" action="update_user.php">
                    <?php
                    $fields = [
                        ['Username', 'text', 'username', $userData['username']],
                        ['Email', 'email', 'email', $userData['email']],
                        ['Address', 'text', 'address', $userData['address']]
                    ];
                    foreach ($fields as [$label, $type, $name, $value]):
                    ?>
                        <label><?= h($label) ?></label>
                        <input type="<?= h($type) ?>" name="<?= h($name) ?>" value="<?= h($value) ?>" required>
                    <?php endforeach; ?>

                    <button class="btn primary" type="submit">Save Changes</button>
                </form>
            </div>
        <?php endif; ?>

    </section>
</div>

</body>
</html>
