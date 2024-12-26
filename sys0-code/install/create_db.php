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
	user_token VARCHAR(128),
	created_at DATETIME,
	keepmeloggedin VARCHAR(255),
	color VARCHAR(50),
	banned INT,
	banned_reason VARCHAR(255),
	telegram_id VARCHAR(50),
	notification_way INT,
	notification_mail INT,
	notification_telegram INT,
 	class_id INT,
	filament_usage INT
	)";
$link->query($sql);
//printer table
$sql = "CREATE TABLE IF NOT EXISTS printer (
        id INT AUTO_INCREMENT PRIMARY KEY,
        printing INT,
        free INT,
        used_by_userid INT,
        printer_url VARCHAR(255),
        apikey VARCHAR(255),
        cancel INT,
        system_status INT,
        mail_sent INT,
        rotation INT,
        color VARCHAR(255)
        )";
$link->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS class (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50)
        )";
$link->query($sql);

//queue table
$sql = "CREATE TABLE IF NOT EXISTS queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        from_userid INT,
        filepath VARCHAR(255),
        print_on INT
        )";
$link->query($sql);

//api table
$sql = "CREATE TABLE IF NOT EXISTS api (
        id INT AUTO_INCREMENT PRIMARY KEY,
        apikey VARCHAR(255)
        )";
$link->query($sql);


//print key table
$sql = "CREATE TABLE IF NOT EXISTS print_key (
        id INT AUTO_INCREMENT PRIMARY KEY,
        print_key VARCHAR(255)
        )";
$link->query($sql);


//reservations table
$sql = "CREATE TABLE IF NOT EXISTS reservations (
        id INT AUTO_INCREMENT PRIMARY KEY,
	set_by_userid INT,
	time_from VARCHAR(255),
	time_to VARCHAR(255),
        day VARCHAR(255),
	for_class INT
        )";
$link->query($sql);

//filament table
$sql = "CREATE TABLE IF NOT EXISTS filament (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255),
	internal_id INT
        )";
$link->query($sql);

echo("db creation finished, you can now close this tab.");
?>
