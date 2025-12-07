document.addEventListener('DOMContentLoaded', function () {
    const loginBtn = document.querySelector('.btn.login');
    const signupBtn = document.querySelector('.btn.signup');

    if (loginBtn) {
        loginBtn.addEventListener('click', function () {
            window.location.href = 'login.php';
        });
    }

    if (signupBtn) {
        signupBtn.addEventListener('click', function () {
            window.location.href = 'signup.php';
        });
    }
});
