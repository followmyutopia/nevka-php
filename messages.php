<?php
/**
 * Nevka Project v1.4
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

$currentChat = $messageStatus = $textarea = $leftSection = $rightSection = "";

if(isset($_GET['id'])) {
    if (userExists($_GET['id'])) {
        $currentChat = validate($_GET['id']);
        $leftSection = "mobile-hidden";
    } else {
        $currentChat = lastId($loggedUser); $rightSection = "mobile-hidden";
    }
    
}
else { $currentChat = lastId($loggedUser); $rightSection = "mobile-hidden"; }

if ($currentChat == 0) { $rightSection = "hidden"; $leftSection = "wide"; }

if(isset($_POST['sendMessage'])) {
    $messageText = validate($_POST["message"]);
    $currentChat = validate($_POST["interlocutor"]);
    if (mb_strlen($messageText) > 1023) {$messageStatus = "Максимум 1023 символа";}
    else {
        $target_file = "tmp/" . basename($_FILES["fileToUpload"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
        $blob = "";

        if ($_FILES["fileToUpload"]["tmp_name"] != "") {
            if (!getimagesize($_FILES["fileToUpload"]["tmp_name"])) { $status = "Файл не является изображением."; $uploadOk = 0; }
            if ($_FILES["fileToUpload"]["size"] > 16777215) { $status = "Аватар не может быть больше 16 MB."; $uploadOk = 0; }
            if ($imageFileType != "jpg" && $imageFileType != "jpeg" && $imageFileType != "png") { $status = "Разрешено использовать только JPG и PNG файлы."; $uploadOk = 0; }
            if ($uploadOk == 1) {
                $source = file_get_contents($_FILES["fileToUpload"]["tmp_name"]);
                if ($imageFileType == "jpg" || $imageFileType == "jpeg") { $blob = 'data:image/jpeg;base64,'.base64_encode($source); }
                else if ($imageFileType == "png") { $blob = 'data:image/png;base64,'.base64_encode($source); }
                $messageStatus = sendMessage($_COOKIE["user_token"], $currentChat, $messageText, $blob);
            }
        } else {
            if ($messageText != "") { $wallStatus = sendMessage($_COOKIE["user_token"], $currentChat, $messageText, $blob); }
            else { $messageStatus = "Введите текст"; }
        }
    }
}
?>

<div class="left nomargin-right mobile-expand <?php echo $leftSection ?>">
    <div class="peopleWindow">
        <table style="float:left"><?php showChatsTable($_COOKIE["user_token"]); ?></table>
    </div>
</div>
<div class="right mobile-expand <?php echo $rightSection ?>">
    <?php currentUserHeader($currentChat) ?>

    <div class="activeChat">
        <div id="chatWindow"></div>
        <form class="<?php echo $textarea ?>" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>?id=<?php echo $currentChat ?>" method="post" enctype="multipart/form-data">
            <textarea name="message"></textarea><input class="hidden" name="interlocutor" value="<?php echo $currentChat ?>">
            <button id="send" class="button mobile-expand send" type="submit" name="sendMessage">Отправить</button>
            <input class="fileInput attachment mobile-expand send" type="file" name="fileToUpload" id="fileToUpload">
            <span class="console send" style="margin-top:1px"><?php echo $messageStatus ?></span>
        </form>
</div>

<script>$(function(){
    $("#messagesmenu").addClass("active");
    $('#chatWindow').scrollTop($('#chatWindow')[0].scrollHeight);
    $(".a").click(function() { window.location = $(this).data("href"); });
    $.post("updateAsync.php", { currentChat: <?php echo $currentChat ?>, type: 0 }, function(data){
        if (data != $("#chatWindow").html()) {
            $("#chatWindow").html(data).scrollTop($('#chatWindow')[0].scrollHeight);
        }
    });
    var buffer = 0;
    window.setInterval(function(){
        $.post("updateAsync.php", { type: 2 }, function(data){
            if (data > 0) {
                if (data != buffer) {
                    $.post("updateAsync.php", { currentChat: <?php echo $currentChat ?>, type: 0 }, function(e){
                        $("#chatWindow").html(e).scrollTop($('#chatWindow')[0].scrollHeight);
                    });
                }
                buffer = data;
            }
        });
    },500);
    $('textarea').keypress(function (e) { if(e.which == 13) { $('#send').click(); } });
    if (<?php echo $currentChat ?> != 0) { $("#pplNotice").hide(); }
});</script>

<?php include 'footer.php'; ?>