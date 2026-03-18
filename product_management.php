<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

session_start();
require_once 'connectdb.php';

/* ========== ADMIN SECURITY CHECK ========== */

if (!isset($_SESSION['uid'])) {
    header("Location: login.php");
    exit;
}

$admin_uid = (int)$_SESSION['uid'];

$stmt = $db->prepare("SELECT role FROM users WHERE uid = ?");
$stmt->execute([$admin_uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'admin') {
    die("Access denied.");
}

/* ========== HELPER ========== */
function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* ========== MESSAGES ========== */
$success = "";
$error = "";

/* ========== CLONE PRODUCT ========== */
if (isset($_POST['clone_product'])) {
    $pid = (int)$_POST['pid'];

    try {
        $stmt = $db->prepare("
            INSERT INTO products 
            (uid, title, image, product_type, price, description, is_available)
            SELECT 
                uid,
                CONCAT(title, ' (Copy)'),
                image,
                product_type,
                price,
                description,
                0
            FROM products
            WHERE pid = ?
        ");
        $stmt->execute([$pid]);

        $success = "Product cloned successfully (hidden by default).";
    } catch (PDOException $e) {
        $error = "Failed to clone product.";
    }
}

/* ========== TOGGLE VISIBILITY ========== */
if (isset($_POST['toggle_visibility'])) {
    $pid = (int)$_POST['pid'];

    try {
        $stmt = $db->prepare("
            UPDATE products 
            SET is_available = CASE 
                WHEN is_available = 1 THEN 0 
                ELSE 1 
            END
            WHERE pid = ?
        ");
        $stmt->execute([$pid]);

        $success = "Product visibility updated.";
    } catch (PDOException $e) {
        $error = "Failed to update visibility.";
    }
}

/* ========== DELETE PRODUCT ========== */
if (isset($_POST['delete_product'])) {
    $pid = (int)$_POST['pid'];

    try {
        $stmt = $db->prepare("DELETE FROM products WHERE pid = ?");
        $stmt->execute([$pid]);

        $success = "Product deleted permanently.";
    } catch (PDOException $e) {
        $error = "Failed to delete product.";
    }
}

$stmt = $db->query("
    SELECT p.*, u.username 
    FROM products p
    LEFT JOIN users u ON u.uid = p.uid
    ORDER BY p.pid DESC
");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Rentique | Manage Inventory</title>
    <link rel="stylesheet" href="css/rentique.css">
    <script>
        // Apply saved theme immediately to prevent flash
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.classList.add('light-mode');
        }
    </script>
</head>
<body>

<div class="dashboard-container">

    <aside class="sidebar">
        <h2>Admin Panel</h2>
        <a href="admin_dashboard.php" class="side-link">Dashboard</a>
        <a href="account_management.php" class="side-link">Account Management</a>
        <a href="report.php" class="side-link">Reports</a>
        <a href="logout.php" class="side-link">Logout</a>
    </aside>


    <section class="main-content">
        <div class="section-block">
            <h2>Manage Inventory</h2>

            <?php if (!empty($success)): ?>
                <p style="color:#00ff88; font-weight:bold;"><?= h($success) ?></p>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <p style="color:#ff4d4d; font-weight:bold;"><?= h($error) ?></p>
            <?php endif; ?>
            <?php

$totalProducts = count($products);
$availableCount = 0;
$hiddenCount = 0;

foreach ($products as $p) {
    if ((int)$p['is_available'] === 1) {
        $availableCount++;
    } else {
        $hiddenCount++;
    }
}
?>

<div class="overview-grid" style="margin-bottom:20px;">
    <div class="overview-card">
        <h3>Total Products</h3>
        <p class="green"><?= $totalProducts ?></p>
    </div>
    <div class="overview-card">
        <h3>Available</h3>
        <p class="green"><?= $availableCount ?></p>
    </div>
    <div class="overview-card">
        <h3>Hidden</h3>
        <p class="green"><?= $hiddenCount ?></p>
    </div>
</div>
            <table class="main-table">
                <thead>
                    <tr>
                        <th>PID</th>
                        <th>Title</th>
                        <th>Seller</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th style="width:260px;">Actions</th>
                    </tr>
                </thead>
                <tbody>

                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="6">No products found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= (int)$p['pid'] ?></td>
                            <td><?= h($p['title']) ?></td>
                            <td><?= h($p['username'] ?? 'Unknown') ?></td>
                            <td>£<?= number_format((float)$p['price'], 2) ?></td>
                            <td>
                                <?php if ((int)$p['is_available'] === 1): ?>
                <span style="color:#00ff88; font-weight:600;">● Available</span>
                    <?php else: ?>
                        <span style="color:#ff4d4d; font-weight:600;">● Hidden</span>
                            <?php endif; ?>
                            </td>
                            <td>
    <div class="action-buttons">

        <form method="post">
            <input type="hidden" name="pid" value="<?= $p['pid'] ?>">
            <button type="submit" name="clone_product" class="btn-action btn-clone">Clone</button>
        </form>

        <form method="post">
            <input type="hidden" name="pid" value="<?= $p['pid'] ?>">
            <button type="submit" name="toggle_product" class="btn-action btn-toggle">
                <?= $p['is_available'] ? 'Hide' : 'Unhide' ?>
            </button>
        </form>

        <form method="post" onsubmit="return confirm('Delete this product?')">
            <input type="hidden" name="pid" value="<?= $p['pid'] ?>">
            <button type="submit" name="delete_product" class="btn-action btn-delete">Delete</button>
        </form>

    </div>
</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
</body>
</html>