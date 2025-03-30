<?php
session_start();
        $file_path=$_SESSION["current_file"];
        include "../../config/config.php";
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true or $_SESSION["role"][0]!=="1"){
                die("no_auth");
            exit;
        }
$username=$_SESSION["username"];
if($_GET["pc"]=="1")
	echo(get_base64_preview("/var/www/html/user_files/public/".$_GET["file"]));
else
	echo(get_base64_preview("/var/www/html/user_files/$username/".$_GET["file"]));
function get_base64_preview($filename){
                $base64="";
                $file=fopen($filename,"r");
                $start=-1;
                while(!feof($file)&&$start!=0){
                        $buf=fgets($file);
                        if(stripos($buf,"thumbnail end")!==false)
                                $start=0;
                        if($start==1)
                                $base64.=$buf;
                        if(stripos($buf,"thumbnail begin")!==false)
                                $start=1;
                }
                fclose($file);
                $base64=str_replace(";","",$base64);
                $base64=str_replace(" ","",$base64);
                return $base64;
        }
?>
