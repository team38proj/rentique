<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'connectdb.php';

$errors = [];

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token");
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $secret_answer = strtolower(trim($_POST['secret_answer'] ?? ''));

    if ($username === '') $errors[] = "Username is required";
    if ($email === '') $errors[] = "Email is required";
    if ($password === '') $errors[] = "Password is required";
    if ($secret_answer === '') $errors[] = "Secret answer is required";

    if (empty($errors)) {

        $check = $db->prepare("SELECT uid FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {
            $errors[] = "Email already registered";
        } else {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $hashed_secret   = password_hash($secret_answer, PASSWORD_DEFAULT);

            $stmt = $db->prepare("
                INSERT INTO users (username, email, password, role, secret_answer)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $username,
                $email,
                $hashed_password,
                'customer',
                $hashed_secret
            ]);

            header("Location: login.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rentique | Sign Up</title>
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

<h2>CREATE ACCOUNT</h2>

<?php if (!empty($errors)): ?>
<p class="error-message">
    <?= implode("<br>", array_map('htmlspecialchars', $errors)); ?>
</p>
<?php endif; ?>

<form method="POST">

<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

<!-- Username -->
<div class="field">
    <div class="icon">
        <svg xmlns="http://www.w3.org/2000/svg" height="24" width="24">
            <path d="M480-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47Z"/>
        </svg>
    </div>
    <input type="text" name="username" placeholder="Username" required>
</div>

<!-- Email -->
<div class="field">
    <div class="icon">
        <svg xmlns="http://www.w3.org/2000/svg" height="24" width="24">
            <path d="M160-200v-400h640v400H160Z"/>
        </svg>
    </div>
    <input type="email" name="email" placeholder="Email" required>
</div>

<!-- Password -->
<div class="field">
    <div class="icon">
        <svg xmlns="http://www.w3.org/2000/svg" height="24" width="24">
            <path d="M240-80v-400h480v400H240Z"/>
        </svg>
    </div>
    <input type="password" name="password" placeholder="Password" required>
</div>

<!-- Secret Answer -->
<div class="field">
    <div class="icon">
        <svg xmlns="http://www.w3.org/2000/svg" height="24" width="24">
            <path d="M480-640q-66 0-113 47t-47 113h320q0-66-47-113t-113-47Z"/>
        </svg>
    </div>
    <input type="text" name="secret_answer"
           placeholder="What is your favourite colour?" required>
</div>

<button type="submit" class="btn">SIGN UP</button>

</form>

<p>Already have an account? <a href="login.php">Login</a></p>

</div>
</div>

</body>
</html>


