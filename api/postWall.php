<?php
/**
 * Nevka PHP API v1.4
 * 2025 Avicus Delacroix
 * 
 * Posts an entry on the wall.
 * Accepts: token, chat, text, image (multipart/form-data)
 * Returns: result, error? (json)
 */

include '../lib/ImageResize.php';
use \Gumlet\ImageResize;

if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {
    date_default_timezone_set("Europe/Moscow");

    $token = $text = $timestamp = $postImage = "";

    function validate($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    if (isset($_POST['token']) && isset($_POST['text']) && isset($_POST['image'])) {
        $token = validate($_POST['token']);
        $text = validate($_POST['text']);
        $timestamp = time();

        if ($_POST['image'] != "") {
            $image = new ImageResize($_POST['image']);
            $image->quality_jpg = 90;
            $image->resizeToLongSide(800);
            $postImage = 'data:image/jpeg;base64,'.base64_encode($image->getImageAsString(IMAGETYPE_JPEG));
        }

        include '../credentials.php';

        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
        }

        // get ID by token and record it to loggedUser

        $sql = "SELECT id FROM users WHERE token='$token' LIMIT 1";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_row();
            $loggedUser = $row[0];
        } else {
            echo "{\"result\":\"failure\",\"error\":\"wrong_token\"}";
        }

        // update online

        $sql = "UPDATE users SET timestamp='$timestamp' WHERE token='$token'";
        $conn->query($sql);

        $sql = "INSERT INTO walls (user, text, image, timestamp)
            VALUES ('$loggedUser', '$text', '$postImage', '$timestamp')";

        if ($conn->query($sql) === TRUE) {
            echo "{\"result\":\"success\"}";
        } else {
            echo "{\"result\":\"failure\",\"error\":\"Ошибка записи в БД\"}";
        }

        $conn->close();
    } else {
        echo "{\"result\":\"failure\",\"error\":\"Параметры token, text, image обязательны\"}";
    }
}
?>