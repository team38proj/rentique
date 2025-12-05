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

/* PROCESS CHECKOUT */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* Reload basket safely */
    $stmt = $db->prepare("SELECT * FROM basket WHERE uid=?");
    $stmt->execute([$uid]);
    $basketItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$basketItems) {
        $errorMessage = "Your basket is empty.";
    }

    $cardLast4 = "";

    if (!$errorMessage) {

        /* CARD SELECTION */
        $usingSaved = !empty($_POST['use_saved_card']);

        if ($usingSaved) {

            $cardId = intval($_POST['use_saved_card']);

            $stmt = $db->prepare("SELECT * FROM saved_cards WHERE id=? AND uid=?");
            $stmt->execute([$cardId, $uid]);
            $card = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$card) die("Invalid saved card.");

            $cardLast4 = substr($card['masked_card_number'], -4);

        } else {

            /* NEW CARD */
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

            /* Save new card */
            $stmt = $db->prepare("
                INSERT INTO saved_cards (uid, cardholder_name, card_type, masked_card_number)
                VALUES (?,?,?,?)
            ");
            $stmt->execute([$uid, $name, $type, $masked]);
        }

        /* CREATE ORDER ID */
        $order_id = time() . rand(1000, 9999);

        /* INSERT TRANSACTIONS */
        foreach ($basketItems as $item) {

            $receiving_uid = $item['uid'];

            $stmt = $db->prepare("
                INSERT INTO transactions 
                (pid, paying_uid, receiving_uid, price, order_id, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $item['pid'],
                $uid,
                $receiving_uid,
                $item['price'],
                $order_id
            ]);
        }

        /* CLEAR BASKET */
        $stmt = $db->prepare("DELETE FROM basket WHERE uid=?");
        $stmt->execute([$uid]);

        /* STORE LAST 4 FOR ORDER COMPLETE */
        $_SESSION['last_card4'] = $cardLast4;

        /* REDIRECT */
        header("Location: OrderComplete.php");
        exit;
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
    </script>

    <script src="js/Checkout.js" defer></script>
</head>

<body id="checkoutPage">

<header>
    <img src="images/logo.jpeg" class="header-logo">
    <span class="brand-name">Checkout</span>
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
                <div id="orderTotal"></div>
            </div>
        </div>

        <div class="right-side">

            <form action="Checkout.php" method="POST" id="checkoutForm">

                <h1>Checkout</h1>
                <h2>Enter your card details</h2>

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
