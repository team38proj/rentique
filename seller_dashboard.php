<?php
session_start();
require_once 'connectdb.php';

if (!isset($_SESSION['uid'])) {
    header("Location: login.php");
    exit;
}

$seller_uid = (int)$_SESSION['uid'];

$messages = [
    'seller_info' => "",
    'bank' => "",
    'upload' => "",
    'update' => "",
    'delete' => ""
];

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
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

/* Helper to safely query lists */
function getList($db, $query, $params = []) {
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/* Helper to safely execute update/insert/delete */
function execQuery($db, $query, $params = []) {
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/* UPDATE SELLER INFORMATION */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_seller_info'])) {
    $fields = ['username', 'billing_fullname', 'email', 'address'];
    $values = array_map(fn($f) => trim($_POST[$f] ?? ''), $fields);
    
    if (!in_array('', $values, true)) {
        $success = execQuery($db, "UPDATE users SET username = ?, billing_fullname = ?, email = ?, address = ? WHERE uid = ?", array_merge($values, [$seller_uid]));
        $messages['seller_info'] = $success ? "Information updated successfully." : "Database error updating information.";
    } else {
        $messages['seller_info'] = "All fields are required.";
    }
}

/* UPDATE BANK DETAILS */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_bank'])) {
    $sortcode = trim($_POST['sortcode'] ?? '');
    $banknum = trim($_POST['banknum'] ?? '');
    
    $sortcode = preg_replace('/[^0-9]/', '', $sortcode);
    $banknum = preg_replace('/[^0-9]/', '', $banknum);
    
    if ($sortcode === "" || $banknum === "") {
        $messages['bank'] = "All fields are required.";
    } elseif (strlen($sortcode) !== 6) {
        $messages['bank'] = "Sort code must be exactly 6 digits.";
    } elseif (strlen($banknum) !== 8) {
        $messages['bank'] = "Account number must be exactly 8 digits.";
    } else {
        $success = execQuery($db, "UPDATE users SET pay_sortcode = ?, pay_banknumber = ? WHERE uid = ?", [$sortcode, $banknum, $seller_uid]);
        $messages['bank'] = $success ? "Bank details updated." : "Database error updating bank details.";
    }
}

/* LOAD SELLER DATA - Must be done BEFORE product upload check */
$sellerData = getRow($db, "SELECT username, email, address, billing_fullname, pay_sortcode, pay_banknumber FROM users WHERE uid = ?", [$seller_uid]);
if (!$sellerData) die("Database error loading seller data.");

/* PRODUCT UPLOAD HANDLER */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['upload_item'])) {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    $hasAddress = !empty($sellerData['address']);
    $hasSortcode = !empty($sellerData['pay_sortcode']) && strlen(preg_replace('/[^0-9]/', '', $sellerData['pay_sortcode'])) === 6;
    $hasBanknum = !empty($sellerData['pay_banknumber']) && strlen(preg_replace('/[^0-9]/', '', $sellerData['pay_banknumber'])) === 8;
    
    if (!$hasAddress || !$hasSortcode || !$hasBanknum) {
        $messages['upload'] = "Please complete your return address and bank details before adding listings.";
    } elseif ($title !== "" && $category !== "" && $price > 0 && isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $originalName = basename($_FILES['image']['name']);
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
        $imgName = time() . "_" . $safeName;
        $targetDir = __DIR__ . "/images/";
        $targetPath = $targetDir . $imgName;
        
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $success = execQuery($db, "INSERT INTO products (title, image, product_type, price, uid, description, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())", [$title, $imgName, $category, $price, $seller_uid, $description]);
            $messages['upload'] = $success ? "Item uploaded successfully." : "Database error while saving item.";
        } else {
            $messages['upload'] = "Image upload failed.";
        }
    } else {
        $messages['upload'] = "All fields are required and a valid image must be selected.";
    }
}

/* UPDATE LISTING */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_listing'])) {
    $pid = intval($_POST['pid'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if ($title !== "" && $category !== "" && $price > 0) {
        $success = execQuery($db, "UPDATE products SET title = ?, product_type = ?, price = ?, description = ? WHERE pid = ? AND uid = ?", [$title, $category, $price, $description, $pid, $seller_uid]);
        $messages['update'] = $success ? "Listing updated." : "Update failed.";
    } else {
        $messages['update'] = "All fields are required.";
    }
}

/* DELETE LISTING */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_listing'])) {
    $pid = intval($_POST['pid'] ?? 0);
    $success = execQuery($db, "DELETE FROM products WHERE pid = ? AND uid = ?", [$pid, $seller_uid]);
    $messages['delete'] = $success ? "Listing deleted." : "Error deleting listing.";
}

