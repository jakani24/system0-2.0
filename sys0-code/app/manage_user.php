<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benutzerverwaltung</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body style="background-color: <?php echo $_SESSION['color']; ?>;">

<?php
session_start();
require_once "../log/log.php";
require_once "../config/config.php";
include "../assets/components.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"][3] !== "1") {
    header("location: /login/login.php");
    exit;
}
$_SESSION["rid"]++;
?>
<script src="/assets/js/load_page.js"></script>
<script>
function load_user()
{
	$(document).ready(function(){
   	$('#content').load("/assets/php/user_page.php");
	});
}
</script>
<div id="content"></div>
<div class="container mt-12" style="min-height:95vh">
    <h4>Benutzer suchen und verwalten</h4>
    <form id="userSearchForm">
        <input type="text" class="form-control" name="username" placeholder="Benutzername eingeben">
        <button type="submit" class="btn btn-primary">Suchen</button>
    </form>
    <div style="overflow-x: auto;">
    <table class="table mt-5" id="userTable" >
        <thead>
            <tr>
                <th>Nutzer</th>
                <th>Klasse</th>
                <th>Drucken</th>
                <th>Private Cloud</th>
                <th>Öffentliche Cloud</th>
                <th>Alle Drucker freigeben</th>
                <th>Benutzerrechte</th>
                <th>Admin erstellen</th>
                <th>Log ansehen</th>
                <th>API-Key</th>
                <th>Druckschlüssel</th>
                <th>Debug</th>
                <th>Öffentliche Dateien löschen</th>
		<th>Manuell verifizieren</th>
                <th>Löschen</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
    </div>
</div>

<div id="footer">
</div>

<script>
$(document).ready(function () {
    function fetchUsers(username = '') {
        $.get('/api/fetch_users.php', { username }, function (data) {
            $('#userTable tbody').html(data);
        });
    }

    $('#userSearchForm').on('submit', function (e) {
        e.preventDefault();
        const username = $(this).find('[name="username"]').val();
        fetchUsers(username);
    });

    $(document).on('change', '.updateField', function () {
        const field = $(this).data('field');
        const userId = $(this).data('userid');
        const value = $(this).is(':checkbox') ? ($(this).is(':checked') ? 1 : 0) : $(this).val();

        $.post('/api/update_user.php', { userId, field, value }, function (response) {
            console.log(response);
        });
    });

    $(document).on('click', '.deleteUser', function () {
        const userId = $(this).data('userid');
        if (confirm('Sind Sie sicher, dass Sie diesen Benutzer löschen möchten?')) {
            $.post('/api/delete_user.php', { userId }, function () {
                fetchUsers();
            });
        }
    });

    $(document).on('click', '.verify_user', function () {
        const userId = $(this).data('userid');
        $.post('/api/verify_user.php', { userId }, function () {
            fetchUsers();
        });
    });

    fetchUsers(); // Initiale Benutzer laden
    load_user();
});
</script>

</body>
</html>
