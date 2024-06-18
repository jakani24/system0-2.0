<?php
define('DB_SERVER', 'sys0-db');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '1234');
define('DB_NAME', 'sys0_db');
$api=""; //the telegram api key for jakach notification system
$SENDGRID_API_KEY=""; //our new api key, for the new mail address
$sendgrid_email=""; //our new email
$chat_id=""; //chat id of the admin => janis steiner
/* Attempt to connect to MySQL database */
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
// Check connection
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
?>
