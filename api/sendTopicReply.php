<?php
/**
 * Nevka PHP API v1.4
 * 2025 Avicus Delacroix
 * 
 * Sends a message to the specified chat.
 * Accepts: token, topic, text (multipart/form-data)
 * Returns: result, error? (json)
 */

if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {
    date_default_timezone_set("Europe/Moscow");

    $token = $topic = $messageText = $header = $timestamp = "";

    function validate($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    if (isset($_POST['token']) && isset($_POST['topic']) && isset($_POST['text'])) {
        $token = validate($_POST['token']);
        $topic = validate($_POST['topic']);
        $messageText = validate($_POST['text']);

        $timestamp = time();

        include '../credentials.php';

        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
        }

        // update online

        $sql = "UPDATE users SET timestamp='$timestamp' WHERE token='$token'";
        $conn->query($sql);

        // get ID by token and record it to loggedUser

        $sql = "SELECT id FROM users WHERE token='$token' LIMIT 1";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_row();
            $loggedUser = $row[0];
        } else {
            $loggedUser = "";
            echo "{\"result\":\"failure\",\"error\":\"wrong_token\"}";
        }

        if ($loggedUser != "") {
            // get the header

            $sql = "SELECT topic, header FROM discussions WHERE id='$topic' LIMIT 1";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $row = $result->fetch_row();
                if ($row[1]) {
                    $header = $row[0];
                } else {
                    echo "{\"result\":\"failure\",\"error\":\"Такой темы не существует\"}";
                }
            } else {
                echo "{\"result\":\"failure\",\"error\":\"Такой темы не существует\"}";
            }

            $sql = "INSERT INTO discussions (topic, author, message, time, header, activity)
                VALUES ('$header', '$loggedUser', '$messageText', '$timestamp', '0', $timestamp)";

            if ($conn->query($sql) === TRUE) {
                $sqlUpdateHeader = "UPDATE discussions SET activity='$timestamp' WHERE id='$topic'";
                if ($conn->query($sqlUpdateHeader) === TRUE) {
                    echo "{\"result\":\"success\"}";
                } else {
                    echo "{\"result\":\"failure\",\"error\":\"Невозможно обновить активность обсуждения\"}";
                }
            } else {
                echo "{\"result\":\"failure\",\"error\":\"Ошибка записи в БД\"}";
            }
        }

        $conn->close();
    } else {
        echo "{\"result\":\"failure\",\"error\":\"Параметры token, topic, text обязательны\"}";
    }
}
?>