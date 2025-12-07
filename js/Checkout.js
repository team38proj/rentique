document.addEventListener('DOMContentLoaded', function () {

    const savedCardSelect = document.getElementById('savedCardSelect');
    const newCardSection = document.getElementById('newCardSection');
    const checkoutForm = document.getElementById('checkoutForm');

    const billingName = window.userBillingName || '';
    const realCardHidden = document.querySelector('input[name="card_number_real"]');
    const cardNumberInput = document.querySelector('input[name="card_number"]');

    /* Saved Card Toggle */
    if (savedCardSelect && newCardSection) {
        savedCardSelect.addEventListener('change', function () {
            newCardSection.style.display = this.value === '' ? 'block' : 'none';
        });
    }

    /* Force numeric card input */
    if (cardNumberInput && realCardHidden) {
        cardNumberInput.addEventListener('input', function (e) {
            const digits = e.target.value.replace(/\D/g, '').slice(0, 16);
            realCardHidden.value = digits;
            e.target.value = digits;
        });
    }

    /* Validation */
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function (e) {

            let errors = [];

            const usingSaved = savedCardSelect && savedCardSelect.value !== '';

            const name = checkoutForm.cardholder_name.value.trim();
            const number = realCardHidden.value.trim();
            const type = checkoutForm.card_type.value.trim();
            const expiry = checkoutForm.expiry_date.value;
            const cvv = checkoutForm.cvv.value.trim();

            const newCardFilled = name || number || type || expiry || cvv;

            if (!usingSaved && !newCardFilled) {
                errors.push('Select a saved card or enter card details');
            }

            if (!usingSaved && newCardFilled) {

                if (!name || !number || !type || !expiry || !cvv) {
                    errors.push('All fields are required');
                }

                if (!/^\d{13,16}$/.test(number)) {
                    errors.push('Invalid card number');
                }

                if (type === 'Visa' && !number.startsWith('4')) {
                    errors.push('Invalid Visa number');
                }

                if (type === 'MasterCard') {
                    const prefix = parseInt(number.substring(0, 2), 10);
                    if (prefix < 51 || prefix > 55) {
                        errors.push('Invalid MasterCard number');
                    }
                }

                if (!/^\d{3}$/.test(cvv)) {
                    errors.push('Invalid CVV');
                }

                if (expiry && new Date(expiry + '-01') < new Date()) {
                    errors.push('Expiry date invalid');
                }

                if (billingName && name !== billingName) {
                    errors.push('Cardholder name must match billing name');
                }
            }

            if (errors.length > 0) {
                e.preventDefault();
                alert(errors.join('\n'));
            }
        });
    }

    /* Render Basket Summary */
    if (window.basket) {
        renderSummary(window.basket);
    }

    function renderSummary(basket) {
    const delivery = 4.99;
    let subtotal = 0;

    const container = document.getElementById('orderItems');
    const deliveryBox = document.getElementById('orderDelivery');
    const totalBox = document.getElementById('orderTotal');

    container.innerHTML = '';

    basket.forEach(item => {

        subtotal += parseFloat(item.price);

        const div = document.createElement('div');
        div.classList.add('summaryItem');

        div.innerHTML = `
            <span class="summaryTitle">${item.title}</span>
            <span class="summaryPrice">£${parseFloat(item.price).toFixed(2)}</span>
        `;

        container.appendChild(div);
    });

    deliveryBox.innerHTML =
        `<span>Delivery</span><span>£${delivery.toFixed(2)}</span>`;

    totalBox.innerHTML =
        `<span>Total</span><span>£${(subtotal + delivery).toFixed(2)}</span>`;
}

});
