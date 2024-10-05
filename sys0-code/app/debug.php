<!DOCTYPE html>
<html>
<?php
// Initialize the session
session_start();
include "../config/config.php";
include "../api/queue.php";
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"][9]!=="1"){
    header("location: /login/login.php");
    exit;
}
$username=htmlspecialchars($_SESSION["username"]);
$id=$_SESSION["id"];
?>


<script src="/assets/js/load_page.js"></script>
<script>
function load_user()
{
	$(document).ready(function(){
   	$('#content').load("/assets/php/user_page.php");
	});
}

function update_input(input,action,id){
	var selector=document.getElementById(input);
	var selector_value=selector.value;
	fetch("/api/printer_settings.php?action="+action+"&value="+selector.value+"&id="+id);

}

async function delete_input(input,action,id,row){
	var selector=document.getElementById(input);
	var selector_value=selector.value;
	await fetch("/api/printer_settings.php?action="+action+"&value="+selector.value+"&id="+id);
	//document.getElementById("table1").deleteRow(row);
	location.reload();
}
</script>
<?php
	$role=$_SESSION["role"];
	echo "<script type='text/javascript' >load_user()</script>";


?>
<?php $color=$_SESSION["color"]; ?>
<?php 
	$color=$_SESSION["color"]; 
	include "../assets/components.php";
	$tab=$_GET["show"];
?>
<div id="content"></div>

<head>
  <title>Drucker Einstellungen</title>
  
