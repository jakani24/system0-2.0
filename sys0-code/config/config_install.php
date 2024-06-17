<?php
$DB_SERVER='sys0-db';
$DB_USERNAME='root';
$DB_PASSWORD='1234';
$DB_DATABASE='sys0_db';
/* Attempt to connect to MySQL database */
$conn = new mysqli($DB_SERVER, $DB_USERNAME, $DB_PASSWORD);
// Check connection
if($conn === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
?>
