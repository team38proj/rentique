document.addEventListener('DOMContentLoaded', function () {

    const savedCardSelect = document.getElementById('savedCardSelect');
    const newCardSection = document.getElementById('newCardSection');
    const checkoutForm = document.getElementById('checkoutForm');

    const billingName = window.userBillingName || '';
    const realCardHidden = document.querySelector('input[name="card_number_real"]');
    const cardNumberInput = document.querySelector('input[name="card_number"]');

    const PLATFORM_FEE_PER_ITEM = Number(window.platformFeePerItem || 4.99);

    const impactToggleBtn = document.getElementById('impactToggleBtn');
    const impactBox = document.getElementById('impactBox');

    if (impactToggleBtn && impactBox) {
        impactBox.style.display = 'none';

        impactToggleBtn.addEventListener('click', function () {
            const isOpen = impactBox.style.display === 'block';

            if (isOpen) {
                impactBox.style.display = 'none';
                impactToggleBtn.textContent = 'Read more';
            } else {
                impactBox.style.display = 'block';
                impactToggleBtn.textContent = 'Show less';
            }
        });
    }

    if (savedCardSelect && newCardSection) {
        savedCardSelect.addEventListener('change', function () {
            newCardSection.style.display = this.value === '' ? 'block' : 'none';
        });
    }

    if (cardNumberInput && realCardHidden) {
        cardNumberInput.addEventListener('input', function (e) {
            const digits = e.target.value.replace(/\D/g, '').slice(0, 16);
            realCardHidden.value = digits;
            e.target.value = digits;
        });
    }

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

                if (expiry) {
                    const expiryDate = new Date(expiry + '-01');
                    const now = new Date();
                    now.setDate(1);
                    now.setHours(0, 0, 0, 0);

                    if (expiryDate < now) {
                        errors.push('Expiry date invalid');
                    }
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

    if (window.basket) {
        renderSummary(window.basket);
        renderImpact(window.basket);
    }

    function renderSummary(basket) {
        const shipping = basket.length ? 4.99 : 0;
        let subtotal = 0;
        let itemCount = 0;

        const container = document.getElementById('orderItems');
        const deliveryBox = document.getElementById('orderDelivery');
        const feeBox = document.getElementById('orderPlatformFee');
        const totalBox = document.getElementById('orderTotal');

        if (container) container.innerHTML = '';

        basket.forEach(item => {
            const qty = item.quantity ? Math.max(1, parseInt(item.quantity, 10)) : 1;
            const days = item.rental_days ? Math.max(1, parseInt(item.rental_days, 10)) : 1;
            const perDay = item.price ? parseFloat(item.price) : 0;

            const lineTotal = perDay * days * qty;
            subtotal += lineTotal;
            itemCount += qty;

            if (container) {
                const div = document.createElement('div');
                div.classList.add('summaryLine');

                const itemLabel = qty > 1
                    ? escapeHtml(item.title || '') + ' (' + days + ' days, x' + qty + ')'
                    : escapeHtml(item.title || '') + ' (' + days + ' days)';

                div.innerHTML =
                    '<span>' + itemLabel + '</span>' +
                    '<span>£' + lineTotal.toFixed(2) + '</span>';

                container.appendChild(div);
            }
        });

        if (container) {
            const subtotalDiv = document.createElement('div');
            subtotalDiv.classList.add('summaryLine');
            subtotalDiv.innerHTML =
                '<span>Subtotal</span>' +
                '<span>£' + subtotal.toFixed(2) + '</span>';
            container.appendChild(subtotalDiv);
        }

        const platformFeeTotal = itemCount * PLATFORM_FEE_PER_ITEM;
        const total = subtotal + shipping + platformFeeTotal;

        if (deliveryBox) {
            deliveryBox.innerHTML =
                '<div class="summaryLine">' +
                    '<span>Shipping</span>' +
                    '<span>£' + shipping.toFixed(2) + '</span>' +
                '</div>';
        }

        if (feeBox) {
            feeBox.innerHTML =
                '<div class="summaryLine">' +
                    '<span>Platform fee (' + itemCount + ' items)</span>' +
                    '<span>£' + platformFeeTotal.toFixed(2) + '</span>' +
                '</div>';
        }

        if (totalBox) {
            totalBox.innerHTML =
                '<div class="summaryTotal">' +
                    '<span>Total</span>' +
                    '<span class="price">£' + total.toFixed(2) + '</span>' +
                '</div>';
        }
    }

    function renderImpact(basket) {
        let totalItems = 0;
        let estimatedOrderValue = 0;

        basket.forEach(item => {
            const qty = item.quantity ? Math.max(1, parseInt(item.quantity, 10)) : 1;
            const days = item.rental_days ? Math.max(1, parseInt(item.rental_days, 10)) : 1;
            const price = item.price ? parseFloat(item.price) : 0;

            totalItems += qty;
            estimatedOrderValue += (price * qty * days);
        });

        const platformFees = totalItems * PLATFORM_FEE_PER_ITEM;
        const estimatedCheckoutTotal = estimatedOrderValue + platformFees;

        const estimatedCo2Saved = totalItems * 4.2;
        const estimatedWasteReduced = totalItems * 0.8;
        const estimatedDonation = estimatedCheckoutTotal * 0.05;

        const impactCo2 = document.getElementById('impactCo2');
        const impactWaste = document.getElementById('impactWaste');
        const impactDonation = document.getElementById('impactDonation');

        if (impactCo2) impactCo2.textContent = estimatedCo2Saved.toFixed(1) + ' kg';
        if (impactWaste) impactWaste.textContent = estimatedWasteReduced.toFixed(1) + ' kg';
        if (impactDonation) impactDonation.innerHTML = '£' + estimatedDonation.toFixed(2);
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
