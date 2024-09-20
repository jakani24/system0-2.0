<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Viewer</title>
    <script src="/assets/js/load_page.js"></script>
</head>

<?php
// Initialize the session and include session checks (unchanged)
session_start();
include "../config/config.php";
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"][6] !== "1") {
    header("location: /login/login.php");
    exit;
}

// Sanitize session variables
$username = htmlspecialchars($_SESSION["username"]);
$id = intval($_SESSION["id"]);
$color = htmlspecialchars($_SESSION["color"]);
include "../assets/components.php";
?>

<script>
// Load user content with AJAX
$(document).ready(function () {
    $('#content').load("/assets/php/user_page.php");
});
</script>

<body style="background-color: <?= $color ?>;">
<div id="content"></div>

<div class="container m-3" style="min-height:95vh">
    <div class="row justify-content-center">
        <div class="col-md-auto">
            <h1>Log Entries</h1>

            <!-- Filter Form -->
            <div class="overflow-auto">
                <form method="GET" action="">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>IP Address</th>
                            <th>Type</th>
                            <th>Username</th>
                            <th>Info</th>
                        </tr>
                        <tr>
                            <td>---</td>
                            <td>---</td>
                            <td>
                                <select class="form-select" name="type_">
                                    <option value="All_types">All Types</option>
                                    <option value="PRINT::UPLOAD::PRINTER">PRINT::UPLOAD::PRINTER</option>
                                    <option value="PRINT:JOB:START:FAILED">PRINT:JOB:START:FAILED</option>
                                    <option value="PRINT::UPLOAD::QUEUE">PRINT::UPLOAD::QUEUE</option>
                                    <option value="PRINT::UPLOAD::FILE::FAILED">PRINT::UPLOAD::FILE::FAILED</option>
                                    <option value="JOB::PRINTERCTRL::FREE">JOB::PRINTERCTRL::FREE</option>
                                    <option value="JOB::QUEUECTRL::REMOVE">JOB::QUEUECTRL::REMOVE</option>
                                    <option value="JOB::PRINTERCTRL::CANCEL::FAILED">JOB::PRINTERCTRL::CANCEL::FAILED</option>
                                    <option value="JOB::PRINTERCTRL::CANCEL">JOB::PRINTERCTRL::CANCEL</option>
                                </select>
                            </td>
                            <td>
                                <select class="form-select" id="username" name="username">
                                    <option value="All_usernames">All Users</option>
                                    <?php
                                    // Fetch all usernames in one query
                                    $sql = "SELECT username FROM users ORDER BY username ASC";
                                    $stmt = mysqli_prepare($link, $sql);
                                    mysqli_stmt_execute($stmt);
                                    mysqli_stmt_bind_result($stmt, $uname);

                                    // Populate the dropdown with usernames
                                    while (mysqli_stmt_fetch($stmt)) {
                                        echo '<option value="' . htmlspecialchars($uname) . '">' . htmlspecialchars($uname) . '</option>';
                                    }
                                    mysqli_stmt_close($stmt);
                                    ?>
                                </select>
                            </td>
                            <td>
                                <button type="submit" class="btn btn-primary">Apply Filter</button>
                            </td>
                        </tr>
                        </thead>
                        <tbody>

                        <?php
                        // Define default filters
                        $type_filter = isset($_GET["type_"]) ? $_GET["type_"] : "All_types";
                        $user_filter = isset($_GET["username"]) ? $_GET["username"] : "All_usernames";

                        // Pagination variables
                        $items_per_page = 25;
                        $current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                        if ($current_page < 1) {
                            $current_page = 1;
                        }
                        $offset = ($current_page - 1) * $items_per_page;

                        // Read and process the log file
                        $log_entries = [];
                        if ($fp = fopen("../log/sys0.log", "r")) {
                            while (($line = fgets($fp)) !== false) {
                                $data = explode(";", $line);
                                if (count($data) >= 5) {
                                    // Add valid log entries to an array
                                    $log_entries[] = [
                                        'date' => htmlspecialchars($data[0]),
                                        'ip' => htmlspecialchars($data[1]),
                                        'type' => htmlspecialchars($data[2]),
                                        'username' => htmlspecialchars($data[3]),
                                        'info' => htmlspecialchars($data[4])
                                    ];
                                }
                            }
                            fclose($fp);
                        }

                        // Reverse the log entries to display the latest first
                        $log_entries = array_reverse($log_entries);

                        // Filter log entries by type and username
                        $filtered_entries = array_filter($log_entries, function ($entry) use ($type_filter, $user_filter) {
                            $type_match = ($type_filter === "All_types" || $entry['type'] === $type_filter);
                            $user_match = ($user_filter === "All_usernames" || $entry['username'] === $user_filter);
                            return $type_match && $user_match;
                        });

                        // Calculate total pages and slice the log entries for the current page
                        $total_entries = count($filtered_entries);
                        $total_pages = ceil($total_entries / $items_per_page);
                        $paged_entries = array_slice($filtered_entries, $offset, $items_per_page);

                        // Display the filtered entries for the current page
                        foreach ($paged_entries as $entry) {
                            echo "<tr>
                                    <td>{$entry['date']}</td>
                                    <td>{$entry['ip']}</td>
                                    <td>{$entry['type']}</td>
                                    <td>{$entry['username']}</td>
                                    <td>{$entry['info']}</td>
                                  </tr>";
                        }

                        if (empty($paged_entries)) {
                            echo "<tr><td colspan='5'>No log entries found.</td></tr>";
                        }
                        ?>

                        </tbody>
                    </table>
                </form>

                <!-- Pagination Controls -->
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($current_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $current_page - 1 ?>&type_=<?= urlencode($type_filter) ?>&username=<?= urlencode($user_filter) ?>">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($page = 1; $page <= $total_pages; $page++): ?>
                            <li class="page-item <?= ($page === $current_page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $page ?>&type_=<?= urlencode($type_filter) ?>&username=<?= urlencode($user_filter) ?>"><?= $page ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $current_page + 1 ?>&type_=<?= urlencode($type_filter) ?>&username=<?= urlencode($user_filter) ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>

            </div>
        </div>
    </div>
</div>

<div id="footer"></div>
</body>
</html>
