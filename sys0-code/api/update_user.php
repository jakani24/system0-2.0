<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"][3] !== "1") {
    header("location: /login/login.php");
    exit;
}
require_once "../config/config.php";

$userId = $_POST['userId'];
$field = $_POST['field'];
$value = $_POST['value'];

if (strpos($field, 'role') !== false) {
    $index = (int)filter_var($field, FILTER_SANITIZE_NUMBER_INT);
    $sql = "SELECT role FROM users WHERE id = ?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($role);
    $stmt->fetch();
    $stmt->close();

    $role[$index] = $value;
    $sql = "UPDATE users SET role = ? WHERE id = ?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("si", $role, $userId);
} else {
    $sql = "UPDATE users SET $field = ? WHERE id = ?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("ii", $value, $userId);
}
$stmt->execute();
$stmt->close();
?>
