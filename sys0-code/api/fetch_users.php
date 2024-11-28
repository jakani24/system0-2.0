<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"][3] !== "1") {
    header("location: /login/login.php");
    exit;
}
require_once "../config/config.php";

$username = isset($_GET['username']) ? '%' . htmlspecialchars($_GET['username']) . '%' : '%';

$sql = "SELECT users.id, users.username, users.role, users.class_id, users.banned, class.name 
        FROM users
        LEFT JOIN class ON users.class_id = class.id 
        WHERE users.username LIKE ?";
$stmt = $link->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['username']}</td>";
    echo "<td>
        <select class='form-select updateField' data-field='class_id' data-userid='{$row['id']}'>";
    $classQuery = $link->query("SELECT id, name FROM class");
    while ($class = $classQuery->fetch_assoc()) {
        $selected = $class['id'] == $row['class_id'] ? 'selected' : '';
        echo "<option value='{$class['id']}' $selected>{$class['name']}</option>";
    }
    if($row["class_id"]==0){
	echo "<option value='0' selected>Lehrperson</option>";
    }else{
	echo "<option value='0'>Lehrperson</option>";
    }
    echo "</select>
    </td>";

    $role=substr($row['role'],0,11);
    foreach (str_split($role) as $index => $perm) {
        $checked = $perm === "1" ? "checked" : "";
        echo "<td>
            <input type='checkbox' class='form-check-input updateField' data-field='role[$index]' data-userid='{$row['id']}' $checked>
        </td>";
    }

    if($row['banned']==1)
    	echo "<td><button class='btn btn-success verify_user' data-userid='{$row['id']}'>Manuell verifizieren</button></td>";
    else
	echo "<td>Bereits verifiziert</td>";
    echo "<td><button class='btn btn-danger deleteUser' data-userid='{$row['id']}'>Löschen</button></td>";
    echo "</tr>";
}
$stmt->close();
?>
