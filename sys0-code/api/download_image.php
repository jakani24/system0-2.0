<?php
	// Get parameters
	$username = htmlspecialchars($_GET["username"]);
	$printer_url = $_GET["url"];

	// Path to save the downloaded image
	$path = "/var/www/html/user_files/$username/$printer_url.jpeg";

	// Use escapeshellarg() to prevent command injection
	$safe_printer_url = escapeshellarg($printer_url);
	$safe_path = escapeshellarg($path);
	
	// Download the latest snapshot from the printer URL
	exec("wget --quiet http://$safe_printer_url/webcam/?action=snapshot -O $safe_path");

?>