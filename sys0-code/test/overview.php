<!DOCTYPE html>
<html>
<head>
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
</script>
<style>
<style>
//styöes for the filename description
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
</style>
</head>
<body>
	<div id="content"></div>
	<!-- placeholder button to be activated to open cancel modal -->
	<button style="display:none" type="button" class="btn btn-primary" data-bs-toggle="modal" id="open_cancel_modal" data-bs-target="#cancel_modal">
		  Launch cancel modal
	</button>
	<div id="printer-container"></div>
	





<script>
function update_cancel_modal(printer_id,rid){
        const modal_=document.getElementById("cancel_modal");
        const button=document.getElementById("send_cancel_command");
        button.href="overview.php?cancel="+printer_id+"&rid="+rid;
	document.getElementById("open_cancel_modal").click();
}
function fetchPrinterData() {
    fetch('fetch_printer_data.php')
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
	}

        if(printer.view==0){
		if(own_id==printer.userid or cancel_all=="1"){
			printerCard.innerHTML = `
        		    <div class="card-body">
        		        <h5 class="card-title">Drucker ${printer.printer_id}</h5>
        		    </div>
        		    <div class="card-body">
        		        <iframe height="230px" scrolling="no" width="100%" src="/app/webcam.php?printer_id=${printer.printer_id}&username=<?php echo($username); ?>&url=${printer.url}"></iframe>
        		        <div class="progress">
        		            <div class="progress-bar" role="progressbar" style="width: ${printer.progress}%" aria-valuenow="${printer.progress}" aria-valuemin="0" aria-valuemax="100"></div>
        		        </div>
        		        <table class="table table-borderless">
        		            <thead>
        		                <tr><td>Status</td><td style="color: ${getColorByStatus(printer.view)}">${printerStatus}</td></tr>
        		                <tr><td>Genutzt von</td><td>${printer.username}</td></tr>
        		                <tr><td>Filamentfarbe</td><td>${printer.filament_color}</td></tr>
        		                <tr><td>Erwartete Druckzeit</td><td>${printer.print_time_total}</td></tr>
        		                <tr><td>Verbleibende Druckzeit</td><td>${printer.print_time_left}</td></tr>
        		                <tr><td>Vergangene Druckzeit</td><td>${printer.print_time}</td></tr>
        		                <tr><td>Datei</td><td>${printer.file}</td></tr>
        		            </thead>
		        		<tr><td><a class='btn btn-success' href='overview.php?free=${printer.printer_id}'>Freigeben</a></td></tr>
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
    }
}

document.addEventListener('DOMContentLoaded', () => {
    fetchPrinterData();
    setInterval(fetchPrinterData, 6000); // Refresh every 60 seconds
});
</script>

</body>
