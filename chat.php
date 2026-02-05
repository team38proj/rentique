<?php
session_start();
require_once 'connectdb.php';

$uid = $_SESSION['uid'] ?? null;
if (!$uid) {
    header("Location: login.php");
    exit;
}

$orderPublicId = trim($_GET['order_id'] ?? '');
$sellerUidParam = intval($_GET['seller_uid'] ?? 0);

if ($orderPublicId === '' || $sellerUidParam <= 0) {
    die("Missing order_id or seller_uid.");
}

$stmt = $db->prepare("SELECT id, buyer_uid FROM orders WHERE order_id = ? LIMIT 1");
$stmt->execute([$orderPublicId]);
$orderRow = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$orderRow) die("Order not found.");

$orderIdFk = (int)$orderRow['id'];
$buyerUid = (int)$orderRow['buyer_uid'];

$stmt = $db->prepare("SELECT role FROM users WHERE uid = ? LIMIT 1");
$stmt->execute([$uid]);
$roleRow = $stmt->fetch(PDO::FETCH_ASSOC);
$role = $roleRow['role'] ?? 'customer';

$isBuyer = ($uid == $buyerUid);
$isSeller = ($uid == $sellerUidParam);
$isAdmin = ($role === 'admin');

if (!$isBuyer && !$isSeller && !$isAdmin) {
    die("Access denied.");
}

$stmt = $db->prepare("
    SELECT id FROM conversations
    WHERE order_id_fk = ? AND buyer_uid = ? AND seller_uid = ?
    LIMIT 1
");
$stmt->execute([$orderIdFk, $buyerUid, $sellerUidParam]);
$conv = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$conv) die("Conversation not found.");

$conversationId = (int)$conv['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_body'])) {
    $body = trim($_POST['message_body']);

    if ($body !== '') {
        $senderRole = $isAdmin ? 'admin' : ($isSeller ? 'seller' : 'buyer');

        $stmt = $db->prepare("
            INSERT INTO messages (conversation_id, sender_role, sender_uid, body, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$conversationId, $senderRole, $uid, $body]);
    }

    header("Location: chat.php?order_id=" . urlencode($orderPublicId) . "&seller_uid=" . intval($sellerUidParam));
    exit;
}

$stmt = $db->prepare("
    SELECT
        m.sender_role,
        m.sender_uid,
        u.username AS sender_username,
        m.body,
        m.created_at
    FROM messages m
    LEFT JOIN users u ON u.uid = m.sender_uid
    WHERE m.conversation_id = ?
    ORDER BY m.created_at ASC, m.id ASC
");
$stmt->execute([$conversationId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function display_sender_name($row) {
    if (($row['sender_role'] ?? '') === 'system' || empty($row['sender_uid'])) {
        return 'System';
    }
    $name = trim((string)($row['sender_username'] ?? ''));
    if ($name !== '') return $name;
    return 'User';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Chat</title>
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
            <li><a href="productsPage.php">Shop</a></li>
            <button id="themeToggle">Theme</button>
        </ul>
    </nav>
</header>

<main style="max-width:900px;margin:20px auto;">
    <h2 style="text-align:center;">Chat for Order <?= h($orderPublicId) ?></h2>

    <div style="border:1px solid #ddd; padding:14px; border-radius:10px; min-height:360px;">
        <?php foreach ($messages as $m): ?>
            <div style="margin:10px 0;">
                <div style="font-size:12px; opacity:0.8;">
                    <?= h(display_sender_name($m)) ?>, <?= h($m['created_at']) ?>
                </div>
                <div style="padding:10px; border-radius:10px; background:#f4f4f4;">
                    <?= nl2br(h($m['body'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <form method="POST" style="margin-top:14px;">
        <textarea name="message_body" class="inputbox" style="width:100%;height:90px;" placeholder="Type your message"></textarea>
        <button type="submit" class="button" style="margin-top:10px;">Send</button>
    </form>
</main>

</body>
</html>
