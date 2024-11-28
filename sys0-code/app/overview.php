<!DOCTYPE html>
<html>
<head>
<?php
// Initialize the session
session_start();
include "../config/config.php";
include "../api/queue.php";
$role=$_SESSION["role"];
$username=$_SESSION["username"];
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
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
function update_cancel_modal(printer_id,rid){
        const modal_=document.getElementById("cancel_modal");
        const button=document.getElementById("send_cancel_command");
        button.href="overview.php?cancel="+printer_id+"&rid="+rid;
	document.getElementById("open_cancel_modal").click();
}
</script>
<?php
        echo "<script type='text/javascript' >load_user()</script>";
?>
<?php $color=$_SESSION["color"]; ?>
<?php
        function seconds_to_time($seconds) {
            // Convert seconds to hours
            $hours = floor($seconds / 3600);

            // Convert remaining seconds to minutes
            $minutes = floor(($seconds % 3600) / 60);
                if($hours!=0){
                        if($hours==1)
                                return sprintf("%d Stunde %d Minuten", $hours, $minutes);
                        else
                                return sprintf("%d Stunden %d Minuten", $hours, $minutes);
                }
                else
                        return sprintf("%d Minuten", $minutes);
        }
        function short_path($filePath, $firstCharsCount, $lastCharsCount) {
	    // Get the first few characters of the path
	    $filePath=str_replace(".gcode","",$filePath);
	    if(strlen($filePath)>=$firstCharsCount+$lastCharsCount+3){
		    $firstChars = substr($filePath, 0, $firstCharsCount);
		    
		    // Get the last few characters of the path
		    $lastChars = substr($filePath, -$lastCharsCount);
	    
		    // Return the shortened path
		    return $firstChars . "..." . $lastChars;
		}
		else{
			return $filePath;
		}
	}
        $color=$_SESSION["color"];
        include "../assets/components.php";
        if(!isset($_SESSION["rid"]))
                $_SESSION["rid"]=0;
        $_SESSION["rid"]++;

	if(isset($_GET["set_class"]) && isset($_POST["class"])){
		$class_id=htmlspecialchars($_POST["class"]);
		$sql="update users set class_id=$class_id where username='$username'";
		$stmt = mysqli_prepare($link, $sql);
		mysqli_stmt_execute($stmt);
		$stmt->close();
		$sql="select name from class where id=$class_id";
		$stmt = mysqli_prepare($link, $sql);
		mysqli_stmt_execute($stmt);
		$class_name="";
		mysqli_stmt_bind_result($stmt, $class_name);
		$stmt->close();
		$_SESSION["class"]=$class_name;
		$_SESSION["class_id"]=$class_id;
	}
?>

  <title>Alle Drucker</title>
<style>
 /* Style for the description */
    .description {
        display: none; /* Hide the description by default */
        position: absolute;
        background-color: rgba(0, 0, 0, 0.7);
        color: #fff;
        padding: 10px;
        border-radius: 5px;
        width: 200px;
    }
    
    /* Style for the element to trigger hover */
    .hover-element {
        position: relative;
        /* Add some space below the element */
        
    }
    
    /* Style for the element to trigger hover when hovered */
    .hover-element:hover .description {
        display: block; /* Show the description on hover */
    }

