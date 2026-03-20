<?php
if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {

    date_default_timezone_set("Europe/Moscow");

    if (isset($_POST['token']) && isset($_POST['topic']) && isset($_POST['text'])) {

        include '../credentials.php';
        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
            exit;
        }

        $token = $conn->real_escape_string($_POST['token']);
        $topic = $conn->real_escape_string($_POST['topic']);
        $messageText = $conn->real_escape_string($_POST['text']);
        $timestamp = time();

        // update activity timestamp
        $conn->query("UPDATE users SET timestamp='$timestamp' WHERE token='$token'");

        // get logged user
        $result = $conn->query("SELECT id FROM users WHERE token='$token' LIMIT 1");
        if ($result->num_rows === 0) {
            echo "{\"result\":\"failure\",\"error\":\"wrong_token\"}";
            exit;
        }
        $row = $result->fetch_row();
        $loggedUser = $row[0];

        // get discussion header
        $result = $conn->query("SELECT topic, header FROM discussions WHERE id='$topic' LIMIT 1");
        if ($result->num_rows === 0) {
            echo "{\"result\":\"failure\",\"error\":\"Такой темы не существует\"}";
            exit;
        }

        $row = $result->fetch_row();
        if (!$row[1]) {
            echo "{\"result\":\"failure\",\"error\":\"Такой темы не существует\"}";
            exit;
        }

        $header = $conn->real_escape_string($row[0]);

        // insert message
        $sql = "INSERT INTO discussions (topic, author, message, time, header, activity)
                VALUES ('$header', '$loggedUser', '$messageText', '$timestamp', '0', '$timestamp')";

        if ($conn->query($sql) === TRUE) {

            $conn->query("UPDATE discussions SET activity='$timestamp' WHERE id='$topic'");
            echo "{\"result\":\"success\"}";
        } else {
            echo "{\"result\":\"failure\",\"error\":\"Ошибка записи в БД\"}";
        }

        $conn->close();
    } else {
        echo "{\"result\":\"failure\",\"error\":\"Параметры token, topic, text обязательны\"}";
    }
}
