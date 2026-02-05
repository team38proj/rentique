<?php
session_start();
require_once 'connectdb.php';

$uid = $_SESSION['uid'] ?? null;
if (!$uid) {
    header("Location: login.php");
    exit;
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

/* Action */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderItemId = intval($_POST['order_item_id'] ?? 0);
    $courier = trim($_POST['return_courier'] ?? '');
    $tracking = trim($_POST['return_tracking'] ?? '');

    $stmt = $db->prepare("
        SELECT oi.id, oi.order_id_fk, o.order_id AS order_public_id, o.buyer_uid, oi.seller_uid
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id_fk
        WHERE oi.id = ? AND o.buyer_uid = ?
        LIMIT 1
    ");
    $stmt->execute([$orderItemId, $uid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) die("Invalid item.");

    $stmt = $db->prepare("
        UPDATE order_shipments
        SET buyer_marked_returned_at = NOW(),
            buyer_return_courier = ?,
            buyer_return_tracking = ?
        WHERE order_item_id = ?
    ");
    $stmt->execute([$courier, $tracking, $orderItemId]);

    add_system_message($db, (int)$row['order_id_fk'], (int)$row['buyer_uid'], (int)$row['seller_uid'],
        "Buyer marked returned. Courier: " . ($courier ?: "N/A") . ". Tracking: " . ($tracking ?: "N/A") . "."
    );

    header("Location: buyer_returns.php");
    exit;
}

/* List buyer order items */
$stmt = $db->prepare("
    SELECT
        oi.id AS order_item_id,
        o.order_id AS order_public_id,
        o.rental_end,
        o.return_deadline,
        oi.title,
        oi.image,
        oi.seller_uid,
        s.buyer_marked_returned_at
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id_fk
    JOIN order_shipments s ON s.order_item_id = oi.id
    WHERE o.buyer_uid = ?
    ORDER BY o.created_at DESC, oi.id DESC
");
$stmt->execute([$uid]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Returns</title>
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
            <li><a href="user_dashboard.php">Account</a></li>
            <button id="themeToggle">Theme</button>
        </ul>
    </nav>
</header>

<main style="max-width:1100px;margin:20px auto;">
    <h2>Your returns</h2>

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
                        <div>Rental end <?= htmlspecialchars($r['rental_end']) ?></div>
                        <div>Return deadline <?= htmlspecialchars($r['return_deadline']) ?></div>
                        <div>
                            Chat
                            <a href="chat.php?order_id=<?= urlencode($r['order_public_id']) ?>&seller_uid=<?= intval($r['seller_uid']) ?>">
                                open
                            </a>
                        </div>
                    </div>
                </div>

                <?php if (!$r['buyer_marked_returned_at']): ?>
                    <form method="POST" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-top:12px;">
                        <input type="hidden" name="order_item_id" value="<?= intval($r['order_item_id']) ?>">
                        <input class="inputbox" name="return_courier" placeholder="Courier" style="max-width:220px;">
                        <input class="inputbox" name="return_tracking" placeholder="Tracking number" style="max-width:260px;">
                        <button class="button" type="submit">Mark returned</button>
                    </form>
                <?php else: ?>
                    <div style="margin-top:10px;">Returned marked at <?= htmlspecialchars($r['buyer_marked_returned_at']) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

</body>
</html>
