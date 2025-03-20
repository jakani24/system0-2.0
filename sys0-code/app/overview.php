<!DOCTYPE html>
<html data-bs-theme="dark">
<head>
<title>System0 Übersicht</title>
<?php
	// Initialize the session
	session_start();
	include "../config/config.php";
	include "../api/queue.php";
	include "../assets/components.php";
	$role=$_SESSION["role"];
	$username=$_SESSION["username"];
	// Check if the user is logged in, if not then redirect him to login page
	if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
	    header("location: /login/login.php");
	    exit;
	}
	$username=htmlspecialchars($_SESSION["username"]);
	$id=$_SESSION["id"];
	$_SESSION["rid"]++;

	//echo("GOT RID: ".$_GET["rid"]." Expected RID: ".$_SESSION["rid"]-1);
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
                $sql="update printer set free=1,printing=0,cancel=0, used_by_userid=0 where id=$printer_id";
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
			$stmt = mysqli_prepare($link, $sql);
                        mysqli_stmt_execute($stmt);
		}
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

<script src="/assets/js/load_page.js"></script>
<script>
	function load_user()
	{
	        $(document).ready(function(){
	        $('#content').load("/assets/php/user_page.php");
	        });
	}
	load_user();
	//check queue
	fetch("/api/async_queue_check.php");
