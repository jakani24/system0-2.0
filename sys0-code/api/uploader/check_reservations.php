<?php
date_default_timezone_set('Europe/Zurich');
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
	$is_reserved=check_reservation_conflict($link, $class);
	if($is_reserved==0){
		$is_reserved=check_print_reservation_conflict($link, $class, $file_path);
	}
	echo(json_encode(["status"=>$is_reserved]));


function find_print_time($file) {
    $handle = fopen($file, "r");
    $targetPhrase = "; estimated printing time (normal mode) = ";
    $time = null;

    while (($line = fgets($handle)) !== false) {
        if (strpos($line, $targetPhrase) !== false) {
            // Extract the time after the target phrase
            $time = trim(str_replace($targetPhrase, "", $line));
            break; // Stop once the desired line is found
        }
    }

    fclose($handle);

    return $time;
}



function check_reservation_conflict($link, $class) {
    $reservation_conflict = false;
    $today = date("Y-m-d");
    $time_now = date("H:i");
    $for_class = [];

    // Query for reservations that start today or extend into today
    $sql = "
        SELECT day, time_from, time_to, for_class 
        FROM reservations 
        WHERE day <= '$today' AND 
              (day = '$today' AND time_from <= '$time_now' OR day < '$today');
    ";
    $stmt = $link->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Calculate the actual end time of the reservation
        $reservation_end = strtotime($row["day"] . " " . $row["time_to"]);
        $current_time = strtotime("$today $time_now");

        if ($current_time <= $reservation_end) {
            $reservation_conflict = true;
            $for_class[] = $row["for_class"];
        }
    }

    // Default value for for_class if no conflicts are found
    if (empty($for_class)) {
        $for_class[] = 0;
    }

    // Determine the appropriate response based on the conflict status
    $response = 0;

    if ($reservation_conflict && !in_array($class, $for_class) && $class != 0) {
	$response=1;
    } elseif ($class == 0 && $reservation_conflict) {
	$response=2;
    }

    return $response;
}

function check_print_reservation_conflict($link, $class, $path) {
    $reservation_conflict = false;
    $for_class = [];
    $today = date("Y-m-d");
    $time_now = date("H:i");

    // Calculate the end time of the print
    $print_time = find_print_time($path); // Assume this function is already defined
    preg_match('/(\d+)h/', $print_time, $hours_match);
    preg_match('/(\d+)m/', $print_time, $minutes_match);
    $hours = isset($hours_match[1]) ? (int)$hours_match[1] : 0;
    $minutes = isset($minutes_match[1]) ? (int)$minutes_match[1] : 0;
	//echo("uses ".$minutes." Minutes and ".$hours." hours");
    $start_time = DateTime::createFromFormat('H:i', $time_now);
    $end_time = clone $start_time;
    $end_time->modify("+{$hours} hour");
    $end_time->modify("+{$minutes} minutes");

    // Query to get all relevant reservations (today and future overlaps)
    $sql = "
        SELECT day, time_from, time_to, for_class 
        FROM reservations 
        WHERE day >= '$today';
    ";
    $stmt = $link->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check for conflicts with reservations
    while ($row = $result->fetch_assoc()) {
        $reservation_start = DateTime::createFromFormat('Y-m-d H:i', $row["day"] . ' ' . $row["time_from"]);
        $reservation_end = DateTime::createFromFormat('Y-m-d H:i', $row["day"] . ' ' . $row["time_to"]);

        // Adjust reservation end time for multi-day overlaps
        if ($reservation_end < $reservation_start) {
            $reservation_end->modify('+1 day');
        }

        // Check if the print overlaps with any reservation period
        if ($start_time < $reservation_end && $end_time > $reservation_start) {
            $reservation_conflict = true;
            $for_class[] = $row["for_class"];
        }
    }

    // Default value for for_class if no conflicts are found
    if (empty($for_class)) {
        $for_class[] = 0;
    }

    // Build response based on conflict and user access
    $response = 0;

    if ($reservation_conflict && !in_array($class, $for_class) && $class != 0) {
	$response=1;
    } elseif ($class == 0 && $reservation_conflict) {
	$response=2;
    }

    return $response;
}
?>



