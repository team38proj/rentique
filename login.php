<?php
/*
>>>> Login page
*/
session_start();
require('connectdb.php');

$errors = [];

/* PROCESS LOGIN */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF validation failed");
    }

    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '') {
        $errors[] = "Email is required";
    }

    if ($password === '') {
        $errors[] = "Password is required";
    }

    if (empty($errors)) {

        // Fetch user including role
        $stmt = $db->prepare("SELECT uid, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {

            $_SESSION['uid'] = $user['uid'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            session_regenerate_id(true);

            // REDIRECT BASED ON ROLE
            if ($user['role'] == 'admin') {
                header("Location: admin_dashboard.php");
                exit;
            } else {
                header("Location: user_dashboard.php");
                exit;
            }

        } else {
            $errors[] = "Invalid email or password";
        }
    }
}

$csrf_token = generate_csrf_token();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentique | Login</title>
    <link rel="stylesheet" href="css/rentique.css">
    <script src="js/login.js" defer></script>
    <script src="js/script.js" defer></script>
        <style>
    body {
      background-image: url("images/Dakar1.png");
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
    }
    </style>
</head>

<body>
<div class="auth-wrapper">
    <div class="login-container">

        <h2>Login</h2>

        
        <form id="loginform" method="POST" action="login.php" novalidate>

            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <!-- Email field -->
            <div class="field">
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px">
                        <path d="M160-200q-33 0-56.5-23.5T80-280v-400q0-33 23.5-56.5T160-760h640q33 0 56.5 23.5T880-680v400q0 33-23.5 56.5T800-200H160Zm320-260L160-640v360h640v-360L480-460Z"/>
                    </svg>
                </div>
                <input type="email" id="email" name="email" placeholder="Email">
            </div>

            <!-- Password -->
            <div class="field">
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px">
                        <path d="M240-80q-33 0-56.5-23.5T160-160v-400q0-33 23.5-56.5T240-640h40v-80q0-83 58.5-141.5T480-920q83 0 141.5 58.5T680-720v80h40q33 0 56.5 23.5T800-560v400q0 33-23.5 56.5T720-80H240Zm240-200q33 0 56.5-23.5T560-360q0-33-23.5-56.5T480-440q-33 0-56.5 23.5T400-360q0 33 23.5 56.5T480-280ZM360-640h240v-80q0-50-35-85t-85-35q-50 0-85 35t-35 85v80Z"/>
                    </svg>
                </div>
                <input type="password" id="password" name="password" placeholder="Password">
            </div>

            <button type="submit" class="btn">Login</button>
        </form>

        <p id="error-message">
            <?php
            if (!empty($errors)) {
                echo implode("<br>", array_map('htmlspecialchars', $errors));
            }
            ?>
        </p>

        <p>New here? <a href="signup.php">Create an Account</a></p>

    </div>
</div>
</body>
</html>
