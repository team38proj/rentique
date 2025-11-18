<?php
// Victor Backend – Start session and load database connection //
session_start();
require_once 'connectdb.php';

// Victor Backend – Ensure user is logged in //
if (!isset($_SESSION['uid'])) {
    die("Access denied. Please log in.");
}
$uid = $_SESSION['uid'];

// Victor Backend – Fetch the user's billing_fullname from users table //
try {
    $stmt = $db->prepare("SELECT billing_fullname FROM users WHERE uid = ?");
    $stmt->execute([$uid]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        die("User not found in database.");
    }

    $billingFullName = $userData['billing_fullname'];
} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}

// Victor Backend – Fetch saved cards for this user (only masked numbers) //
$savedCards = [];
try {
    $stmt = $db->prepare("SELECT id, cardholder_name, card_type, masked_card_number FROM saved_cards WHERE uid = ?");
    $stmt->execute([$uid]);
    $savedCards = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Database error: " . htmlspecialchars($e->getMessage());
}

// Victor Backend – Handle form submission //
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // If user selects an existing saved card
    if (!empty($_POST['use_saved_card'])) {
        $selectedCardId = intval($_POST['use_saved_card']);

        // Verify card belongs to user
        $stmt = $db->prepare("SELECT * FROM saved_cards WHERE id = ? AND uid = ?");
        $stmt->execute([$selectedCardId, $uid]);
        $card = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$card) {
            die("Invalid card selection.");
        }

        // Process payment here
        echo "<p>Payment processed using saved card ending in: " . htmlspecialchars(substr($card['masked_card_number'], -4)) . "</p>";
        exit;
    }

    // Victor Backend – Handle NEW card details //
    $name = trim($_POST['cardholder_name'] ?? '');
    $number = trim($_POST['card_number'] ?? '');
    $type = trim($_POST['card_type'] ?? '');
    $expiry = trim($_POST['expiry_date'] ?? '');
    $cvv = trim($_POST['cvv'] ?? '');

    // Victor Backend – Validation
    $errors = [];

    if ($name === '' || $number === '' || $type === '' || $expiry === '' || $cvv === '') {
        $errors[] = "All fields are required.";
    }

    if ($name !== $billingFullName) {
        $errors[] = "Cardholder name must match your billing full name on file: " . htmlspecialchars($billingFullName);
    }

    if (!preg_match('/^[0-9]{16}$/', $number)) {
        $errors[] = "Card number must be 16 digits.";
    }

    if (!preg_match('/^[0-9]{3}$/', $cvv)) {
        $errors[] = "CVV must be 3 digits.";
    }

    if (strtotime($expiry . "-01") < time()) { // expiry is month input, convert to first day
        $errors[] = "Expiry date must be in the future.";
    }

    if (!empty($errors)) {
        foreach ($errors as $err) {
            echo "<p style='color:red;'>" . htmlspecialchars($err) . "</p>";
        }
        exit;
    }

    // Victor Backend – Mask card number for storage //
    $masked = str_repeat('*', 12) . substr($number, -4);

    // Victor Backend – Insert new saved card //
    $stmt = $db->prepare("INSERT INTO saved_cards (uid, cardholder_name, card_type, masked_card_number) VALUES (?, ?, ?, ?)");
    $stmt->execute([$uid, $name, $type, $masked]);

    // Victor Backend – Process payment (demo) //
    echo "<p>Payment processed using new card ending in: " . substr($number, -4) . "</p>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="rentique.css">
    <title>Checkout: Rentique</title>
    <script src="script.js" defer></script> <!-- Victor Backend – External JS file -->
</head>
<body id="checkoutPage">

<header>
    <img src="logo.jpeg" alt="Rentique logo" class="header-logo">
    <span class="brand-name">Checkout</span>
</header>

<div class="main">
    <div class="card">
        <div class="left-side">
            <div class="order-summary">
                <h3>Order Summary</h3>
                <ul>
                    <li>
                        <span class="item-name">Green Satin Dress (3 days)</span>
                        <span class="item-price">£45.00</span>
                    </li>
                    <li>
                        <span class="item-name">Delivery</span>
                        <span class="item-price">£4.99</span>
                    </li>
                </ul>
                <div class="order-total">
                    <span>Total</span>
                    <span>£49.99</span>
                </div>
            </div>
        </div>

        <div class="right-side">

            <form action="checkout.php" method="POST" id="checkoutForm">

                <h1>Checkout</h1>
                <h2>Almost there! Please choose or enter your card details.</h2>

                <!-- Victor Backend – Saved Card Selection -->
                <?php if (!empty($savedCards)): ?>
                    <p>Use Saved Card</p>
                    <select class="inputbox" name="use_saved_card" id="savedCardSelect">
                        <option value="">Select saved card</option>
                        <?php foreach ($savedCards as $card): ?>
                            <option value="<?= htmlspecialchars($card['id']) ?>">
                                <?= htmlspecialchars($card['card_type']) ?> ending in <?= htmlspecialchars(substr($card['masked_card_number'], -4)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <p style="text-align:center; font-weight:bold;">— OR —</p>
                <?php endif; ?>

                <!-- Victor Backend – New Card Fields -->
                <div id="newCardSection">
                    <p>Cardholder Name</p>
                    <input type="text" class="inputbox" name="cardholder_name" required>

                    <p>Card Number</p>
                    <input type="text" class="inputbox" name="card_number" required>

                    <p>Card Type</p>
                    <select class="inputbox" name="card_type" required>
                        <option value="">Select a card type</option>
                        <option value="Visa">Visa</option>
                        <option value="MasterCard">MasterCard</option>
                        <option value="Other">Other</option>
                    </select>

                    <div class="expcvv">
                        <div style="flex:1;">
                            <p class="expcvv_text">Expiry Date</p>
                            <input type="month" class="inputbox" name="expiry_date" required>
                        </div>
                        <div style="flex:1;">
                            <p class="expcvv_text2">CVV</p>
                            <input type="password" class="inputbox" name="cvv" required>
                        </div>
                    </div>
                </div>

                <button type="submit" class="button">Confirm</button>
            </form>

        </div>
    </div>
</div>

</body>
</html>
