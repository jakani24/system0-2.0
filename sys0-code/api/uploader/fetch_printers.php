<?php
	//this file returns a list of available printers, theyr status and theyr color
	session_start();
	include "../../config/config.php";
	if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true or $_SESSION["role"][0]!=="1"){
		die("no_auth");
	    exit;
	}
	$sql = "SELECT p.id, f.name AS color, p.free, p.system_status
            FROM printer p
            LEFT JOIN filament f ON p.color = f.internal_id
            ORDER BY p.id";

	$stmt = mysqli_prepare($link, $sql);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);

	$printers = [];
	while ($row = mysqli_fetch_assoc($result)) {
		$printers[] = [
			'id' => $row['id'],
			'free' => $row['free'],
			'error_status' => $row['system_status'],
			'color' => htmlspecialchars($row['color'], ENT_QUOTES, 'UTF-8')
		];
	}
	echo json_encode($printers);
?>

