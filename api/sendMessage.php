<?php
/**
 * Nevka Project v1.6 API
 * 2025 Avicus Delacroix
 * 
 * Sends a message to the specified chat.
 * Accepts: token, chat, text, image (multipart/form-data)
 * Returns: result, error? (json)
 */

date_default_timezone_set("Europe/Moscow");
include '../lib/ImageResize.php';
use \Gumlet\ImageResize;

$token = $currentChat = $messageText = $messageImage = $timestamp = $query = "";

function validate($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if (isset($_POST['token']) && isset($_POST['chat']) && isset($_POST['text']) && isset($_POST['image'])) {
    $token = validate($_POST['token']);
    $currentChat = validate($_POST['chat']);
    $messageText = validate($_POST['text']);

    $image = new ImageResize($_POST['image']);
    $image->quality_jpg = 90;
    $image->resizeToLongSide(800);
    $messageImage = 'data:image/jpeg;base64,'.base64_encode($image->getImageAsString(IMAGETYPE_JPEG));

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
        echo "{\"result\":\"failure\",\"error\":\"token_not_valid\"}";
    }

    // check if user exists

    $sql = "SELECT id FROM users WHERE id='$loggedUser' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $timestamp = time();
        if ($loggedUser < $currentChat) {
            $query = "M".$loggedUser."M".$currentChat."M";
        } else {
            $query = "M".$currentChat."M".$loggedUser."M";
        }

        $sql = "INSERT INTO messages (query, author, text, image, timestamp)
        VALUES ('$query', '$loggedUser', '$messageText', '$messageImage', '$timestamp')";

        if ($conn->query($sql) === TRUE) {
            echo "{\"result\":\"success\"}";
        } else {
            echo "{\"result\":\"failure\",\"error\":\"Ошибка записи в БД\"}";
        }
    } else {
        echo "{\"result\":\"failure\",\"error\":\"not_existing_user\"}";
    }

    $conn->close();
} else {
    echo "{\"result\":\"failure\",\"error\":\"Параметры token, chat, text, image обязательны\"}";
}
?>