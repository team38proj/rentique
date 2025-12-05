<?php
session_start();
require_once 'connectdb.php';

// Require login
if (!isset($_SESSION['uid'])) {
    header("Location: login.php");
    exit;
}

$seller_uid = $_SESSION['uid'];

/* ------------------------------------------------------
   SAVE SELLER INFORMATION (USERNAME / FULL NAME / EMAIL / ADDRESS)
------------------------------------------------------ */

$seller_info_msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_seller_info'])) {

    $username = trim($_POST['username']);
    $fullname = trim($_POST['billing_fullname']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    if ($username !== "" && $fullname !== "" && $email !== "" && $address !== "") {
        try {
            $stmt = $db->prepare("
                UPDATE users 
                SET username = ?, billing_fullname = ?, email = ?, address = ?
                WHERE uid = ?
            ");
            $stmt->execute([$username, $fullname, $email, $address, $seller_uid]);

            $seller_info_msg = "Information updated successfully.";
        } catch (PDOException $e) {
            $seller_info_msg = "Database error updating information.";
        }
    } else {
        $seller_info_msg = "All fields are required.";
    }
}

/* ------------------------------------------------------
   SAVE BANK DETAILS
------------------------------------------------------ */

$bank_msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_bank'])) {

    $sortcode = trim($_POST['sortcode']);
    $banknum = trim($_POST['banknum']);

    if ($sortcode !== "" && $banknum !== "") {
        try {
            $stmt = $db->prepare("
                UPDATE users 
                SET pay_sortcode = ?, pay_banknumber = ?
                WHERE uid = ?
            ");
            $stmt->execute([$sortcode, $banknum, $seller_uid]);

            $bank_msg = "Bank details updated.";
        } catch (PDOException $e) {
            $bank_msg = "Database error updating bank details.";
        }
    } else {
        $bank_msg = "All fields are required.";
    }
}

/* ------------------------------------------------------
   PRODUCT UPLOAD HANDLER
------------------------------------------------------ */

$upload_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['upload_item'])) {

    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $price = trim($_POST['price']);
    $description = trim($_POST['description']);

    if ($title !== "" && $category !== "" && $price > 0 && isset($_FILES['image'])) {

        $imgName = basename($_FILES['image']['name']);
        $imgTmp = $_FILES['image']['tmp_name'];
        $targetPath = "images/" . $imgName;

        if (move_uploaded_file($imgTmp, $targetPath)) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO products (title, image, product_type, price, uid, description, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$title, $imgName, $category, $price, $seller_uid, $description]);

                $upload_message = "Item uploaded.";
            } catch (PDOException $e) {
                $upload_message = "Upload failed.";
            }
        } else {
            $upload_message = "Image upload failed.";
        }
    } else {
        $upload_message = "All fields required.";
    }
}

/* ------------------------------------------------------
   LOAD SELLER DATA
------------------------------------------------------ */

