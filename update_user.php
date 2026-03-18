<?php
session_start();
require_once 'connectdb.php';

if (!isset($_SESSION['uid'])) {
    header("Location: login.php");
    exit;
}

$user_uid = (int)$_SESSION['uid'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $address  = trim($_POST['address'] ?? '');

    if ($username === '' || $email === '' || $address === '') {
        die("All fields are required.");
    }

    try {
        $stmt = $db->prepare("
            UPDATE users 
            SET username = ?, email = ?, address = ?
            WHERE uid = ?
        ");

        $stmt->execute([$username, $email, $address, $user_uid]);

        header("Location: user_dashboard.php#settings");
        exit;

    } catch (PDOException $e) {
        error_log("Update error: " . $e->getMessage());
        die("An error occurred while updating your profile.");
    }
}
?>
