<?php
	//auth stuff
	session_start();
	if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true or $_SESSION["role"][0]!=="1"){
	    header("location: /login/login.php");
	    exit;
	}
	$username=htmlspecialchars($_SESSION["username"]);
?>
<!DOCTYPE html>
<html lang="de" data-bs-theme="dark">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>System0 - Print</title>
		<?php include "../../assets/components.php"; ?>
	</head>
	<body>
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
		<script type='text/javascript' >load_user()</script>
		<?php
                	if(isset($_GET["cloudprint"])){
				echo("<script>let cloudprint=1;</script>");
			}else{
				echo("<script>let cloudprint=0;</script>");
			}
		?>
		<!-- navbar -->
		<div id="content"></div>
		<!-- div where all our content goes -->
		<div style="min-height:95vh">
			<!-- we need to show a file upload thing and offer the selectnio of printers -->
			<div class="container mt-5 d-flex justify-content-center">
				<form>
					<div class="mb-3">
						<label for="fileUpload" class="form-label">3D-Druck Datei</label>
						<?php
                                                	if(isset($_GET["cloudprint"])){
                                                	        echo('<input type="text" value="'.$_GET["cloudprint"].'" class="form-control" disabled id="file_upload">');
                                                	}else{
								echo('<input type="file" class="form-control" accept=".gcode" id="file_upload">');
							}
                                        	?>
					</div>
					<div class="mb-3">
						<label for="selectOption" class="form-label">Drucker</label>
						<select class="form-select" id="selectOption">
						    <option selected value="not_set">Bitte wähle einen Drucker</option>
						</select>
					</div>
					<a style="cursor: pointer" onclick="start_upload(1)" class="btn btn-primary">Drucken</a>
				</form>
			</div>

		</div>
		<!-- footer -->
		<div id="footer"></div>

		<script>
			let global_error="";
			//js to handle backend requests
			//load printers
			document.addEventListener("DOMContentLoaded", function () {
				const selectElement = document.getElementById("selectOption");
				const apiUrl = "/api/uploader/fetch_printers.php"; // Replace with your actual API URL
    				function getUrlParam(name) {
        				const urlParams = new URLSearchParams(window.location.search);
        				return urlParams.get(name);
    				}

    				const preselectId = getUrlParam("preselect"); // Get "preselect" value from URL

    				fetch(apiUrl)
        				.then(response => response.json())
        				.then(data => {
        				    data.forEach(item => {
        				        const option = document.createElement("option");
                				option.value = item.id;
						if(item.free==0){
                					option.textContent = `Drucker ${item.id} - ${item.color}`;
                				}else{
							option.textContent = `Drucker ${item.id} - ${item.color} - Warteschlange`;
						}
						if (item.id == preselectId) {
                				    option.selected = true;
                				}
						selectElement.appendChild(option);
        				    });
        				})
        			.catch(error => console.error("Error fetching data:", error));
			});
			async function start_upload(use_checks){
				//main function handles the steps from user pressing upload button via checking params to starting job via api
				//we have a modal that shows progress to the user
				document.getElementById("close_progress_modal").style.display = "none";
				document.getElementById("close_progress_modal2").style.display = "none";
				let steps = [
        			    "Initialisierung",
        			    "Datei auf System0 Hochladen",
        			    "Nach Reservationskonflikten suchen",
        			    "Nach Invaliden Druckeinstellungen suchen",
        			    "Job an Drucker senden",
				    "Fertig!"
			        ];
				let progressContent = document.getElementById("progressContent");
			        progressContent.innerHTML = ""; // Clear previous content

			        let modal = new bootstrap.Modal(document.getElementById("progressModal"));
        			modal.show();
				add_step(0,progressContent,steps);
				//initialising => set all vars to 0 etc
				finish_step(0,progressContent,steps);
				if(cloudprint==0){
					add_step(1,progressContent,steps);
					//upload file to system0
					if(await upload_file()==0){
						finish_step(1,progressContent,steps);
					}else{
						add_error("Fehler beim Upload der Datei - "+global_error,progressContent);
						cancel_step(1,progressContent,steps);
						show_close_button();
						return;
					}
				}else{
					//just tell the server what the file is.
					await fetch("/api/uploader/cloudprint.php?file=<?php echo($_GET['cloudprint']); ?>");
				}
				global_error="";
				//check if there is a reservation ongoing during this print
				add_step(2,progressContent,steps);
				let status=await check_reservations();
				if(status==0){
                                        finish_step(2,progressContent,steps);
                                }else if(status==1){
					//reserved and user is student
					add_error("Die Drucker sind zurzeit reserviert. Bitte versuche es später erneut.");
					cancel_step(2,progressContent,steps);
					show_close_button();
					return;
				}else if(status==2){
					//reserved but user is admin
					add_warning("Die Drucker sind Zurzeit reserviert. Als Lehrperson wird ihr Druck allerdings trozdem gedruckt. Bitte gehen Sie sicher, dass nicht eine Klasse beeinträchtigt wird.",progressContent);
                                	finish_step(2,progressContent,steps);
				}else{
                                        add_error("Fehler beim überprüfen der Reservationen - "+global_error,progressContent);
                                        cancel_step(2,progressContent,steps);
					show_close_button();
					return;
                                }
				global_error="";
				//search for invalid print settings.
				add_step(3,progressContent,steps);
				status=await check_illegal_settings(progressContent);
				if(status==0){
					finish_step(3,progressContent,steps);
				}else if(use_checks==0){
					add_warning("Warnung: Dieser Druck wird mit sehr hohen Temparaturen gedruckt. Dies kann zur zerstörung des Druckers führen!",progressContent);
					finish_step(3,progressContent,steps);
				}else if(status==1){
					add_error("Achtung deine Drucktemparatur ist sehr hoch eingestellt. Dies kann zur zerstörung des Druckers führen! Bitte fahre nur fort, wenn du dir sicher bist, was du tust!",progressContent);
					add_circumvent_link(progressContent);
					cancel_step(3,progressContent,steps);
					show_close_button();
					return;
				}else{
					add_error("Fehler beim prüfen der Druckeinstellungen",progressContent);
					cancel_step(3,progressContent,steps);
                                        show_close_button();
                                        return;
				}
				global_error="";
				//send to printer
				add_step(4,progressContent,steps);
				status=await start_job();
				if(status==0){
					finish_step(4,progressContent,steps);
					add_step(5,progressContent,steps);
					finish_step(5,progressContent,steps);
					add_success("Job erfolgreich gestartet",progressContent);
				}else{
					add_error("Fehler beim starten des Jobs. "+global_error, progressContent);
					cancel_step(4,progressContent,steps);
					show_close_button();
					return;
				}
			}

			function add_circumvent_link(progressContent) {
                                    let stepHtml = `
                                        <div>
						<a onclick="start_upload(0);" target="_blank" class="step-link">Drücke hier, um alle überprüfungen zu umgehen</a>
                                        </div>
                                        `;

                                        progressContent.innerHTML += stepHtml;
                        }

			function finish_step(index,progressContent,steps){
				let stepId = "step-" + index;
				let stepElement = document.getElementById(stepId);
                                if (stepElement) {
                                        stepElement.innerHTML = `
                                       		<span class="text-success fw-bold">✔</span>
                                        	<span>${steps[index]}</span>
                                	`;
                                }
				if (index >= steps.length-1){
                                        document.getElementById("close_progress_modal").style.display = "block";
                                        document.getElementById("close_progress_modal2").style.display = "block";
				}
			}
                        function show_close_button(){
                                document.getElementById("close_progress_modal").style.display = "block";
                                document.getElementById("close_progress_modal2").style.display = "block";
                        }
			function cancel_step(index,progressContent,steps){
                                let stepId = "step-" + index;
                                let stepElement = document.getElementById(stepId);
                                if (stepElement) {
                                        stepElement.innerHTML = `
                                                <span class="text-success fw-bold">❌</span>
                                                <span>${steps[index]}</span>
                                        `;
				}
                                document.getElementById("close_progress_modal").style.display = "block";
                                document.getElementById("close_progress_modal2").style.display = "block";
                        }
                        function add_error(msg,progressContent){
				let errorHtml = `
					<br>
					<div class='alert alert-danger' role='alert'>Fehler - ${msg}</div>
                                        `;

                                progressContent.innerHTML += errorHtml;
                        }
			function add_success(msg,progressContent){
                                let errorHtml = `
                                        <br>
                                        <div class='alert alert-success' role='alert'>Erfolg - ${msg}</div>
                                        `;

                                progressContent.innerHTML += errorHtml;
                        }
			function add_warning(msg,progressContent){
                                let errorHtml = `
                                        <br>
                                        <div class='alert alert-warning' role='alert'>Warnung - ${msg}</div>
                                        `;

                                progressContent.innerHTML += errorHtml;
                        }
			function add_step(index,progressContent,steps) {
                                    let stepId = "step-" + index;
                                    let stepHtml = `
                                        <div class="step-container" id="${stepId}">
                                            <span class="spinner-border text-primary" role="status"></span>
                                            <span>${steps[index]}</span>
                                        </div>
                                        `;

                                        progressContent.innerHTML += stepHtml;
                        }
			async function check_illegal_settings(progressContent){
				try {
                                const response = await fetch("/api/uploader/check_illegal_settings.php");
                                if (!response.ok) {
                                    throw new Error(`HTTP error! Status: ${response.status}`);
                                }

                                const data = await response.json();
				if(data["status"]!=0){
					global_error="Dieser Fehler ist auf dem Drucker. Warte einige Minuten und versuche es erneut.";
				}
                                return data["status"];
                         } catch (error) {
                                return 4;
                         }
			}
			async function start_job(){
				let printer_id=document.getElementById("selectOption").value;
				if(printer_id=="not_set"){
					global_error="Kein Drucker ausgewählt";
					return 5;
				}
                                try {
                                const response = await fetch("/api/uploader/start_job.php?printer="+printer_id);
                                if (!response.ok) {
                                    throw new Error(`HTTP error! Status: ${response.status}`);
                                }

                                const data = await response.json();
                                return data["status"];
                         } catch (error) {
                                return 5;
                         }
                        }
			async function upload_file(){
				const fileInput = document.getElementById('file_upload');
            			const file = fileInput.files[0];

            			if (!file) {
					global_error="Keine Datei ausgewählt";
                			return 1;
		            	}

			            const formData = new FormData();
        			    formData.append('file', file);

        			    try {
                			const response = await fetch('/api/uploader/upload_file.php', {
                			    method: 'POST',
                			    body: formData,
                			});
                			if (response.ok) {
                			    const result = await response.json();
						if(result.status=="error"){
							global_error=result.message;
							return 1;
						}
                			} else {
						return 1;
                			}
            			} catch (error) {
					return 1;
            			}
				return 0;
			}
			async function check_reservations() {
			    try {
			        const response = await fetch("/api/uploader/check_reservations.php");
        			if (!response.ok) {
        			    throw new Error(`HTTP error! Status: ${response.status}`);
        			}

			        const data = await response.json();
            			return data["status"];
    			 } catch (error) {
    			    	return 4;
    			 }
			}

		</script>
	<!-- progress modal -->
	<div class="modal fade" id="progressModal" tabindex="1" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog" aria-labelledby="progressModalLabel" aria-hidden="false">
    		<div class="modal-dialog" role="document">
        		<div class="modal-content">
        		    <div class="modal-header">
        		        <h5 class="modal-title" id="progressModalLabel">Fortschritt</h5>
                		<button id="close_progress_modal" type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schliessen"></button>
            		    </div>
         	   		<div class="modal-body">
           			     <div id="progressContent"></div>
           			</div>
            			<div class="modal-footer">
                			<button id="close_progress_modal2" type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schliessen</button>
            			</div>
        		</div>
    		</div>
	</div>

	</body>
</html>
