<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
require_once 'connectdb.php';

$uid = $_SESSION['uid'] ?? null;
if (!$uid) die("Access denied.");

$errorMessage = "";

/* USER DATA */
try {
    $stmt = $db->prepare("SELECT billing_fullname FROM users WHERE uid=?");
    $stmt->execute([$uid]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$userData) die("User not found.");
    $billingFullName = $userData['billing_fullname'];
} catch (Exception $e) {
    die("Database error.");
}

/* SAVED CARDS */
$stmt = $db->prepare("SELECT * FROM saved_cards WHERE uid=?");
$stmt->execute([$uid]);
$savedCards = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* BASKET */
$stmt = $db->prepare("SELECT * FROM basket WHERE uid=?");
$stmt->execute([$uid]);
$basket = $stmt->fetchAll(PDO::FETCH_ASSOC);

$platformFeePerItem = 4.99;

function safe_str($v) {
    $v = trim((string)$v);
    return $v === '' ? null : $v;
}

/* PROCESS CHECKOUT */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $stmt = $db->prepare("SELECT * FROM basket WHERE uid=?");
    $stmt->execute([$uid]);
    $basketItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$basketItems) {
        $errorMessage = "Your basket is empty.";
    }

    $cardLast4 = "";

    if (!$errorMessage) {

        $usingSaved = !empty($_POST['use_saved_card']);

        if ($usingSaved) {

            $cardId = intval($_POST['use_saved_card']);

            $stmt = $db->prepare("SELECT * FROM saved_cards WHERE id=? AND uid=?");
            $stmt->execute([$cardId, $uid]);
            $card = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$card) die("Invalid saved card.");

            $cardLast4 = substr($card['masked_card_number'], -4);

        } else {

            $name   = trim($_POST['cardholder_name']);
            $number = trim($_POST['card_number_real']);
            $type   = trim($_POST['card_type']);
            $expiry = trim($_POST['expiry_date']);
            $cvv    = trim($_POST['cvv']);

            if (!$number || strlen($number) < 4) {
                die("Invalid card number.");
            }

            $masked = str_repeat("*", 12) . substr($number, -4);
            $cardLast4 = substr($number, -4);

            $stmt = $db->prepare("
                INSERT INTO saved_cards (uid, cardholder_name, card_type, masked_card_number)
                VALUES (?,?,?,?)
            ");
            $stmt->execute([$uid, $name, $type, $masked]);
        }

        $orderPublicId = time() . rand(1000, 9999);

        $buyerAddr1 = safe_str($_POST['buyer_address_line1'] ?? '');
        $buyerAddr2 = safe_str($_POST['buyer_address_line2'] ?? '');
        $buyerCity = safe_str($_POST['buyer_city'] ?? '');
        $buyerPostcode = safe_str($_POST['buyer_postcode'] ?? '');
        $buyerCountry = safe_str($_POST['buyer_country'] ?? '');

        $shipBy = (new DateTime('now'))->modify('+3 days')->format('Y-m-d H:i:s');

        $minDays = null;
        foreach ($basketItems as $bi) {
            $d = max(1, intval($bi['rental_days'] ?? 1));
            if ($minDays === null || $d < $minDays) $minDays = $d;
        }
        if ($minDays === null) $minDays = 1;

        $rentalStart = (new DateTime('today'))->format('Y-m-d');
        $rentalEnd = (new DateTime('today'))->modify('+' . $minDays . ' days')->format('Y-m-d');
        $returnDeadline = (new DateTime($rentalEnd . ' 10:00:00'))->modify('+3 days')->format('Y-m-d H:i:s');

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("
                INSERT INTO orders
                (order_id, buyer_uid, status, created_at, ship_by, rental_start, rental_end, return_deadline,
                 buyer_address_line1, buyer_address_line2, buyer_city, buyer_postcode, buyer_country)
                VALUES (?, ?, 'paid', NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $orderPublicId,
                $uid,
                $shipBy,
                $rentalStart,
                $rentalEnd,
                $returnDeadline,
                $buyerAddr1,
                $buyerAddr2,
                $buyerCity,
                $buyerPostcode,
                $buyerCountry
            ]);

            $orderIdFk = (int)$db->lastInsertId();

            foreach ($basketItems as $item) {

                $sellerUid = intval($item['seller_uid'] ?? 0);
                if ($sellerUid <= 0) {
                    throw new Exception("Missing seller for basket item.");
                }

                $qty = max(1, intval($item['quantity'] ?? 1));
                $days = max(1, intval($item['rental_days'] ?? 1));

                $perDay = floatval($item['price']);
                $lineTotal = $perDay * $days * $qty;

                $stmt = $db->prepare("
                    INSERT INTO order_items
                    (order_id_fk, pid, seller_uid, title, image, product_type, per_day_price, rental_days, quantity, line_total, platform_fee, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $orderIdFk,
                    $item['pid'],
                    $sellerUid,
                    $item['title'],
                    $item['image'],
                    $item['product_type'],
                    $perDay,
                    $days,
                    $qty,
                    $lineTotal,
                    $platformFeePerItem
                ]);

                $orderItemId = (int)$db->lastInsertId();

                $stmt = $db->prepare("INSERT INTO order_shipments (order_item_id) VALUES (?)");
                $stmt->execute([$orderItemId]);

                $stmt = $db->prepare("UPDATE products SET is_available = 0 WHERE pid = ?");
                $stmt->execute([$item['pid']]);

                $stmt = $db->prepare("
                    SELECT id FROM conversations
                    WHERE order_id_fk = ? AND buyer_uid = ? AND seller_uid = ?
                    LIMIT 1
                ");
                $stmt->execute([$orderIdFk, $uid, $sellerUid]);
                $conv = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$conv) {
                    $stmt = $db->prepare("
                        INSERT INTO conversations (order_id_fk, buyer_uid, seller_uid, created_at)
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmt->execute([$orderIdFk, $uid, $sellerUid]);
                    $conversationId = (int)$db->lastInsertId();
                } else {
                    $conversationId = (int)$conv['id'];
                }

                $stmt = $db->prepare("
                    INSERT INTO messages (conversation_id, sender_role, sender_uid, body, created_at)
                    VALUES (?, 'system', NULL, ?, NOW())
                ");
                $stmt->execute([
                    $conversationId,
                    "Order placed. Order ID: " . $orderPublicId . ". Rental days: " . $days . "."
                ]);

                $addrText = "Buyer address: "
                    . ($buyerAddr1 ? $buyerAddr1 : '') . " "
                    . ($buyerAddr2 ? $buyerAddr2 : '') . " "
                    . ($buyerCity ? $buyerCity : '') . " "
                    . ($buyerPostcode ? $buyerPostcode : '') . " "
                    . ($buyerCountry ? $buyerCountry : '');

                $stmt->execute([
                    $conversationId,
                    "Order received. Ship by: " . $shipBy . ". " . trim($addrText)
                ]);
            }

            $stmt = $db->prepare("DELETE FROM basket WHERE uid=?");
            $stmt->execute([$uid]);

            $_SESSION['last_card4'] = $cardLast4;
            $_SESSION['last_order_id'] = $orderPublicId;

            $db->commit();

            header("Location: OrderComplete.php");
            exit;

        } catch (Exception $ex) {
            $db->rollBack();
            $errorMessage = "Checkout failed. " . $ex->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="css/rentique.css">
    <title>Rentique | Checkout</title>

    <script>
        window.userBillingName = <?= json_encode($billingFullName) ?>;
        window.basket = <?= json_encode($basket) ?>;
        window.platformFeePerItem = <?= json_encode($platformFeePerItem) ?>;
    </script>

    <script src="js/Checkout.js" defer></script>
    <script src="js/theme.js" defer></script>
</head>

<body id="checkoutPage">

<header>
    <img src="images/logo4.png" class="header-logo">
    <span class="brand-name">Checkout</span>
    <button id="themeToggle">Theme</button>
</header>

<?php if ($errorMessage): ?>
    <div style="color:red; text-align:center; margin:10px 0;">
        <?= htmlspecialchars($errorMessage) ?>
    </div>
<?php endif; ?>

<div class="main">
    <div class="card">

        <div class="left-side">
            <div class="order-summary">
                <h3>Order Summary</h3>
                <div id="orderItems"></div>
                <div id="orderDelivery"></div>
                <div id="orderPlatformFee"></div>
                <div id="orderTotal"></div>
            </div>
        </div>

        <div class="right-side">

            <form action="Checkout.php" method="POST" id="checkoutForm">

                <h1>Checkout</h1>
                <h2>Enter your card details</h2>

                <p>Shipping address</p>
                <input type="text" class="inputbox" name="buyer_address_line1" placeholder="Address line 1" required>
                <input type="text" class="inputbox" name="buyer_address_line2" placeholder="Address line 2">
                <input type="text" class="inputbox" name="buyer_city" placeholder="City" required>
                <input type="text" class="inputbox" name="buyer_postcode" placeholder="Postcode" required>
                <input type="text" class="inputbox" name="buyer_country" placeholder="Country" required>

                <?php if ($savedCards): ?>
                    <p>Use Saved Card</p>

                    <select class="inputbox" name="use_saved_card" id="savedCardSelect">
                        <option value="">Select saved card</option>

                        <?php foreach ($savedCards as $card): ?>
                        <option value="<?= $card['id'] ?>">
                            <?= htmlspecialchars($card['card_type']) ?> ending in <?= substr($card['masked_card_number'], -4) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <p style="text-align:center;font-weight:bold;">OR</p>
                <?php endif; ?>

                <div id="newCardSection">

                    <p>Cardholder Name</p>
                    <input type="text" class="inputbox" name="cardholder_name">

                    <p>Card Number</p>
                    <input type="text" class="inputbox" name="card_number">

                    <p>Card Type</p>
                    <select class="inputbox" name="card_type">
                        <option value="">Select type</option>
                        <option value="Visa">Visa</option>
                        <option value="MasterCard">MasterCard</option>
                        <option value="Other">Other</option>
                    </select>

                    <div class="expcvv">

                        <div style="flex:1;">
                            <p>Expiry Date</p>
                            <input type="month" class="inputbox" name="expiry_date">
                        </div>

                        <div style="flex:1;">
                            <p>CVV</p>
                            <input type="password" class="inputbox" name="cvv">
                        </div>

                    </div>

                </div>

                <input type="hidden" name="card_number_real">

                <button type="submit" class="button">Confirm</button>

            </form>

        </div>

    </div>
</div>

</body>
</html>
