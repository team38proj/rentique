<?php

$db_host = 'localhost';
$db_name = 'cs2team38_db';
$username = 'root';
$password = '';

// Jay - backend (Connection Script for DB)

try {
	$db = new PDO("mysql:dbname=$db_name;host=$db_host", $username, $password); 
	#$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $ex) {
	echo("Failed to connect to the database.<br>");
	echo("error in connectdb" . $ex->getMessage());
	exit;
}

?>