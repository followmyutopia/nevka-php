<?php
/**
 * Nevka Project v1.11
 * 2025 Avicus Delacroix
 */

$loggedUser = "";
include 'db.php';
include 'header.php';
use \Gumlet\ImageResize;

if(!isset($_COOKIE["user_token"])) { header("Refresh:0; url=index.php"); exit; }
else { $loggedUser = checkCookie($_COOKIE["user_token"]); updateOnline($_COOKIE["user_token"]); }
if(isset($_GET['logout'])) { logout(); }
$loggedUserData = getUserData($loggedUser);
if (!isset($loggedUserData[1]) || !isset($loggedUserData[2])) {header("Refresh:0; url=settings.php");}

$showUser = $show = $activeMenu = $userData = $status = $wallStatus = $city = $bio = $personalElement = $publicElement = $online = "";

if(!isset($_GET['id'])) {$showUser = $loggedUser; $activeMenu = "#profilemenu"; }
else if ($_GET['id'] == $loggedUser) { $showUser = validate($_GET['id']); $activeMenu = "#profilemenu"; }
else {
    if (userExists($_GET['id'])) { $showUser = validate($_GET['id']); $activeMenu = "#peoplemenu"; }
    else { $showUser = $loggedUser; $activeMenu = "#profilemenu"; }
}

$userData = getUserData($showUser);

if (isset($userData[0])) { $showAvatar = "<img src=".$userData[0].">";}
else { $showAvatar = "<p style='text-align: center'>Аватар не установлен<br>Пожалуйста, установите аватар.<br><br>Аватар может быть только в формате JPG/JPEG и с соотношением сторон 1:1. При установке НЕ квадратных фото они будут обрезаны по центру.<br><br></p>"; }

if (isset($userData[1]) && isset($userData[2])) { $showName = $userData[1]." ".$userData[2]; }
else { header("Refresh:0; url=settings.php"); }

if ($showUser == $loggedUser) { $publicElement = "hidden"; }
else { $personalElement = "hidden"; }

if (isset($userData[4]) && $userData[4] != "") { $city = "<p>Город: ".$userData[4]."</p>"; }
if (isset($userData[5]) && $userData[5] != "") { $bio = "<p>Информация: ".$userData[5]."</p>"; }

$lastOnline = date_create_from_format("d.m.Y в h:i a", $userData[6]); $now = new DateTime();
$difference = ($lastOnline->diff($now)->days * 1440) + ($lastOnline->diff($now)->h * 60) + $lastOnline->diff($now)->i;
if ($difference <= 5) { $online = "<p class=\"userStatus\" style=\"color:greenyellow;padding-top: 12px\">Онлайн</p>"; }
else if ($difference < 60) { $online = "<p class=\"userStatus\" style=\"color:lightslategray\">Был(а) в сети:<br>".$lastOnline->diff($now)->i." минут назад</p>"; }
else { $online = "<p class=\"userStatus\" style=\"color:lightslategray\">Был(а) в сети:<br>".$userData[6]."</p>"; }