</head>
<body>
	<div class="container mt-5" style="min-height: 95vh;">
		<div class="row justify-content-center">
	  	<div style="width: 100hh">
		<ul class="nav nav-tabs">
			<li class="nav-item">
				<a class="nav-link" href="debug.php?show=printer_settings" id="printer_settings_tab">Druckereinstellungen</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="debug.php?show=camera_settings" id="camera_settings_tab">Kameraeinstellungen</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="debug.php?show=class_settings" id="class_settings_tab">Klasseneinstellungen</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="debug.php?show=filament_settings" id="filament_settings_tab">Filamenteinstellungen</a>
			</li>

		</ul>
		<div id="printer_settings" style="display:none">
	      <h1>Druckerfreigabe erzwingen (falls beim freigeben Fehlermeldungen angezeigt werden)</h1>
				<?php
					if(isset($_POST['free']))
					{
						$printer_id=htmlspecialchars($_GET['free']);
						$sql="select used_by_userid from printer where id=$printer_id";
						$stmt = mysqli_prepare($link, $sql);					
						mysqli_stmt_execute($stmt);
						mysqli_stmt_store_result($stmt);
						mysqli_stmt_bind_result($stmt, $cnt);
						mysqli_stmt_fetch($stmt);	
						$sql="update printer set free=1,printing=0,cancel=0 ,used_by_userid=0 where id=$printer_id";
						$stmt = mysqli_prepare($link, $sql);					
						mysqli_stmt_execute($stmt);
					}
					if($_GET["action"]=="add_filament"){
						$name=$_POST["filament_name"];
						$id=$_POST["filament_id"];
						$sql="INSERT INTO filament (internal_id,name) VALUES ($id,'$name')";
						$stmt = mysqli_prepare($link, $sql);					
						mysqli_stmt_execute($stmt);
					}
					if($_GET["action"]=="add_class"){
						$name=$_POST["class_name"];
						$id=$_POST["filament_id"];
						$sql="INSERT INTO class (name) VALUES ('$name')";
						$stmt = mysqli_prepare($link, $sql);					
						mysqli_stmt_execute($stmt);
					}
					if($_GET["update_status"]){
						$status=$_GET["status"];
						$printer=$_GET["update_status"];
						$sql="UPDATE printer SET system_status=$status WHERE id = $printer";
						$stmt = mysqli_prepare($link, $sql);
                                                mysqli_stmt_execute($stmt);
					}
					$cnt=0;
					$url="";
					$apikey="";
					$sql="select count(*) from printer";
					$stmt = mysqli_prepare($link, $sql);					
					mysqli_stmt_execute($stmt);
					mysqli_stmt_store_result($stmt);
					mysqli_stmt_bind_result($stmt, $cnt);
					mysqli_stmt_fetch($stmt);	
					//echo($cnt);
					echo("<div class='container'><div class='row'><div class='col'><div class='overflow-auto'><table class='table'><thead><tr><th>Druckerid</th><th>Freigeben</th><th>Druckerstatus ändern</th></tr></thead><tbody>");
					$last_id=0;					
					$system_status=0;
					while($cnt!=0)
					{
						$userid=0;
						$sql="select id,printer_url,apikey,cancel,used_by_userid, system_status from printer where id>$last_id ORDER BY id";
						$cancel=0;
						$stmt = mysqli_prepare($link, $sql);					
						mysqli_stmt_execute($stmt);
						mysqli_stmt_store_result($stmt);
						mysqli_stmt_bind_result($stmt, $printer_id,$url,$apikey,$cancel,$userid,$system_status);
						mysqli_stmt_fetch($stmt);
						$last_id=$printer_id;
						$used_by_user="";
						$sql="select username from users where id=$userid";
						$stmt = mysqli_prepare($link, $sql);					
						mysqli_stmt_execute($stmt);
						mysqli_stmt_store_result($stmt);
						mysqli_stmt_bind_result($stmt, $used_by_user);
						mysqli_stmt_fetch($stmt);

						if($system_status==0)
							echo("<tr><td>$printer_id</td><td><form method='POST' action='?free=$printer_id&show=$tab'><button type='submit' value='free'  name='free' class='btn btn-dark'>Free</button></form></td><td><a href='debug.php?update_status=$printer_id&status=1&show=$tab' class='btn btn-danger'>Status auf kaputt setzen</a></td></tr>");
						else
							echo("<tr><td>$printer_id</td><td><form method='POST' action='?free=$printer_id&show=$tab'><button type='submit' value='free'  name='free' class='btn btn-dark'>Free</button></form></td><td><a href='debug.php?update_status=$printer_id&status=0&show=$tab' class='btn btn-success'>Status auf bereit setzen</a></td></tr>");
						$cnt--;
					}
					echo("</tbody></table></div></div></div></div>");
				?>
				<br><br>

			</div>
			<div id="camera_settings" style="display:none">
			<!-- Rotation der Druckerkameras: -->
			<h1>Rotation der Druckerkameras</h1>
			<?php
				//list printers => form => action=rot&rot=180
				$cnt=0;
				$url="";
				$apikey="";
				$sql="select count(*) from printer";
				$stmt = mysqli_prepare($link, $sql);					
				mysqli_stmt_execute($stmt);
				mysqli_stmt_store_result($stmt);
				mysqli_stmt_bind_result($stmt, $cnt);
				mysqli_stmt_fetch($stmt);	
				//echo($cnt);
				echo("<div class='container'><div class='row'><div class='col'><div class='overflow-auto'><table class='table'><thead><tr><th>Druckerid</th><th>Rotation</th></tr></thead><tbody>");
				$last_id=0;	
				$rotation=0;
				while($cnt!=0)
				{
					$userid=0;
					$sql="select rotation,id from printer where id>$last_id ORDER BY id";
					$cancel=0;
					$stmt = mysqli_prepare($link, $sql);					
					mysqli_stmt_execute($stmt);
					mysqli_stmt_store_result($stmt);
					mysqli_stmt_bind_result($stmt, $rotation,$printer_id);
					mysqli_stmt_fetch($stmt);

					
					$last_id=$printer_id;
					
					$used_by_user="";

					echo("<tr><td>$printer_id</td><td><form method='POST' action='?id=$printer_id'><input type='number' value='$rotation' id='rotation$printer_id' name='rotation' placeholder='rotation (deg)' oninput='update_input(\"rotation$printer_id\",\"update_rotation\",\"$printer_id\");'></input></td></form></tr>");
					
					$cnt--;
				}
				echo("</tbody></table></div></div></div>");
			?>
			</div></div>
			<div id="class_settings" style="display:none">
			<h1>Klassen</h1>
			<?php
				
					$cnt=0;
					$url="";
					$apikey="";
					$sql="select count(*) from class";
					$stmt = mysqli_prepare($link, $sql);					
					mysqli_stmt_execute($stmt);
					mysqli_stmt_store_result($stmt);
					mysqli_stmt_bind_result($stmt, $cnt);
					mysqli_stmt_fetch($stmt);	
					//echo($cnt);
					echo("<div class='container'><div class='row'><div class='col'><div class='overflow-auto'><table class='table' id='table2'><thead><tr><th>Klasse</th><th>Hinzufügen/Löschen</th></tr></thead><tbody>");
					
					//form to add a color
					echo("<form action='debug.php?action=add_class&show=$tab' method='post'>");
						echo("<td><input type='text' placeholder='Klasse' name='class_name' required></input></td>");
						echo("<td><button type='submit' value='add' class='btn btn-primary'>Hinzufügen</button></td>");
					echo("</form>");
					
					$last_id=0;	
					$color="";
					$id=0;
					$row=1;
					while($cnt!=0)
					{
						$userid=0;
						$sql="select id,name from class where id>$last_id ORDER BY id";
						$cancel=0;
						$stmt = mysqli_prepare($link, $sql);					
						mysqli_stmt_execute($stmt);
						mysqli_stmt_store_result($stmt);
						mysqli_stmt_bind_result($stmt,$id, $name);
						mysqli_stmt_fetch($stmt);

						
						$last_id=$id;
						
						$used_by_user="";
						$row++;
						echo("<tr><td><input type='text' id='class$id' value='$name' name='class' placeholder='Klasse' oninput='update_input(\"class$id\",\"update_class\",\"$id\");'></input></td><td><button class='btn btn-danger' onclick='delete_input(\"class$id\",\"delete_class\",\"$id\",$row);'>Löschen</button></td></tr>");
						$cnt--;
					}
					echo("</tbody></table></div></div></div>");
					echo("</div>");

			?>
				</div>
				<div id="filament_settings" style="display:none">
				<h1>Filamente</h1>
				<?php
					//list printers => form => color
					$cnt=0;
					$url="";
					$apikey="";
					$sql="select count(*) from filament";
					$stmt = mysqli_prepare($link, $sql);					
					mysqli_stmt_execute($stmt);
					mysqli_stmt_store_result($stmt);
					mysqli_stmt_bind_result($stmt, $cnt);
					mysqli_stmt_fetch($stmt);	
					//echo($cnt);
					echo("<div class='container'><div class='row'><div class='col'><div class='overflow-auto'><table class='table' id='table1'><thead><tr><th>Filamente</th><th>Farbe</th><th>Hinzufügen/Löschen</th></tr></thead><tbody>");
					
					//form to add a color
					echo("<form action='debug.php?action=add_filament&show=$tab' method='post'>");
						echo("<td><input type='number' placeholder='Filament id' name='filament_id' required></input></td>");
						echo("<td><input type='text' placeholder='filament  Farbe' name='filament_name' required></input></td>");
						echo("<td><button type='submit' value='add' class='btn btn-primary'>Hinzufügen</button></td>");
					echo("</form>");
					
					$last_id=0;	
					$color="";
					$id=0;
					$row=1;
					while($cnt!=0)
					{
						$userid=0;
						$sql="select id,name,internal_id from filament where id>$last_id ORDER BY id";
						$cancel=0;
						$stmt = mysqli_prepare($link, $sql);					
						mysqli_stmt_execute($stmt);
						mysqli_stmt_store_result($stmt);
						mysqli_stmt_bind_result($stmt,$id, $color,$printer_id);
						mysqli_stmt_fetch($stmt);

						
						$last_id=$id;
						
						$used_by_user="";
						$row++;
						echo("<tr><td>$printer_id</td><td><form method='POST' action='?id=$printer_id'><input type='text' id='filament$printer_id' value='$color' name='color' placeholder='Filamentfarbe' oninput='update_input(\"filament$printer_id\",\"update_filament\",\"$printer_id\");'></input></td></form><td><button class='btn btn-danger' onclick='delete_input(\"filament$printer_id\",\"delete_filament\",\"$printer_id\",$row);'>Löschen</button></td></tr>");
						$cnt--;
					}
					echo("</tbody></table></div></div></div>");
					echo("</div>");
				
				?>
				</div>
				<?php
					test_queue($link);
				?>
				</div>
	    </div>
	  </div>
	</div>
	<div id="footer"></div>
<script>
	//decide which div should be shown:
    // Get the URL parameters
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);

    // Get the value of the "show" parameter
    const show_div = document.getElementById(urlParams.get('show'));
	const nav_tab = document.getElementById(urlParams.get('show')+"_tab");
	show_div.style.display="block";
	nav_tab.setAttribute('class', 'nav-link active');
</script>
</body>

</html>
