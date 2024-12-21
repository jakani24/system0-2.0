<!DOCTYPE html>
<html data-bs-theme="dark">
<?php
// Initialize the session
session_start();
include "../config/config.php";
include "../api/queue.php";
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true or $_SESSION["role"][1]!="1"){
    header("location: /login/login.php");
    exit;
}
$username=htmlspecialchars($_SESSION["username"]);
$id=$_SESSION["id"];
$username=$_SESSION["username"];
$file_upload_err="nan";
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
<?php
	$role=$_SESSION["role"];

	echo "<script type='text/javascript' >load_user()</script>";


?>
<?php $color=$_SESSION["color"]; ?>
<?php 
	$color=$_SESSION["color"]; 
	include "../assets/components.php";

	function get_base64_preview($filename){
		$base64="";
		$file=fopen($filename,"r");
		$start=-1;
		while(!feof($file)&&$start!=0){
			$buf=fgets($file);
			if(stripos($buf,"thumbnail end")!==false)
				$start=0;
			if($start==1)
				$base64.=$buf;
			if(stripos($buf,"thumbnail begin")!==false)
				$start=1;
		}
		fclose($file);
		$base64=str_replace(";","",$base64);
		$base64=str_replace(" ","",$base64);
		return $base64;
	}
	if(isset($_GET["delete"])){
		$path="/var/www/html/user_files/$username/".str_replace("..","",htmlspecialchars($_GET["delete"]));
		if(unlink($path))
			$success="Datei wurde gelöscht!";
		else
			$err="Datei konnte nicht gelöscht werden!";
	}
	if(isset($_GET["public"])){
		$path="/var/www/html/user_files/$username/".str_replace("..","",htmlspecialchars($_GET["public"]));
		$public_path="/var/www/html/user_files/public/".str_replace("..","",htmlspecialchars($_GET["public"]));
		if(copy($path,$public_path))
			$success="Datei wurde veröffentlicht";
		else
			$err="Datei konnte nicht veröffentlicht werden.";
	}
	if(!empty($_FILES['file']))
	{
		$ok_ft=array("gcode","");
		$unwanted_chr=[' ','(',')','/','\\','<','>',':',';','?','*','"','|','%'];
		$filetype = strtolower(pathinfo($_FILES['file']['name'],PATHINFO_EXTENSION));
		$path = "/var/www/html/user_files/$username/";
		$filename=basename( $_FILES['file']['name']);
		$filename=str_replace($unwanted_chr,"_",$filename);
		$path = $path . $filename;
		if(!in_array($filetype,$ok_ft))
		{
			$err="Dieser Dateityp wird nicht unterstüzt.";
		}
		else
		{
			if(move_uploaded_file($_FILES['file']['tmp_name'], $path)) {
				$success="Datei wurde hochgeladen.";
			}
			else
			{
				$err="Ein Fehler beim Uploaden der Datei ist aufgetreten! Versuche es erneut!";
			}
		}
		unset($_FILES['file']);
	}
?>
<div id="content"></div>

<head>
  <title>Eigene Dateien</title>
  
</head>
<body>
	<div class="container mt-4" style="height: auto;min-height:100vh">
		<div class="row justify-content-center">
			      <h1>Eigene Dateien</h1>
				<div class="container">
					<button type="button" class="btn btn-link" data-bs-toggle="modal" data-bs-target="#upoload_file" id="lnk_1">Datei Hochladen</button>
					<form action="cloud.php" method="POST">
						<input type="text" name="search" placeholder="Suchbegriff">
						<button type="submit" class="btn btn-secondary my-5">Suchen</button>
					</form>
				<?php
					if(!empty($success))
						echo("<center><div class='alert alert-success' role='alert'>$success</div></center>");
					if(!empty($err))
                                                echo("<center><div class='alert alert-danger' role='alert'>$err</div></center>");
				?>
				<div style="overflow-y:auto;overflow-x:auto">
				  <table class="table">
				    <thead>
				      <tr>
					<th>Preview</th>
					<th>File Name</th>
					<th>Print File</th>
					<th>Delete File</th>
					<th>Download File</th>
					<th>Make Public</th>
				      </tr>
				    </thead>
				    <tbody>
				      <?php
				      $directory = "/var/www/html/user_files/$username/";

				      // Check if the directory exists
				      if (is_dir($directory)) {
					  $files = glob($directory . '/*.gcode'); //*/


					  // Iterate through the files and display them in the table
					  $count = 1;
					  foreach ($files as $file) {
						if(isset($_POST["search"])){
							if (stripos(basename($file), $_POST["search"]) !== false) {
							      echo '<tr>';
							      echo '<td><img  style="display:block; width:100px;height:100px;" id="base64image" src="data:image;base64,' . get_base64_preview($file) . '"/></td>';
							      echo '<td>' . basename($file) . '</td>';
							      echo '<td><a href="print.php?cloudprint='.basename($file).'">Drucken</a></td>';
							      echo "<td><a href='cloud.php?delete=".basename($file)."' >" . "Löschen" . '</a></td>';
							      echo "<td><a href='/user_files/$username/".basename($file)."' download>" . "Herunterladen" . '</a></td>';
							      echo "<td><a href='cloud.php?public=".basename($file)."'>Öffentlich verfügbar machen</a></td>";
							      echo '</tr>';
							}
						}else{
							echo '<tr>';
							echo '<td><img  style="display:block; width:100px;height:100px;" id="base64image" src="data:image;base64,' . get_base64_preview($file) . '"/></td>';
							echo '<td>' . basename($file) . '</td>';
							echo '<td><a href="print.php?cloudprint='.basename($file).'">Drucken</a></td>';
							echo "<td><a href='cloud.php?delete=".basename($file)."' >" . "Löschen" . '</a></td>';
							echo "<td><a href='/user_files/$username/".basename($file)."' download>" . "Herunterladen" . '</a></td>';
							echo "<td><a href='cloud.php?public=".basename($file)."'>Öffentlich verfügbar machen</a></td>";
							echo '</tr>';
						}
					  }
				      } else {
					  echo '<tr><td colspan="2">Directory not found</td></tr>';
				      }
				      ?>
				    </tbody>
				  </table>
				</div>	
			    </div>
		</div>
	</div>
	<div class="modal fade" id="upoload_file" tabindex="1" role="dialog" aria-labelledby="upoload_file" aria-hidden="false">
		      <div class="modal-dialog" role="document">
		        <div class="modal-content">
		          <div class="modal-header">
		            <h5 class="modal-title" id="exampleModalLabel">Datei Hochladen</h5>
		            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

		          </div>
				<div class="modal-body">
					<form action="cloud.php" method="post" enctype="multipart/form-data">
						<div class="mb-3">
						    <label for="file" class="form-label">Datei wählen:</label>
						    <input type="file" class="form-control" id="file" name="file" required accept=".gcode">
						</div>
						<button type="submit" class="btn btn-secondary">Upload</button>	<br>

					</form>
				</div>
				  </div>
				</form>
			</div>
	</div>
	<div id="footer"></div>
</body>

</html>
