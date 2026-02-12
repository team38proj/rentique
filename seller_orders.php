<?php
session_start();
require_once 'connectdb.php';

$uid = $_SESSION['uid'] ?? null;
if (!$uid) {
    header("Location: login.php");
    exit;
}

$stmt = $db->prepare("SELECT role FROM users WHERE uid = ? LIMIT 1");
$stmt->execute([$uid]);
$roleRow = $stmt->fetch(PDO::FETCH_ASSOC);
$role = $roleRow['role'] ?? '';

if ($role !== 'customer') {
    die("Access denied.");
}

function add_system_message(PDO $db, int $orderIdFk, int $buyerUid, int $sellerUid, string $msg) {
    $stmt = $db->prepare("
        SELECT id FROM conversations
        WHERE order_id_fk = ? AND buyer_uid = ? AND seller_uid = ?
        LIMIT 1
    ");
    $stmt->execute([$orderIdFk, $buyerUid, $sellerUid]);
    $c = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$c) return;

    $conversationId = (int)$c['id'];

    $stmt = $db->prepare("
        INSERT INTO messages (conversation_id, sender_role, sender_uid, body, created_at)
        VALUES (?, 'system', NULL, ?, NOW())
    ");
    $stmt->execute([$conversationId, $msg]);
}

/* Actions */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';
    $orderItemId = intval($_POST['order_item_id'] ?? 0);

    $stmt = $db->prepare("
        SELECT oi.id, oi.order_id_fk, o.order_id AS order_public_id, o.buyer_uid, oi.seller_uid, oi.pid
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id_fk
        WHERE oi.id = ? AND oi.seller_uid = ?
        LIMIT 1
    ");
    $stmt->execute([$orderItemId, $uid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        die("Invalid item.");
    }

    $orderIdFk = (int)$row['order_id_fk'];
    $buyerUid = (int)$row['buyer_uid'];
    $sellerUid = (int)$row['seller_uid'];
    $pid = (int)$row['pid'];
    $orderPublicId = $row['order_public_id'];

    if ($action === 'mark_shipped') {
        $courier = trim($_POST['courier'] ?? '');
        $tracking = trim($_POST['tracking_number'] ?? '');

        $stmt = $db->prepare("
            UPDATE order_shipments
            SET shipped_at = NOW(), courier = ?, tracking_number = ?
            WHERE order_item_id = ?
        ");
        $stmt->execute([$courier, $tracking, $orderItemId]);

        add_system_message($db, $orderIdFk, $buyerUid, $sellerUid,
            "Seller marked shipped. Courier: " . ($courier ?: "N/A") . ". Tracking: " . ($tracking ?: "N/A") . "."
        );

        header("Location: seller_orders.php");
        exit;
    }

    if ($action === 'mark_received') {
        $stmt = $db->prepare("
            UPDATE order_shipments
            SET seller_marked_received_at = NOW()
            WHERE order_item_id = ?
        ");
        $stmt->execute([$orderItemId]);

        $stmt = $db->prepare("UPDATE products SET is_available = 1 WHERE pid = ? AND uid = ?");
        $stmt->execute([$pid, $uid]);

        add_system_message($db, $orderIdFk, $buyerUid, $sellerUid,
            "Seller confirmed item received back and relisted."
        );

         add_system_message($db, $orderIdFk, $buyerUid, $sellerUid,
        "Your return has been confirmed. Please leave a star rating for this product in your dashboard."
    );

        header("Location: seller_orders.php");
        exit;
    }
}

/* List seller items */
$stmt = $db->prepare("
    SELECT
        oi.id AS order_item_id,
        o.order_id AS order_public_id,
        o.ship_by,
        o.buyer_uid,
        oi.title,
        oi.image,
        oi.rental_days,
        s.shipped_at,
        s.courier,
        s.tracking_number,
        s.buyer_marked_returned_at,
        s.buyer_return_courier,
        s.buyer_return_tracking,
        s.seller_marked_received_at
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id_fk
    JOIN order_shipments s ON s.order_item_id = oi.id
    WHERE oi.seller_uid = ?
    ORDER BY o.created_at DESC, oi.id DESC
");
$stmt->execute([$uid]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Seller Orders</title>
    <link rel="stylesheet" href="css/rentique.css">
    <script src="js/theme.js" defer></script>
</head>
<body>

<header>
    <nav class="navbar">
        <div class="logo">
            <a href="index.php">
                <img src="images/rentique_logo.png" alt="Rentique Logo">
            </a>
            <span>rentique.</span>
        </div>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="seller_dashboard.php">Dashboard</a></li>
            <button id="themeToggle">Theme</button>
        </ul>
    </nav>
</header>

<main style="max-width:1100px;margin:20px auto;">
    <h2>Orders to fulfil</h2>

    <?php if (!$rows): ?>
        <p>No orders yet.</p>
    <?php else: ?>
        <?php foreach ($rows as $r): ?>
            <div style="border:1px solid #ddd; padding:14px; border-radius:10px; margin:12px 0;">
                <div style="display:flex; gap:14px; align-items:center;">
                    <img src="images/<?= htmlspecialchars($r['image']) ?>" style="width:90px;height:90px;object-fit:cover;border-radius:10px;">
                    <div style="flex:1;">
                        <div>Order <?= htmlspecialchars($r['order_public_id']) ?></div>
                        <div><?= htmlspecialchars($r['title']) ?></div>
                        <div>Rental days <?= intval($r['rental_days']) ?></div>
                        <div>Ship by <?= htmlspecialchars($r['ship_by']) ?></div>
                        <div>
                            Chat
                            <a href="chat.php?order_id=<?= urlencode($r['order_public_id']) ?>&seller_uid=<?= intval($uid) ?>">
                                open
                            </a>
                        </div>
                    </div>
                </div>

                <div style="margin-top:12px;">

                    <?php if (!$r['shipped_at']): ?>
                        <form method="POST" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                            <input type="hidden" name="action" value="mark_shipped">
                            <input type="hidden" name="order_item_id" value="<?= intval($r['order_item_id']) ?>">
                            <input class="inputbox" name="courier" placeholder="Courier" style="max-width:220px;">
                            <input class="inputbox" name="tracking_number" placeholder="Tracking number" style="max-width:260px;">
                            <button class="button" type="submit">Mark shipped</button>
                        </form>
                    <?php else: ?>
                        <div>Shipped at <?= htmlspecialchars($r['shipped_at']) ?>, <?= htmlspecialchars($r['courier'] ?? '') ?>, <?= htmlspecialchars($r['tracking_number'] ?? '') ?></div>
                    <?php endif; ?>

                    <?php if ($r['buyer_marked_returned_at']): ?>
                        <div style="margin-top:8px;">Buyer marked returned at <?= htmlspecialchars($r['buyer_marked_returned_at']) ?>, <?= htmlspecialchars($r['buyer_return_courier'] ?? '') ?>, <?= htmlspecialchars($r['buyer_return_tracking'] ?? '') ?></div>
                    <?php endif; ?>

                    <?php if ($r['buyer_marked_returned_at'] && !$r['seller_marked_received_at']): ?>
                        <form method="POST" style="margin-top:10px;">
                            <input type="hidden" name="action" value="mark_received">
                            <input type="hidden" name="order_item_id" value="<?= intval($r['order_item_id']) ?>">
                            <button class="button" type="submit">Confirm received and relist</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($r['seller_marked_received_at']): ?>
                        <div style="margin-top:8px;">Seller confirmed received at <?= htmlspecialchars($r['seller_marked_received_at']) ?></div>
                    <?php endif; ?>

                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

</body>
</html>

