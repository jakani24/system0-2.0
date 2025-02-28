<?php
session_start();
        include "../../config/config.php";
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true or $_SESSION["role"][0]!=="1"){
                die("no_auth");
            exit;
        }
	$username=$_SESSION["username"];
	$path = "/var/www/html/user_files/$username/";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_FILES["file"]) && $_FILES["file"]["error"] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES["file"]["tmp_name"];
        $fileName = basename($_FILES["file"]["name"]);
        $filePath = $path . $fileName;
	$filetype = strtolower(pathinfo($_FILES['file']['name'],PATHINFO_EXTENSION));
	if($filetype==="gcode"){
        	if (move_uploaded_file($fileTmpPath, $filePath)) {
        	    echo json_encode(["status" => "success", "message" => "Datei hochgeladen", "file" => $filePath]);
			$_SESSION["current_file"]="$filePath";
        	} else {
        	    echo json_encode(["status" => "error", "message" => "Konnte datei nicht in Benutzerordner verschieben"]);
        	}
	}else{
		echo json_encode(["status" => "error", "message" => "Dieser Dateityp wird nicht unterstützt"]);
	}
    } else {
        echo json_encode(["status" => "error", "message" => "Unbekannter Fehler"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalide Anfrage"]);
}
?>
