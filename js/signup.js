document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('loginform');
    const username = document.getElementById('username');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm-password');
    const errorBox = document.getElementById('error-message');

    if (!form) return;

    form.addEventListener('submit', function (e) {
        let errors = [];

        if (username.value.trim() === '') {
            errors.push('Username is required');
        }

        if (email.value.trim() === '') {
            errors.push('Email is required');
        } else if (!/^\S+@\S+\.\S+$/.test(email.value.trim())) {
            errors.push('Invalid email format');
        }

        if (password.value.trim() === '') {
            errors.push('Password is required');
        }

        if (confirmPassword.value.trim() === '') {
            errors.push('Repeat password is required');
        } else if (confirmPassword.value.trim() !== password.value.trim()) {
            errors.push('Passwords do not match');
        }

        if (errors.length > 0) {
            e.preventDefault();
            errorBox.innerText = errors.join('. ');
        }
    });
});
