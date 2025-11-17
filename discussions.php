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

$name = $message = $status = $searchQuery = "";

if(isset($_POST['createTopic'])) {
    $name = validate($_POST["topicName"]);
    $message = validate($_POST["topicMessage"]);
    if (mb_strlen($message) > 255 || strlen($name) > 255) {$status = "Поля максимум 255 символов";}
    else {
        if ($name == "") { $status = "Введите тему"; }
        else if ($message == "") { $status = "Введите сообщение"; }
        else { $status = createTopic($_COOKIE["user_token"], $name, $message); }
    }
}

if(isset($_POST['searchTopics'])) {
    $query = validate($_POST["query"]);
    $searchQuery = $query;
}
?>

<form id="editor" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post" class="discussion" style="display: none">
    <h2>Создание темы</h2>
    <p>Тема:</p><input class="" type="text" name="topicName">
    <p>Сообщение:</p><textarea name="topicMessage"></textarea>
    <button class="button" type="submit" name="createTopic">Создать тему</button>
    <button class="button" id="returnButton">Вернуться к списку тем</button>
    <span class="console " tyle="margin-top:1px"><?php echo $status; ?></span>
</form>

<div id="list">
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
        <input class="userSearch" type="text" name="query">
        <button class="hidden" type="submit" name="searchTopics"></button>
    </form>
    <div class="buttonpad">
        <button class="button" id="createButton">Создать тему</button>
    </div>
    <table id="peopleList">
        <?php getDiscussions($searchQuery) ?>
    </table>
</div>

<script>$(function(){
    $("#discussionsmenu").addClass("active");
    $(".a").click(function() { window.location = $(this).data("href"); });
    $("#createButton").click(() => { $("#list").slideToggle(300); $("#editor").slideToggle(300); });
    $("#returnButton").click(() => { $("#list").slideToggle(300); $("#editor").slideToggle(300); });
});</script>

<?php include 'footer.php'; ?>