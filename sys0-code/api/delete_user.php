<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"][3] !== "1") {
    header("location: /login/login.php");
    exit;
}

require_once "../config/config.php";

function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return false; // Gibt false zurück, wenn das Verzeichnis nicht existiert
    }

    $files = array_diff(scandir($dir), array('.', '..')); // Ignoriert "." und ".."
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            deleteDirectory($path); // Rekursiver Aufruf für Unterordner
        } else {
            unlink($path); // Datei löschen
        }
    }
    return rmdir($dir); // Verzeichnis löschen
}

$userId = $_POST['userId'];

$sql = "SELECT username FROM users WHERE id = ?";
$stmt = $link->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();

$sql = "DELETE FROM users WHERE id = ?";
$stmt = $link->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->close();

deleteDirectory("/var/www/html/user_files/$username/");
?>
