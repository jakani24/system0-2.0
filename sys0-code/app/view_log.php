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

            <!-- Search Form -->
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
                            <td>
                                <!-- Search by IP Address -->
                                <input type="text" class="form-control" name="search_ip" value="<?= isset($_GET['search_ip']) ? htmlspecialchars($_GET['search_ip']) : '' ?>" placeholder="Search IP...">
                            </td>
                            <td>
                                <!-- Search by Type -->
                                <input type="text" class="form-control" name="search_type" value="<?= isset($_GET['search_type']) ? htmlspecialchars($_GET['search_type']) : '' ?>" placeholder="Search Type...">
                            </td>
                            <td>
                                <!-- Search by Username -->
                                <input type="text" class="form-control" name="search_username" value="<?= isset($_GET['search_username']) ? htmlspecialchars($_GET['search_username']) : '' ?>" placeholder="Search Username...">
                            </td>
                            <td>
                                <!-- Search by Info -->
                                <input type="text" class="form-control" name="search_info" value="<?= isset($_GET['search_info']) ? htmlspecialchars($_GET['search_info']) : '' ?>" placeholder="Search Info...">
                            </td>
                        </tr>
                        <tr>
                            <td colspan="5">
                                <button type="submit" class="btn btn-primary">Search</button>
                            </td>
                        </tr>
                        </thead>
                        <tbody>

                        <?php
                        // Retrieve search terms from GET parameters
                        $search_ip = isset($_GET['search_ip']) ? $_GET['search_ip'] : '';
                        $search_type = isset($_GET['search_type']) ? $_GET['search_type'] : '';
                        $search_username = isset($_GET['search_username']) ? $_GET['search_username'] : '';
                        $search_info = isset($_GET['search_info']) ? $_GET['search_info'] : '';

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

                        // Filter log entries by search terms (partial matches)
                        $filtered_entries = array_filter($log_entries, function ($entry) use ($search_ip, $search_type, $search_username, $search_info) {
                            $ip_match = empty($search_ip) || stripos($entry['ip'], $search_ip) !== false;
                            $type_match = empty($search_type) || stripos($entry['type'], $search_type) !== false;
                            $username_match = empty($search_username) || stripos($entry['username'], $search_username) !== false;
                            $info_match = empty($search_info) || stripos($entry['info'], $search_info) !== false;
                            return $ip_match && $type_match && $username_match && $info_match;
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
                                <a class="page-link" href="?page=<?= $current_page - 1 ?>&search_ip=<?= urlencode($search_ip) ?>&search_type=<?= urlencode($search_type) ?>&search_username=<?= urlencode($search_username) ?>&search_info=<?= urlencode($search_info) ?>">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($page = 1; $page <= $total_pages; $page++): ?>
                            <li class="page-item <?= ($page === $current_page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $page ?>&search_ip=<?= urlencode($search_ip) ?>&search_type=<?= urlencode($search_type) ?>&search_username=<?= urlencode($search_username) ?>&search_info=<?= urlencode($search_info) ?>"><?= $page ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $current_page + 1 ?>&search_ip=<?= urlencode($search_ip) ?>&search_type=<?= urlencode($search_type) ?>&search_username=<?= urlencode($search_username) ?>&search_info=<?= urlencode($search_info) ?>">Next</a>
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
