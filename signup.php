<?php
/* Signup backend – Rentique */

session_start();
require_once 'connectdb.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    if ($username === '') $errors[] = "Username is required";
    if ($email === '') $errors[] = "Email is required";
    if ($password === '') $errors[] = "Password is required";
    if ($confirm === '') $errors[] = "Repeat password is required";
    if ($password !== $confirm) $errors[] = "Passwords do not match";

    if (empty($errors)) {

        try {
            $stmt = $db->prepare("SELECT uid FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            $exists = $stmt->fetch();

            if ($exists) {
                $errors[] = "Email or username already registered";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $db->prepare(
                    "INSERT INTO users (username, password, email) VALUES (?, ?, ?)"
                );

                $stmt->execute([$username, $hashed, $email]);

                $_SESSION['uid'] = $db->lastInsertId();
                $_SESSION['username'] = $username;

                header("Location: index.php");
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = "Database error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup</title>
    <link rel="stylesheet" href="css/rentique.css">
</head>
<body>
<div class="auth-wrapper">
<div class="login-container">

    <h2>Signup</h2>

    <?php if (!empty($errors)): ?>
        <p id="error-message" style="color:red;">
            <?php foreach ($errors as $e) echo htmlspecialchars($e) . ". "; ?>
        </p>
    <?php endif; ?>

    <form method="POST" id="signupForm" novalidate>

        <div class="field">
            <div class="icon">
                <svg xmlns="http://www.w3.org/2000/svg" height="24" width="24">
                    <path d="M480-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM160-160v-112q0-34 17.5-62.5T224-378q62-31 126-46.5T480-440q66 0 130 15.5T736-378q29 15 46.5 43.5T800-272v112H160Z"/>
                </svg>
            </div>
            <input type="text" name="username" id="username" placeholder="Username">
        </div>

        <div class="field">
            <div class="icon">
                <svg xmlns="http://www.w3.org/2000/svg" height="24" width="24">
                    <path d="M160-200q-33 0-56.5-23.5T80-280v-400q0-33 23.5-56.5T160-760h640q33 0 56.5 23.5T880-680v400q0 33-23.5 56.5T800-200H160Zm320-260L160-640v360h640v-360L480-460Z"/>
                </svg>
            </div>
            <input type="email" name="email" id="email" placeholder="Email">
        </div>

        <div class="field">
            <div class="icon">
                <svg xmlns="http://www.w3.org/2000/svg" height="24" width="24">
                    <path d="M240-80q-33 0-56.5-23.5T160-160v-400q0-33 23.5-56.5T240-640h40v-80q0-83 58.5-141.5T480-920q83 0 141.5 58.5T680-720v80h40q33 0 56.5 23.5T800-560v400q0 33-23.5 56.5T720-80H240Zm240-200q33 0 56.5-23.5T560-360q0-33-23.5-56.5T480-440q-33 0-56.5 23.5T400-360q0 33 23.5 56.5T480-280ZM360-640h240v-80q0-50-35-85t-85-35q-50 0-85 35t-35 85v80Z"/>
                </svg>
            </div>
            <input type="password" name="password" id="password" placeholder="Password">
        </div>

        <div class="field">
            <div class="icon">
                <svg xmlns="http://www.w3.org/2000/svg" height="24" width="24">
                    <path d="M240-80q-33 0-56.5-23.5T160-160v-400q0-33 23.5-56.5T240-640h40v-80q0-83 58.5-141.5T480-920q83 0 141.5 58.5T680-720v80h40q33 0 56.5 23.5T800-560v400q0 33-23.5 56.5T720-80H240Zm240-200q33 0 56.5-23.5T560-360q0-33-23.5-56.5T480-440q-33 0-56.5 23.5T400-360q0 33 23.5 56.5T480-280ZM360-640h240v-80q0-50-35-85t-85-35q-50 0-85 35t-35 85v80Z"/>
                </svg>
            </div>
            <input type="password" name="confirm_password" id="confirm-password" placeholder="Repeat password">
        </div>

        <button type="submit" class="btn">Signup</button>
    </form>

    <p>Already have an account? <a href="login.php">login</a></p>

</div>
</div>

<script>
// frontend validation same as original

const form = document.getElementById('signupForm');
const usernameEl = document.getElementById('username');
const emailEl = document.getElementById('email');
const passwordEl = document.getElementById('password');
const confirmEl = document.getElementById('confirm-password');
const errorBox = document.getElementById('error-message');

form.addEventListener('submit', function(e) {
    let errors = [];

    if (usernameEl.value.trim() === '') errors.push("Username is required");
    if (emailEl.value.trim() === '') errors.push("Email is required");
    else if (!/^\S+@\S+\.\S+$/.test(emailEl.value.trim()))
        errors.push("Please enter a valid email address");

    if (passwordEl.value === '') errors.push("Password is required");
    if (confirmEl.value === '') errors.push("Repeat password is required");
    if (passwordEl.value !== confirmEl.value) errors.push("Passwords do not match");

    if (errors.length > 0) {
        e.preventDefault();
        if (errorBox) {
            errorBox.style.color = "red";
            errorBox.innerText = errors.join(". ");
        }
    }
});
</script>

</body>
</html>
