<?php include '../lib/ImageResize.php';
use \Gumlet\ImageResize;

// fix for PHP version less than 8
function str_starts_with_FIX($haystack, $needle) {
    return substr($haystack, 0, strlen($needle)) === $needle;
}

function str_ends_with_FIX($haystack, $needle) {
    return substr($haystack, -strlen($needle)) === $needle;
}

if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {
    date_default_timezone_set("Europe/Moscow");
    $token = $currentChat = $messageText = $messageImage = $timestamp = $query = $targetToken = $reply = "";
    function validate($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    if (isset($_POST['token']) && isset($_POST['chat']) && isset($_POST['text'])) {
        $token = validate($_POST['token']);
        $currentChat = validate($_POST['chat']);
        $messageText = validate($_POST['text']);
        $timestamp = time();
        if (isset($_POST['image']) && $_POST['image'] != "") {
            $image = new ImageResize($_POST['image']);
            $image->quality_jpg = 90;
            $image->resizeToLongSide(800);
            $messageImage = 'data:image/jpeg;base64,' . base64_encode($image->getImageAsString(IMAGETYPE_JPEG));
        } else {
            $messageImage = "";
        }
        if (isset($_POST['reply'])) {
            $reply = validate($_POST['reply']);
        } else {
            $reply = "";
        }
        include '../credentials.php';
        include 'prepareText.php';
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
        }
        $sql = "SELECT id, firstname, lastname FROM users WHERE token='$token' LIMIT 1";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_row();
            $loggedUser = $row[0];
            $senderName = $row[1] . " " . $row[2];
        } else {
            $loggedUser = "";
            echo "{\"result\":\"failure\",\"error\":\"wrong_token\"}";
        }
        if ($loggedUser != "") {
            $sql = "UPDATE users SET timestamp='$timestamp' WHERE token='$token'";
            $conn->query($sql);
            $sql = "SELECT id, expo_push_token FROM users WHERE id='$currentChat' LIMIT 1";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $row = $result->fetch_row();
                $targetToken = $row[1];
                if ($loggedUser < $currentChat) {
                    $query = "M" . $loggedUser . "M" . $currentChat . "M";
                } else {
                    $query = "M" . $currentChat . "M" . $loggedUser . "M";
                }
                if ($reply != "") {
                    $sql = "INSERT INTO messages (query, author, text, image, timestamp, unread, reply) VALUES ('$query', '$loggedUser', '$messageText', '$messageImage', '$timestamp', '1', '$reply')";
                } else {
                    $sql = "INSERT INTO messages (query, author, text, image, timestamp, unread) VALUES ('$query', '$loggedUser', '$messageText', '$messageImage', '$timestamp', '1')";
                }
                if ($conn->query($sql) === TRUE) {
                    $pushHeader = $senderName;
                    if ($messageText != "") {
                        $processedText = prepareText($messageText);
                        $pushBody = $processedText;
                        if (str_starts_with_FIX($pushBody, "NE1[") && str_ends_with_FIX($pushBody, "]")) {
                            $pushBody = "[Зашифрованное сообщение]";
                        }
                    } else {
                        $pushBody = "(изображение)";
                    }
                    $pushData = "{\"url\": \"/(tabs)/messages/chat?id=" . $loggedUser . "&name=" . $senderName . "\"}";
                    if ($targetToken != "" && $targetToken != NULL) {
                        $ch = curl_init("https://exp.host/--/api/v2/push/send");
                        $payload = json_encode(array("to" => $targetToken, "title" => $pushHeader, "body" => $pushBody, "data" => $pushData));
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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
        }
        $conn->close();
    } else {
        echo "{\"result\":\"failure\",\"error\":\"Параметры token, chat, text, image обязательны\"}";
    }
}
