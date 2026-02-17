<?php

/* Database connection */
$db_host = 'localhost';
$db_name = 'cs2team38_db';
$db_user = 'cs2team38';
$db_pass = 'A9BLN1Yz5VXDDF3ewpaDXNNEb';
$username_local = 'root';
$password_local = '';
$db_host_local = 'localhost';

try {
    $db = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Failed to connect to database. Trying locally installed DB now.<br>";
    echo "Remote DB Error: " . $e->getMessage();

    try {
		$db = new PDO("mysql:dbname=$db_name;host=$db_host_local", $username_local, $password_local); 
		#$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	} catch(PDOException $ex) {
		echo("Failed to connect to local database.<br>");
		echo("Error in connectdb for local DB connection: " . $ex->getMessage());
	exit;
}
}

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
