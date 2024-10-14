<?php
// Initialize the session and check if the user is authenticated
session_start();
include "../config/config.php";

// Perform authentication and role checks
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"][9] !== "1") {
    echo "Unauthorized access!";
    exit;
}

// Sanitize session variables
$username = htmlspecialchars($_SESSION["username"]);
$id = intval($_SESSION["id"]);
$color = htmlspecialchars($_SESSION["color"]);

// Server statistics retrieval

// Get server load
$load = sys_getloadavg();
$load_percentage = round(($load[0] / 4) * 100); // Assuming a 4-core processor

// Get CPU usage
$cpu_usage = shell_exec("top -bn1 | grep 'Cpu(s)' | sed 's/.*, *\\([0-9.]*\\)%* id.*/\\1/' | awk '{print 100 - $1}'");
$cpu_usage = round($cpu_usage, 2);

// Get RAM usage
$ram_usage = shell_exec("free | grep Mem | awk '{print $3/$2 * 100.0}'");
$ram_usage = round($ram_usage, 2);

// Get Disk usage
$disk_usage = shell_exec("df -h /dev/sda1 | grep / | awk '{print $5}'");

// Get server uptime
$uptime = shell_exec("uptime -p");

// Output stats with Bootstrap styling

echo "
<div class='col-md-6 mb-4'>
    <div class='card'>
        <div class='card-body'>
            <p class='stat-label'>CPU Usage: {$cpu_usage}%</p>
            <div class='progress'>
                <div class='progress-bar bg-danger' role='progressbar' style='width: {$cpu_usage}%' aria-valuenow='{$cpu_usage}' aria-valuemin='0' aria-valuemax='100'>{$cpu_usage}%</div>
            </div>
        </div>
    </div>
</div>

<div class='col-md-6 mb-4'>
    <div class='card'>
        <div class='card-body'>
            <p class='stat-label'>RAM Usage: {$ram_usage}%</p>
            <div class='progress'>
                <div class='progress-bar bg-warning' role='progressbar' style='width: {$ram_usage}%' aria-valuenow='{$ram_usage}' aria-valuemin='0' aria-valuemax='100'>{$ram_usage}%</div>
            </div>
        </div>
    </div>
</div>

<div class='col-md-6 mb-4'>
    <div class='card'>
        <div class='card-body'>
            <p class='stat-label'>Server Load (1 min avg): {$load[0]}</p>
            <div class='progress'>
                <div class='progress-bar bg-info' role='progressbar' style='width: {$load_percentage}%' aria-valuenow='{$load_percentage}' aria-valuemin='0' aria-valuemax='100'>{$load_percentage}%</div>
            </div>
        </div>
    </div>
</div>

<div class='col-md-6 mb-4'>
    <div class='card'>
        <div class='card-body'>
            <p class='stat-label'>Disk Usage: {$disk_usage}</p>
            <div class='progress'>
                <div class='progress-bar bg-success' role='progressbar' style='width: {$disk_usage}' aria-valuenow='{$disk_usage}' aria-valuemin='0' aria-valuemax='100'>{$disk_usage}</div>
            </div>
        </div>
    </div>
</div>

<div class='col-md-12 mb-4'>
    <div class='card'>
        <div class='card-body'>
            <p class='stat-label'>Server Uptime: {$uptime}</p>
        </div>
    </div>
</div>
";
?>
