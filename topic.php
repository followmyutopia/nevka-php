<?php
/**
 * Nevka Project v1.3
 * 2024-2025 Avicus Delacroix
 */

$loggedUser = "";
include 'db.php';
include 'header.php';

if(!isset($_COOKIE["user_token"])) { header("Refresh:0; url=index.php"); exit; }
else { $loggedUser = checkCookie($_COOKIE["user_token"]); updateOnline($_COOKIE["user_token"]); }
if(isset($_GET['logout'])) { logout(); }
$loggedUserData = getUserData($loggedUser);
if (!isset($loggedUserData[1]) || !isset($loggedUserData[2])) {header("Refresh:0; url=settings.php");}

$data = [];
$description = $status = $currentTopic = $title = "";

if(isset($_GET['id'])) {
    $checkedId = validate($_GET['id']);
    $data = getTopicHeader($checkedId);
    $currentTopic = $checkedId;
    $title = $data["0"];
    $description = "Тема создана пользователем ".getNameById($data["1"])." ".date("d.m.Y в h:i a", $data["3"]).".";
} else {
    header("Refresh:0; url=discussions.php");
}

if(isset($_POST['sendReply'])) {
    $messageText = validate($_POST["reply"]);
    if (mb_strlen($messageText) > 1023) {$status = "Максимум 1023 символа";}
    else {
        if ($messageText == "") {
            $status = "Введите текст";
        } else {
            $status = sendReply($title, $_COOKIE["user_token"], $messageText);
        }
    }
}
?>

<div class="discussion">
    <h1><?php echo $title ?></h1>
    <p><?php echo $description?></p><hr><span class="clearfix"></span>
    <p><?php echo $data["2"] ?></p>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>?id=<?php echo $currentTopic ?>" method="post">
        <textarea name="reply"></textarea>
        <button class="button" id="send" type="submit" name="sendReply">Ответить</button>
        <span class="console " tyle="margin-top:1px"><?php echo $status; ?></span>
    </form>

    <table id="responsesTable"></table>
</div>

<script>$(function(){
    $("#discussionsmenu").addClass("active");
    $.post("updateAsync.php", { currentChat: "<?php echo $data["0"] ?>", type: 1 }, function(data){
        if (data != $("#responsesTable").html()) {
            $("#responsesTable").html(data);
        }
    });
    window.setInterval(function(){ $.post("updateAsync.php", { currentChat: "<?php echo $data["0"] ?>", type: 1 }, function(data){ if (data != $("#responsesTable").html()) { $("#responsesTable").html(data); } }); },1000);
    $('textarea').keypress(function (e) { if(e.which == 13) { $('#send').click(); } });
});</script>

<?php include 'footer.php'; ?>