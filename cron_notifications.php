<?php
require_once 'connectdb.php';

/*
Run every hour.

*/

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

/* 2 days before rental end */
$stmt = $db->prepare("
    SELECT o.id, o.order_id, o.buyer_uid, oi.seller_uid, o.rental_end
    FROM orders o
    JOIN order_items oi ON oi.order_id_fk = o.id
    WHERE o.rental_end IS NOT NULL
");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$now = new DateTime('now');
foreach ($rows as $r) {
    $rentalEnd = new DateTime($r['rental_end'] . ' 00:00:00');
    $diffDays = (int)$now->diff($rentalEnd)->format('%r%a');

    if ($diffDays === 2) {
        add_system_message($db, (int)$r['id'], (int)$r['buyer_uid'], (int)$r['seller_uid'],
            "Reminder. Rental ends in 2 days for Order " . $r['order_id'] . "."
        );
    }
}

/* Return day at 10:00 */
$stmt = $db->prepare("
    SELECT o.id, o.order_id, o.buyer_uid, oi.seller_uid, o.rental_end
    FROM orders o
    JOIN order_items oi ON oi.order_id_fk = o.id
    WHERE o.rental_end IS NOT NULL
");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
    $returnDay10 = new DateTime($r['rental_end'] . ' 10:00:00');
    $windowStart = (clone $returnDay10)->modify('-30 minutes');
    $windowEnd = (clone $returnDay10)->modify('+30 minutes');

    if ($now >= $windowStart && $now <= $windowEnd) {
        add_system_message($db, (int)$r['id'], (int)$r['buyer_uid'], (int)$r['seller_uid'],
            "Return day. Seller return address available in chat. You have 3 days to return. Order " . $r['order_id'] . "."
        );
    }
}

echo "OK\n";