/* LOAD LISTINGS */
$listings = getList($db, "SELECT pid, title, image, product_type, price, description FROM products WHERE uid = ?", [$seller_uid]);
$activeListings = count($listings);

/* LOAD RENTED ITEMS */
$rentedItems = getList($db, "SELECT price FROM transactions WHERE receiving_uid = ?", [$seller_uid]);
$itemsRentedOut = count($rentedItems);
$totalEarnings = array_sum(array_column($rentedItems, 'price'));

/* LOAD ORDER CHATS FOR SELLER */
$orderChats = getList($db, "
    SELECT
        c.id AS conversation_id,
        o.order_id AS order_public_id,
        o.created_at AS order_created_at,
        u.username AS buyer_name,
        c.buyer_uid,
        c.seller_uid
    FROM conversations c
    JOIN orders o ON o.id = c.order_id_fk
    JOIN users u ON u.uid = c.buyer_uid
    WHERE c.seller_uid = ?
    ORDER BY o.created_at DESC, c.id DESC
", [$seller_uid]);

/* Get chat previews */
$chatPreviews = [];
foreach ($orderChats as $c) {
    $cid = (int)$c['conversation_id'];
    $chatPreviews[$cid] = getRow($db, "SELECT sender_role, sender_uid, body, created_at FROM messages WHERE conversation_id = ? ORDER BY created_at DESC, id DESC LIMIT 1", [$cid]);
}

/* Count new messages */
$msgCount = getRow($db, "SELECT COUNT(*) AS cnt FROM messages m JOIN conversations c ON c.id = m.conversation_id WHERE c.seller_uid = ? AND m.sender_uid IS NOT NULL AND m.sender_uid <> ?", [$seller_uid, $seller_uid]);
$newMessages = (int)($msgCount['cnt'] ?? 0);

$categories = ['Dresses', 'Suits', 'Accessories', 'Jackets', 'Shoes'];

/* Validation checks for displaying warnings */
$hasAddress = !empty($sellerData['address']);
$sortcodeClean = preg_replace('/[^0-9]/', '', $sellerData['pay_sortcode'] ?? '');
$banknumberClean = preg_replace('/[^0-9]/', '', $sellerData['pay_banknumber'] ?? '');
$sortcodeValid = strlen($sortcodeClean) === 6;
$banknumberValid = strlen($banknumberClean) === 8;
$canAddListing = $hasAddress && $sortcodeValid && $banknumberValid;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Rentique | Seller Dashboard</title>
    <link rel="stylesheet" href="css/rentique.css">
    <script>
        // Apply saved theme immediately to prevent flash
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.classList.add('light-mode');
        }
    </script>
    <style>
        .cart-icon {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cart-icon svg {
            width: 20px;
            height: 20px;
            stroke: #ffffff;
            transition: all 0.3s ease;
        }
        html.light-mode .cart-icon svg {
            stroke: #333333;
        }
        .cart-icon:hover svg {
            stroke: #00ff00;
            filter: drop-shadow(0 0 10px rgba(0, 255, 0, 0.5));
        }
        #themeToggle {
            background: transparent;
            border: 1px solid rgba(0, 255, 0, 0.3);
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
            border-color: rgba(0, 255, 0, 0.5);
            background: transparent;
        }
        #themeToggle:hover {
            background: rgba(0, 255, 0, 0.1);
            border-color: #00ff00;
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
            <li><a href="basketPage.php" class="cart-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"></path>
                </svg>
            </a></li>
            <li><button id="themeToggle" onclick="toggleTheme()">🌙</button></li>
            <li><a href="user_dashboard.php">Account</a></li>
            <li><a href="index.php?logout=1" class="btn logout">Logout</a></li>
        </ul>
    </nav>
</header>

