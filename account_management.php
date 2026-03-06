<?php
/* Account Management Page 
Jay - backend 
Saja - frontend 
*/

session_start();

/*Expel user from page if not an admin*/
if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
require('connectdb.php');

$errors = [];
$success = "";

if (isset($_POST['create_admin'])) {

    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $username = trim($_POST['admin_username']);
    $email = trim($_POST['admin_email']);
    $password = $_POST['admin_password'];

    if (empty($username) || empty($email) || empty($password)) {
        $errors[] = "All fields are required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    $stmt = $db->prepare("SELECT uid FROM users WHERE email=?");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        $errors[] = "Email already exists.";
    }

    if (empty($errors)) {

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("
            INSERT INTO users (username,email,password,role)
            VALUES (?, ?, ?, 'admin')
        ");

        if ($stmt->execute([$username,$email,$hashed])) {
            $success = "Admin account created successfully.";
        }
    }
}
/* Collect $users variable from users table */
$stmt = $db->prepare("
    SELECT 
        u.uid,
        u.username,
        u.email,
        u.role,
        u.created_at,
        COUNT(p.uid) AS product_count
    FROM users u
    LEFT JOIN products p ON p.uid = u.uid
    GROUP BY u.uid
    ORDER BY u.uid ASC
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$csrf_token = generate_csrf_token();
/* Handle POST actions */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $uid = (int)$_POST['uid'];
    $action = $_POST['action'];

    /* UPDATE USERNAME + EMAIL */
    if ($action === 'update_user') {
    $new_username = trim($_POST['new_username']);
    $new_email = trim($_POST['new_email']);

    /* Check if username already exists (excluding this user) */
        if ($new_username !== '') {
            $check = $db->prepare("SELECT uid FROM users WHERE username = ? AND uid != ?");
            $check->execute([$new_username, $uid]);

            if ($check->fetch()) {
                die("Username already taken.");
            }

            $stmt = $db->prepare("UPDATE users SET username = ? WHERE uid = ?");
            $stmt->execute([$new_username, $uid]);
        }

    /* Check if email already exists*/
        if ($new_email !== '') {
            $check = $db->prepare("SELECT uid FROM users WHERE email = ? AND uid != ?");
            $check->execute([$new_email, $uid]);

            if ($check->fetch()) {
                die("Email already in use.");
            }

            $stmt = $db->prepare("UPDATE users SET email = ? WHERE uid = ?");
            $stmt->execute([$new_email, $uid]);
        }
    }

    /* RESET PASSWORD */
        if ($action === 'reset_password') {
            $new_password = trim($_POST['new_password']);

            if ($new_password === '') {
                die("Password cannot be empty.");
            }

            $hashed = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $db->prepare("UPDATE users SET password = ? WHERE uid = ?");
            $stmt->execute([$hashed, $uid]);
        }

    /* RESET PASSWORD */
        if ($action === 'reset_secret') {
            $new_secret = trim($_POST['new_secret']);

            if ($new_secret === '') {
                die("Secret answer cannot be empty.");
            }

            $hashed_secret = password_hash($new_secret, PASSWORD_DEFAULT);

            $stmt = $db->prepare("UPDATE users SET secret_answer = ? WHERE uid = ?");
            $stmt->execute([$hashed_secret, $uid]);
        }


    /* DELETE USER */
    if ($action === 'delete_user') {
        $stmt = $db->prepare("DELETE FROM users WHERE uid = ?");
        $stmt->execute([$uid]);
    }

    header("Location: account_management.php");
    exit;
    }
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
    <script>
        // Apply saved theme immediately to prevent flash
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.classList.add('light-mode');
        }
    </script>
    <style>
.account-page .dashboard-container{
display:flex;
min-height:100vh;
}

.account-page .sidebar{
width:260px;
flex-shrink:0;
}

.account-page .main-content{
flex:1;
padding:30px;
}

.account-page .section-block{
width:100%;
background:#0f0f0f;
padding:25px;
border-radius:14px;
margin-bottom:30px;
}

.admin-create-form{
display:flex;
flex-direction:column;
gap:15px;
max-width:500px;
margin-top:20px;
}

.admin-create-form input{
padding:14px;
border-radius:10px;
border:1px solid #333;
background:#0f0f0f;
color:#fff;
}

.btn-primary{
background:#a6ff00;
color:#000;
padding:14px;
border-radius:30px;
border:none;
font-weight:600;
cursor:pointer;
}

.table-wrapper{
width:100%;
overflow-x:auto;
margin-top:20px;
}

.account-page .main-table{
width:100%;
border-collapse:collapse;
min-width:1100px;
}

