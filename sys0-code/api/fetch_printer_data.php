<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /login/login.php");
    exit;
}

include "../config/config.php";

header('Content-Type: application/json');

function seconds_to_time($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    if($hours != 0) {
        if($hours == 1)
            return sprintf("%d Stunde %d Minuten", $hours, $minutes);
        else
            return sprintf("%d Stunden %d Minuten", $hours, $minutes);
    } else {
        return sprintf("%d Minuten", $minutes);
    }
}

function short_path($filePath, $firstCharsCount, $lastCharsCount) {
    $filePath = str_replace(".gcode", "", $filePath);
    if(strlen($filePath) >= $firstCharsCount + $lastCharsCount + 3) {
        $firstChars = substr($filePath, 0, $firstCharsCount);
        $lastChars = substr($filePath, -$lastCharsCount);
        return $firstChars . "..." . $lastChars;
    } else {
        return $filePath;
    }
}

$printers = [];
$sql = "SELECT rotation, free, printer.id, printer_url, apikey, cancel, used_by_userid, system_status, printer.color, COALESCE(name, 'nicht verfügbar') AS real_color, COALESCE(username,'nicht verfügbar') FROM printer LEFT JOIN filament ON printer.color=internal_id LEFT JOIN users ON used_by_userid=users.id ORDER BY printer.id";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
mysqli_stmt_bind_result($stmt, $rotation, $is_free, $printer_id, $url, $apikey, $cancel, $userid, $system_status, $filament_color,$real_color,$used_by_user);

while (mysqli_stmt_fetch($stmt)) {
	$used_by_user=explode("@",$used_by_user)[0];
    $printer = [
        "rotation" => $rotation,
        "is_free" => $is_free,
        "printer_id" => $printer_id,
        "url" => $url,
        "cancel" => $cancel,
        "userid" => $userid,
        "system_status" => $system_status,
        "filament_color" => $real_color,
	"username" => $used_by_user
    ];

    if ($is_free == 0 && $system_status == 0 && $cancel==0) {
        exec("curl --max-time 10 $url/api/job?apikey=$apikey > /var/www/html/user_files/" . $_SESSION["username"] . "/json.json");
        $fg = file_get_contents("/var/www/html/user_files/" . $_SESSION["username"] . "/json.json");
        $json = json_decode($fg, true);
        $printer["progress"] = (int) $json['progress']['completion'];
        $printer["file"] = short_path($json["job"]["file"]["name"], 10, 10);
	$printer["full_file"]=$json["job"]["file"]["name"];
        $printer["print_time_total"] = seconds_to_time(intval($json["job"]["estimatedPrintTime"]));
        $printer["print_time_left"] = seconds_to_time(intval($json["progress"]["printTimeLeft"]));
        $printer["print_time"] = seconds_to_time(intval($json["progress"]["printTime"]));
	if($printer["progress"]==100){
		$printer["print_status"]="Fertig";
		$printer["view"]=0;
	}else{
		$printer["print_status"]="Drucken";
		$printer["view"]=1;
	}
	$printer["progress"]=int($json["job"]["progress"]["printTime"])/(int($json["job"]["progress"]["printTime"])+int($json["job"]["progress"]["printTimeLeft"]));
    }else if($cancel==1){
	exec("curl --max-time 10 $url/api/job?apikey=$apikey > /var/www/html/user_files/" . $_SESSION["username"] . "/json.json");
        $fg = file_get_contents("/var/www/html/user_files/" . $_SESSION["username"] . "/json.json");
        $json = json_decode($fg, true);
        //$printer["progress"] = (int) $json['progress']['completion'];
	$printer["progress"]=int($json["job"]["progress"]["printTime"])/(int($json["job"]["progress"]["printTime"])+int($json["job"]["progress"]["printTimeLeft"]));
        $printer["file"] = short_path($json["job"]["file"]["name"], 10, 10);
        $printer["print_time_total"] = seconds_to_time(intval($json["job"]["estimatedPrintTime"]));
        $printer["print_time_left"] = seconds_to_time(intval($json["progress"]["printTimeLeft"]));
        $printer["print_time"] = seconds_to_time(intval($json["progress"]["printTime"]));
	$printer["print_status"]="Abgebrochen";
	$printer["full_file"]=$json["job"]["file"]["name"];
	$printer["view"]=2;
    }/*else if($system_status==0){
	$printer["print_status"]="Bereit";
	$printer["view"]=3;
    }*/else if(($is_free == 1 && $system_status==0) or $system_status==99){ //check if a print has been started from another location
    	exec("curl --max-time 10 $url/api/job?apikey=$apikey > /var/www/html/user_files/" . $_SESSION["username"] . "/json.json");
	$fg = file_get_contents("/var/www/html/user_files/" . $_SESSION["username"] . "/json.json");
        $json = json_decode($fg, true);
	if($json['state']=="Starting print from SD" or $json['state']=="Printing" or $json['state']=="Printing from SD" or $system_status==99){
		$printer["print_status"]="Von anderer Quelle aus gestartet.";
		//$printer["progress"] = (int) $json['progress']['completion'];
		$printer["progress"]=int($json["job"]["progress"]["printTime"])/(int($json["job"]["progress"]["printTime"])+int($json["job"]["progress"]["printTimeLeft"]));
        	$printer["file"] = short_path($json["job"]["file"]["name"], 10, 10);
        	$printer["full_file"]=$json["job"]["file"]["name"];
        	$printer["print_time_total"] = seconds_to_time(intval($json["job"]["estimatedPrintTime"]));
        	$printer["print_time_left"] = seconds_to_time(intval($json["progress"]["printTimeLeft"]));
        	$printer["print_time"] = seconds_to_time(intval($json["progress"]["printTime"]));
		$printer["view"]=5;
		//insert into db that this one is printing
		$sql="UPDATE printer SET system_status=99 WHERE id = $printer_id";
		$stmt2 = mysqli_prepare($link, $sql);
		mysqli_stmt_execute($stmt2);
		mysqli_stmt_close($stmt2);
	}else{
		$printer["print_status"]="Bereit";
        	$printer["view"]=3;
	}
    }else{
	$printer["print_status"]="Problem / Nicht betriebsbereit";
	$printer["view"]=4;
    }

    $printers[] = $printer;
}

mysqli_stmt_close($stmt);
echo json_encode($printers);
?>
