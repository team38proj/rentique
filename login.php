<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'connectdb.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed");
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '') $errors[] = "Email is required";
    if ($password === '') $errors[] = "Password is required";

    if (empty($errors)) {

        $stmt = $db->prepare("SELECT uid, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {

            session_regenerate_id(true);

            if ($user['role'] === 'admin') {

                $_SESSION['uid']   = $user['uid'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role']  = $user['role'];

                header("Location: admin_dashboard.php");
                exit;
            }

            $_SESSION['secret_mfa_user'] = [
                'uid'   => $user['uid'],
                'email' => $user['email'],
                'role'  => $user['role']
            ];

            header("Location: secret_verify.php");
            exit;

        } else {
            $errors[] = "Invalid email or password";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentique | Login</title>
    <link rel="icon" type="image/png" href="/images/rentique_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

          /* krish's revamped login page */

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #000;
            position: relative;
        }

        /* took image from about us to use as bg overlay */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("images/Dakar1.png");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.4;
            z-index: -2;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 30% 50%, rgba(0, 255, 0, 0.1) 0%, transparent 60%);
            z-index: -1;
        }

        .auth-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 20px;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-container {
            background: #111;
            border: 2px solid #00FF00;
            border-radius: 16px;
            padding: 40px 35px;
            box-shadow: 0 20px 40px rgba(0, 255, 0, 0.1);
            transition: all 0.3s ease;
        }

        .login-container:hover {
            border-color: #00cc00; 
            box-shadow: 0 25px 50px rgba(0, 255, 0, 0.15);
        }


        .logo-area {
            text-align: center;
            margin-bottom: 25px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }

        .logo-image {
            width: 80px;
            height: 80px;
            background: #000;
            border: 2px solid #00FF00;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 8px;
            transition: border-color 0.3s ease;
        }

        .login-container:hover .logo-image {
            border-color: #00cc00; 
        }

        .logo-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .logo-text {
            font-size: 28px;
            font-weight: bold;
            color: #00FF00;
            text-transform: lowercase;
            letter-spacing: 1px;
            transition: color 0.3s ease;
        }

        .login-container:hover .logo-text {
            color: #00cc00; 
        }


        .signup-link {
        display: inline-block;
        padding: 8px 22px;       
        border: 2px solid #00FF00;
        border-radius: 30px;
        margin-top: 4px;            
        font-weight: 600;
        font-size: 14px;            
        line-height: 1.2;     
        transition: all 0.3s ease;
        }

        .signup-link:hover {
        background: #00cc00 !important; 
    border-color: #00cc00 !important;
    color: #000 !important;
}

