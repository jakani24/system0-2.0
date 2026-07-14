<?php
	
	$username="sys0_autonomous";
	//no auth, we only check if any printe rhas finished and if yes, if we should send a mail, if yes send the mail.

	include "../config/config.php";
	include "../api/queue.php";
	test_queue($link);

	//iterate over all printers and receive theyr status
	

	$cnt=0;
	$url="";
	$apikey="";
	$sql="select count(*) from printer where printing=1";
	$stmt = mysqli_prepare($link, $sql);					
	mysqli_stmt_execute($stmt);
	mysqli_stmt_store_result($stmt);
	mysqli_stmt_bind_result($stmt, $cnt);
	mysqli_stmt_fetch($stmt);	
	//echo($cnt);
	$is_free=0;						
	$last_id=0;	
	$mail_sent=1;	
	$used_by_userid=0;				
	while($cnt!=0)
	{	

		$sql="select free,id,printer_url,apikey,cancel,used_by_userid,mail_sent from printer where id>$last_id and printing=1 ORDER BY id";
		$cancel=0;
		$stmt = mysqli_prepare($link, $sql);					
		mysqli_stmt_execute($stmt);
		mysqli_stmt_store_result($stmt);
		mysqli_stmt_bind_result($stmt, $is_free,$printer_id,$url,$apikey,$cancel,$used_by_userid,$mail_sent);
		mysqli_stmt_fetch($stmt);
		$last_id=$printer_id;

		//printer is printing - use cURL instead of exec()
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url . '/api/job');
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Api-Key: " . $apikey));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($ch);
		curl_close($ch);
		file_put_contents("/var/www/html/user_files/$username/json.json", $response);
		
		$fg=file_get_contents("/var/www/html/user_files/$username/json.json");
		$json=json_decode($fg,true);
		
		
		$used_by_user="";
		$telegram_id="";
		$notification_telegram=0;
		$notification_mail=0;
		$sql="select username,telegram_id,notification_telegram,notification_mail from users where id=$used_by_userid";
		$stmt = mysqli_prepare($link, $sql);					
		mysqli_stmt_execute($stmt);
		mysqli_stmt_store_result($stmt);
		mysqli_stmt_bind_result($stmt, $used_by_user,$telegram_id,$notification_telegram,$notification_mail);
		mysqli_stmt_fetch($stmt);
		$username3=explode("@",$used_by_user);
		$username2=$username3[0];
		$progress=(int) $json['progress']['completion'];
		if($progress<0)
			$progress=-$progress;
		$file=$json['job']['file']['name'];
		if($progress==100){
				//print finished
				//check if mail has not been sent:
				
				if($mail_sent==0 && $notification_telegram==1){
					//send telegram message
					echo("sending telegram for printer $printer_id<br>");
					$text = "Hi $username2\nDein Druck auf Drucker $printer_id ist fertig\nDatei, welche du gedruckt hast: $file\n";
					
					$ch = curl_init();
					$api_url = "https://api.telegram.org/" . $api . "/sendMessage";
					curl_setopt($ch, CURLOPT_URL, $api_url);
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
						'chat_id' => $telegram_id,
						'text' => $text
					)));
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_exec($ch);
					curl_close($ch);
					
					$sql="update printer set mail_sent=1 where id=$printer_id";
					$stmt = mysqli_prepare($link, $sql);					
					mysqli_stmt_execute($stmt);

				}

				if($mail_sent==0 && $notification_mail==1)
				{

					echo("sending mail for printer $printer_id<br>");
					
					// Use cURL to send email via SendGrid API
					$email_content = "Hallo $username2<br>Dein 3D-Druck auf Drucker $printer_id ist fertig.<br>Bitte hole diesen ab und vergiss nicht den Drucker danach freizugeben!<br>Deine Aufträge: <a href='https://app.ksw3d.ch/system0/html/php/login/v3/php/overview.php?private'>https://app.ksw3d.ch/system0/html/php/login/v3/php/overview.php?private</a><br>Datei, welche du gedruckt hast: $file<br><br>Vielen dank für dein Vertrauen in uns!<br>Code Camp 2024<br>";
					
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, "https://api.sendgrid.com/v3/mail/send");
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
					curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
						"personalizations" => array(
							array(
								"to" => array(
									array("email" => $used_by_user)
								)
							)
						),
						"from" => array("email" => $sendgrid_email),
						"subject" => "3D-Druck $file abholbereit",
						"content" => array(
							array(
								"type" => "text/html",
								"value" => $email_content
							)
						)
					)));
					curl_setopt($ch, CURLOPT_HTTPHEADER, array(
						"Authorization: Bearer " . $SENDGRID_API_KEY,
						"Content-Type: application/json"
					));
					
					$out="";
					curl_exec($ch);
					curl_close($ch);
					
					$sql="update printer set mail_sent=1 where id=$printer_id";
					$stmt = mysqli_prepare($link, $sql);					
					mysqli_stmt_execute($stmt);
				}
		}
		else if($cancel==1){
				//print cancelled
		}						
		//else: print still running
		$cnt--;
	}


?>