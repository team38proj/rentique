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
        max-width: 100%;
        margin: 40px auto;
        }

        /* Cross‑browser placeholder styling */
        input::placeholder {
        color: #444;
        opacity: 1;
        }

    </style>
</head>

<body>

  <h2>Account Management</h2>

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

                <button name="action" value="reset_password" style="background:#222; color:#fff;">
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