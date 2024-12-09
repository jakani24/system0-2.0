<!DOCTYPE html>
<html data-bs-theme="dark">
<?php
// Initialize the session
session_start();
include "../config/config.php";
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: /login/login.php");
    exit;
}
$username=htmlspecialchars($_SESSION["username"]);
?>


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
	$role=$_SESSION["role"];
	echo "<script type='text/javascript' >load_user()</script>";
?>
<?php
	$color=$_SESSION["color"]; 
	include "../assets/components.php";
?>
<?php echo(" <body style='background-color:$color'> ");?>
<div id="content"></div>

<head>
  <title>Bug report</title>
</head>
<body>
	<div class="center-container" style="min-height: 95vh;">
			<div class="container">
			  <div class="container mt-5 text-center">
				<h1>Fehler melden</h1>
				<form method="post" action="bugreport.php?sent">
				  <div class="form-group">
					<label class="my-3" for="bugDescription">Beschreibung des Fehlers:</label>
					<textarea class="form-control mx-auto" id="bugDescription" name="bug" rows="5" style="width:50%;" required></textarea>
				  </div>
				  <div class="form-group">
					<label class="my-3" for="email">Deine Email für weitere Nachfragen (optional)</label>
					<input type="text" class="form-control mx-auto" id="email" name="email" style="width:50%;" value="<?php echo($_SESSION["username"]); ?>">
				  </div>
				  <button type="submit" class="btn btn-secondary my-5">abschicken</button>
				</form>
				<?php
				  if(isset($_GET["sent"]))
				  {
					$email = htmlspecialchars($_POST["email"]);
					$bug = htmlspecialchars($_POST["bug"]);
					$text = urlencode("JWAF INFORMATION:\nuser: $username;\nemail: $email\nbug: $bug\nEND");
					exec("curl \"https://api.telegram.org/$api/sendMessage?chat_id=$chat_id&text=$text\"");
					  echo '<div class="alert alert-success" role="alert">Vielen Dank, deine Fehlermeldung ist bei uns angekommen und wir kümmern uns darum.</div>';
				  }
				?>
			  </div>
			</div>
		</div>
	<div id="footer"></div>
</body>

</html>
