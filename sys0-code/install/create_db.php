<?php
include "../config/config_install.php";
$sql = "CREATE DATABASE IF NOT EXISTS $DB_DATABASE";
	if ($conn->query($sql) === TRUE) {
		echo '<br><div class="alert alert-success" role="alert">Database created successfully!</div>';
	} else {
		$success=0;
		echo '<br><div class="alert alert-danger" role="alert">
				Error creating database: ' . $conn->error .'
		</div>';
	}
$conn->close();

include "../config/config.php";
//create tables used by sys0
// Create user table
$sql = "CREATE TABLE IF NOT EXISTS users (
	id INT AUTO_INCREMENT PRIMARY KEY,
	username VARCHAR(255) NOT NULL,
	password VARCHAR(255),
	role VARCHAR(255),
	created_at DATETIME,
	keepmeloggedin VARCHAR(255),
	color VARCHAR(50),
	banned INT,
	banned_reason VARCHAR(255),
	telegram_id VARCHAR(50),
	notification_way INT,
	notification_mail INT,
	notification_telegram INT
	)";
$link->query($sql);
?>