.signup-link:hover::after {
    display: none;
}

        h2 {
            color: #fff;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            padding-bottom: 12px;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: #00FF00;
            border-radius: 3px;
            transition: background 0.3s ease, width 0.3s ease;
        }

        .login-container:hover h2::after {
            background: #00cc00; 
            width: 80px;
        }

        .error-message {
            background: rgba(255, 0, 0, 0.1);
            border-left: 4px solid #ff4444;
            color: #ff8888;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-message i {
            color: #ff4444;
            font-size: 16px;
        }

        .field {
            margin-bottom: 20px;
        }

        .field input {
            width: 100%;
            height: 50px;
            padding: 0 16px;
            background: #1a1a1a;
            border: 2px solid #333;
            border-radius: 8px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            color: #fff;
            transition: all 0.3s ease;
        }

        .field input:hover {
            border-color: #00cc00;
            background: #222;
        }

        .field input:focus {
            outline: none;
            border-color: #00FF00;
            background: #222;
            box-shadow: 0 0 0 3px rgba(0, 255, 0, 0.1);
        }

        .field input::placeholder {
            color: #666;
        }

        .btn {
            width: 100%;
            height: 50px;
            background: #00FF00;
            color: #000;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn:hover {
            background: #00cc00;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 204, 0, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn i {
            font-size: 14px;
            transition: transform 0.3s ease;
        }

        .btn:hover i {
            transform: translateX(5px);
        }

        .links {
            text-align: center;
            margin-top: 25px;
        }

        .links p {
            color: #888;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .links a {
            color: #00FF00;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
        }

        .links a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #00FF00;
            transition: width 0.3s ease, background 0.3s ease;
        }

        .links a:hover {
            color: #00cc00; 
        }

        .links a:hover::after {
            width: 100%;
            background: #00cc00;
        }

        .signup-link {
            display: inline-block;
            padding: 8px 24px;
            border: 2px solid #00FF00;
            border-radius: 30px;
            margin-top: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .signup-link:hover {
            background: #00cc00 !important; 
            border-color: #00cc00 !important;
            color: #000 !important;
        }

        .signup-link:hover::after {
            display: none;
        }

        html.light-mode body {
            background: #f5f5f5;
        }

        html.light-mode .login-container {
            background: #fff;
            border-color: #00FF00;
        }

        html.light-mode .login-container:hover {
            border-color: #00cc00;
        }

        html.light-mode h2 {
            color: #000;
        }

        html.light-mode .logo-image {
            background: #fff;
            border-color: #00FF00;
        }

        html.light-mode .login-container:hover .logo-image {
            border-color: #00cc00;
        }

        html.light-mode .logo-text {
            color: #00FF00;
        }

        html.light-mode .login-container:hover .logo-text {
            color: #00cc00;
        }

        html.light-mode .field input {
            background: #f5f5f5;
            border-color: #ddd;
            color: #000;
        }

        html.light-mode .field input:hover {
            border-color: #00cc00;
            background: #fff;
        }

        html.light-mode .field input:focus {
            background: #fff;
            border-color: #00FF00;
        }

        html.light-mode .field input::placeholder {
            color: #999;
        }

        html.light-mode .links p {
            color: #666;
        }

        html.light-mode .links a {
            color: #00FF00;
        }

        html.light-mode .links a:hover {
            color: #00cc00;
        }

        html.light-mode .signup-link {
            border-color: #00FF00;
        }

        html.light-mode .signup-link:hover {
            background: #00cc00 !important;
            border-color: #00cc00 !important;
        }

        @media (max-width: 480px) {
            .auth-wrapper {
                padding: 15px;
            }
            
            .login-container {
                padding: 30px 25px;
            }
            
            .logo-image {
                width: 70px;
                height: 70px;
            }
            
            .logo-text {
                font-size: 24px;
            }
            
            h2 {
                font-size: 22px;
            }
        }

 
        .btn.loading {
            position: relative;
            color: transparent;
            pointer-events: none;
        }

        .btn.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid #000;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.8s infinite linear;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

<div class="auth-wrapper">
    <div class="login-container">
        
        <div class="logo-area">
            <div class="logo-image">
                <img src="/images/rentique_logo.png" alt="Rentique Logo">
            </div>
            <div class="logo-text">rentique.</div>
        </div>

        <h2>Sign In To Your Account</h2>

        <?php if (!empty($errors)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= implode("<br>", array_map('htmlspecialchars', $errors)); ?></span>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['reset']) && $_GET['reset'] == '1'): ?>
  <p style="text-align:center; color:#a3ff00; margin-top:12px;">
    Password reset successful. Please log in.
  </p>
<?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

            <div class="field">
                <input type="email" name="email" placeholder="Email address" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="field">
                <input type="password" name="password" placeholder="Password" required>
            </div>

            <button type="submit" class="btn">
                <i class="fas fa-arrow-right"></i> Log in
            </button>
        </form>
        
 

        <div class="links">
            <a href="forgot_password.php" class="signup-link">Forgot password?</a>

            <p>Don't have an account?</p>
            <a href="signup.php" class="signup-link">Create Account</a>
        </div>

    </div>
</div>

<script>
// changes theme
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'light') {
        document.documentElement.classList.add('light-mode');
    }
    const form = document.querySelector('form');
    const submitBtn = document.querySelector('.btn');
    
    form.addEventListener('submit', function(e) {
        if (!submitBtn.classList.contains('loading')) {
            submitBtn.classList.add('loading');
        }
    });
});
</script>

</body>
</html>
