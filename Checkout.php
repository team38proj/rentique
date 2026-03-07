<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
require_once 'connectdb.php';

$uid = $_SESSION['uid'] ?? null;
if (!$uid) die("Access denied.");

$errorMessage = "";

$userData = null;
if (isset($_SESSION['uid'])) {
    try {
        $stmt = $db->prepare("SELECT uid, email, billing_fullname, role FROM users WHERE uid = ?");
        $stmt->execute([$_SESSION['uid']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Navbar user fetch error: " . $e->getMessage());
    }
}

/* USER DATA */
try {
    $stmt = $db->prepare("SELECT billing_fullname FROM users WHERE uid=?");
    $stmt->execute([$uid]);
    $userDataCheckout = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$userDataCheckout) die("User not found.");
    $billingFullName = $userDataCheckout['billing_fullname'];
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

            $name   = trim($_POST['cardholder_name'] ?? '');
            $number = trim($_POST['card_number_real'] ?? '');
            $type   = trim($_POST['card_type'] ?? '');
            $expiry = trim($_POST['expiry_date'] ?? '');
            $cvv    = trim($_POST['cvv'] ?? '');

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

                $stmtMsg = $db->prepare("
                    INSERT INTO messages (conversation_id, sender_role, sender_uid, body, created_at)
                    VALUES (?, 'system', NULL, ?, NOW())
                ");

                $stmtMsg->execute([
                    $conversationId,
                    "Order placed. Order ID: " . $orderPublicId . ". Rental days: " . $days . "."
                ]);

                $addrText = "Buyer address: "
                    . ($buyerAddr1 ? $buyerAddr1 : '') . " "
                    . ($buyerAddr2 ? $buyerAddr2 : '') . " "
                    . ($buyerCity ? $buyerCity : '') . " "
                    . ($buyerPostcode ? $buyerPostcode : '') . " "
                    . ($buyerCountry ? $buyerCountry : '');

                $stmtMsg->execute([
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
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $errorMessage = "Checkout failed. " . $ex->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentique | Checkout</title>
    <link rel="stylesheet" href="css/rentique.css">
    <link rel="stylesheet" href="assets/global.css">
    <link rel="icon" type="image/png" href="images/rentique_logo.png">
    <script src="js/theme.js" defer></script>

    <script>
        window.userBillingName = <?= json_encode($billingFullName) ?>;
        window.basket = <?= json_encode($basket) ?>;
        window.platformFeePerItem = <?= json_encode($platformFeePerItem) ?>;
    </script>

    <script src="js/Checkout.js" defer></script>

    <style>
        .cart-icon {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cart-icon svg {
            width: 20px;
            height: 20px;
            stroke: #eaeaea;
            transition: all 0.3s ease;
        }

        html.light-mode .cart-icon svg {
            stroke: #000000;
        }

        .cart-icon:hover svg {
            stroke: #00FF00;
        }

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

        #checkoutPage {
            background: #0a0a0a;
            color: #eaeaea;
            height: auto;
            overflow: auto;
            display: block;
        }

        .intro {
            text-align: center;
            background: #0f0f0f;
            border-radius: 12px;
            padding: 28px 20px;
            margin-bottom: 28px;
        }

        .intro h1 {
            color: #a3ff00;
            font-size: 4rem;
            font-weight: 800;
            margin: 0 0 10px;
            line-height: 1.1;
        }

        .intro .subtitle {
            font-size: 1.1rem;
            color: #f1f1f1;
            margin: 0;
        }

        .checkoutContainer {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-top: 30px;
            align-items: start;
        }

        .checkoutLeft {
            border: 1px solid rgba(163, 255, 0, 0.35);
            border-radius: 16px;
            padding: 20px;
            background: rgba(20, 20, 20, 0.35);
        }

        .checkoutCard {
            background: #171717;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            padding: 28px;
            margin-bottom: 22px;
            box-shadow: 0 0 0 1px rgba(163, 255, 0, 0.05);
        }

        .checkoutCard h2 {
            font-size: 1.9rem;
            margin: 0 0 18px;
            color: #a3ff00;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        #checkoutPage .inputbox {
            width: 100%;
            padding: 16px 16px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background-color: #0b0b0b;
            color: #eaeaea;
            margin-bottom: 16px;
            outline: none;
            font-size: 1rem;
            transition: all 0.25s ease;
            box-sizing: border-box;
        }

        #checkoutPage .inputbox:focus {
            border-color: #a3ff00;
            box-shadow: 0 0 0 3px rgba(163, 255, 0, 0.08);
        }

        .checkoutGrid2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .checkout-alert {
            border-radius: 10px;
            padding: 12px 14px;
            border: 1px solid #333;
            margin-top: 12px;
        }

        .checkout-alert--error {
            background: rgba(217, 83, 79, 0.12);
            border-color: rgba(217, 83, 79, 0.35);
            color: #ffd1d1;
        }

        .checkoutBtn {
            width: 100%;
            background: #a3ff00;
            color: #000;
            border: none;
            border-radius: 12px;
            padding: 16px 20px;
            font-size: 1.15rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.25s ease;
            margin-top: 8px;
        }

        .checkoutBtn:hover {
            background: #b7ff2a;
            transform: translateY(-1px);
        }

        .checkoutRight {
            position: sticky;
            top: 20px;
        }

        .summaryPanel {
            background: #171717;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 0 0 1px rgba(163, 255, 0, 0.05);
        }

        .summaryBox {
            padding: 28px 28px 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .summaryBox h2 {
            font-size: 2.4rem;
            font-weight: 800;
            color: #a3ff00;
            margin: 0 0 20px;
            text-transform: uppercase;
            line-height: 1.1;
        }

        .summaryBox #orderItems,
        .summaryBox #orderDelivery,
        .summaryBox #orderPlatformFee,
        .summaryBox #orderTotal {
            font-size: 1.05rem;
            color: #f2f2f2;
            line-height: 1.8;
        }

        .summaryBox #orderItems > div,
        .summaryBox #orderDelivery > div,
        .summaryBox #orderPlatformFee > div,
        .summaryBox #orderTotal > div,
        .summaryBox p {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin: 10px 0;
        }

        .summaryBox strong {
            color: #a3ff00;
        }

        .impactBox {
            background: transparent;
            border: none;
            border-radius: 0;
            padding: 24px 28px 28px;
            margin-top: 0;
        }

        .impactBox h2 {
            font-size: 1.9rem;
            margin: 0 0 14px;
            color: #a3ff00;
            font-weight: 800;
            text-transform: uppercase;
            line-height: 1.15;
        }

        .impactIntro {
            color: #cfcfcf;
            font-size: 0.95rem;
            margin-bottom: 12px;
            line-height: 1.5;
        }

        .impactRow {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .impactRow:last-of-type {
            border-bottom: none;
        }

        .impactLabel {
            color: #d8d8d8;
            font-size: 0.95rem;
        }

        .impactValue {
            color: #a3ff00;
            font-weight: 700;
            white-space: nowrap;
        }

        .impactNote {
            margin-top: 14px;
            font-size: 0.85rem;
            color: #aaaaaa;
            line-height: 1.5;
        }

        @media (max-width: 900px) {
            .checkoutContainer {
                grid-template-columns: 1fr;
            }

            .checkoutRight {
                position: static;
            }

            .checkoutGrid2 {
                grid-template-columns: 1fr;
            }

            .intro h1 {
                font-size: 3rem;
            }

            .summaryBox h2,
            .impactBox h2,
            .checkoutCard h2 {
                font-size: 1.7rem;
            }
        }
    </style>
</head>

<body id="checkoutPage">

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
            <li><a href="productsPage.php">Shop</a></li>
            <li><a href="AboutUs.php">About</a></li>
            <li><a href="Contact.php">Contact</a></li>
            <li><a href="FAQTestimonials.php">FAQ</a></li>
            <li><a href="game.php" class="active">Game</a></li>

            <?php if (isset($userData)): ?>
                <li><a href="dashboard.php">Style Planner</a></li>
            <?php else: ?>
                <a href="login.php" class="active">Style Planner</a>
            <?php endif; ?>

            <li><a href="basketPage.php" class="cart-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"></path>
                </svg>
            </a></li>

            <li>
                <button id="themeToggle" onclick="toggleTheme()">&#127769;</button>
            </li>

            <?php if (isset($userData['role']) && $userData['role'] === 'customer'): ?>
                <li><a href="seller_dashboard.php">Sell</a></li>
                <li><a href="user_dashboard.php"><?= htmlspecialchars($userData['billing_fullname'] ?? "Account") ?></a></li>
                <li><a href="index.php?logout=1" class="btn login">Logout</a></li>
            <?php elseif (isset($userData['role']) && $userData['role'] === 'admin'): ?>
                <li><a href="admin_dashboard.php">Admin</a></li>
                <li><a href="index.php?logout=1" class="btn login">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php" class="btn login">Login</a></li>
                <li><a href="signup.php" class="btn signup">Sign Up</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<main class="page-container">

    <div class="intro">
        <h1>Checkout</h1>
        <p class="subtitle">Enter shipping and payment details to place your order.</p>
    </div>

    <?php if ($errorMessage): ?>
        <div class="checkout-alert checkout-alert--error">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <div class="checkoutContainer">

        <section class="checkoutLeft">
            <form action="Checkout.php" method="POST" id="checkoutForm">

                <div class="checkoutCard">
                    <h2>Shipping Address</h2>

                    <input type="text" class="inputbox" name="buyer_address_line1" placeholder="Address line 1" required>
                    <input type="text" class="inputbox" name="buyer_address_line2" placeholder="Address line 2">

                    <div class="checkoutGrid2">
                        <input type="text" class="inputbox" name="buyer_city" placeholder="City" required>
                        <input type="text" class="inputbox" name="buyer_postcode" placeholder="Postcode" required>
                    </div>

                    <input type="text" class="inputbox" name="buyer_country" placeholder="Country" required>
                </div>

                <div class="checkoutCard">
                    <h2>Payment</h2>

                    <?php if ($savedCards): ?>
                        <p style="color:#a3ff00; margin: 8px 0 6px;">Use saved card</p>

                        <select class="inputbox" name="use_saved_card" id="savedCardSelect">
                            <option value="">Select saved card</option>
                            <?php foreach ($savedCards as $card): ?>
                                <option value="<?= $card['id'] ?>">
                                    <?= htmlspecialchars($card['card_type']) ?> ending in <?= substr($card['masked_card_number'], -4) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <p style="text-align:center;font-weight:bold; margin: 10px 0;">OR</p>
                    <?php endif; ?>

                    <div id="newCardSection">
                        <input type="text" class="inputbox" name="cardholder_name" placeholder="Cardholder name">
                        <input type="text" class="inputbox" name="card_number" placeholder="Card number">

                        <select class="inputbox" name="card_type">
                            <option value="">Card type</option>
                            <option value="Visa">Visa</option>
                            <option value="MasterCard">MasterCard</option>
                            <option value="Other">Other</option>
                        </select>

                        <div class="checkoutGrid2">
                            <input type="month" class="inputbox" name="expiry_date">
                            <input type="password" class="inputbox" name="cvv" placeholder="CVV">
                        </div>
                    </div>

                    <input type="hidden" name="card_number_real">
                </div>

                <button type="submit" class="checkoutBtn">Place Order</button>

            </form>
        </section>

        <aside class="checkoutRight">
            <div class="summaryPanel">
                <div class="summaryBox">
                    <h2>Order Summary</h2>
                    <div id="orderItems"></div>
                    <div id="orderDelivery"></div>
                    <div id="orderPlatformFee"></div>
                    <div id="orderTotal"></div>
                </div>

                <div class="impactBox">
                    <h2>Your Sustainability Impact</h2>
                    <p class="impactIntro">
                        Renting instead of buying helps reduce waste and supports a more circular fashion model.
                    </p>

                    <div class="impactRow">
                        <span class="impactLabel">Items rented</span>
                        <span class="impactValue" id="impactItems">0</span>
                    </div>

                    <div class="impactRow">
                        <span class="impactLabel">Total rental days</span>
                        <span class="impactValue" id="impactDays">0</span>
                    </div>

                    <div class="impactRow">
                        <span class="impactLabel">Estimated CO&#8322; saved</span>
                        <span class="impactValue" id="impactCo2">0.0 kg</span>
                    </div>

                    <div class="impactRow">
                        <span class="impactLabel">Estimated textile waste reduced</span>
                        <span class="impactValue" id="impactWaste">0.0 kg</span>
                    </div>

                    <div class="impactRow">
                        <span class="impactLabel">Estimated 5% donation</span>
                        <span class="impactValue" id="impactDonation">&#163;0.00</span>
                    </div>

                    <p class="impactNote">
                        Estimates are based on the number of rented items and your current basket value.
                    </p>
                </div>
            </div>
        </aside>

    </div>

</main>

<?php
if (file_exists('footer.html')) {
    include 'footer.html';
}
?>

<script>
function toggleTheme() {
    const body = document.body;
    const themeToggle = document.getElementById('themeToggle');
    if (body.classList.contains('light-mode')) {
        body.classList.remove('light-mode');
        themeToggle.innerHTML = '&#127769;';
        localStorage.setItem('theme', 'dark');
    } else {
        body.classList.add('light-mode');
        themeToggle.innerHTML = '&#9728;';
        localStorage.setItem('theme', 'light');
    }
}
document.addEventListener('DOMContentLoaded', function () {
    const savedTheme = localStorage.getItem('theme');
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        if (savedTheme === 'light') {
            document.body.classList.add('light-mode');
            themeToggle.innerHTML = '&#9728;';
        } else {
            themeToggle.innerHTML = '&#127769;';
        }
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const basket = Array.isArray(window.basket) ? window.basket : [];
    const platformFeePerItem = parseFloat(window.platformFeePerItem || 0);

    let totalItems = 0;
    let totalRentalDays = 0;
    let estimatedOrderValue = 0;

    basket.forEach(item => {
        const qty = Math.max(1, parseInt(item.quantity || 1, 10));
        const days = Math.max(1, parseInt(item.rental_days || 1, 10));
        const price = parseFloat(item.price || 0);

        totalItems += qty;
        totalRentalDays += (qty * days);
        estimatedOrderValue += (price * qty * days);
    });

    const platformFees = totalItems * platformFeePerItem;
    const estimatedCheckoutTotal = estimatedOrderValue + platformFees;

    const estimatedCo2Saved = (totalItems * 4.2);
    const estimatedWasteReduced = (totalItems * 0.8);
    const estimatedDonation = (estimatedCheckoutTotal * 0.05);

    const impactItems = document.getElementById('impactItems');
    const impactDays = document.getElementById('impactDays');
    const impactCo2 = document.getElementById('impactCo2');
    const impactWaste = document.getElementById('impactWaste');
    const impactDonation = document.getElementById('impactDonation');

    if (impactItems) impactItems.textContent = totalItems;
    if (impactDays) impactDays.textContent = totalRentalDays;
    if (impactCo2) impactCo2.textContent = estimatedCo2Saved.toFixed(1) + ' kg';
    if (impactWaste) impactWaste.textContent = estimatedWasteReduced.toFixed(1) + ' kg';
    if (impactDonation) impactDonation.innerHTML = '&#163;' + estimatedDonation.toFixed(2);
});
</script>

</body>
</html>
