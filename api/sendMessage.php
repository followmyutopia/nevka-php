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

$token = $currentChat = $messageText = $messageImage = $timestamp = $query = $targetToken = "";

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
    $timestamp = time();

    if ($_POST['image'] != "") {
        $image = new ImageResize($_POST['image']);
        $image->quality_jpg = 90;
        $image->resizeToLongSide(800);
        $messageImage = 'data:image/jpeg;base64,'.base64_encode($image->getImageAsString(IMAGETYPE_JPEG));
    }

    include '../credentials.php';

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
    }

    // get ID by token and record it to loggedUser

    $sql = "SELECT id, firstname, lastname FROM users WHERE token='$token' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        $loggedUser = $row[0];
        $senderName = $row[1]." ".$row[2];
    } else {
        echo "{\"result\":\"failure\",\"error\":\"token_not_valid\"}";
    }

    // update online

    $sql = "UPDATE users SET timestamp='$timestamp' WHERE token='$token'";
    $conn->query($sql);

    // check if user exists

    $sql = "SELECT id, expo_push_token FROM users WHERE id='$currentChat' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        $targetToken = $row[1];

        if ($loggedUser < $currentChat) {
            $query = "M".$loggedUser."M".$currentChat."M";
        } else {
            $query = "M".$currentChat."M".$loggedUser."M";
        }

        $sql = "INSERT INTO messages (query, author, text, image, timestamp, unread)
        VALUES ('$query', '$loggedUser', '$messageText', '$messageImage', '$timestamp', '1')";

        if ($conn->query($sql) === TRUE) {
            $pushHeader = $senderName;
            if ($messageText != "") {
                $pushBody = $messageText;
            } else {
                $pushBody = "(изображение)";
            }

            if ($targetToken != "") {
                $ch = curl_init("https://exp.host/--/api/v2/push/send");
                $payload = json_encode(array("to" => $targetToken, "title" => $pushHeader, "body" => $pushBody));
                curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
                curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
                $result = curl_exec($ch);
                curl_close($ch);
            }

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