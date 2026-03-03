<?php
/* Account Management Page */

session_start();

/*Expel user from page if not an admin*/
if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
require('connectdb.php');

$errors = [];
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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
    <style>
        body {
            background-color: #2f2f4f;
        }
        body { font-family: Arial, sans-serif; margin: 18px; color: #111; }
        h2 { margin: 0 0 10px; }
        h3 { margin: 18px 0 8px; }
        .meta { margin-bottom: 12px; }

        .kpi { border-collapse: collapse; width: 100%; max-width: 900px; }
        .kpi td { border: 1px solid #ddd; padding: 8px; }

        .tbl { border-collapse: collapse; width: 100%; max-width: 900px; margin-top: 10px; }
        .tbl td, .tbl th { border: 1px solid #ddd; padding: 8px; }
        .tbl th { background: #f3f3f3; text-align: left; }

        .right { text-align: right; }

        .actions { margin: 12px 0; }
        .actions button {
        padding: 10px 14px;
        border-radius: 10px;
        border: 1px solid #222;
        background: #222;
        color: #fff;
        cursor: pointer;
        }
        .actions button:hover { opacity: 0.9; }

        @media print { .actions { display:none; } }

        .admin-wrapper {
        background: #fff;
        color: #111;
        padding: 20px;
        border-radius: 10px;
        width: fit-content;
        margin: 40px auto;
        }

        /* Cross‑browser placeholder styling - lighter and visible */
        input::placeholder {
            color: #666 !important;
            opacity: 1 !important;
            font-weight: 400;
        }

        /* For older browsers */
        input::-webkit-input-placeholder {
            color: #666 !important;
            opacity: 1 !important;
            font-weight: 400;
        }

        input::-moz-placeholder {
            color: #666 !important;
            opacity: 1 !important;
            font-weight: 400;
        }

        input:-ms-input-placeholder {
            color: #666 !important;
            opacity: 1 !important;
            font-weight: 400;
        }

        input:-moz-placeholder {
            color: #666 !important;
            opacity: 1 !important;
            font-weight: 400;
        }

        /*return to dashboard link styling */
        .return-btn {
        padding: 10px 16px;
        background: #222;
        color: #fff;
        border: 1px solid #000;
        border-radius: 3px;
        cursor: pointer;
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