if(isset($_POST["setAvatar"])) {
    $target_file = "tmp/" . basename($_FILES["fileToUpload"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    $blob = "";

    if ($_FILES["fileToUpload"]["tmp_name"] != "") {
        if (!getimagesize($_FILES["fileToUpload"]["tmp_name"])) { $status = "Файл не является изображением."; $uploadOk = 0; }
        if ($_FILES["fileToUpload"]["size"] > 16777215) { $status = "Аватар не может быть больше 16 MB."; $uploadOk = 0; }
        if ($imageFileType != "jpg" && $imageFileType != "jpeg") { $status = "Разрешено использовать только JPG файлы."; $uploadOk = 0; }
        if ($uploadOk == 1) {

            $image = new ImageResize($_FILES["fileToUpload"]["tmp_name"]);
            $image->quality_jpg = 90;
            $image->crop(480, 480);
            $blob = 'data:image/jpeg;base64,'.base64_encode($image);

            $status = setAvatar($_COOKIE["user_token"],$blob);
        }
    } else { $status = "Файл отсутствует"; }
}

if(isset($_POST['setStatus'])) {
    $newStatus = validate($_POST["status"]);
    if (mb_strlen($newStatus) > 127) {$wallStatus = "Статус максимум 127 символа";}
    else {
        $userData[3] = setStatus($_COOKIE["user_token"],$newStatus);
    }
}

if(isset($_POST['postWall'])) {
    $wallText = validate($_POST["message"]);
    if (mb_strlen($wallText) > 1023) {$wallStatus = "Максимум 1023 символа";}
    else {
        $target_file = "tmp/" . basename($_FILES["fileToUpload"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
        $blob = "";

        if ($_FILES["fileToUpload"]["tmp_name"] != "") {
            if (!getimagesize($_FILES["fileToUpload"]["tmp_name"])) { $status = "Файл не является изображением."; $uploadOk = 0; }
            if ($_FILES["fileToUpload"]["size"] > 16777215) { $status = "Изображение не может быть больше 16 MB."; $uploadOk = 0; }
            if ($imageFileType != "jpg" && $imageFileType != "jpeg" && $imageFileType != "png") { $status = "Разрешено использовать только JPG и PNG файлы."; $uploadOk = 0; }
            if ($uploadOk == 1) {
                $image = new ImageResize($_FILES["fileToUpload"]["tmp_name"]);
                if ($imageFileType == "jpg" || $imageFileType == "jpeg") {
                    $image->quality_jpg = 90;
                    $image->resizeToLongSide(640);
                    $blob = 'data:image/jpeg;base64,'.base64_encode($image);
                }
                else if ($imageFileType == "png") {
                    $image->quality_jpg = 90;
                    $image->resizeToLongSide(640);
                    $blob = 'data:image/jpeg;base64,'.base64_encode($image->getImageAsString(IMAGETYPE_JPEG));
                }
                $wallStatus = addWallEntry($_COOKIE["user_token"], $wallText, $blob);
            }
        } else {
            if ($wallText != "") { $wallStatus = addWallEntry($_COOKIE["user_token"], $wallText, $blob); }
            else { $wallStatus = "Введите текст"; }
        }
        }
}

if(isset($_POST['checkDeletePermission'])) { $wallStatus = deleteWallEntry($_COOKIE["user_token"], $_POST["id"]); }
?>

<div class="left">
    <?php echo $showAvatar ?>
    <form class="<?php echo $personalElement ?>" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>"
        method="post" enctype="multipart/form-data">
        <input class="fileInput wide" type="file" name="fileToUpload" id="fileToUpload">
        <button class="button wide" type="submit" name="setAvatar">Сменить аватар</button>
    </form>
    <button class="a button wide <?php echo $publicElement ?>" data-href="messages.php?id=<?php echo $showUser ?>">Написать сообщение</button>
    <p class="console"><?php echo $status ?></p>
</div>

<div class="right">
    <h1 class="clearfix"><?php echo $showName ?><?php echo $online ?></h1>
    <div class="info">
        <form id="statusForm" style="display:none" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
            <input type="text" name="status" value="<?php echo $userData[3] ?>">
            <button class="hidden" type="submit" name="setStatus"></button>
        </form>
        <span id="statusBar"><?php echo $userData[3] ?><br><span id="setStatusButton" class="<?php
            echo $personalElement ?>" style="color: lightslategray">Установить статус</span></span>
        <?php echo $city.$bio ?>
    </div>
    <form class="<?php echo $personalElement ?>" style="padding-bottom:5%" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>"
        method="post" enctype="multipart/form-data">
        <textarea name="message"></textarea>
        <button class="button mobile-expand" style="margin-right:-3px" type="submit" name="postWall">Опубликовать на стене</button>
        <input class="fileInput attachment mobile-expand" type="file" name="fileToUpload" id="fileToUpload">
        <span id="wallStatus"><?php echo $wallStatus ?></span>
    </form>
</div>

<div class="wall">
    <?php getWallEntries($showUser, $loggedUser) ?>
</div>

<form class="hidden" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post"><input id="idToCheck" name="id"><button id="tryDelete" type="submit" name="checkDeletePermission"></button></form><span class="hidden" id="title"><?php echo $showName ?></span>

<script>$(function(){
    document.addEventListener('dragover', (e) => {
        e.preventDefault()
    });
    document.addEventListener('drop', (e) => {
        e.preventDefault()
    });
    document.title = $("#title").text();
    $("<?php echo $activeMenu ?>").addClass("active");
    function toggleStatusEditor() { $("#statusBar").slideToggle(250,function(){ $("#statusForm").slideToggle(300); }); };
    $("#setStatusButton").click(function(){ toggleStatusEditor(); });
    $(".delPostButton").click(function(){ $("#idToCheck").val($(this).attr("id")); $("#tryDelete").trigger("click"); });
    if ($("#setStatusButton").attr("class") == "hidden") { $(".delPostButton").hide(); }
    $(".a").click(function() { window.location = $(this).data("href"); });
    $(".likeButton").click(function() {
        var heartId = "#like-" + $(this).data("id");
        var counterId = "#likeCount-" + $(this).data("id");
        var counter = parseInt($(counterId).text());
        $.post("updateAsync.php", { currentChat: $(this).data("id"), type: 4 }, function(data){
            if (data == 1) {
                $(heartId).css("color", "crimson");
                if (counter > 0) $(counterId).text(" " + (counter+1));
                else $(counterId).text(" " + (1));
            } else if (data == 0) {
                $(heartId).css("color", "rgba(255, 255, 255, 0.85)");
                if (counter == 1) $(counterId).text(""); 
                else if (counter > 0) $(counterId).text(" " + (counter-1));
            }
        });
    });
});</script>

<?php include 'footer.php'; ?>