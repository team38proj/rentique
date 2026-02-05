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
$role = $roleRow['role'] ?? 'customer';

$isAdmin = ($role === 'admin');

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

if ($isAdmin) {
    $stmt = $db->prepare("
        SELECT ac.id, ac.user_uid, ac.status, ac.created_at, u.username
        FROM admin_conversations ac
        JOIN users u ON u.uid = ac.user_uid
        ORDER BY ac.status ASC, ac.created_at DESC
    ");
    $stmt->execute();
    $convs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $activeId = intval($_GET['conv_id'] ?? 0);
    if ($activeId <= 0 && $convs) $activeId = (int)$convs[0]['id'];

} else {
    $stmt = $db->prepare("SELECT id FROM admin_conversations WHERE user_uid = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$uid]);
    $c = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$c) {
        $stmt = $db->prepare("INSERT INTO admin_conversations (user_uid, status, created_at) VALUES (?, 'open', NOW())");
        $stmt->execute([$uid]);
        $activeId = (int)$db->lastInsertId();
    } else {
        $activeId = (int)$c['id'];
    }
    $convs = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['body'])) {
    $body = trim($_POST['body']);
    if ($body !== '') {
        $senderRole = $isAdmin ? 'admin' : 'user';
        $stmt = $db->prepare("
            INSERT INTO admin_messages (admin_conversation_id, sender_role, sender_uid, body, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$activeId, $senderRole, $uid, $body]);
    }

    $redir = "admin_support.php";
    if ($isAdmin) $redir .= "?conv_id=" . intval($activeId);
    header("Location: " . $redir);
    exit;
}

$stmt = $db->prepare("
    SELECT
        am.sender_role,
        am.sender_uid,
        u.username AS sender_username,
        am.body,
        am.created_at
    FROM admin_messages am
    LEFT JOIN users u ON u.uid = am.sender_uid
    WHERE am.admin_conversation_id = ?
    ORDER BY am.created_at ASC, am.id ASC
");
$stmt->execute([$activeId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Support</title>
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
            <?php if ($isAdmin): ?>
                <li><a href="admin_dashboard.php">Admin</a></li>
            <?php else: ?>
                <li><a href="user_dashboard.php">Account</a></li>
            <?php endif; ?>
            <button id="themeToggle">Theme</button>
        </ul>
    </nav>
</header>

<main style="max-width:1100px;margin:20px auto; display:flex; gap:14px;">

    <?php if ($isAdmin): ?>
        <div style="width:320px; border:1px solid #ddd; border-radius:10px; padding:12px; height:520px; overflow:auto;">
            <h3>Inbox</h3>
            <?php foreach ($convs as $cv): ?>
                <div style="margin:8px 0;">
                    <a href="admin_support.php?conv_id=<?= (int)$cv['id'] ?>">
                        Conversation <?= (int)$cv['id'] ?>, <?= h($cv['username']) ?>, <?= h($cv['status']) ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div style="flex:1;">
        <h2>Support chat</h2>

        <div style="border:1px solid #ddd; padding:14px; border-radius:10px; min-height:360px; height:420px; overflow:auto;">
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
            <textarea name="body" class="inputbox" style="width:100%;height:90px;" placeholder="Type your message"></textarea>
            <button type="submit" class="button" style="margin-top:10px;">Send</button>
        </form>
    </div>
</main>

</body>
</html>
