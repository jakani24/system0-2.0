<?php
	session_start();
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true or $_SESSION["role"][0]!=="1"){
                die("no_auth");
            exit;
        }
        $username=$_SESSION["username"];
        $path = "/var/www/html/user_files/$username/";
	$public_path = "/var/www/html/user_files/public/";
	if($_GET["pc"]=="1")
        	$_SESSION["current_file"]=$public_path.$_GET["file"];
	else
		$_SESSION["current_file"]=$path.$_GET["file"];
?>
