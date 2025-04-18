<!DOCTYPE html>
<html lang="en">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script>
    function load_footer() {
      $(document).ready(function(){
        $('#footer').load("/assets/html/footer.html");
      });
    }
    load_footer();
	function update_telegram_id(){
		var a=document.getElementById("telegram_id");
		var tel_id=a.value;
		fetch("/api/update_settings.php?telegram_id="+tel_id);
	}
	function update_notification(div_id){
		var a=document.getElementById(div_id);
		var tel_id=a.checked;
		fetch("/api/update_settings.php?"+div_id+"="+tel_id);
	}
	//check if sendmail must be executed
	fetch("/api/sendmail.php");
  </script>

	<style>
		.green {
			color: #ffffff;
			text-decoration: none;
		}
		.green:hover,
		.green:active {
			color: #e2e2e2;
			text-decoration: none;
		}
	</style>

</head>
<body>

	<nav class="navbar navbar-expand-lg navbar-light bg-dark">
		<div class="container-fluid">
			<a class="navbar-brand" href="/index.php">
				<img src="/assets/images/ksw_logo.png" width="auto" height="30" alt="Logo">
			</a>
			<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
			</button>
			<div class="collapse navbar-collapse" id="navbarContent">
				<ul class="navbar-nav me-auto mb-2 mb-lg-0">
				</ul>
				<ul class="navbar-nav">
					<?php
						session_start();
						if($_SESSION["role"][0]==="1")
							echo('
								<li class="nav-item">
									<a class="btn btn-link green" href="/app/print.php">Datei drucken</a>
								</li>
								');
						if($_SESSION["role"][1]==="1")
							echo('
								<li class="nav-item">
									<a class="btn btn-link green" href="/app/cloud.php">Deine Dateien</a>
								</li>
								');
					
						if($_SESSION["role"][2]==="1")
							echo('
								<li class="nav-item">
									<a class="btn btn-link green" href="/app/public_cloud.php">Öffentliche Dateien</a>
								</li>
								');
						echo('
							<li class="nav-item">
								<a class="btn btn-link green" href="/app/bugreport.php">Fehler melden</a>
							</li>
							');
						echo('
							<li class="nav-item">
								<a class="btn btn-link green" href="/user_files/sys0_autonomous/anleitung.pdf">Anleitung</a>
							</li>
							');
						?>
					<li class="nav-item">
						<a class="btn green" role="button" data-bs-toggle="modal" data-bs-target="#account"><i class="fa-solid fa-gear"></i></a>
						<a href="/login/logout.php" class="btn me-2 green" role="button"><i class="fa-solid fa-right-from-bracket"></i></a>
					</li>
				</ul>
			</div>
		</div>
	</nav>




<div class="modal fade" id="account" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl modal-fullscreen-sm-down modal-fullscreen-lg-down" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel">Account Einstellungen</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

			</div>
			<div class="modal-body">


						
				<div class="container">
					<div class="d-flex flex-wrap justify-content-center">
						<?php
							session_start();
							echo('
								<div class="card m-2" style="width: 14em;" href="#">
									<div class="d-flex justify-content-center align-items-center card-img-top" style="height:auto;margin-top:20px">
										<i class="fa-solid fa-lock fa-3x justify-content-center"></i>
									</div>
									
									<div class="card-body">
										<h5 class="card-title">Jakach account verknüpfen / Passwort zurücksetzen</h5>
										<p class="card-text">Hier kannst du dein Passwort ändern</p>
										<a href="/login/reset-password.php" class="stretched-link"></a>
									</div>

								</div>
								');
							echo('
                                                                <div class="card m-2" style="width: 14em;" href="#">
                                                                        <div class="d-flex justify-content-center align-items-center card-img-top" style="height:auto;margin-top:20px">
                                                                                <i class="fa-solid fa-server fa-3x justify-content-center"></i>
                                                                        </div>

                                                                        <div class="card-body">
                                                                                <h5 class="card-title">Systemstatus</h5>
                                                                                <p class="card-text">Hier kannst du den Systemstatus unserer Server einsehen</p>
                                                                                <a href="https://stats.uptimerobot.com/4nr4WTYQVy" class="stretched-link"></a>
                                                                        </div>

                                                                </div>
                                                                ');
							if($_SESSION["role"][5]==="1")
								echo('
									<div class="card m-2" style="width: 14em;" href="#">
										<div class="d-flex justify-content-center align-items-center card-img-top" style="height: auto;margin-top:20px">
											<i class="fa-solid fa-user-plus fa-3x justify-content-center"></i>
										</div>
										<div class="card-body">
											<h5 class="card-title">Neuer Admin erstellen</h5>
											<p class="card-text">Erstellen Sie ein neues Administratorkonto, um Systemeinstellungen und Benutzer zu verwalten.</p>
											<a href="/app/create_admin.php" class="stretched-link"></a>
										</div>

									</div>
									');
						
							if($_SESSION["role"][4]==="1")
								echo('

									<div class="card m-2" style="width: 14em;" href="#">
										<div class="d-flex justify-content-center align-items-center card-img-top" style="height: auto;margin-top:20px">
											<i class="fa-solid fa-users fa-3x justify-content-center"></i>
										</div>
										<div class="card-body">
											<h5 class="card-title">Benutzer verwalten</h5>
											<p class="card-text">Bearbeiten Sie ein Benutzerkonto aus dem System.</p>
											<a href="/app/manage_user.php" class="stretched-link"></a>
										</div>

									</div>
									');
							echo('
								<div class="card m-2" style="width: 14em;" href="#">
									<div class="d-flex justify-content-center align-items-center card-img-top" style="height: auto;margin-top:20px">
										<i class="fa-solid fa-trash fa-3x justify-content-center"></i>
									</div>
									<div class="card-body">
										<h5 class="card-title">Account löschen</h5>
										<p class="card-text">Konto und alle damit verbundenen Daten dauerhaft löschen.</p>
										<a href="/app/delete-account.php" class="stretched-link"></a>
									</div>

								</div>
								');
						
							echo('
								<div class="card m-2" style="width: 14em;" href="#">
									<div class="d-flex justify-content-center align-items-center card-img-top" style="height: auto;margin-top:20px">
										<i class="fa-solid fa-shield-halved fa-3x justify-content-center"></i>
									</div>
									<div class="card-body">
										<h5 class="card-title">Datenschutzrichtlinie</h5>
										<p class="card-text">Lesen Sie unsere Datenschutzrichtlinie, um zu erfahren, wie wir mit Ihren Daten umgehen.</p>
										<a href="/app/privacy-policy.php" class="stretched-link"></a>
									</div>

								</div>
								');
						
							echo('
								<div class="card m-2" style="width: 14em;" href="#">
									<div class="d-flex justify-content-center align-items-center card-img-top" style="height: auto;margin-top:20px">
										<i class="fa-solid fa-circle-info fa-3x justify-content-center"></i>
									</div>
									<div class="card-body">
										<h5 class="card-title">Disclaimer</h5>
										<p class="card-text">Lesen Sie unseren Haftungsausschluss für wichtige Informationen zur Nutzung unserer Dienste.</p>
										<a href="/app/disclaimer.php" class="stretched-link"></a>
									</div>

								</div>
								');
						
							if($_SESSION["role"][6]==="1")

								echo('
									<div class="card m-2" style="width: 14em;" href="#">
										<div class="d-flex justify-content-center align-items-center card-img-top" style="height: auto;margin-top:20px">
											<i class="fa-solid fa-file fa-3x justify-content-center"></i>
										</div>
										<div class="card-body">
											<h5 class="card-title">System0 Protokoll ansehen</h5>
											<p class="card-text">Zeigen Sie das Protokoll der Systemaktivitäten und -ereignisse an.</p>
											<a href="/app/view_log.php" class="stretched-link"></a>
										</div>

									</div>
									');
						
							if($_SESSION["role"][7]==="1")                        	
								echo('
									<div class="card m-2" style="width: 14em;" href="#">
										<div class="d-flex justify-content-center align-items-center card-img-top" style="height: auto;margin-top:20px">
											<i class="fa-solid fa-key fa-3x justify-content-center"></i>
										</div>
										<div class="card-body">
											<h5 class="card-title">System0 APIkey ansehen</h5>
											<p class="card-text">Zeigen Sie den API-Schlüssel an, der für den Zugriff auf die Systemfunktionalitäten verwendet wird.</p>
											<a href="/app/view_apikey.php" class="stretched-link"></a>
										</div>

									</div>
								');

							if($_SESSION["role"][9]==="1")
								echo('
									<div class="card m-2" style="width: 14em;" href="#">
										<div class="d-flex justify-content-center align-items-center card-img-top" style="height: auto;margin-top:20px">
											<i class="fa-solid fa-print fa-3x justify-content-center"></i>
										</div>
										<div class="card-body">
											<h5 class="card-title">Einstellungen & Statistiken</h5>
											<p class="card-text">Hier findest du das Debug-Tool und die Einstellungen.</p>
											<a href="/app/debug.php?show=printer_settings" class="stretched-link"></a>
										</div>

									</div>
	 								');

	  						if($_SESSION["role"][9]==="1")
								echo('
									<div class="card m-2" style="width: 14em;" href="#">
										<div class="d-flex justify-content-center align-items-center card-img-top" style="height: auto;margin-top:20px">
											<i class="fa-solid fa-calendar-days fa-3x justify-content-center"></i>
										</div>
										<div class="card-body">
											<h5 class="card-title">Drucker Reservieren</h5>
											<p class="card-text">Hier kannst du Drucker Reservieren.</p>
											<a href="/app/reservations.php" class="stretched-link"></a>
										</div>

									</div>
	 								');
	 						if($_SESSION["role"][8]==="1")
								echo('
									<div class="card m-2" style="width: 14em;" href="#">
										<div class="d-flex justify-content-center align-items-center card-img-top" style="height: auto;margin-top:20px">
											<i class="fa-solid fa-key  fa-3x justify-content-center"></i>
										</div>
										<div class="card-body">
											<h5 class="card-title">Druckschlüssel erstellen</h5>
											<p class="card-text">Hier kannst du Druckschlüssel erstellen.</p>
											<a href="/app/create_key.php" class="stretched-link"></a>
										</div>

									</div>
	 								');
								?>
							<div class="card m-2" style="width: 14em;" href="#">
								<div class="d-flex justify-content-center align-items-center card-img-top" style="height: auto;margin-top:20px">
									<i class="fa-solid fa-message fa-3x justify-content-center"></i>
								</div>
								<div class="card-body">
									<input id="telegram_id" type="text" class="form-control mb-2" placeholder="Telegram Chat Id" value="<?php echo($_SESSION["telegram_id"]); ?>" oninput="update_telegram_id();">
									<div class="form-check form-switch">
										<?php if($_SESSION["notification_telegram"]==1)
echo('<input class="form-check-input" type="checkbox" id="notification_telegram" checked="true" onclick="update_notification(\'notification_telegram\');">');
										else
											echo('<input class="form-check-input" type="checkbox" id="notification_telegram"  onclick="update_notification(\'notification_telegram\');">');
										?>
										<label class="form-check-label" for="notification_telegram">Benachrichtigung via Telegram</label>
										<br>
										<?php 
										if($_SESSION["notification_mail"]==1)
											echo('<input class="form-check-input" type="checkbox" id="notification_mail" checked="true" onclick="update_notification(\'notification_mail\');">');
										else
											echo('<input class="form-check-input" type="checkbox" id="notification_mail"  onclick="update_notification(\'notification_mail\');">');
										?>
										<label class="form-check-label" for="notification_mail">Benachrichtigung via Mail</label>
									</div>	
								</div>

							</div>
					
					</div>
				</div>
			</div>
		</div>

	</div>
</div>
</body>
</html>
