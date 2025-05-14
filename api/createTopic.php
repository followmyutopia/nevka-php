<?php
/**
 * Nevka PHP API v1.4
 * 2025 Avicus Delacroix
 * 
 * Creates a topic.
 * Accepts: token, title, message (multipart/form-data)
 * Returns: result, error? (json)
 */

if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {
    date_default_timezone_set("Europe/Moscow");

    $token = $topic = $message = $timestamp = "";

    function validate($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    if (isset($_POST['token']) && isset($_POST['title']) && isset($_POST['message'])) {
        $token = validate($_POST['token']);
        $topic = validate($_POST['title']);
        $message = validate($_POST['message']);
        $timestamp = time();

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

        // create a topic

        $sql = "INSERT INTO discussions (topic, author, message, time, header, activity)
            VALUES ('$topic', '$loggedUser', '$message', '$timestamp', '1', $timestamp)";

        if ($conn->query($sql) === TRUE) {
            echo "{\"result\":\"success\"}";
        } else {
            echo "{\"result\":\"failure\",\"error\":\"Ошибка записи в БД\"}";
        }

        $conn->close();
    } else {
        echo "{\"result\":\"failure\",\"error\":\"Параметры token, title, message обязательны\"}";
    }
}
?>