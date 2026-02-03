<?php
session_start();
require_once 'connectdb.php';

// Require login
if (!isset($_SESSION['uid'])) {
    header("Location: login.php");
    exit;
}

$seller_uid = $_SESSION['uid'];

$seller_info_msg = "";
$bank_msg = "";
$upload_message = "";
$update_message = "";
$delete_message = "";

/* ------------------------------------------------------
   UPDATE SELLER INFORMATION (USERNAME / FULL NAME / EMAIL / ADDRESS)
------------------------------------------------------ */
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
   UPDATE BANK DETAILS
------------------------------------------------------ */
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
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['upload_item'])) {

    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $price = trim($_POST['price']);
    $description = trim($_POST['description']);

    if ($title !== "" && $category !== "" && $price > 0 && isset($_FILES['image']) && $_FILES['image']['error'] === 0) {

        // Sanitize file name
        $originalName = basename($_FILES['image']['name']);
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
        $imgName = time() . "_" . $safeName;

        $imgTmp = $_FILES['image']['tmp_name'];

        // Folder relative to this file
        $targetDir = __DIR__ . "/images/";
        $targetPath = $targetDir . $imgName;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        if (move_uploaded_file($imgTmp, $targetPath)) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO products (title, image, product_type, price, uid, description, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$title, $imgName, $category, $price, $seller_uid, $description]);
                $upload_message = "Item uploaded successfully.";
            } catch (PDOException $e) {
                $upload_message = "Database error while saving item.";
            }
        } else {
            $upload_message = "Image upload failed.";
        }
    } else {
        $upload_message = "All fields are required and a valid image must be selected.";
    }
}

