document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('loginform');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const errorBox = document.getElementById('error-message');

    if (!form) return;

    form.addEventListener('submit', function (e) {
        let errors = [];

        const emailValue = email.value.trim();
        const passValue = password.value.trim();

        if (emailValue === '') {
            errors.push('Email is required');
        }

        if (passValue === '') {
            errors.push('Password is required');
        }

        // Only block the submit if required fields are empty
        if (errors.length > 0) {
            e.preventDefault();
            errorBox.innerText = errors.join('. ');
            return;
        }

   
    });
});
