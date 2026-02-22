<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
require_once 'connectdb.php';

$errorMessage = "";
$step = 1;
$email = "";

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    //  user enters email
    if (isset($_POST['find_account'])) {

        $email = trim($_POST['email'] ?? "");

        if ($email === "") {
            $errorMessage = "Please enter your email.";
            $step = 1;
        } else {
            $stmt = $db->prepare("SELECT uid, secret_answer FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $errorMessage = "Account not found.";
                $step = 1;
            } elseif (empty($user['secret_answer'])) {
                $errorMessage = "This account has no security answer set.";
                $step = 1;
            } else {
                $_SESSION['reset_email'] = $email;
                $step = 2;
            }
        }
    }

    //  answer + new password
    if (isset($_POST['reset_password'])) {

        $email = trim($_SESSION['reset_email'] ?? "");
        $answer = strtolower(trim($_POST['secret_answer'] ?? ""));
        $newPass = $_POST['new_password'] ?? "";
        $confirm = $_POST['confirm_password'] ?? "";

        if ($email === "") {
            $errorMessage = "Session expired. Please try again.";
            $step = 1;
        } elseif ($answer === "") {
            $errorMessage = "Please enter your favourite colour.";
            $step = 2;
        } elseif ($newPass === "" || $confirm === "") {
            $errorMessage = "Please enter and confirm your new password.";
            $step = 2;
        } elseif ($newPass !== $confirm) {
            $errorMessage = "Passwords do not match.";
            $step = 2;
        } else {

            $stmt = $db->prepare("SELECT uid, secret_answer FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $errorMessage = "Account not found.";
                $step = 1;
            } else {
                // secret_answer stored hashed from signup
                if (!password_verify($answer, $user['secret_answer'])) {
                    $errorMessage = "Incorrect security answer.";
                    $step = 2;
                } else {
                    $newHash = password_hash($newPass, PASSWORD_DEFAULT);

                    
                    $up = $db->prepare("UPDATE users SET password = ? WHERE uid = ? LIMIT 1");
                    $up->execute([$newHash, (int)$user['uid']]);

                    unset($_SESSION['reset_email']);
                    header("Location: login.php?reset=1");
                    exit;
                }
            }
        }
    }

    // cancel button
    if (isset($_POST['cancel'])) {
        unset($_SESSION['reset_email']);
        header("Location: login.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Rentique | Forgot Password</title>
<style>
    body{
        margin:0;
        font-family: Arial, sans-serif;
        background:#000;
        color:#fff;
        min-height:100vh;
        display:flex;
        align-items:center;
        justify-content:center;
    }
    .box{
        width:420px;
        background:#111;
        border:2px solid #00ff00;
        border-radius:14px;
        padding:30px;
        box-shadow:0 10px 30px rgba(0,255,0,0.08);
    }
    h1{
        text-align:center;
        color:#00ff00;
        margin:0 0 18px 0;
        font-size:28px;
    }
    .err{
        background:rgba(255,0,0,0.12);
        border:1px solid rgba(255,0,0,0.4);
        padding:10px;
        border-radius:10px;
        margin-bottom:14px;
        text-align:center;
    }
    input{
        width:100%;
        padding:14px;
        border-radius:12px;
        border:1px solid rgba(0,255,0,0.35);
        background:#000;
        color:#fff;
        outline:none;
        margin-top:8px;
    }
    .btn{
        width:100%;
        padding:14px;
        border:none;
        border-radius:12px;
        background:#00ff00;
        color:#000;
        font-weight:bold;
        cursor:pointer;
        margin-top:14px;
    }
    .btn2{
        width:100%;
        padding:14px;
        border-radius:12px;
        background:#222;
        color:#fff;
        border:1px solid rgba(255,255,255,0.2);
        cursor:pointer;
        margin-top:10px;
    }
    a{
        color:#00ff00;
        text-decoration:none;
    }
    p{
        margin:12px 0 0 0;
    }
    .small{
        opacity:0.8;
        font-size:13px;
        margin-top:6px;
        text-align:center;
    }
</style>
</head>
<body>

<div class="box">
    <h1>RESET PASSWORD</h1>

    <?php if ($errorMessage): ?>
        <div class="err"><?= h($errorMessage) ?></div>
    <?php endif; ?>

    <?php if ($step === 1): ?>
        <form method="POST">
            <p>Email</p>
            <input type="email" name="email" required>

            <button class="btn" type="submit" name="find_account">Continue</button>

            <p style="text-align:center; margin-top:14px;">
                <a href="login.php">Back to login</a>
            </p>
        </form>
    <?php else: ?>
        <form method="POST">
            <div class="small">Security question: What is your favourite colour?</div>

            <p>Favourite colour</p>
            <input type="text" name="secret_answer" required>

            <p>New password</p>
            <input type="password" name="new_password" required>

            <p>Confirm password</p>
            <input type="password" name="confirm_password" required>

            <button class="btn" type="submit" name="reset_password">Reset Password</button>
            <button class="btn2" type="submit" name="cancel">Cancel</button>

            <p style="text-align:center; margin-top:14px;">
                <a href="login.php">Back to login</a>
            </p>
        </form>
    <?php endif; ?>
</div>

</body>
</html>