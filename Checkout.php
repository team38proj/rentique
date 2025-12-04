<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="rentique.css">
    <title>Rentique | Checkout</title>
    <script>
        // Victor Backend – Pass billing name to JS
        window.userBillingName = <?= json_encode($billingFullName) ?>;

        // Victor Backend – Pass basket to JS
        window.basket = <?= json_encode($basket) ?>;
    </script>
    <script src="script.js" defer></script>
</head>
<body id="checkoutPage">

<header>
    <span class="brand-name">rentique.</span>
</header>

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
            <form action="checkout.php" method="POST" id="checkoutForm">
                <h1>Checkout</h1>
                <h2>Almost there! Please choose or enter your card details.</h2>

                <!-- Victor Backend – Display saved cards if available -->
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

                <!-- Victor Backend – New card input fields -->
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
                            <p>Expiry Date</p>
                            <input type="month" class="inputbox" name="expiry_date" required>
                        </div>
                        <div style="flex:1;">
                            <p>CVV</p>
                            <input type="password" class="inputbox" name="cvv" required>
                        </div>
                    </div>
                </div>

                <button type="submit" class="button">Confirm</button>
            </form>
        </div>
    </div>
</div>

<footer>
    <p>© 2025 Rentique. All rights reserved.</p>
</footer>

</body>
</html>
