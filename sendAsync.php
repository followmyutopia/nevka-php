<?php
/**
 * Nevka Project v1.6
 * 2025 Avicus Delacroix
 */

include 'db.php';
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
    echo $messageStatus;
}
?>