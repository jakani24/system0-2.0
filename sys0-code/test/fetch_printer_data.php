<?php
session_start();
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
$sql = "SELECT rotation, free, printer.id, printer_url, apikey, cancel, used_by_userid, system_status, printer.color, COALESCE(name, 'nicht verfügbar') AS real_color, COALESCE(username,'nicht verfügbar') FROM printer LEFT JOIN filament ON printer.color=internal_id LEFT JOIN users ON used_by_userid=users.id";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
mysqli_stmt_bind_result($stmt, $rotation, $is_free, $printer_id, $url, $apikey, $cancel, $userid, $system_status, $filament_color,$real_color,$used_by_user);

while (mysqli_stmt_fetch($stmt)) {
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
    }else if($cancel==1){
	exec("curl --max-time 10 $url/api/job?apikey=$apikey > /var/www/html/user_files/" . $_SESSION["username"] . "/json.json");
        $fg = file_get_contents("/var/www/html/user_files/" . $_SESSION["username"] . "/json.json");
        $json = json_decode($fg, true);
        $printer["progress"] = (int) $json['progress']['completion'];
        $printer["file"] = short_path($json["job"]["file"]["name"], 10, 10);
        $printer["print_time_total"] = seconds_to_time(intval($json["job"]["estimatedPrintTime"]));
        $printer["print_time_left"] = seconds_to_time(intval($json["progress"]["printTimeLeft"]));
        $printer["print_time"] = seconds_to_time(intval($json["progress"]["printTime"]));
	$printer["print_status"]="Abgebrochen";
	$printer["view"]=2;
    }else if($system_status==0){
	$printer["print_status"]="Bereit";
	$printer["view"]=3;
    }else{
	$printer["print_status"]="Problem / Nicht betriebsbereit";
	$printer["view"]=4;
    }

    $printers[] = $printer;
}

mysqli_stmt_close($stmt);
echo json_encode($printers);
?>
