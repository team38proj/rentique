<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'connectdb.php';

/* =========================
   Prevent Direct Access
========================= */
if (!isset($_SESSION['secret_mfa_user'])) {
    header("Location: login.php");
    exit;
}

$errors = [];
$userSession = $_SESSION['secret_mfa_user'];

/* =========================
   Process Secret Verification
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $secret_input = strtolower(trim($_POST['secret_answer'] ?? ''));

    if ($secret_input === '') {
        $errors[] = "Secret answer is required.";
    } else {

        // Fetch hashed secret answer from database
        $stmt = $db->prepare("SELECT secret_answer FROM users WHERE uid = ?");
        $stmt->execute([$userSession['uid']]);
        $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dbUser && password_verify($secret_input, $dbUser['secret_answer'])) {

            session_regenerate_id(true);

            /* =========================
               Generate 6-Digit MFA Code
            ========================== */
            $_SESSION['mfa_user'] = [
                'uid'   => $userSession['uid'],
                'email' => $userSession['email'],
                'role'  => $userSession['role']
            ];

            $_SESSION['mfa_code']   = rand(100000, 999999);
            $_SESSION['mfa_expiry'] = time() + 300; // 5 minutes

            // Demo mode (so code shows on screen)
            $_SESSION['demo_mfa'] = true;

            unset($_SESSION['secret_mfa_user']);

            header("Location: verify.php");
            exit;

        } else {
            $errors[] = "Incorrect secret answer.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rentique | Security Verification</title>
<link rel="stylesheet" href="css/rentique.css">

<style>
body {
    background-image:
        linear-gradient(rgba(0,0,0,0.75), rgba(0,0,0,0.75)),
        url("images/Dakar1.png");
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}
</style>

</head>
<body>

<div class="auth-wrapper">
<div class="login-container">

<h2>SECURITY VERIFICATION</h2>

<p>Please enter your secret answer (e.g. favourite colour)</p>

<?php if (!empty($errors)): ?>
<p class="error-message">
    <?= implode("<br>", array_map('htmlspecialchars', $errors)); ?>
</p>
<?php endif; ?>

<form method="POST">

    <div class="field">
        <div class="icon">
            <!-- Lock Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" height="24" width="24">
                <path d="M240-80q-33 0-56.5-23.5T160-160v-400q0-33 
                23.5-56.5T240-640h40v-80q0-83 
                58.5-141.5T480-920q83 
                0 141.5 58.5T680-720v80h40q33 
                0 56.5 23.5T800-560v400q0 
                33-23.5 56.5T720-80H240Z"/>
            </svg>
        </div>

        <input type="text"
               name="secret_answer"
               placeholder="Enter secret answer"
               required>
    </div>

    <button type="submit" class="btn">VERIFY</button>

</form>

<p><a href="login.php">Back to Login</a></p>

</div>
</div>

</body>
</html>

