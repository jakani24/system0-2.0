<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"][3] !== "1") {
    header("location: /login/login.php");
    exit;
}

require_once "../config/config.php";

$userId = $_POST['userId'];

$sql = "UPDATE users SET banned = 0 WHERE id = ?";
$stmt = $link->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->close();

?>
