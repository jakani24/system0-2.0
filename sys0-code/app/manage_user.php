<!DOCTYPE html>
<html>
	<title>Manage user</title>
<?php
// Initialize the session
session_start();
require_once "../log/log.php";
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"][3]!== "1"){
    header("location: /login/login.php");
    exit;
}
$_SESSION["rid"]++;
?>

<?php 
	$color=$_SESSION["color"]; 
	include "../assets/components.php";
?>
<script src="/assets/js/load_page.js"></script>
<script>
function load_user()
{
	$(document).ready(function(){
   	$('#content').load("/assets/php/user_page.php");
	});
}
</script>
<?php $color=$_SESSION["color"]; ?>
<?php echo("<body style='background-color:$color'> ");?>
<div id="content"></div>
<?php
	function get_perm_string(){
		$perm_str="";
		if(isset($_POST["print"]))
			$perm_str.="1";
		else
			$perm_str.="0";
		if(isset($_POST["private_cloud"]))
			$perm_str.="1";
		else
			$perm_str.="0";
		if(isset($_POST["public_cloud"]))
			$perm_str.="1";
		else
			$perm_str.="0";
		if(isset($_POST["printer_ctrl_all"]))
			$perm_str.="1";
		else
			$perm_str.="0";
		if(isset($_POST["change_user_perm"]))
			$perm_str.="1";
		else
			$perm_str.="0";
		if(isset($_POST["create_admin"]))
			$perm_str.="1";
		else
			$perm_str.="0";
		if(isset($_POST["view_log"]))
			$perm_str.="1";
		else
			$perm_str.="0";
		if(isset($_POST["view_apikey"]))
			$perm_str.="1";
		else
			$perm_str.="0";
		if(isset($_POST["create_key"]))
			$perm_str.="1";
		else
			$perm_str.="0";
		if(isset($_POST["debug"]))
			$perm_str.="1";
		else
			$perm_str.="0";
		if(isset($_POST["delete_from_public_cloud"]))
			$perm_str.="1";
		else
			$perm_str.="0";
		return $perm_str;
	}
	function deleteDirectory($dir) {
	    if (!is_dir($dir)) {
		return;
	    }

	    // Get list of files and directories inside the directory
	    $files = scandir($dir);

	    foreach ($files as $file) {
		// Skip current and parent directory links
		if ($file == '.' || $file == '..') {
		    continue;
		}

		$path = $dir . '/' . $file;

		if (is_dir($path)) {
		    // Recursively delete sub-directory
		    deleteDirectory($path);
		} else {
		    // Delete file
		    unlink($path);
		}
	    }

	    // Delete the empty directory
	    rmdir($dir);
	}
	echo ("<script type='text/javascript' >load_user()</script>");
	require_once "../config/config.php"; 
	if(isset($_GET["update_id"]) && $_GET["rid"]==$_SESSION["rid"]-1){
		$tid=$_GET["update_id"];
		$perms=get_perm_string();
		$sql="UPDATE users SET role = '$perms' WHERE id=$tid";
		$stmt = mysqli_prepare($link, $sql);
		mysqli_stmt_execute($stmt);		
	}
	if(isset($_GET['username']) && isset($_GET["delete"]))
	{
		$username_td=$_GET['username'];
		$username_td=htmlspecialchars($username_td);
		$sql="DELETE FROM users WHERE username = '$username_td';";
		//echo($sql);
		$stmt = mysqli_prepare($link, $sql);
		mysqli_stmt_execute($stmt);
		deleteDirectory("/var/www/html/user_files/$username_td/");
		log_("Deleted $username_td","BAN:DELETION");
	}
	else if(isset($_GET["verify"]) && isset($_GET['username']))
	{
		$username_td=htmlspecialchars($_GET['username']);
		$sql="UPDATE users SET banned = 0 WHERE username='$username_td'";
		$stmt = mysqli_prepare($link, $sql);
		mysqli_stmt_execute($stmt);
		log_("Unanned $username_td","BAN:UNBAN");
	}
	

		//how many users do we have?
		$cnt=0;
		$sql="SELECT COUNT(*) FROM users";
       if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Store result
                mysqli_stmt_store_result($stmt);
                    mysqli_stmt_bind_result($stmt, $cnt);
                    if(mysqli_stmt_fetch($stmt)){
			    
                    }
            } else{
                echo "<div class='alert alert-danger' role='alert'>Oops! Something went wrong. Please try again later.</div>";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
	?>


	<div class="container" style="min-height:95vh; min-width:100%">
		<div class="row">
			<div class="col-mt-12" style="overflow-x:auto">
				 <div class="d-flex flex-column align-items-center">
  				      <h4>Nach Benutzer suchen, um zu verwalten</h4>
  				      <form action="manage_user.php" method="GET" >
        				    <input type="text" class="form-control flex-grow-1 mr-2" name="username" placeholder="Benutzername eingeben" >
            					<button type="submit" class="btn btn-primary">Suchen</button>
        				</form>
				</div>

				<!-- list users and their permissions -->
				<?php
					echo("<table class='table' style='overflow-x: auto'>");
						echo("<thead>");
							echo("<tr>");
								echo("<td>Nutzer</td>");
								echo("<td>Drucken</td>");
								echo("<td>Cloud</td>");
								echo("<td>Öffentliche Cloud</td>");
								echo("<td>Alle Drucker abbrechen / freigeben</td>");
								echo("<td>Benutzereinstellungen ändern</td>");
								echo("<td>Administratoren erstellen</td>");
								echo("<td>Log ansehen</td>");
								echo("<td>APIkey ansehen</td>");
								echo("<td>Druckschlüssel erstellen</td>");
								echo("<td>Debug</td>");
								echo("<td>Alle Dateien von Öffentlicher Cloud löschen</td>");
								echo("<td>Aktualisieren</td>");
								echo("<td>Benutzer löschen</td>");
								echo("<td>Benutzer manuell verifizieren</td>");
							echo("</tr>");
						echo("</thead>");
						echo("<tbody>");
							echo("<tr>");
								//how many users do we have?
								$cnt=0;
								if(isset($_GET["username"]))
									$search=htmlspecialchars($_GET["username"]);
								else
									$search="user_not_found";

								$sql="SELECT COUNT(*) FROM users WHERE username LIKE '%$search%'";
								$stmt = mysqli_prepare($link, $sql);
								mysqli_stmt_execute($stmt);
								// Store result
								mysqli_stmt_store_result($stmt);
								mysqli_stmt_bind_result($stmt, $cnt);
								mysqli_stmt_fetch($stmt);
								mysqli_stmt_close($stmt);
								//now we know how many users we have.
								$last_id=0;
								while($cnt!=0){
									$tusername="";
									$trole="";
									$banned=0;
									$tid=0;
									$sql="select id,username,role,banned from users where id>$last_id AND username LIKE '%$search%' ORDER BY id";
									$stmt = mysqli_prepare($link, $sql);
									mysqli_stmt_execute($stmt);
									// Store result
									mysqli_stmt_store_result($stmt);
									mysqli_stmt_bind_result($stmt, $tid,$tusername,$trole,$banned);
									mysqli_stmt_fetch($stmt);
									mysqli_stmt_close($stmt);
									echo("<tr><form action='manage_user.php?update_id=$tid&rid=".$_SESSION["rid"]."&username=$search' method='post'>");
									echo("<td>$tusername</td>");
									if($trole[0]==="1")
										echo('<td><input class="form-check-input" type="checkbox" value="" name="print" checked></td>');
									else
										echo('<td><input class="form-check-input" type="checkbox" value="" name="print" ></td>');
									if($trole[1]==="1")
										echo('<td><input class="form-check-input" type="checkbox" value="" name="private_cloud" checked></td>');
									else
										echo('<td><input class="form-check-input" type="checkbox" value="" name="private_cloud" ></td>');
									if($trole[2]==="1")
										echo('<td><input class="form-check-input" type="checkbox" value="" name="public_cloud" checked></td>');
									else
										echo('<td><input class="form-check-input" type="checkbox" value="" name="public_cloud" ></td>');
									if($trole[3]==="1")
										echo('<td><input class="form-check-input" type="checkbox" value="" name="printer_ctrl_all" checked></td>');
									else
										echo('<td><input class="form-check-input" type="checkbox" value="" name="printer_ctrl_all" ></td>');
									if($trole[4]==="1")
										echo('<td><input class="form-check-input" type="checkbox" value="" name="change_user_perm" checked></td>');
									else
										echo('<td><input class="form-check-input" type="checkbox" value="" name="change_user_perm" ></td>');
									if($trole[5]==="1")
										echo('<td><input class="form-check-input" type="checkbox" value="" name="create_admin" checked></td>');
									else
										echo('<td><input class="form-check-input" type="checkbox" value="" name="create_admin" ></td>');
									if($trole[6]==="1")
										echo('<td><input class="form-check-input" type="checkbox" value="" name="view_log" checked></td>');
									else
										echo('<td><input class="form-check-input" type="checkbox" value="" name="view_log" ></td>');
									if($trole[7]==="1")
										echo('<td><input class="form-check-input" type="checkbox" value="" name="view_apikey" checked></td>');
									else
										echo('<td><input class="form-check-input" type="checkbox" value="" name="view_apikey" ></td>');
									if($trole[8]==="1")
										echo('<td><input class="form-check-input" type="checkbox" value="" name="create_key" checked></td>');
									else
										echo('<td><input class="form-check-input" type="checkbox" value="" name="create_key" ></td>');
									if($trole[9]==="1")
										echo('<td><input class="form-check-input" type="checkbox" value="" name="debug" checked></td>');
									else
										echo('<td><input class="form-check-input" type="checkbox" value="" name="debug" ></td>');
									if($trole[10]==="1")
										echo('<td><input class="form-check-input" type="checkbox" value="" name="delete_from_public_cloud" checked></td>');
									else
										echo('<td><input class="form-check-input" type="checkbox" value="" name="delete_from_public_cloud" ></td>');
									echo('<td><input type="submit" class="btn btn-dark mb-5" value="Aktualisieren"  id="button"></td>');
									echo('<td><a href="manage_user.php?username='.$tusername.'&delete" class="btn btn-danger" >Benutzer löschen</a></td>');
									if($banned==1)
										echo('<td><a href="manage_user.php?username='.$tusername.'&verify" class="btn btn-success" >Benutzer verifizieren</a></td>');
									else
										echo('<td>Benutzer bereits verifiziert</td>');
									echo("</form></tr>");
									$last_id=$tid;
									$cnt--;
								}
					//		echo("</tr>");
						echo("</tbody>");
					echo("</table>");
					mysqli_close($link);
				?>
			</div>
		</div>
	</div>

<div id="footer"></div>
</body>

</html>
