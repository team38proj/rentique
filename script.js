function showDetail(id) {
    
    if (id === 'product1') {
        document.getElementById('productCategory').textContent = 'example'
        document.getElementById('productName').textContent = 'example'
        document.getElementById('productPrice').textContent = '£example'
        document.getElementById('productCondition').textContent = 'example'
        document.getElementById('productSize').textContent = 'example'
        document.getElementById('productMaterial').textContent = 'example'
    }
    
    if (id === 'product2') {
        document.getElementById('productCategory').textContent = 'example'
        document.getElementById('productName').textContent = 'example'
        document.getElementById('productPrice').textContent = '£example'
        document.getElementById('productCondition').textContent = 'example'
        document.getElementById('productSize').textContent = 'example'
        document.getElementById('productMaterial').textContent = 'example'
    }
    
    if (id === 'product3') {
        document.getElementById('productCategory').textContent = 'example'
        document.getElementById('productName').textContent = 'example'
        document.getElementById('productPrice').textContent = '£example'
        document.getElementById('productCondition').textContent = 'example'
        document.getElementById('productSize').textContent = 'example'
        document.getElementById('productMaterial').textContent = 'example'
    }
    
    if (id === 'product4') {
        document.getElementById('productCategory').textContent = 'example'
        document.getElementById('productName').textContent = 'example'
        document.getElementById('productPrice').textContent = '£example'
        document.getElementById('productCondition').textContent = 'example'
        document.getElementById('productSize').textContent = 'example'
        document.getElementById('productMaterial').textContent = 'example'
    }
    
    if (id === 'product5') {
        document.getElementById('productCategory').textContent = 'example'
        document.getElementById('productName').textContent = 'example'
        document.getElementById('productPrice').textContent = '£example'
        document.getElementById('productCondition').textContent = 'example'
        document.getElementById('productSize').textContent = 'example'
        document.getElementById('productMaterial').textContent = 'example'
    }
    
    if (id === 'product6') {
        document.getElementById('productCategory').textContent = 'example'
        document.getElementById('productName').textContent = 'example'
        document.getElementById('productPrice').textContent = '£example'
        document.getElementById('productCondition').textContent = 'example'
        document.getElementById('productSize').textContent = 'example'
        document.getElementById('productMaterial').textContent = 'example'
    }
    
    if (id === 'product7') {
        document.getElementById('productCategory').textContent = 'example'
        document.getElementById('productName').textContent = 'example'
        document.getElementById('productPrice').textContent = '£example'
        document.getElementById('productCondition').textContent = 'example'
        document.getElementById('productSize').textContent = 'example'
        document.getElementById('productMaterial').textContent = 'example'
    }
    
    if (id === 'product8') {
        document.getElementById('productCategory').textContent = 'example'
        document.getElementById('productName').textContent = 'example'
        document.getElementById('productPrice').textContent = '£example'
        document.getElementById('productCondition').textContent = 'example'
        document.getElementById('productSize').textContent = 'example'
        document.getElementById('productMaterial').textContent = 'example'
    }
    
    document.getElementById('productGrid').style.display = 'none'
    document.querySelector('.intro').style.display = 'none'
    document.getElementById('productView').classList.remove('hiddenProducts')
}

function goBack() {
    document.getElementById('productGrid').style.display = 'grid'
    document.querySelector('.intro').style.display = 'block'
    document.getElementById('productView').classList.add('hiddenProducts')
    
    return false
}

let resetButton = document.getElementById('resetBtn')
resetButton.addEventListener('click', function() {
    document.getElementById('searchFilter').value = ''
    document.getElementById('categoryFilter').value = 'all'
})

//CHECKOUT PAGE=====
document.addEventListener('DOMContentLoaded', function() {
    // Victor Backend – Grab saved card select and new card section
    const savedCardSelect = document.getElementById('savedCardSelect');
    const newCardSection = document.getElementById('newCardSection');
    const checkoutForm = document.getElementById('checkoutForm');
    const userBillingName = window.userBillingName || '';

    // Victor Backend – Toggle new card section
    if (savedCardSelect && newCardSection) {
        newCardSection.style.display = 'block';
        savedCardSelect.addEventListener('change', function() {
            newCardSection.style.display = this.value ? 'none' : 'block';
        });
    }

    // Victor Backend – Form validation
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            const usingSavedCard = savedCardSelect && savedCardSelect.value;
            let errors = [];

            if (savedCardSelect && savedCardSelect.options.length > 1 && !usingSavedCard) {
                errors.push("Please select a saved card or enter new card details.");
            }

            if (!usingSavedCard) {
                const name = checkoutForm.cardholder_name.value.trim();
                const number = checkoutForm.card_number_real ? checkoutForm.card_number_real.value.trim() : '';
                const type = checkoutForm.card_type.value.trim();
                const expiry = checkoutForm.expiry_date.value;
                const cvv = checkoutForm.cvv.value.trim();

                if (!name || !number || !type || !expiry || !cvv) errors.push("All new card fields are required.");
                if (!/^\d{16}$/.test(number)) errors.push("Card number INVALID!!");

                // Victor Backend – Card type validation
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

    // Victor Backend – Store real card number in hidden input
    const cardNumberInput = document.querySelector('input[name="card_number"]');
    if (cardNumberInput) {
        const realCardInput = document.createElement('input');
        realCardInput.type = 'hidden';
        realCardInput.name = 'card_number_real';
        cardNumberInput.parentNode.appendChild(realCardInput);

        cardNumberInput.addEventListener('input', function(e) {
            const val = e.target.value.replace(/\D/g, '').slice(0, 16);
            realCardInput.value = val;
            e.target.value = val;
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
