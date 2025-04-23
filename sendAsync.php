<?php
/**
 * Nevka Project v1.6
 * 2025 Avicus Delacroix
 */

include 'db.php';
include 'lib/ImageResize.php';
use \Gumlet\ImageResize;

if(isset($_POST['sendMessage'])) {
    $messageStatus = "OK"; // Panamar proDUCKtion
    $messageText = validate($_POST["message"]);
    $currentChat = validate($_POST["interlocutor"]);
    if (mb_strlen($messageText) > 1023) {$messageStatus = "Максимум 1023 символа";}
    else {
        $target_file = "tmp/" . basename($_FILES["fileToUpload"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
        $blob = "";

        if ($_FILES["fileToUpload"]["tmp_name"] != "") {
            if (!getimagesize($_FILES["fileToUpload"]["tmp_name"])) { $messageStatus = "Файл не является изображением."; $uploadOk = 0; }
            if ($_FILES["fileToUpload"]["size"] > 16777215) { $messageStatus = "Изображение не может быть больше 16 MB."; $uploadOk = 0; }
            if ($imageFileType != "jpg" && $imageFileType != "jpeg" && $imageFileType != "png") { $messageStatus = "Разрешено использовать только JPG и PNG файлы."; $uploadOk = 0; }
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
                $messageStatus = sendMessage($_COOKIE["user_token"], $currentChat, $messageText, $blob);
            }
        } else {
            if ($messageText != "") { $wallStatus = sendMessage($_COOKIE["user_token"], $currentChat, $messageText, $blob); }
            else { $messageStatus = "Введите текст"; }
        }
    }
    echo $messageStatus;
}
?>