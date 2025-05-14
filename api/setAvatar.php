<?php
/**
 * Nevka PHP API v1.4
 * 2025 Avicus Delacroix
 * 
 * Sets an avatar for the currently logged user.
 * Accepts: token, push_token (multipart/form-data)
 * Returns: result, error? (json)
 */

include '../lib/ImageResize.php';
use \Gumlet\ImageResize;

if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {
    date_default_timezone_set("Europe/Moscow");

    $token = $status = "";

    function validate($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    if (isset($_POST['token']) && isset($_POST['avatar']) && $_POST['avatar'] != "") {
        $token = validate($_POST['token']);

        $image = new ImageResize($_POST['avatar']);
        $image->quality_jpg = 90;
        $image->crop(480, 480);
        $newAvatar = 'data:image/jpeg;base64,'.base64_encode($image->getImageAsString(IMAGETYPE_JPEG));

        include '../credentials.php';

        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
        }

        $sql = "UPDATE users SET avatar='$newAvatar' WHERE token='$token'";

        if ($conn->query($sql) === TRUE) {
            echo "{\"result\":\"success\"}";
        } else {
            echo "{\"result\":\"failure\",\"error\":\"wrong_token\"}";
        }

        $conn->close();
    } else {
        echo "{\"result\":\"failure\",\"error\":\"Параметры token, avatar обязательны. Аватар не может быть пустым.\"}";
    }
}
?>