/* ------------------------------------------------------
   UPDATE LISTING
------------------------------------------------------ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_listing'])) {

    $pid = intval($_POST['pid']);
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $price = trim($_POST['price']);
    $description = trim($_POST['description']);

    if ($title !== "" && $category !== "" && $price > 0) {
        try {
            $stmt = $db->prepare("
                UPDATE products 
                SET title = ?, product_type = ?, price = ?, description = ?
                WHERE pid = ? AND uid = ?
            ");
            $stmt->execute([$title, $category, $price, $description, $pid, $seller_uid]);
            $update_message = "Listing updated.";
        } catch (PDOException $e) {
            $update_message = "Update failed.";
        }
    } else {
        $update_message = "All fields are required.";
    }
}

/* ------------------------------------------------------
   DELETE LISTING
------------------------------------------------------ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_listing'])) {

    $pid = intval($_POST['pid']);

    try {
        $stmt = $db->prepare("DELETE FROM products WHERE pid = ? AND uid = ?");
        $stmt->execute([$pid, $seller_uid]);
        $delete_message = "Listing deleted.";
    } catch (PDOException $e) {
        $delete_message = "Error deleting listing.";
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
    die("Database error loading seller data.");
}

/* ------------------------------------------------------
   LOAD LISTINGS
------------------------------------------------------ */
try {
    $stmt = $db->prepare("SELECT pid, title, image, product_type, price, description 
                          FROM products WHERE uid = ?");
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
foreach ($rentedItems as $r) {
    $totalEarnings += $r['price'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Rentique | Seller Dashboard</title>
    <link rel="stylesheet" href="css/rentique.css">
    <script src="js/theme.js" defer></script>

</head>

<body>

<header>
    <nav class="navbar">
        <div class="logo">
            <img src="images/rentique_logo.png" alt="Rentique Logo">
            <span>rentique.</span>
        </div>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="productsPage.php">Shop</a></li>
            <li><a href="AboutUs.php">About</a></li>
            <li><a href="Contact.php">Contact</a></li>
            <li><a href="BasketPage.php">Basket</a></li>
            <button id="themeToggle" class="btn small"> Theme</button>
            <li><a href="login.php" class="btn logout">Logout</a></li>
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

    <!-- STATUS MESSAGES -->
    <?php if ($upload_message || $update_message || $delete_message): ?>
        <div class="section-block">
            <?php if ($upload_message): ?>
                <p style="color: #a3ff00;"><?= htmlspecialchars($upload_message) ?></p>
            <?php endif; ?>
            <?php if ($update_message): ?>
                <p style="color: #a3ff00;"><?= htmlspecialchars($update_message) ?></p>
            <?php endif; ?>
            <?php if ($delete_message): ?>
                <p style="color: #ff6b6b;"><?= htmlspecialchars($delete_message) ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- LISTINGS TABLE -->
    <div id="listings" class="section-block">
        <h2>My Listings</h2>

        <table class="main-table full-width">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Image</th>
                    <th>Edit</th>
                    <th>Delete</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($listings as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['title']) ?></td>
                    <td><?= htmlspecialchars($item['product_type']) ?></td>
                    <td>£<?= number_format($item['price'], 2) ?></td>
                    <td>
                        <?php if (!empty($item['image'])): ?>
                            <img src="images/<?= htmlspecialchars($item['image']) ?>" width="60" alt="Item image">
                        <?php endif; ?>
                    </td>

                    <!-- Edit Button -->
                    <td>
                        <button class="btn small"
                            type="button"
                            onclick="openEditModal(
                                '<?= $item['pid'] ?>',
                                '<?= htmlspecialchars($item['title'], ENT_QUOTES) ?>',
                                '<?= htmlspecialchars($item['product_type'], ENT_QUOTES) ?>',
                                '<?= $item['price'] ?>',
                                '<?= htmlspecialchars($item['description'], ENT_QUOTES) ?>'
                            )">
                            Edit
                        </button>
                    </td>

                    <!-- Delete Button -->
                    <td>
                        <form method="post" onsubmit="return confirmDelete();">
                            <input type="hidden" name="pid" value="<?= $item['pid'] ?>">
                            <button class="btn danger small" type="submit" name="delete_listing">
                                Delete
                            </button>
                        </form>
                    </td>

                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- EDIT MODAL -->
    <div id="editModal" class="modal" style="display:none;">
        <div class="modal-content" style="background:#1a1a1a;padding:20px;border-radius:8px;max-width:500px;margin:10% auto;">
            <h3>Edit Listing</h3>

            <form method="post">

                <input type="hidden" name="pid" id="edit_pid">

                <label>Title</label>
                <input type="text" name="title" id="edit_title" required>

                <label>Category</label>
                <select name="category" id="edit_category" required>
                    <option value="Dresses">Dresses</option>
                    <option value="Suits">Suits</option>
                    <option value="Accessories">Accessories</option>
                    <option value="Jackets">Jackets</option>
                    <option value="Shoes">Shoes</option>
                </select>

                <label>Price (£)</label>
                <input type="number" step="0.01" name="price" id="edit_price" required>

                <label>Description</label>
                <textarea name="description" id="edit_description" required></textarea>

                <button class="btn primary" type="submit" name="update_listing">Save Changes</button>
                <button class="btn" type="button" onclick="closeEditModal()">Cancel</button>

            </form>
        </div>
    </div>

    <!-- ADD NEW ITEM -->
    <div id="additem" class="section-block">
        <h2>Add New Item</h2>

        <form class="settings-form" method="post" enctype="multipart/form-data">

            <label>Item Name</label>
            <input type="text" name="title" required>

            <label>Category</label>
            <select name="category" required>
                <option value="Dresses">Dresses</option>
                <option value="Suits">Suits</option>
                <option value="Accessories">Accessories</option>
                <option value="Jackets">Jackets</option>
                <option value="Shoes">Shoes</option>
            </select>

            <label>Price (£)</label>
            <input type="number" step="0.01" name="price" required>

            <label>Upload Image</label>
            <input type="file" name="image" accept="image/*" required>

            <label>Description</label>
            <textarea name="description" required></textarea>

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

    <?php if ($seller_info_msg): ?>
        <p style="color:#a3ff00;"><?= htmlspecialchars($seller_info_msg) ?></p>
    <?php endif; ?>

    <form class="settings-form" method="post">
        <h3>Seller Information</h3>

        <label>Username</label>
        <input type="text" name="username"
               value="<?= htmlspecialchars($sellerData['username']) ?>" required>

        <label>Full Name</label>
        <input type="text" name="billing_fullname"
               value="<?= htmlspecialchars($sellerData['billing_fullname']) ?>" required>

        <label>Email</label>
        <input type="email" name="email"
               value="<?= htmlspecialchars($sellerData['email']) ?>" required>

        <label>Pickup / Return Address</label>
        <input type="text" name="address"
               value="<?= htmlspecialchars($sellerData['address']) ?>" required>

        <button class="btn primary" type="submit" name="update_seller_info">Save Changes</button>
    </form>
</div>

    <!-- PAYOUT DETAILS -->
    <div id="payout" class="section-block">
    <h2>Payout Details</h2>

    <?php if ($bank_msg): ?>
        <p style="color:#a3ff00;"><?= htmlspecialchars($bank_msg) ?></p>
    <?php endif; ?>

    <form class="settings-form" method="post">

        <label>Sort Code</label>
        <input type="text" name="sortcode"
               value="<?= htmlspecialchars($sellerData['pay_sortcode']) ?>" required>

        <label>Account Number</label>
        <input type="text" name="banknum"
               value="<?= htmlspecialchars($sellerData['pay_banknumber']) ?>" required>

        <button class="btn primary" type="submit" name="update_bank">Save Bank Details</button>
    </form>
</div>


</section>

<script>
function openEditModal(pid, title, category, price, description) {
    document.getElementById("edit_pid").value = pid;
    document.getElementById("edit_title").value = title;
    document.getElementById("edit_category").value = category;
    document.getElementById("edit_price").value = price;
    document.getElementById("edit_description").value = description;

    document.getElementById("editModal").style.display = "block";
}

function closeEditModal() {
    document.getElementById("editModal").style.display = "none";
}

function confirmDelete() {
    return confirm("Are you sure you want to delete this listing?");
}
</script>

</body>
</html>
