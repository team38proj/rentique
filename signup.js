const form = document.getElementById('loginform');
const username = document.getElementById('username');
const email = document.getElementById('email');
const password = document.getElementById('password');
const confirmPassword = document.getElementById('confirm-password');
const error_message = document.getElementById('error-message');


const allInputs = [username, email, password, confirmPassword];

allInputs.forEach(input => {
    input.addEventListener('input', () => {
        if (input.parentElement.classList.contains('incorrect')) {
            input.parentElement.classList.remove('incorrect');
            error_message.innerText = '';
        }
    });
});

form.addEventListener('submit', function (event) {
    const usernameValue = username.value.trim();
    const emailValue = email.value.trim();
    const passwordValue = password.value.trim();
    const confirmPasswordValue = confirmPassword.value.trim();

    let errors = [];

   
    if (usernameValue === '') {
        errors.push('Username is required');
        username.parentElement.classList.add('incorrect');
    } else {
        username.parentElement.classList.remove('incorrect');
    }

   
    if (emailValue === '') {
        errors.push('Email is required');
        email.parentElement.classList.add('incorrect');
    } else if (!/^\S+@\S+\.\S+$/.test(emailValue)) {
        errors.push('Please enter a valid email address');
        email.parentElement.classList.add('incorrect');
    } else {
        email.parentElement.classList.remove('incorrect');
    }

   
    if (passwordValue === '') {
        errors.push('Password is required');
        password.parentElement.classList.add('incorrect');
    } else {
        password.parentElement.classList.remove('incorrect');
    }

   
    if (confirmPasswordValue === '') {
        errors.push('Repeat password is required');
        confirmPassword.parentElement.classList.add('incorrect');
    } else if (confirmPasswordValue !== passwordValue) {
        errors.push('Passwords do not match');
        confirmPassword.parentElement.classList.add('incorrect');
    } else {
        confirmPassword.parentElement.classList.remove('incorrect');
    }

   
    if (errors.length > 0) {
        event.preventDefault();
        error_message.innerText = errors.join('. ');
    }
});
