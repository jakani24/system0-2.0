<?php
function test_queue($link) // Function to check if any printer is free and process all jobs in the queue
{
    $sql = "SELECT id, from_userid, filepath, print_on FROM queue ORDER BY id";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    mysqli_stmt_bind_result($stmt, $qid, $quserid, $qfilepath, $print_on);

    while (mysqli_stmt_fetch($stmt)) {
        $num_of_printers = 0;
        if ($print_on == -1)
            $sql_printers = "SELECT COUNT(*) FROM printer";
        else
            $sql_printers = "SELECT COUNT(*) FROM printer WHERE id = $print_on";
        
        $stmt_printers = mysqli_prepare($link, $sql_printers);
        mysqli_stmt_execute($stmt_printers);
        mysqli_stmt_store_result($stmt_printers);
        mysqli_stmt_bind_result($stmt_printers, $num_of_printers);
        mysqli_stmt_fetch($stmt_printers);

        if ($num_of_printers > 0) {
            $id = 0;
            $papikey = "";
            $purl = "";
            if ($print_on == -1)
                $sql_free_printers = "SELECT id, apikey, printer_url FROM printer WHERE free = 1 ORDER BY id";
            else
                $sql_free_printers = "SELECT id, apikey, printer_url FROM printer WHERE id = $print_on AND free = 1";
            
            $stmt_free_printers = mysqli_prepare($link, $sql_free_printers);
            mysqli_stmt_execute($stmt_free_printers);
            mysqli_stmt_store_result($stmt_free_printers);
            mysqli_stmt_bind_result($stmt_free_printers, $id, $papikey, $purl);

            if (mysqli_stmt_fetch($stmt_free_printers)) { // Found a free printer
                $username = $_SESSION["username"];
                $curl_cmd = 'curl -k -H "X-Api-Key: ' . $papikey . '" -F "select=true" -F "print=true" -F "file=@' . $qfilepath . '" "' . $purl . '/api/files/local" > /var/www/html/user_files/' . $username . '/json.json';
                exec($curl_cmd);
                
                $fg = file_get_contents("/var/www/html/user_files/$username/json.json");
                $json = json_decode($fg, true);

                if ($json['effectivePrint'] == true && $json["effectiveSelect"] == true) {
                    $sql_update_printer = "UPDATE printer SET free = 0, printing = 1, mail_sent = 0, used_by_userid = $quserid WHERE id = $id";
                    $stmt_update_printer = mysqli_prepare($link, $sql_update_printer);
                    mysqli_stmt_execute($stmt_update_printer);

                    $sql_delete_queue = "DELETE FROM queue WHERE id = $qid";
                    $stmt_delete_queue = mysqli_prepare($link, $sql_delete_queue);
                    mysqli_stmt_execute($stmt_delete_queue);
                } else {
                    //echo "Failed sending file to printer for queue ID $qid!<br>";
                }
            } else {
                //echo "No free printer available for queue ID $qid!<br>";
            }
        } else {
            //echo "No printers available for queue ID $qid!<br>";
        }
    }
}
?>
