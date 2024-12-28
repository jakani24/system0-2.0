<?php
// Initialize the session
session_start();
//include "/var/www/html/system0/html/php/login/v3/waf/waf_no_anti_xss.php";
$username = $password = $confirm_password = "";
$role="user";
$username_err = $password_err = $confirm_password_err = "";
$err="";
// Check if the user is already logged in, if yes then redirect him to welcome page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
	header("location: /app/overview.php");
    exit;
}
require_once "../config/config.php";
require_once "../log/log.php";
require_once "../waf/salt.php";
require_once "keepmeloggedin.php";
include "../assets/components.php";
$error=logmein($link);
if($error==="success")
{
        header("LOCATION: /app/overview.php");
}

$auth_token = $_GET["auth"];

// Check the auth token against Jakach login API
$check_url = "https://jakach-auth.duckdns.org:80/api/auth/check_auth_key.php?auth_token=" . $auth_token;

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $check_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute cURL and get the response
$response = curl_exec($ch);

// Check for cURL errors
if(curl_errno($ch)) {
    die("cURL Error: " . curl_error($ch));
}

// Close cURL
curl_close($ch);

// Decode the JSON response
$data = json_decode($response, true);
// Check if the response contains a valid status
if (isset($data['status'])) {
    if ($data['status'] == "success") {
        // Successful authentication: login the user
        $_SESSION["username"] = $data["username"];
        $_SESSION["id"] = $data["id"];
        $_SESSION["email"] = $data["email"];
        $_SESSION["telegram_id"] = $data["telegram_id"];
        $_SESSION["user_token"] = $data["user_token"];
	//load user data
	$sql = "SELECT id, username, password, role, color,banned,banned_reason ,telegram_id,notification_telegram,notification_mail, class_id FROM users WHERE user_token = ?";
	$stmt = mysqli_prepare($link, $sql);
	$user_token=$_SESSION["user_token"];
	mysqli_stmt_bind_param($stmt, "s", $user_token);
	mysqli_stmt_execute($stmt);
	mysqli_stmt_store_result($stmt);
	if(mysqli_stmt_num_rows($stmt) == 1){
		$username = $password = "";
		$username_err = $password_err = $login_err = "";
		$color="";
		$banned=0;
		$banned_reason="";
		$telegram_id="";
		$notification_telegram=0;
		$notification_mail=0;
		$class_id=0;
		$id=0;
		mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $role,$color,$banned,$banned_reason,$telegram_id,$notification_telegram,$notification_mail,$class_id);
		mysqli_stmt_fetch($stmt);
		$_SESSION["loggedin"] = true;
		$_SESSION["id"] = $id;
		$_SESSION["username"] = $username;
		$_SESSION["role"] = $role;
		$_SESSION["token"]=bin2hex(random_bytes(32));
		$_SESSION["color"]=$color;
		$_SESSION["creation_token"]= urlencode(bin2hex(random_bytes(24/2)));
		$_SESSION["telegram_id"]=$telegram_id;
		$_SESSION["notification_telegram"]=$notification_telegram;
		$_SESSION["notification_mail"]=$notification_mail;
		$_SESSION["class_id"]=$class_id;
		mysqli_stmt_close($stmt);
		echo("<script>location.href='/app/overview.php';</script>");
	}else{
		echo("<div class='alert alert-danger'>Dein System0 Account wurde noch nicht mit deinem Jakach account verknüpft!<br>Um deinen Jakach account zu verknüpfen, folge bitte <a href='https://github.com/jakani24/system0-2.0/blob/main/connect_jac.pdf'>dieser</a> Anleitung</div>");
	}

        // Return a success response
    } else {
        // Authentication failed
	echo '<div class="alert alert-danger">Invalid auth token</div>';
    }
} else {
    // Invalid response format or missing status
	echo '<div class="alert alert-danger">Server error</div>';
}

?>
