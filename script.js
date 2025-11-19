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

    const savedCardSelect = document.getElementById('savedCardSelect');
    const newCardSection = document.getElementById('newCardSection');
    const checkoutForm = document.getElementById('checkoutForm');

    // Victor Backend – user's billing name passed from PHP
    const userBillingName = window.userBillingName || '';

    // ---------- Toggle new card section when saved card is selected ----------
    if (savedCardSelect && newCardSection) {
        newCardSection.style.display = 'block'; // default show new card
        savedCardSelect.addEventListener('change', function() {
            newCardSection.style.display = this.value ? 'none' : 'block';
        });
    }

    // ---------- Form validation ----------
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {

            const usingSavedCard = savedCardSelect && savedCardSelect.value;
            let errors = [];

            // Victor Backend – if using saved card, ensure a selection is made
            if (savedCardSelect && savedCardSelect.options.length > 1 && !usingSavedCard) {
                errors.push("Please select a saved card or enter new card details.");
            }

            // Validate new card fields only if not using a saved card
            if (!usingSavedCard) {
                const name = checkoutForm.cardholder_name.value.trim();
                const number = checkoutForm.card_number_real ? checkoutForm.card_number_real.value.trim() : '';
                const type = checkoutForm.card_type.value.trim();
                const expiry = checkoutForm.expiry_date.value;
                const cvv = checkoutForm.cvv.value.trim();

                if (!name || !number || !type || !expiry || !cvv) {
                    errors.push("All new card fields are required.");
                }

                if (!/^\d{16}$/.test(number)) errors.push("Card number INVALID!!");
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

    // ---------- Store new card number in hidden input (no masking) ----------
    const cardNumberInput = document.querySelector('input[name="card_number"]');

    if (cardNumberInput) {
        // Create a hidden input to store the real card number
        const realCardInput = document.createElement('input');
        realCardInput.type = 'hidden';
        realCardInput.name = 'card_number_real';
        cardNumberInput.parentNode.appendChild(realCardInput);

        // Simply store digits as user types, no masking
        cardNumberInput.addEventListener('input', function(e) {
            const val = e.target.value.replace(/\D/g, '').slice(0, 16); // Only digits, max 16
            realCardInput.value = val;
            e.target.value = val; // Show full number as typed
        });
    }
});
