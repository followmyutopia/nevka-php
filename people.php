<?php
/**
 * Nevka Project v1.13
 * 2025 Avicus Delacroix
 */

$loggedUser = "";
include 'db.php';
include 'header.php';

if(!isset($_COOKIE["user_token"])) { header("Refresh:0; url=index.php"); exit; }
else { $loggedUser = checkCookie($_COOKIE["user_token"]); updateOnline($_COOKIE["user_token"]); }
if(isset($_GET['logout'])) { logout(); }
$loggedUserData = getUserData($loggedUser);
if (!isset($loggedUserData[1]) || !isset($loggedUserData[2])) {header("Refresh:0; url=settings.php");}

$searchQuery = "";

if(isset($_POST['searchUsers'])) { $query = validate($_POST["query"]); $searchQuery = $query; }
?>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
    <input class="userSearch" type="text" name="query">
    <button class="hidden" type="submit" name="searchUsers"></button>
</form>
<table id="peopleList"><?php showUsersTable($searchQuery); ?></table>

<script>$(function(){
    $("#peoplemenu").addClass("active");
    $(".a").click(function() { window.location = $(this).data("href"); });
});</script>

<?php include 'footer.php'; ?>