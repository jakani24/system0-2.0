<?php
        //this file returns a list of available printers, theyr status and theyr color
        session_start();
        $file_path=$_SESSION["current_file"];
        include "../../config/config.php";
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true or $_SESSION["role"][0]!=="1"){
                die("no_auth");
            exit;
        }
        include "../../config/config.php";
	//if printer is ready, upload to printer, else upload to queue
	//return 0 if success, else return any int
	$printer_id=intval($_GET["printer"]);
	//check if printer is ready
	$sql="select printer_url, free, system_status,apikey,printer_url from printer where id=$printer_id";
	$stmt = mysqli_prepare($link, $sql);
	mysqli_stmt_execute($stmt);
	mysqli_stmt_store_result($stmt);
	mysqli_stmt_bind_result($stmt, $url,$free,$status,$apikey,$printer_url);
	mysqli_stmt_fetch($stmt);
	mysqli_stmt_close($stmt);
	$result=1;
	$username=$_SESSION["username"];
	$userid=$_SESSION["id"];

	if($free==1 && $status==0){
		//upload to printer
		exec('curl -k -H "X-Api-Key: '.$apikey.'" -F "select=true" -F "print=true" -F "file=@'.$path.'" "'.$printer_url.'/api/files/local" > /var/www/html/user_files/'.$username.'/json.json');
		$fg=file_get_contents("/var/www/html/user_files/$username/json.json");
		$json=json_decode($fg,true);
		if($json['effectivePrint']!=true or $json["effectiveSelect"]!=true)
		{
			$result=1;
		}
		else
		{
			$sql="update printer set free=0, printing=1,mail_sent=0, used_by_userid=$userid where id=$printer_id";
			$stmt = mysqli_prepare($link, $sql);
			mysqli_stmt_execute($stmt);
			 mysqli_stmt_close($stmt);
			$result=0;
		}
	}else if($free!=1 && $status==0){
		//upload to queue 
		$path=$_SESSION["current_file"];
		$sql="INSERT INTO queue (from_userid,filepath,print_on) VALUES (?,?,?)";
		$stmt = mysqli_prepare($link, $sql);
		mysqli_stmt_bind_param($stmt, "isi", $userid,$path,$printer_id);
		mysqli_stmt_execute($stmt);
		mysqli_stmt_close($stmt);
		$result=2;

	}else{
		//error
		$result=1;
	}
	echo(json_encode(["status"=>$result]));
?>
