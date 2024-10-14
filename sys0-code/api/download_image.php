<?php
	// Get parameters
	$username = htmlspecialchars($_GET["username"]);
	$printer_url = $_GET["url"];

	// Path to save the downloaded image
	$path = "/var/www/html/user_files/$username/$printer_url.jpeg";

	// Download the latest snapshot from the printer URL
	exec("wget --quiet \"http://$printer_url/webcam/?action=snapshot\" -O $path");

?>
