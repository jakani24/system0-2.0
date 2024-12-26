<?php
// Initialize the session
session_start();
require_once "../config/config.php";
require_once "keepmeloggedin.php";
$error=logmein($link);
// Check if the user is logged in, otherwise redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
// Define variables and initialize with empty values
$new_password = $confirm_password = "";
$new_password_err = $confirm_password_err = "";
$old_password="";
$old_passwort_err="";
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["old_password"])){
    $login_err="";
    //first: validate old password
    if(empty(trim($_POST["old_password"]))){
        $login_err = "Please enter your password.";
    } else{
        $old_password = trim($_POST["old_password"]);
    }
    if(empty($login_err))
    {
        $sql = "SELECT id, password FROM users WHERE username = ?";
            if($stmt = mysqli_prepare($link, $sql)){
                        // Bind variables to the prepared statement as parameters
                        $param_username = "";
                        mysqli_stmt_bind_param($stmt, "s", $param_username);
                        // Set parameters
                        $param_username = $_SESSION["username"];
                        // Attempt to execute the prepared statement
                        if(mysqli_stmt_execute($stmt)){
                            // Store result
                            mysqli_stmt_store_result($stmt);

                            // Check if username exists, if yes then verify password
                                // Bind result variables
                                mysqli_stmt_bind_result($stmt, $id, $hashed_password);
                                if(mysqli_stmt_fetch($stmt)){
                                    if(password_verify($old_password, $hashed_password)){

                                        // Redirect user to welcome page
                                            $auth=true;
					$change=true;
                                    } else{
                                        // Password is not valid, display a generic error message
                                        $login_err = "Invalid password.";
                                        $auth=false;
                                    }
                                }
                            
                        } else{
                            echo "Oops! Something went wrong. Please try again later.";
                        }

                        // Close statement
                        mysqli_stmt_close($stmt);
                    }
                }
    }
    if($auth===true && $change===true)
    {
        //end of old_password validation
        // Validate new password
        if(empty(trim($_POST["new_password"]))){
            $login_err = "Please enter the new password.";     
        } elseif(strlen(trim($_POST["new_password"])) < 6){
            $login_err = "Password must have atleast 6 characters.";
        }else if(strlen(trim($_POST["new_password"])) > 96)
        {
            $login_err = "Password cannot have more than 96 characters.";
        } 
        else{
            $new_password = trim($_POST["new_password"]);
        }
        
        // Validate confirm password
        if(empty(trim($_POST["confirm_password"]))){
            $login_err = "Please confirm the password.";
        } else{
            $confirm_password = trim($_POST["confirm_password"]);
            if(empty($new_password_err) && ($new_password != $confirm_password)){
                $login_err = "Password did not match.";
            }
        }
            
        // Check input errors before updating the database
        if(empty($login_err) ){
            // Prepare an update statement
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            
            if($stmt = mysqli_prepare($link, $sql)){
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "si", $param_password, $param_id);
                
                // Set parameters
                $param_password = password_hash($new_password, PASSWORD_DEFAULT);
                $param_id = $_SESSION["id"];
                
                // Attempt to execute the prepared statement
                if(mysqli_stmt_execute($stmt)){
                    // Password updated successfully. Destroy the session, and redirect to login page
                    session_destroy();
                    header("location: login.php");
                    exit();
                } else{
                    echo "Oops! Something went wrong. Please try again later.";
                }

                // Close statement
                mysqli_stmt_close($stmt);
            }
        }
    }

if(isset($_POST["user_token"])){
	$sql="update users set user_token = ? where id = ?";
	$stmt = mysqli_prepare($link, $sql);
	$user_token=$_POST["user_token"];
	$id=$_SESSION["id"];
	mysqli_stmt_bind_param($stmt, "si", $user_token, $id);
	mysqli_stmt_execute($stmt);
	mysqli_stmt_close($stmt);
	$msg="User Token wurde hinzugefügt.";
}
        // Close connection
        mysqli_close($link);
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Passwort zurücksetzen</title>
   <!-- <link rel="stylesheet" href="/system0/html/php/login/css/style.css"> -->
</head>
<?php 
	$color=$_SESSION["color"]; 
	include "../assets/components.php";
?>
<?php echo(" <body style='background-color:$color'> "); ?>

<script src="/assets/js/load_page.js"></script>
<script>
	
	function load_user()
	{
		$(document).ready(function(){
		$('#content').load("/assets/php/user_page.php");
		});
		$(document).ready(function(){
	$('#footer').load("/assets/html/footer.html");
	});
	}
</script>
<?php
    $username=$_SESSION["username"];
    $role=$_SESSION["role"];
		echo "<script type='text/javascript' >load_user()</script>";
    

echo("<div id='content'></div>");?>	
<div class="jumbotron d-flex align-items-center" style="height:95vh;">
  <div class="container" style="width:50%;">
    <h3 class="text-center">Passwort zurücksetzen</h3>
	<div class="m-3">
	    <form action="" method="post">
	      <div class="form-group m-2">
		<input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>">
		<label for="username">Altes Passwort:</label>
		<input type="password" class="form-control" id="username" name="old_password" required>
	      </div>
	      <div class="form-group m-2">
		<label for="pwd">Neues Passwort:</label>
		<input type="password" class="form-control" id="pwd" name="new_password" required>
	      </div>
	      <div class="form-group m-2">
		<label for="pwd">Neues Passwort bestätigen:</label>
		<input type="password" class="form-control" id="pwd" name="confirm_password" required>
	      </div>
	      <button type="submit" name="submit" class="btn btn-dark m-2">Bestätigen</button>
	    </form>
	</div>
	<?php
	        if(!empty($login_err)){
	            echo '<div class="alert alert-danger">' . $login_err . '</div>';
	        }
	?>
	<p>Hier kannst du deinen Jakach-Account verknüpfen, um dich leichter einzuloggen.</p>
	<p>Du findest dein User-Token in bei deinem Jakach Account (<a href="https://jakach.duckdns.org:444/?send_to=/account/">hier</a>)
	<div class="m-3">
            <form action="" method="post">
              <div class="form-group m-2">
                <label for="pwd">User Token:</label>
                <input type="text" class="form-control" id="user_token" name="user_token" required>
              </div>
              <button type="submit" name="submit" class="btn btn-dark m-2">Bestätigen</button>
            </form>
        </div>
	<?php
		if(isset($msg))
			 echo '<div class="alert alert-success">' . $msg . '</div>';
	?>
  </div>
</div>
<div id="footer"></div>
</body>
</html>