.account-page .main-table th,
.account-page .main-table td{
padding:12px 14px;
text-align:left;
white-space:nowrap;
}

.account-page .main-table th{
background:#151515;
}

.account-page .main-table tr:nth-child(even){
background:#111;
}

.account-page .action-buttons{
display:flex;
flex-direction:column;
gap:6px;
max-width:150px;
}

.account-page .main-table{
    width:100%;
    min-width:1400px;
    border-collapse:collapse;
}

.account-page .main-table th,
.account-page .main-table td{
    padding:16px 18px;
    vertical-align:top;
}

.account-page .action-buttons{
    display:flex;
    flex-direction:column;
    gap:10px;
}

.account-page .action-buttons input{
    width:100%;
    min-width:160px;
}

.account-page .btn-action{
    width:120px;
}

.account-page .btn-delete{
    width:120px;
}

.account-page .main-table th{
    white-space:nowrap;
}

.account-page .action-buttons button{
    align-self:center;
    width:120px;
}

.account-page .btn-action{
    padding:10px 18px;
    border-radius:10px;
    border:1px solid #a3ff00;
    background:#1a1a1a;
    color:#ddd;
    cursor:pointer;
    transition:0.2s;
}

.account-page .btn-action:hover{
    background:#2a2a2a;
}

.account-page .btn-delete{
    padding:10px 18px;
    border-radius:10px;
    border:none;
    background:#b3261e;
    color:white;
    cursor:pointer;
}

.account-page .btn-delete:hover{
    background:#d63a31;
}

:root.light-mode .account-page .main-table th{
    background:#f4f4f4;
    color:#111;
}

:root.light-mode .account-page .main-table td{
    background:#ffffff;
    color:#111;
}

:root.light-mode .account-page .main-table th,
:root.light-mode .account-page .main-table td{
    border-bottom:1px solid #ddd;
}

:root.light-mode .account-page .action-buttons button{
    background:#252;
    color:#fff;
    border:1px solid #444;
}

:root.light-mode .account-page .action-buttons button:hover{
    background:#a3ff00;
}

:root.light-mode .account-page .btn-delete{
    background:#b3261e;
    color:#fff;
}

:root.light-mode .account-page .btn-delete:hover{
    background:#d63a31;
}
    </style>
</head>

<body>

  <h2>Account Management</h2>

    <h4>
    <form action="admin_dashboard.php" method="GET">
        <button class="return-btn">Return to Dashboard</button>
    </form>
    </h4>

  <div class="admin-wrapper">

  <div class="meta">
    Showing <?= count($users) ?> users
  </div>

  <div class="actions">
    <button onclick="window.location.reload()">Refresh</button>
  </div>

  <table class="tbl">
    <thead>
      <tr>
        <th>UID</th>
        <th>Username</th>
        <th>Email</th>
        <th>Role</th>
        <th>Created At</th>
        <th>Products Listed</th>
        <th>Action: Edit Details</th>
        <th>Action: Password Reset </th>
        <th>Action: Secret Answer Reset
        <th>Action: Delete User </th>
      </tr>
    </thead>

    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= htmlspecialchars($u['uid']) ?></td>
          <td><?= htmlspecialchars($u['username']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><?= htmlspecialchars($u['role']) ?></td>
          <td><?= htmlspecialchars($u['created_at']) ?></td>
          <td><?= htmlspecialchars($u['product_count']) ?></td>

            <td>
            <form method="POST" style="display:flex; gap:3px; align-items:center;">
                <input type="hidden" name="uid" value="<?= $u['uid'] ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <input type="text" name="new_username" placeholder="New username">
                <input type="text" name="new_email" placeholder="New e-mail">

                <button name="action" value="update_user" style="background:#222; color:#fff;">Confirm Changes</button>
            </form>
            </td>

            <td>
            <form method="POST">
                <input type="hidden" name="uid" value="<?= $u['uid'] ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <input type="password" name="new_password" placeholder="New password">

                <button name="action" value="reset_password" style="background:#222; color:#fff;">
                    Reset
                </button>
            </form>
            </td>

            <td>
            <form method="POST">
                <input type="hidden" name="uid" value="<?= $u['uid'] ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <input type="text" name="new_secret" placeholder="New secret answer">

                <button name="action" value="reset_secret" style="background:#222; color:#fff;">
                    Reset
                </button>
            </form>
            </td>


            <td>
            <form method="POST" onsubmit="return confirm('Delete this user?');">
                <input type="hidden" name="uid" value="<?= $u['uid'] ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <button name="action" value="delete_user" style="background:#b30000; color:#fff;">
                    Delete
                </button>
            </form>
            </td>


        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

    </div>

</body>
</html>