try {
    $stmt = $db->prepare("SELECT username, email, address, billing_fullname, pay_sortcode, pay_banknumber
                          FROM users WHERE uid = ?");
    $stmt->execute([$seller_uid]);
    $sellerData = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error");
}

/* ------------------------------------------------------
   LOAD LISTINGS
------------------------------------------------------ */

try {
    $stmt = $db->prepare("SELECT pid, title, image, product_type, price FROM products WHERE uid = ?");
    $stmt->execute([$seller_uid]);
    $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $listings = [];
}

$activeListings = count($listings);

/* ------------------------------------------------------
   LOAD RENTED ITEMS
------------------------------------------------------ */

try {
    $stmt = $db->prepare("SELECT price FROM transactions WHERE receiving_uid = ?");
    $stmt->execute([$seller_uid]);
    $rentedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $rentedItems = [];
}

$itemsRentedOut = count($rentedItems);

$totalEarnings = 0;
foreach ($rentedItems as $r) $totalEarnings += $r['price'];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Rentique | Seller Dashboard</title>
    <link rel="stylesheet" href="css/rentique.css">
</head>

<body>

<header>
    <nav class="navbar">
        <div class="logo">
            <img src="images/rentique_logo.png">
            <span>rentique.</span>
        </div>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="productsPage.php">Shop</a></li>
            <li><a href="AboutUs.php">About</a></li>
            <li><a href="Contact.php">Contact</a></li>
            <li><a href="BasketPage.php" class="cart-icon">Basket</a></li>
            <li><a href="logout.php" class="btn logout">Logout</a></li>
        </ul>
    </nav>
</header>


    <section class="main-content">

        <!-- OVERVIEW -->
        <div id="overview" class="section-block">
            <h2>Welcome, <?= htmlspecialchars($sellerData['username']) ?>!</h2>

            <div class="overview-grid">
                <div class="overview-card">
                    <h3>Active Listings</h3>
                    <p class="green"><?= $activeListings ?></p>
                </div>

                <div class="overview-card">
                    <h3>Items Rented Out</h3>
                    <p class="green"><?= $itemsRentedOut ?></p>
                </div>

                <div class="overview-card">
                    <h3>Total Earnings</h3>
                    <p class="green">£<?= number_format($totalEarnings, 2) ?></p>
                </div>
            </div>
        </div>

        <!-- LISTINGS -->
        <div id="listings" class="section-block">
            <h2>My Listings</h2>

            <table class="main-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Image</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($listings as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['title']) ?></td>
                        <td><?= htmlspecialchars($item['product_type']) ?></td>
                        <td>£<?= number_format($item['price'], 2) ?></td>
                        <td><img src="images/<?= htmlspecialchars($item['image']) ?>" width="60"></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- ADD NEW ITEM -->
        <div id="additem" class="section-block">
            <h2>Add New Item</h2>

            <p><?= $upload_message ?></p>

            <form class="settings-form" method="post" enctype="multipart/form-data">

                <label>Item Name</label>
                <input type="text" name="title">

                <label>Category</label>
                <select name="category">
                    <option>Dresses</option>
                    <option>Suits</option>
                    <option>Accessories</option>
                    <option>Jackets</option>
                    <option>Shoes</option>
                </select>

                <label>Price (£)</label>
                <input type="number" name="price">

                <label>Upload Image</label>
                <input type="file" name="image">

                <label>Description</label>
                <textarea name="description"></textarea>

                <button class="btn primary" type="submit" name="upload_item">Upload Item</button>
            </form>
        </div>

        <!-- EARNINGS -->
        <div id="earnings" class="section-block">
            <h2>Earnings</h2>

            <div class="earning-card">
                <h3>Total Earned</h3>
                <p class="green">£<?= number_format($totalEarnings, 2) ?></p>
            </div>
        </div>

        <!-- MESSAGES -->
        <div id="messages" class="section-block">
            <h2>Messages</h2>
            <p>No messages available.</p>
        </div>

        <!-- SETTINGS -->
        <div id="settings" class="section-block">
            <h2>Settings</h2>

            <p><?= $seller_info_msg ?></p>

            <form class="settings-form" method="post">
                <h3>Seller Information</h3>

                <label>Username</label>
                <input type="text" name="username"
                       value="<?= htmlspecialchars($sellerData['username']) ?>">

                <label>Full Name</label>
                <input type="text" name="billing_fullname"
                       value="<?= htmlspecialchars($sellerData['billing_fullname']) ?>">

                <label>Email</label>
                <input type="email" name="email"
                       value="<?= htmlspecialchars($sellerData['email']) ?>">

                <label>Pickup / Return Address</label>
                <input type="text" name="address"
                       value="<?= htmlspecialchars($sellerData['address']) ?>">

                <button class="btn primary" type="submit" name="update_seller_info">Save Changes</button>
            </form>
        </div>

        <!-- PAYOUT DETAILS -->
        <div id="payout" class="section-block">
            <h2>Payout Details</h2>

            <p><?= $bank_msg ?></p>

            <form class="settings-form" method="post">

                <label>Sort Code</label>
                <input type="text" name="sortcode"
                       value="<?= htmlspecialchars($sellerData['pay_sortcode']) ?>">

                <label>Account Number</label>
                <input type="text" name="banknum"
                       value="<?= htmlspecialchars($sellerData['pay_banknumber']) ?>">

                <button class="btn primary" type="submit" name="update_bank">Save Bank Details</button>
            </form>
        </div>

    </section>

</div>

</body>
</html>