</style>
</head>
<body>
        <div id="content"></div>
      	<!-- placeholder button to be activated to open cancel modal -->
	<button style="display:none" type="button" class="btn btn-primary" data-bs-toggle="modal" id="open_cancel_modal" data-bs-target="#cancel_modal">
		  Launch cancel modal
	</button>

	<div>
                <div class="row justify-content-center">
                <div style="width: 100%;min-height:95vh">
                                <?php
                                        if(isset($_GET['free'])&&$_GET["rid"]==($_SESSION["rid"]-1))
                                        {
						$cnt="";
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
						//try to find out how much filament was used
						$stmt->close();
						//load apikey etc
						$url="";
						$apikey="";
						$sql="select printer_url,apikey from printer where id=$printer_id";
						$stmt = mysqli_prepare($link, $sql);
                                                mysqli_stmt_execute($stmt);
                                                mysqli_stmt_store_result($stmt);
                                                mysqli_stmt_bind_result($stmt, $url,$apikey);
                                                mysqli_stmt_fetch($stmt);
						$stmt->close();
						//connect to the printer
						exec("curl --max-time 10 $url/api/job?apikey=$apikey > /var/www/html/user_files/$username/finish.json");
						$fg=file_get_contents("/var/www/html/user_files/$username/finish.json");
                                                $json=json_decode($fg,true);
						$userid=$_SESSION["id"];
						if(isset($json['job']['filament']['tool0']['volume'])){
							$filament_usage=intval($json['job']['filament']['tool0']['volume']);
							$sql="UPDATE users SET filament_usage = COALESCE(filament_usage,0) + $filament_usage WHERE id = $cnt";
							//echo($sql);
							$stmt = mysqli_prepare($link, $sql);
                                                	mysqli_stmt_execute($stmt);
						}
						
						//echo("used $filament_usage mm of filament");
                                        }
                                        if(isset($_GET['remove_queue'])&&$_GET["rid"]==($_SESSION["rid"]-1))
                                        {
                                                $id=htmlspecialchars($_GET['remove_queue']);
                                                $sql="delete from queue where id=$id";
                                                $stmt = mysqli_prepare($link, $sql);
                                                mysqli_stmt_execute($stmt);
                                        }
                                        if(isset($_GET['cancel'])&&$_GET["rid"]==($_SESSION["rid"]-1))
                                        {
                                                $apikey="";
                                                $printer_url="";
                                                $printer_id=htmlspecialchars($_GET['cancel']);
                                                $sql="select used_by_userid,apikey,printer_url from printer where id=$printer_id";
                                                $stmt = mysqli_prepare($link, $sql);
                                                mysqli_stmt_execute($stmt);
                                                mysqli_stmt_store_result($stmt);
                                                mysqli_stmt_bind_result($stmt, $cnt,$apikey,$printer_url);
                                                mysqli_stmt_fetch($stmt);

                                                exec("curl -k -H \"X-Api-Key: $apikey\" -H \"Content-Type: application/json\" --data '{\"command\":\"cancel\"}' \"$printer_url/api/job\" > /var/www/html/user_files/$username/json.json");
                                                $fg=file_get_contents("/var/www/html/user_files/$username/json.json");
                                                $json=json_decode($fg,true);
                                                if($json["error"]!="")
                                                {
                                                        echo("<div class='alert alert-danger' role='alert'>Beim abbrechen ist es zu einem Fehler gekommen. Bitte versuche es später erneut.</div>");
                                                }
                                                else
                                                {
                                                        $sql="update printer set cancel=1 where id=$printer_id";
                                                        $stmt = mysqli_prepare($link, $sql);
                                                        mysqli_stmt_execute($stmt);
                                                }

                                        }

                                        $cnt=0;
                                        $url="";
                                        $apikey="";
                                        if(isset($_GET["private"]))
                                                $sql="select count(*) from printer where used_by_userid=".$_SESSION["id"];
                                        else
                                                $sql="select count(*) from printer";
                                        $stmt = mysqli_prepare($link, $sql);
                                        mysqli_stmt_execute($stmt);
                                        mysqli_stmt_store_result($stmt);
                                        mysqli_stmt_bind_result($stmt, $cnt);
                                        mysqli_stmt_fetch($stmt);
                                        //echo($cnt);
                                        $is_free=0;
                                        echo("<div><div class='row'>");
                                        echo("<div class='d-flex flex-wrap justify-content-center align-items-stretch'>");
                                        echo("<div style='width:100%;margin-left:5px'>");
                                                if(isset($_GET["private"]))
                                                        echo("<br><a class='btn btn-dark' href='overview.php'>Alle Drucker anzeigen</a>");
                                                else
                                                        echo("<br><a class='btn btn-dark' href='overview.php?private'>Nur eigene Aufträge anzeigen</a>");
                                        echo("</div>");
                                        $last_id=0;
                                        $system_status=0;
                                        $rotation=0;
                                        while($cnt!=0)
                                        {
                                                $userid=0;
                                                if(isset($_GET["private"]))
                                                        $sql="select rotation,free,id,printer_url,apikey,cancel,used_by_userid,system_status,color from printer where id>$last_id and used_by_userid=".$_SESSION["id"]." ORDER BY id";
                                                else
                                                        $sql="select rotation,free,id,printer_url,apikey,cancel,used_by_userid,system_status,color from printer where id>$last_id ORDER BY id";
                                                $cancel=0;
                                                $filament_color="";
                                                $stmt = mysqli_prepare($link, $sql);
                                                mysqli_stmt_execute($stmt);
                                                mysqli_stmt_store_result($stmt);
                                                mysqli_stmt_bind_result($stmt, $rotation,$is_free,$printer_id,$url,$apikey,$cancel,$userid,$system_status,$filament_color);
                                                mysqli_stmt_fetch($stmt);
                                                $last_id=$printer_id;
						$filament_color=intval($filament_color);
						//get the real color
						$sql="select name from filament where internal_id=$filament_color";
						$stmt = mysqli_prepare($link, $sql);
                                                mysqli_stmt_execute($stmt);
                                                mysqli_stmt_store_result($stmt);
                                                mysqli_stmt_bind_result($stmt,$filament_color);
                                                mysqli_stmt_fetch($stmt);

                                                if($is_free==0 && $system_status==0){
                                                        //printer is printing
                                                        exec("curl --max-time 10 $url/api/job?apikey=$apikey > /var/www/html/user_files/$username/json.json");
                                                        $fg=file_get_contents("/var/www/html/user_files/$username/json.json");
                                                        $json=json_decode($fg,true);

                                                        $used_by_user="";
                                                        $sql="select username from users where id=$userid";
                                                        $stmt = mysqli_prepare($link, $sql);
                                                        mysqli_stmt_execute($stmt);
                                                        mysqli_stmt_store_result($stmt);
                                                        mysqli_stmt_bind_result($stmt, $used_by_user);
                                                        mysqli_stmt_fetch($stmt);
                                                        $username2=explode("@",$used_by_user);

                                                        $progress=(int) $json['progress']['completion'];
                                                        if($progress<0)
                                                                $progress=-$progress;
                                                        $file=$json['job']['file']['name'];
                                                        if($progress==100){
                                                                        $print_time=seconds_to_time(intval($json["progress"]["printTime"]));
                                                                        $print_time_left=seconds_to_time(intval($json["progress"]["printTimeLeft"]));
                                                                        $print_time_total=seconds_to_time(intval($json["job"]["estimatedPrintTime"]));
                                                                        echo("<div class='card m-4 align-self-start'>");
                                                                        echo("<div class='card-body'>");
                                                                        echo("<h5 class='card-title'>Drucker $printer_id</h5>");
                                                                        echo("</div>");
                                                                        echo("<div class='card-body'>");
                                                                        echo("<iframe height='230px' scrolling='no' width='100%' src='/app/webcam.php?printer_id=$printer_id&username=".$_SESSION["username"]."&url=$url&rotation=$rotation'></iframe>");
                                                                        echo("<div class='progress'>");
                                                                        echo("<div class='progress-bar' role='progressbar' style='width: $progress%' aria-valuenow='$progress' aria-valuemin='0' aria-valuemax='100'>$progress%</div>");
                                                                        echo("</div>");
                                                                        echo("<table class='table table-borderless'>");
                                                                        echo("<thead>");
                                                                        echo("<tr><td>Status</td><td style='color:green'>Fertig</td></tr>");
                                                                        echo("<tr><td>Genutzt von</td><td>".$username2[0]."</td></tr>");
                                                                        if(!empty($filament_color) && $filament_color!=NULL)
                                                        			echo("<tr><td>Filamentfarbe</td><td >$filament_color</td></tr>");
                                                                        echo("<tr><td>Erwartete Druckzeit</td><td>$print_time_total</td></tr>");
                                                                        echo("<tr><td>Verbleibende Druckzeit</td><td>$print_time_left</td></tr>");
                                                                        echo("<tr><td>Vergangene Druckzeit</td><td>$print_time</td></tr>");
                                                                        echo("<tr><td>Datei</td><td><div class='hover-element'>".short_path($json["job"]["file"]["name"],10,10)."<div class='description'>".$json["job"]["file"]["name"]."</div></div></td></tr>");
                                                                        echo("</div>");
                                                                        if($userid==$_SESSION["id"] or $role[3]==="1"){
                                                                                echo("<tr><td><a class='btn btn-success' href='overview.php?free=$printer_id&rid=".$_SESSION["rid"]."'>Freigeben</a></td></tr>");
                                                                        }
                                                                        echo("</thead>");
                                                                        echo("</table>");
                                                                        echo("</div>");
                                                                        echo("</div>");
                                                        }
                                                        else if($cancel==1){
                                                                        $print_time=seconds_to_time(intval($json["progress"]["printTime"]));
                                                                        $print_time_left=seconds_to_time(intval($json["progress"]["printTimeLeft"]));
                                                                        $print_time_total=seconds_to_time(intval($json["job"]["estimatedPrintTime"]));
                                                                        echo("<div class='card m-4 align-self-start'>");
                                                                        echo("<div class='card-body'>");
                                                                        echo("<h5 class='card-title'>Drucker $printer_id</h5>");
                                                                        echo("</div>");
                                                                        echo("<div class='card-body'>");
                                                                        echo("<iframe height='230px' scrolling='no' width='100%' src='/app/webcam.php?printer_id=$printer_id&username=".$_SESSION["username"]."&url=$url&rotation=$rotation'></iframe>");
                                                                        echo("<div class='progress'>");
                                                                        echo("<div class='progress-bar' role='progressbar' style='width: $progress%' aria-valuenow='$progress' aria-valuemin='0' aria-valuemax='100'>$progress%</div>");
                                                                        echo("</div>");
                                                                        echo("<table class='table table-borderless'>");
                                                                        echo("<thead>");
                                                                        echo("<tr><td>Status</td><td style='color:red'>Druck Abgebrochen</td></tr>");
                                                                        echo("<tr><td>Genutzt von</td><td>".$username2[0]."</td></tr>");
                                                                        if(!empty($filament_color) && $filament_color!=NULL)
                                                        			echo("<tr><td>Filamentfarbe</td><td >$filament_color</td></tr>");
                                                                        echo("<tr><td>Erwartete Druckzeit</td><td>$print_time_total</td></tr>");
                                                                        echo("<tr><td>Verbleibende Druckzeit</td><td>$print_time_left</td></tr>");
                                                                        echo("<tr><td>Vergangene Druckzeit</td><td>$print_time</td></tr>");
                                                                        echo("<tr><td>Datei</td><td><div class='hover-element'>".short_path($json["job"]["file"]["name"],10,10)."<div class='description'>".$json["job"]["file"]["name"]."</div></div></td></tr>");
                                                                        if($userid==$_SESSION["id"] or $role[3]=="1"){
                                                                                echo("<tr><td><a class='btn btn-success' href='overview.php?free=$printer_id&rid=".$_SESSION["rid"]."'>Freigeben</a></td></tr>");
                                                                        }
                                                                        echo("</thead>");
                                                                        echo("</table>");
                                                                        echo("</div>");
                                                                        echo("</div>");
                                                        }
                                                        else{
                                                                        $print_time=seconds_to_time(intval($json["progress"]["printTime"]));
                                                                        $print_time_left=seconds_to_time(intval($json["progress"]["printTimeLeft"]));
                                                                        $print_time_total=seconds_to_time(intval($json["job"]["estimatedPrintTime"]));
                                                                        echo("<div class='card m-4 align-self-start'>");
                                                                        echo("<div class='card-body'>");
                                                                        echo("<h5 class='card-title'>Drucker $printer_id</h5>");
                                                                        echo("</div>");
                                                                        echo("<div class='card-body'>");
                                                                        echo("<iframe height='230px' scrolling='no' width='100%' src='/app/webcam.php?printer_id=$printer_id&username=".$_SESSION["username"]."&url=$url&rotation=$rotation'></iframe>");
                                                                        echo("<div class='progress'>");
                                                                        echo("<div class='progress-bar' role='progressbar' style='width: $progress%' aria-valuenow='$progress' aria-valuemin='0' aria-valuemax='100'>$progress%</div>");
                                                                        echo("</div>");
                                                                        echo("<table class='table table-borderless'>");
                                                                        echo("<thead>");
                                                                        echo("<tr><td>Status</td><td style='color:orange'>Drucken</td></tr>");
                                                                        echo("<tr><td>Genutzt von</td><td>".$username2[0]."</td></tr>");
                                                                        if(!empty($filament_color) && $filament_color!=NULL)
                                                        			echo("<tr><td>Filamentfarbe</td><td >$filament_color</td></tr>");
                                                                        echo("<tr><td>Erwartete Druckzeit</td><td>$print_time_total</td></tr>");
                                                                        echo("<tr><td>Verbleibende Druckzeit</td><td>$print_time_left</td></tr>");
                                                                        echo("<tr><td>Vergangene Druckzeit</td><td>$print_time</td></tr>");
                                                                        echo("<tr><td>Datei</td><td><div class='hover-element'>".short_path($json["job"]["file"]["name"],10,10)."<div class='description'>".$json["job"]["file"]["name"]."</div></div></td></tr>");
                                                                        if($userid==$_SESSION["id"] or $role[3]==="1"){
                                                                                //echo("<tr><td><a class='btn btn-danger' data-toggle='modal' data-target='cancel_modal'>Abbrechen</a></td></tr>");
										echo("<tr><td><button class='btn btn-danger' onclick='update_cancel_modal(\"$printer_id\",\"".$_SESSION["rid"]."\")'>Abbrechen</button></td></tr>");
                                                                        }
                                                                        echo("</thead>");
                                                                        echo("</table>");
                                                                        echo("</div>");
                                                                        echo("</div>");
                                                        }
                                                }else if($system_status==0){
                                                        //printer is free
                                                        echo("<div class='card m-4 align-self-start'>");
                                                        echo("<div class='card-body'>");
                                                        echo("<h5 class='card-title'>Drucker $printer_id</h5>");
                                                        echo("</div>");
                                                        echo("<div class='card-body'>");
                                                        echo("<iframe height='230px' scrolling='no' width='100%' src='/app/webcam.php?printer_id=$printer_id&username=".$_SESSION["username"]."&url=$url&rotation=$rotation'></iframe>");
                                                        echo("<table class='table table-borderless'>");
                                                        echo("<thead>");
                                                        echo("<tr><td>Status</td><td style='color:green'>Bereit</td></tr>");
                                                        if(!empty($filament_color) && $filament_color!=NULL)
                                                        	echo("<tr><td>Filamentfarbe</td><td >$filament_color</td></tr>");
                                                        echo("<tr><td><a class='btn btn-dark' href='print.php?preselect=$printer_id'>Drucken</a></td></tr>");
                                                        echo("</thead>");
                                                        echo("</table>");
                                                        echo("</div>");
                                                        echo("</div>");

                                                }else{
							//printer is free but has a problem
                                                        echo("<div class='card m-4 align-self-start'>");
                                                        echo("<div class='card-body'>");
                                                        echo("<h5 class='card-title'>Drucker $printer_id</h5>");
                                                        echo("</div>");
                                                        echo("<div class='card-body'>");
                                                        echo("<iframe height='230px' scrolling='no' width='100%' src='/app/webcam.php?printer_id=$printer_id&username=".$_SESSION["username"]."&url=$url&rotation=$rotation'></iframe>");
                                                        echo("<table class='table table-borderless'>");
                                                        echo("<thead>");
                                                        echo("<tr><td>Status</td><td style='color:red'>Problem / nicht Betriebsbereit</td></tr>");
                                                        echo("</thead>");
                                                        echo("</table>");
                                                        echo("</div>");
                                                        echo("</div>");

						}
                                                $cnt--;
                                        }
                                        echo("</div></div>");

                                ?>
                                <br><br>
                                <?php
                                        test_queue($link);
                                ?>
                                </div>
            </div>
        </div>
        </div>
        <!-- We currently do not show the queue -->
        <div style="width: 100hh">
        <center><h3>Warteschlange</h3></center>
        <?php
                $userid=$_SESSION["id"];
                $cnt=0;
                $filepath="";
                $sql="select count(*) from queue";
                $stmt = mysqli_prepare($link, $sql);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);
                mysqli_stmt_bind_result($stmt, $cnt);
                mysqli_stmt_fetch($stmt);
                //echo($cnt);
                echo("<div class='container'><div class='row'><div class='col'><div class='overflow-auto'><table class='table'><thead><tr><th>Datei</th><th>Drucken auf Drucker</th><th>aus der Warteschlange entfernen</th></tr></thead><tbody>");
                $last_id=0;
                $form_userid=0;
                $print_on=0;
                while($cnt!=0)
                {
                        $sql="select id,filepath,from_userid,print_on from queue where id>$last_id order by id";
                        $cancel=0;
                        $stmt = mysqli_prepare($link, $sql);
                        echo mysqli_error($link);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_store_result($stmt);
                        mysqli_stmt_bind_result($stmt, $queue_id,$filepath,$from_userid,$print_on);
                        mysqli_stmt_fetch($stmt);
                        $filepath=basename($filepath);
                        $last_id=$queue_id;
                        echo("<tr><td>$filepath</td>");
                        if($print_on==-1)
                                echo("<td>Erster verfügbarer Drucker</td>");
                        else
                                echo("<td>$print_on</td>");
                        if($_SESSION["role"][3]==="1" or $_SESSION["id"]==$from_userid)
                                echo("<td><form method='POST' action='?remove_queue=$queue_id&rid=".$_SESSION["rid"]."'><button type='submit' value='remove'  name='remove' class='btn btn-danger'>Löschen</button></form></td></tr>");

                        $cnt--;
                }
                echo("</tbody></table></div></div></div></div>");
        ?>
        <br><br>
        </div>
	<!-- class selector -->
	<div class="modal fade" id="select_class" tabindex="1" role="dialog" aria-labelledby="class" aria-hidden="false">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
		  		<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">Klasse angeben</h5>
				</div>
				<div class="modal-body">
		  			<p>Hallo <?php echo(str_replace("."," ",str_replace("@kantiwattwil.ch","",$_SESSION["username"]))); ?> bitte wähle deine Klasse aus der Liste unten aus. <br>
					Wenn deine Klasse nicht in der Liste ist, bitte deine Lehrperson deine Klasse in den Einstellungen hinzuzufügen.</p>
					<form action="overview.php?set_class" method="post">
						<select name="class">
						<?php
							//alle klassen auflisten
							$sql="select * from class";
							$stmt = mysqli_prepare($link, $sql);
							$stmt->execute();
							$result = $stmt->get_result();
							while($row = $result->fetch_assoc()) {
								echo("<option value='".$row["id"]."'>".$row["name"]."</option>");
							}
						?>
							<option value='0'>Lehrperson</option>
						</select>
				</div>
				<div class="modal-footer">
					<button type="submit" name="submit" class="btn btn-dark">Bestätigen</button>
				</div>
			</div>
			</form>
		</div>
	</div>

	<!-- cancel modal -->
        <div class="modal fade" id="cancel_modal" tabindex="1" role="dialog" aria-labelledby="cancel_modal" aria-hidden="false">
              <div class="modal-dialog" role="document">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Druck abbrechen</h5>
                  </div>
                        <div class="modal-body">
				Möchtest du den Druck wirklich abbrechen?
                        </div>
                        <div class="modal-footer">
				<button type="button" class="btn btn-primary" data-bs-dismiss="modal">nicht abbrechen</button>
                                <a type="button" id="send_cancel_command" href="#" class="btn btn-danger">Druck abbrechen</a>
                        </div>
                          </div>
                </div>
        </div>

	<?php
		if($_SESSION["class_id"]==""){
			echo("<script>");
			    echo("var modal = document.getElementById('select_class');");
			    echo("modal.classList.add('show');");
			    echo("modal.style.display = 'block';");
			    echo("modal.removeAttribute('aria-hidden');");
			    echo("document.body.classList.add('modal-open');");
			echo("</script>");
		}
	?>
        <div id="footer"></div>
</body>

</html>
