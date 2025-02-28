<?php
        //this file returns a list of available printers, theyr status and theyr color
        session_start();
        $file_path=$_SESSION["current_file"];
        include "../../config/config.php";
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true or $_SESSION["role"][0]!=="1"){
                die("no_auth");
            exit;
        }
        $class=$_SESSION["class_id"];
        include "../../config/config.php";
        //check if printers are reserved right now or will be while print is running
        $is_unsafe=check_file($file_path);
        echo(json_encode(["status"=>$is_unsafe]));


function extract_param($gcode) {
    // Match the pattern S followed by digits, capturing the digits
    $matches = [];
    $pattern = '/[S|T]([0-9]+)/';

    if (preg_match($pattern, $gcode, $matches)) {
        return (int)$matches[1]; // Return the first capture group as an integer
    } else {
        return false; // No match found
    }
}
function check_file($path){//check file for temperature which are to high
	$file = fopen($path, 'r');
	$cnt=0;
	while (!feof($file)&&$cnt!=2) {
	    $line = fgets($file);
	    // Extract parameter from lines with specific commands
	    if (strpos($line, 'M104') !== false || strpos($line, 'M140') !== false) {
		$cnt++;
	        $parameter = extract_param($line);
		if(strpos($line, 'M104') !== false){ //extruder_temp
			$ex_temp=$parameter;
		}
		if(strpos($line, 'M140') !== false){ //bed temp
			$bed_temp=$parameter;
		}
	    }
	}
	//echo("bed:$bed_temp;ex:$ex_temp");
	if($bed_temp>75 or $ex_temp>225){
		return 1;
	}else{
		return 0;
	}
}
?>