</script>
<style>
    .description {
        display: none; /* Hide the description by default */
        position: absolute;
        background-color: rgba(0, 0, 0, 0.7);
        color: #fff;
        padding: 10px;
        border-radius: 5px;
        width: 200px;
        z-index: 10; /* Ensure it appears above other elements */
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
	<div id="printer-container"></div>


	<!-- Modals -->

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
					<button type="submit" name="submit" class="btn btn-secondary">Bestätigen</button>
				</div>
			</div>
			</form>
		</div>
	</div>

<script>
function update_cancel_modal(printer_id,rid){
        const modal_=document.getElementById("cancel_modal");
        const button=document.getElementById("send_cancel_command");
        button.href="overview.php?cancel="+printer_id+"&rid="+rid;
	document.getElementById("open_cancel_modal").click();
}
function fetchPrinterData() {
    fetch('/api/fetch_printer_data.php')
        .then(response => response.json())
        .then(data => {
            // Update the printer data on the page
            updatePrinterData(data);
        })
        .catch(error => console.error('Error fetching printer data:', error));
}


function updatePrinterData(data) {
    const own_id=<?php echo($_SESSION["id"]); ?>;
    const cancel_all=<?php echo($_SESSION["role"][3]); ?>;
    const printerContainer = document.getElementById('printer-container');
    printerContainer.innerHTML = '';

    const row = document.createElement('div');
    row.className = 'row';

    data.forEach(printer => {
        const col = document.createElement('div');
        col.className = 'col-md-4'; // Adjust the column size according to your needs

        const printerCard = document.createElement('div');
        printerCard.className = 'card m-4 align-self-start';

        let printerStatus = '';
        if (printer.view == 0) {
            printerStatus = 'Fertig';
        }else if(printer.view==1){
		printerStatus = 'Drucken';
	}else if(printer.view==2){
                printerStatus = 'Abgebrochen';
	}else if(printer.view==3){
		printerStatus = 'Bereit';
	}else if(printer.view==4){
		printerStatus = 'Problem / Nicht betriebsbereit';
	}else if(printer.view==5){
                printerStatus = 'Von anderer Quelle aus gestartet';
        }

        if(printer.view==0 || printer.view==2){
		if(own_id==printer.userid || cancel_all=="1"){
			printerCard.innerHTML = `
        		    <div class="card-body">
        		        <h5 class="card-title">Drucker ${printer.printer_id}</h5>
        		    </div>
        		    <div class="card-body">
        		        <iframe height="230px" scrolling="no" width="100%" src="/app/webcam.php?printer_id=${printer.printer_id}&username=<?php echo($username); ?>&url=${printer.url}"></iframe>
        		        <div class="progress">
        		            <div class="progress-bar" role="progressbar" style="width: ${printer.progress}%" aria-valuenow="${printer.progress}" aria-valuemin="0" aria-valuemax="100">${printer.progress}%</div>
        		        </div>
        		        <table class="table table-borderless">
        		            <thead>
        		                <tr><td>Status</td><td style="color: ${getColorByStatus(printer.view)}">${printerStatus}</td></tr>
        		                <tr><td>Genutzt von</td><td>${printer.username}</td></tr>
        		                <tr><td>Filamentfarbe</td><td>${printer.filament_color}</td></tr>
        		                <tr><td>Erwartete Druckzeit</td><td>${printer.print_time_total}</td></tr>
        		                <tr><td>Verbleibende Druckzeit</td><td>${printer.print_time_left}</td></tr>
        		                <tr><td>Vergangene Druckzeit</td><td>${printer.print_time}</td></tr>
        		                <tr><td>Datei</td><td><div class='hover-element'>${printer.file}<div class='description'>${printer.full_file}</div></div></td></tr>
        		            </thead>
		        		<tr><td><a class='btn btn-success' href='overview.php?free=${printer.printer_id}&rid=<?php echo($_SESSION["rid"]); ?>'>Freigeben</a></td></tr>
			        </table>
        		    </div>
        		`;
		}else{
			printerCard.innerHTML = `
				<div class="card-body">
					<h5 class="card-title">Drucker ${printer.printer_id}</h5>
				</div>
				<div class="card-body">
					<iframe height="230px" scrolling="no" width="100%" src="/app/webcam.php?printer_id=${printer.printer_id}&username=<?php echo($username); ?>&url=${printer.url}"></iframe>
					<div class="progress">
						<div class="progress-bar" role="progressbar" style="width: ${printer.progress}%" aria-valuenow="${printer.progress}" aria-valuemin="0" aria-valuemax="100">${printer.progress}%</div>
					</div>
					<table class="table table-borderless">
						<thead>
							<tr><td>Status</td><td style="color: ${getColorByStatus(printer.view)}">${printerStatus}</td></tr>
							<tr><td>Genutzt von</td><td>${printer.username}</td></tr>
							<tr><td>Filamentfarbe</td><td>${printer.filament_color}</td></tr>
							<tr><td>Erwartete Druckzeit</td><td>${printer.print_time_total}</td></tr>
							<tr><td>Verbleibende Druckzeit</td><td>${printer.print_time_left}</td></tr>
							<tr><td>Vergangene Druckzeit</td><td>${printer.print_time}</td></tr>
							<tr><td>Datei</td><td><div class='hover-element'>${printer.file}<div class='description'>${printer.full_file}</div></div></td></tr>
						</thead>
					</table>
				</div>
			`;
		}
	}else if(printer.view==1){
		if(own_id==printer.userid || cancel_all=="1"){
			printerCard.innerHTML = `
				<div class="card-body">
        		        	<h5 class="card-title">Drucker ${printer.printer_id}</h5>
				</div>
				<div class="card-body">
       					<iframe height="230px" scrolling="no" width="100%" src="/app/webcam.php?printer_id=${printer.printer_id}&username=<?php echo($username); ?>&url=${printer.url}"></iframe>
        		        	<div class="progress">
              					<div class="progress-bar" role="progressbar" style="width: ${printer.progress}%" aria-valuenow="${printer.progress}" aria-valuemin="0" aria-valuemax="100">${printer.progress}%</div>
               				</div>
             				<table class="table table-borderless">
           					<thead>
          						<tr><td>Status</td><td style="color: ${getColorByStatus(printer.view)}">${printerStatus}</td></tr>
                  					<tr><td>Genutzt von</td><td>${printer.username}</td></tr>
                        				<tr><td>Filamentfarbe</td><td>${printer.filament_color}</td></tr>
                        				<tr><td>Erwartete Druckzeit</td><td>${printer.print_time_total}</td></tr>
                        				<tr><td>Verbleibende Druckzeit</td><td>${printer.print_time_left}</td></tr>
                       	 				<tr><td>Vergangene Druckzeit</td><td>${printer.print_time}</td></tr>
							<tr><td>Datei</td><td><div class='hover-element'>${printer.file}<div class='description'>${printer.full_file}</div></div></td></tr>
                    				</thead>
		                	<tr><td><button class='btn btn-danger' onclick='update_cancel_modal(${printer.printer_id},<?php echo($_SESSION["rid"]); ?>)'>Abbrechen</button></td></tr>
					</table>
            			</div>
        		`;
                }else{
			printerCard.innerHTML = `
				<div class="card-body">
					<h5 class="card-title">Drucker ${printer.printer_id}</h5>
				</div>
				<div class="card-body">
					<iframe height="230px" scrolling="no" width="100%" src="/app/webcam.php?printer_id=${printer.printer_id}&username=<?php echo($username); ?>&url=${printer.url}"></iframe>
					<div class="progress">
						<div class="progress-bar" role="progressbar" style="width: ${printer.progress}%" aria-valuenow="${printer.progress}" aria-valuemin="0" aria-valuemax="100">${printer.progress}%</div>
					</div>
					<table class="table table-borderless">
						<thead>
							<tr><td>Status</td><td style="color: ${getColorByStatus(printer.view)}">${printerStatus}</td></tr>
							<tr><td>Genutzt von</td><td>${printer.username}</td></tr>
							<tr><td>Filamentfarbe</td><td>${printer.filament_color}</td></tr>
							<tr><td>Erwartete Druckzeit</td><td>${printer.print_time_total}</td></tr>
							<tr><td>Verbleibende Druckzeit</td><td>${printer.print_time_left}</td></tr>
							<tr><td>Vergangene Druckzeit</td><td>${printer.print_time}</td></tr>
							<tr><td>Datei</td><td><div class='hover-element'>${printer.file}<div class='description'>${printer.full_file}</div></div></td></tr>
						</thead>
					</table>
				</div>
			`;
	        }
	}else if(printer.view==3){
		printerCard.innerHTML = `
                                <div class="card-body">
                                        <h5 class="card-title">Drucker ${printer.printer_id}</h5>
                                </div>
                                <div class="card-body">
                                        <iframe height="230px" scrolling="no" width="100%" src="/app/webcam.php?printer_id=${printer.printer_id}&username=<?php echo($username); ?>&url=${printer.url}"></iframe>
                                        <table class="table table-borderless">
                                                <thead>
                                                        <tr><td>Status</td><td style="color: ${getColorByStatus(printer.view)}">${printerStatus}</td></tr>
                                                        <tr><td>Filamentfarbe</td><td>${printer.filament_color}</td></tr>
                                                </thead>
                                        <tr><td><a class='btn btn-secondary' href='print.php?preselect=${printer.printer_id}'>Drucken</a></td></tr>
					</table>
                                </div>
                        `;
	}else if(printer.view==4){
		printerCard.innerHTML = `
                                <div class="card-body">
                                        <h5 class="card-title">Drucker ${printer.printer_id}</h5>
                                </div>
                                <div class="card-body">
                                        <iframe height="230px" scrolling="no" width="100%" src="/app/webcam.php?printer_id=${printer.printer_id}&username=<?php echo($username); ?>&url=${printer.url}"></iframe>
                                        <table class="table table-borderless">
                                                <thead>
                                                        <tr><td>Status</td><td style="color: ${getColorByStatus(printer.view)}">${printerStatus}</td></tr>
                                                </thead>
                                        </table>
                                </div>
                        `;
	}else if(printer.view==5){
		if(cancel_all=="1"){
                        printerCard.innerHTML = `
                            <div class="card-body">
                                <h5 class="card-title">Drucker ${printer.printer_id}</h5>
                            </div>
                            <div class="card-body">
                                <iframe height="230px" scrolling="no" width="100%" src="/app/webcam.php?printer_id=${printer.printer_id}&username=<?php echo($username); ?>&url=${printer.url}"></iframe>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: ${printer.progress}%" aria-valuenow="${printer.progress}" aria-valuemin="0" aria-valuemax="100">${printer.progress}%</div>
                                </div>
                                <table class="table table-borderless">
                                    <thead>
                                        <tr><td>Status</td><td style="color: ${getColorByStatus(printer.view)}">${printerStatus}</td></tr>
                                        <tr><td>Genutzt von</td><td>Externer Nutzer</td></tr>
                                        <tr><td>Filamentfarbe</td><td>${printer.filament_color}</td></tr>
                                        <tr><td>Erwartete Druckzeit</td><td>${printer.print_time_total}</td></tr>
                                        <tr><td>Verbleibende Druckzeit</td><td>${printer.print_time_left}</td></tr>
                                        <tr><td>Vergangene Druckzeit</td><td>${printer.print_time}</td></tr>
                                        <tr><td>Datei</td><td><div class='hover-element'>${printer.file}<div class='description'>${printer.full_file}</div></div></td></tr>
                                    </thead>
                                        <tr><td><a class='btn btn-success' href='overview.php?free=${printer.printer_id}&rid=<?php echo($_SESSION["rid"]); ?>'>Freigeben</a></td></tr>
                                </table>
                            </div>
                        `;
                }else{
                        printerCard.innerHTML = `
                                <div class="card-body">
                                        <h5 class="card-title">Drucker ${printer.printer_id}</h5>
                                </div>
                                <div class="card-body">
                                        <iframe height="230px" scrolling="no" width="100%" src="/app/webcam.php?printer_id=${printer.printer_id}&username=<?php echo($username); ?>&url=${printer.url}"></iframe>
                                        <div class="progress">
                                                <div class="progress-bar" role="progressbar" style="width: ${printer.progress}%" aria-valuenow="${printer.progress}" aria-valuemin="0" aria-valuemax="100">${printer.progress}%</div>
                                        </div>
                                        <table class="table table-borderless">
                                                <thead>
                                                        <tr><td>Status</td><td style="color: ${getColorByStatus(printer.view)}">${printerStatus}</td></tr>
                                                        <tr><td>Genutzt von</td><td>Externer Nutzer</td></tr>
                                                        <tr><td>Filamentfarbe</td><td>${printer.filament_color}</td></tr>
                                                        <tr><td>Erwartete Druckzeit</td><td>${printer.print_time_total}</td></tr>
                                                        <tr><td>Verbleibende Druckzeit</td><td>${printer.print_time_left}</td></tr>
                                                        <tr><td>Vergangene Druckzeit</td><td>${printer.print_time}</td></tr>
                                                        <tr><td>Datei</td><td><div class='hover-element'>${printer.file}<div class='description'>${printer.full_file}</div></div></td></tr>
                                                </thead>
                                        </table>
                                </div>
                        `;
                }
	}

        col.appendChild(printerCard);
        row.appendChild(col);
    });

    printerContainer.appendChild(row);
}


function getColorByStatus(status) {
    switch (status) {
        case 0:
            return 'green';
        case 1:
            return 'orange';
        case 2:
            return 'orange';
        case 3:
            return 'green';
	case 4:
	    return 'red';
	case 5:
	    return 'orange';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    fetchPrinterData();
    setInterval(fetchPrinterData, 60000); // Refresh every 6 seconds
});
</script>


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
