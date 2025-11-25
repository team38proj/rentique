document.addEventListener('DOMContentLoaded', function() {
    const savedCardSelect = document.getElementById('savedCardSelect');
    const newCardSection = document.getElementById('newCardSection');
    const checkoutForm = document.getElementById('checkoutForm');
    const userBillingName = window.userBillingName || '';

    // Show/hide new card section based on saved card selection
    if (savedCardSelect && newCardSection) {
        newCardSection.style.display = 'block'; // default show
        savedCardSelect.addEventListener('change', function() {
            newCardSection.style.display = this.value && this.value !== "" ? 'none' : 'block';
        });
    }

    //  hidden input for real card number exists
    let realCardInput = document.querySelector('input[name="card_number_real"]');
    if (!realCardInput) {
        realCardInput = document.createElement('input');
        realCardInput.type = 'hidden';
        realCardInput.name = 'card_number_real';
        checkoutForm.appendChild(realCardInput);
    }

    //  card number input and sync to hidden input
    const cardNumberInput = document.querySelector('input[name="card_number"]');
    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function(e) {
            const val = e.target.value.replace(/\D/g, '').slice(0, 16); // keep only digits
            realCardInput.value = val;  // hidden field for backend
            e.target.value = val;       // sanitized visible input
        });
    }

    // Form validation
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            const usingSavedCard = savedCardSelect && savedCardSelect.value && savedCardSelect.value !== "";
            let errors = [];

            // Check if user filled new card fields
            const anyNewCardFilled = (checkoutForm.cardholder_name.value.trim() ||
                                      checkoutForm.card_number.value.trim() ||
                                      checkoutForm.card_type.value.trim() ||
                                      checkoutForm.expiry_date.value.trim() ||
                                      checkoutForm.cvv.value.trim());

            // Must select a saved card OR enter new card info
            if (!usingSavedCard && !anyNewCardFilled) {
                errors.push("Please select a saved card or enter new card details.");
            }

            // Validate new card only if not using saved card
            if (!usingSavedCard && anyNewCardFilled) {
                const name = checkoutForm.cardholder_name.value.trim();
                const number = realCardInput.value.trim();
                const type = checkoutForm.card_type.value.trim();
                const expiry = checkoutForm.expiry_date.value;
                const cvv = checkoutForm.cvv.value.trim();

                if (!name || !number || !type || !expiry || !cvv) errors.push("All new card fields are required.");
                if (!/^\d{13,16}$/.test(number)) errors.push("Card number INVALID!!"); // allow 13–16 digits

                if (type === "Visa" && !number.startsWith("4")) errors.push("Visa card INVALID!!");
                if (type === "MasterCard") {
                    const prefix = parseInt(number.slice(0, 2), 10);
                    if (prefix < 51 || prefix > 55) errors.push("MasterCard INVALID!!");
                }

                if (!/^\d{3}$/.test(cvv)) errors.push("CVV INVALID!!");
                if (new Date(expiry + "-01") < new Date()) errors.push("Expiry date INVALID!!");
                if (name !== userBillingName) errors.push(`Cardholder name must match your billing name: ${userBillingName}`);
            }

            if (errors.length > 0) {
                e.preventDefault();
                alert(errors.join("\n"));
            }
        });
    }

    // Victor Backend – Dynamic order summary
    const deliveryCost = 4.99;
    window.renderOrderSummary = function(basket) {
        const orderItemsContainer = document.getElementById('orderItems');
        const deliveryContainer = document.getElementById('orderDelivery');
        const totalContainer = document.getElementById('orderTotal');
        orderItemsContainer.innerHTML = '';
        let subtotal = 0;

        basket.forEach(item => {
            subtotal += parseFloat(item.price);
            const div = document.createElement('div');
            div.style.display = 'flex';
            div.style.alignItems = 'center';
            div.style.marginBottom = '10px';
            div.innerHTML = `
                <img src="${item.image}" alt="${item.title}" style="width:50px;height:50px;margin-right:10px;object-fit:cover;border-radius:4px;">
                <span style="flex:1;">${item.title}</span>
                <span>£${parseFloat(item.price).toFixed(2)}</span>
            `;
            orderItemsContainer.appendChild(div);
        });

        deliveryContainer.innerHTML = `<div style="display:flex;justify-content:space-between;margin-top:10px;"><span>Delivery</span><span>£${deliveryCost.toFixed(2)}</span></div>`;
        const total = subtotal + deliveryCost;
        totalContainer.innerHTML = `<div style="display:flex;justify-content:space-between;font-weight:bold;margin-top:10px;"><span>Total</span><span>£${total.toFixed(2)}</span></div>`;
    };

    if (window.basket && window.basket.length > 0) {
        window.renderOrderSummary(window.basket);
    }
});