<section class="main-content">

    <div id="overview" class="section-block">
        <h2>Welcome, <?= h($sellerData['username']) ?>!</h2>

        <!-- DEBUG SECTION - Set to false to hide -->
        <?php if (false): ?>
        <div style="background: #f0f0f0; padding: 15px; border-radius: 8px; margin-bottom: 20px; color: #333;">
            <h3 style="color: #333;">Debug Information</h3>
            <p><strong>Address:</strong> <?= h($sellerData['address']) ?> (<?= $hasAddress ? 'Valid' : 'Invalid' ?>)</p>
            <p><strong>Sort Code (raw):</strong> <?= h($sellerData['pay_sortcode']) ?></p>
            <p><strong>Sort Code (cleaned):</strong> <?= h($sortcodeClean) ?> (Length: <?= strlen($sortcodeClean) ?>, Valid: <?= $sortcodeValid ? 'Yes' : 'No' ?>)</p>
            <p><strong>Bank Number (raw):</strong> <?= h($sellerData['pay_banknumber']) ?></p>
            <p><strong>Bank Number (cleaned):</strong> <?= h($banknumberClean) ?> (Length: <?= strlen($banknumberClean) ?>, Valid: <?= $banknumberValid ? 'Yes' : 'No' ?>)</p>
            <p><strong>Can Add Listing:</strong> <?= $canAddListing ? 'Yes' : 'No' ?></p>
        </div>
        <?php endif; ?>

        <div class="overview-grid">
            <?php
            $stats = [
                ['Active Listings', $activeListings],
                ['Items Rented Out', $itemsRentedOut],
                ['Total Earnings', '£' . number_format($totalEarnings, 2)],
                ['Messages', $newMessages]
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

    <?php if (array_filter([$messages['upload'], $messages['update'], $messages['delete']])): ?>
        <div class="section-block">
            <?php if ($messages['upload']): ?>
                <p style="color: #a3ff00;"><?= h($messages['upload']) ?></p>
            <?php endif; ?>
            <?php if ($messages['update']): ?>
                <p style="color: #a3ff00;"><?= h($messages['update']) ?></p>
            <?php endif; ?>
            <?php if ($messages['delete']): ?>
                <p style="color: #ff6b6b;"><?= h($messages['delete']) ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div id="listings" class="section-block">
        <h2>My Listings</h2>

        <table class="main-table full-width">
            <thead>
                <tr>
                    <?php foreach (['Item', 'Category', 'Price', 'Image', 'Edit', 'Delete'] as $header): ?>
                        <th><?= h($header) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($listings)): ?>
                    <tr><td colspan="6">No listings yet. Add your first item below!</td></tr>
                <?php else: ?>
                    <?php foreach ($listings as $item): ?>
                    <tr>
                        <td><?= h($item['title']) ?></td>
                        <td><?= h($item['product_type']) ?></td>
                        <td>£<?= number_format((float)$item['price'], 2) ?></td>
                        <td>
                            <?php if (!empty($item['image'])): ?>
                                <img src="images/<?= h($item['image']) ?>" width="60" alt="Item image">
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn small" type="button"
                                onclick="openEditModal('<?= (int)$item['pid'] ?>','<?= h($item['title']) ?>','<?= h($item['product_type']) ?>','<?= h($item['price']) ?>','<?= h($item['description']) ?>')">
                                Edit
                            </button>
                        </td>
                        <td>
                            <form method="post" onsubmit="return confirmDelete();">
                                <input type="hidden" name="pid" value="<?= (int)$item['pid'] ?>">
                                <button class="btn danger small" type="submit" name="delete_listing">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="editModal" class="modal" style="display:none;">
        <div class="modal-content" style="background:#1a1a1a;padding:20px;border-radius:8px;max-width:500px;margin:10% auto;">
            <h3>Edit Listing</h3>
            <form method="post">
                <input type="hidden" name="pid" id="edit_pid">
                
                <?php
                $editFields = [
                    ['Title', 'text', 'title', 'edit_title'],
                    ['Category', 'select', 'category', 'edit_category'],
                    ['Price (£)', 'number', 'price', 'edit_price'],
                    ['Description', 'textarea', 'description', 'edit_description']
                ];
                foreach ($editFields as [$label, $type, $name, $id]):
                ?>
                    <label><?= h($label) ?></label>
                    <?php if ($type === 'select'): ?>
                        <select name="<?= h($name) ?>" id="<?= h($id) ?>" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= h($cat) ?>"><?= h($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($type === 'textarea'): ?>
                        <textarea name="<?= h($name) ?>" id="<?= h($id) ?>" required></textarea>
                    <?php else: ?>
                        <input type="<?= h($type) ?>" <?= $type === 'number' ? 'step="0.01"' : '' ?> name="<?= h($name) ?>" id="<?= h($id) ?>" required>
                    <?php endif; ?>
                <?php endforeach; ?>

                <button class="btn primary" type="submit" name="update_listing">Save Changes</button>
                <button class="btn" type="button" onclick="closeEditModal()">Cancel</button>
            </form>
        </div>
    </div>

    <div id="additem" class="section-block">
        <h2>Add New Item</h2>

        <?php if (!$canAddListing): ?>
            <div style="background: rgba(255, 107, 107, 0.1); border: 1px solid #ff6b6b; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <p style="color: #ff6b6b; margin: 0;">
                    <strong>⚠️ Action Required:</strong> You must complete the following before adding listings:
                </p>
                <ul style="color: #ff6b6b; margin: 10px 0 0 20px;">
                    <?php if (!$hasAddress): ?>
                        <li>Set your return/pickup address in Settings below</li>
                    <?php endif; ?>
                    <?php if (!$sortcodeValid): ?>
                        <li>Add a valid 6-digit sort code in Payout Details below (current: <?= strlen($sortcodeClean) ?> digits)</li>
                    <?php endif; ?>
                    <?php if (!$banknumberValid): ?>
                        <li>Add a valid 8-digit account number in Payout Details below (current: <?= strlen($banknumberClean) ?> digits)</li>
                    <?php endif; ?>
                </ul>
                <p style="color: #ff6b6b; margin: 10px 0 0 0; font-size: 0.9em;">
                    Scroll down to complete your Settings and Payout Details sections.
                </p>
            </div>
        <?php endif; ?>

        <form class="settings-form" method="post" enctype="multipart/form-data">
            <?php
            $addFields = [
                ['Item Name', 'text', 'title'],
                ['Category', 'select', 'category'],
                ['Price (£)', 'number', 'price'],
                ['Upload Image', 'file', 'image'],
                ['Description', 'textarea', 'description']
            ];
            foreach ($addFields as [$label, $type, $name]):
            ?>
                <label><?= h($label) ?></label>
                <?php if ($type === 'select'): ?>
                    <select name="<?= h($name) ?>" required <?= !$canAddListing ? 'disabled' : '' ?>>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= h($cat) ?>"><?= h($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($type === 'textarea'): ?>
                    <textarea name="<?= h($name) ?>" required <?= !$canAddListing ? 'disabled' : '' ?>></textarea>
                <?php elseif ($type === 'file'): ?>
                    <input type="file" name="<?= h($name) ?>" accept="image/*" required <?= !$canAddListing ? 'disabled' : '' ?>>
                <?php else: ?>
                    <input type="<?= h($type) ?>" <?= $type === 'number' ? 'step="0.01"' : '' ?> name="<?= h($name) ?>" required <?= !$canAddListing ? 'disabled' : '' ?>>
                <?php endif; ?>
            <?php endforeach; ?>

            <button class="btn primary" type="submit" name="upload_item" <?= !$canAddListing ? 'disabled' : '' ?>>
                <?= $canAddListing ? 'Upload Item' : 'Complete Required Information First' ?>
            </button>
        </form>
    </div>

    <div id="earnings" class="section-block">
        <h2>Earnings</h2>
        <div class="earning-card">
            <h3>Total Earned</h3>
            <p class="green">£<?= number_format($totalEarnings, 2) ?></p>
        </div>
    </div>

    <div id="messages" class="section-block">
        <h2>Messages</h2>

        <div style="margin:10px 0; display:flex; gap:12px; flex-wrap:wrap;">
            <?php
            $buttons = [
                ['admin_support.php', 'Message admin support'],
                ['seller_orders.php', 'Shipping and returns']
            ];
            foreach ($buttons as [$href, $label]):
            ?>
                <a class="btn primary" href="<?= h($href) ?>"><?= h($label) ?></a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($orderChats)): ?>
            <p>No order chats yet.</p>
        <?php else: ?>
            <table class="main-table full-width">
                <thead>
                    <tr>
                        <?php foreach (['Order', 'Buyer', 'Last message', 'When', 'Open'] as $header): ?>
                            <th><?= h($header) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderChats as $c): 
                        $cid = (int)$c['conversation_id'];
                        $p = $chatPreviews[$cid] ?? null;
                        $txt = $p ? (string)$p['body'] : '';
                        if (strlen($txt) > 80) $txt = substr($txt, 0, 80) . '...';
                        $when = $p ? (string)$p['created_at'] : (string)$c['order_created_at'];
                        $orderId = (string)$c['order_public_id'];
                    ?>
                        <tr>
                            <td><?= h($orderId) ?></td>
                            <td><?= h($c['buyer_name']) ?></td>
                            <td><?= h($txt) ?></td>
                            <td><?= h($when) ?></td>
                            <td><a href="chat.php?order_id=<?= urlencode($orderId) ?>&seller_uid=<?= (int)$seller_uid ?>">Open</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div id="settings" class="section-block">
        <h2>Settings</h2>

        <?php if ($messages['seller_info']): ?>
            <p style="color:#a3ff00;"><?= h($messages['seller_info']) ?></p>
        <?php endif; ?>

        <form class="settings-form" method="post">
            <h3>Seller Information</h3>

            <?php
            $sellerFields = [
                ['Username', 'text', 'username', $sellerData['username']],
                ['Full Name', 'text', 'billing_fullname', $sellerData['billing_fullname']],
                ['Email', 'email', 'email', $sellerData['email']],
                ['Pickup / Return Address', 'text', 'address', $sellerData['address']]
            ];
            foreach ($sellerFields as [$label, $type, $name, $value]):
            ?>
                <label><?= h($label) ?></label>
                <input type="<?= h($type) ?>" name="<?= h($name) ?>" value="<?= h($value) ?>" required>
            <?php endforeach; ?>

            <button class="btn primary" type="submit" name="update_seller_info">Save Changes</button>
        </form>
    </div>

    <div id="payout" class="section-block">
        <h2>Payout Details</h2>

        <?php if ($messages['bank']): ?>
            <p style="color:#a3ff00;"><?= h($messages['bank']) ?></p>
        <?php endif; ?>

        <form class="settings-form" method="post">
            <p style="font-size: 0.9em; color: #888; margin-bottom: 15px;">
                Enter your UK bank details for receiving rental payments. Sort code must be 6 digits, account number must be 8 digits.
            </p>
            
            <?php
            $bankFields = [
                ['Sort Code (6 digits)', 'text', 'sortcode', $sellerData['pay_sortcode'], 'e.g., 123456'],
                ['Account Number (8 digits)', 'text', 'banknum', $sellerData['pay_banknumber'], 'e.g., 12345678']
            ];
            foreach ($bankFields as $field):
                [$label, $type, $name, $value, $placeholder] = $field;
                $maxlength = ($name === 'sortcode') ? 6 : 8;
            ?>
                <label><?= h($label) ?></label>
                <input 
                    type="<?= h($type) ?>" 
                    name="<?= h($name) ?>" 
                    value="<?= h($value) ?>" 
                    maxlength="<?= h($maxlength) ?>"
                    placeholder="<?= h($placeholder) ?>"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    title="Numbers only, <?= h($maxlength) ?> digits"
                    required>
            <?php endforeach; ?>

            <button class="btn primary" type="submit" name="update_bank">Save Bank Details</button>
        </form>
    </div>

</section>

<script>
function openEditModal(pid, title, category, price, description) {
    const fields = {edit_pid: pid, edit_title: title, edit_category: category, edit_price: price, edit_description: description};
    Object.entries(fields).forEach(([id, val]) => document.getElementById(id).value = val);
    document.getElementById("editModal").style.display = "block";
}

function closeEditModal() {
    document.getElementById("editModal").style.display = "none";
}

function confirmDelete() {
    return confirm("Are you sure you want to delete this listing?");
}

function toggleTheme() {
    const html = document.documentElement;
    const themeToggle = document.getElementById('themeToggle');
    if (html.classList.contains('light-mode')) {
        html.classList.remove('light-mode');
        themeToggle.textContent = '🌙';
        localStorage.setItem('theme', 'dark');
    } else {
        html.classList.add('light-mode');
        themeToggle.textContent = '☀️';
        localStorage.setItem('theme', 'light');
    }
}
document.addEventListener('DOMContentLoaded', function () {
    const themeToggle = document.getElementById('themeToggle');
    if (localStorage.getItem('theme') === 'light') {
        themeToggle.textContent = '☀️';
    } else {
        themeToggle.textContent = '🌙';
    }
});
</script>

</body>
</html>
