<?php

/* Database connection */
$db_host = 'localhost';
$db_name = 'cs2team38_db';
$db_user = 'cs2team38';
$localdb_user = 'root';
$db_pass = 'A9BLN1Yz5VXDDF3ewpaDXNNEb';
$localdb_pass = '';

try {
    // Online attempt
    $db = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass
    );
} catch (PDOException $e) {
    try {
        // local attempt
        $db = new PDO(
            "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
            $localdb_user,
            $localdb_pass
        );
        echo "Please note, you are connected to a local version of the database only.";
    } catch (PDOException $e2) {
        // Both failed
        echo "Failed to connect to database.<br>";
        echo "Error: " . $e2->getMessage();
        exit;
    }
}

$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);


/* CSRF FUNCTIONS */

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/* Sanitize Input */
function sanitize_input($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

?>
