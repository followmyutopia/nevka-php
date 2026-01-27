<?php
include '../lib/ImageResize.php';
use \Gumlet\ImageResize;

if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {

    date_default_timezone_set("Europe/Moscow");

    if (isset($_POST['token']) && isset($_POST['avatar']) && $_POST['avatar'] != "") {

        include '../credentials.php';
        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
            exit;
        }

        $token = $conn->real_escape_string($_POST['token']);

        // process avatar
        $image = new ImageResize($_POST['avatar']);
        $image->quality_jpg = 90;
        $image->crop(480, 480);
        $newAvatar = $conn->real_escape_string('data:image/jpeg;base64,' . base64_encode($image->getImageAsString(IMAGETYPE_JPEG)));

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
