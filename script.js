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

/*CHECKOUT VALIDATION*/

document.addEventListener("DOMContentLoaded", () => {

    const form = document.getElementById('checkoutForm');
    const useSaved = document.getElementById('useSaved');
    const newCard = document.getElementById('newCardSection');

    if (useSaved) {
        useSaved.addEventListener('change', () => {
            newCard.style.display = useSaved.checked ? 'none' : 'block';
        });
    }

    if (form) {
        form.addEventListener('submit', (e) => {

            // Skip validation when using saved card
            if (useSaved && useSaved.checked) return;

            const num = form.cardNumber.value.trim();
            const cvv = form.cvv.value.trim();
            const expiry = new Date(form.expiry.value);

            let errs = [];

            if (!/^[0-9]{16}$/.test(num)) errs.push("Card number must be 16 digits.");
            if (!/^[0-9]{3}$/.test(cvv)) errs.push("CVV must be 3 digits.");
            if (expiry < new Date()) errs.push("Expiry date must be future.");

            if (errs.length > 0) {
                e.preventDefault();
                alert(errs.join("\n"));
            }
        });
    }
});
