<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'connectdb.php';

$errors = [];

/* =========================
   Prevent Direct Access
========================= */
if (
    !isset($_SESSION['mfa_user']) ||
    !isset($_SESSION['mfa_code']) ||
    !isset($_SESSION['mfa_expiry'])
) {
    header("Location: login.php");
    exit;
}

/* =========================
   Expiry Check
========================= */
if (time() > $_SESSION['mfa_expiry']) {

    unset($_SESSION['mfa_user']);
    unset($_SESSION['mfa_code']);
    unset($_SESSION['mfa_expiry']);
    unset($_SESSION['demo_mfa']);

    session_destroy();

    die("Verification code expired. Please log in again.");
}

/* =========================
   Process Form
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF validation failed");
    }

    $entered_code = trim($_POST['code'] ?? '');

    if ($entered_code === '') {
        $errors[] = "Verification code is required.";
    }
    elseif (!ctype_digit($entered_code) || strlen($entered_code) !== 6) {
        $errors[] = "Invalid verification code format.";
    }
    elseif ($entered_code == $_SESSION['mfa_code']) {

        // Successful login
        $_SESSION['uid']   = $_SESSION['mfa_user']['uid'];
        $_SESSION['email'] = $_SESSION['mfa_user']['email'];
        $_SESSION['role']  = $_SESSION['mfa_user']['role'];

        unset($_SESSION['mfa_user']);
        unset($_SESSION['mfa_code']);
        unset($_SESSION['mfa_expiry']);
        unset($_SESSION['demo_mfa']);

        if ($_SESSION['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: user_dashboard.php");
        }
        exit;

    } else {
        $errors[] = "Incorrect verification code.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rentique | Security Verification</title>
    <link rel="stylesheet" href="css/rentique.css">
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

<h2>Security Verification</h2>

<p>Please enter the 6-digit verification code sent to your email.</p>

<?php if (!empty($errors)): ?>
    <p class="error-message">
        <?= implode("<br>", array_map('htmlspecialchars', $errors)); ?>
    </p>
<?php endif; ?>

<!-- =========================
     Demo Display
========================= -->
<?php if (!empty($_SESSION['demo_mfa'])): ?>
    <div style="margin:20px 0; padding:15px; background:#111; border:1px solid #00ff88; color:#00ff88; border-radius:6px;">
        <strong>Demo MFA Code:</strong><br><br>
        <span style="font-size:28px; letter-spacing:5px;">
            <?= htmlspecialchars($_SESSION['mfa_code']); ?>
        </span><br><br>
        <small>This code will expire in 5 minutes.</small>
    </div>
<?php endif; ?>

<form method="POST">

    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

    <div class="field">
        <div class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" height="24" width="24">
                <path d="M240-80q-33 0-56.5-23.5T160-160v-400q0-33 
                23.5-56.5T240-640h40v-80q0-83 
                58.5-141.5T480-920q83 0 
                141.5 58.5T680-720v80h40q33 0 
                56.5 23.5T800-560v400q0 
                33-23.5 56.5T720-80H240Z"/>
            </svg>
        </div>

        <input type="text"
               name="code"
               placeholder="Enter 6-digit code"
               maxlength="6"
               required>
    </div>

    <button type="submit" class="btn">Verify</button>

</form>

</div>
</div>

</body>
</html>



