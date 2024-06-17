<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '1234');
define('DB_NAME', 'system0');
$api="bot6975511033:AAGGswiwKYwCVbehpGE3hz_tLc9xuSAoBVg"; //the telegram api key for jakach notification system
$SENDGRID_API_KEY="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"; //our new api key, for the new mail address
$sendgrid_email="print@ksw3d.ch"; //our new email
$chat_id="6587711215"; //chat id of the admin => janis steiner
/* Attempt to connect to MySQL database */
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
 
// Check connection
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
?>
