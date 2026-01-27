<?php
if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {

    date_default_timezone_set("Europe/Moscow");

    if (isset($_POST['token']) && isset($_POST['push_token'])) {

        include '../credentials.php';
        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
            exit;
        }

        // Escape SQL inputs
        $token = $conn->real_escape_string($_POST['token']);
        $pushTokenRaw = $_POST['push_token'];

        if ($pushTokenRaw != "null") {
            $pushToken = $conn->real_escape_string($pushTokenRaw);
            $sql = "UPDATE users SET expo_push_token='$pushToken' WHERE token='$token'";
        } else {
            $sql = "UPDATE users SET expo_push_token=NULL WHERE token='$token'";
        }

        if ($conn->query($sql) === TRUE) {
            echo "{\"result\":\"success\"}";
        } else {
            echo "{\"result\":\"failure\",\"error\":\"wrong_token\"}";
        }

        $conn->close();

    } else {
        echo "{\"result\":\"failure\",\"error\":\"Параметры token, push_token обязательны\"}";
    }

}
