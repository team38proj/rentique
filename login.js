const form = document.getElementById('loginform');
const email = document.getElementById('email');
const password = document.getElementById('password');
const error_message = document.getElementById('error-message');


const allInputs = [email, password];

allInputs.forEach(input => {
    input.addEventListener('input', () => {
        if (input.parentElement.classList.contains('incorrect')) {
            input.parentElement.classList.remove('incorrect');
            error_message.innerText = '';
        }
    });
});

function getLoginFormErrors(emailValue, passwordValue) {
    let errors = [];

   
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

    return errors;
}

form.addEventListener('submit', event => {
    const emailValue = email.value.trim();
    const passwordValue = password.value.trim();

    const errors = getLoginFormErrors(emailValue, passwordValue);

    if (errors.length > 0) {
        event.preventDefault();
        error_message.innerText = errors.join('. ');
    }
});
