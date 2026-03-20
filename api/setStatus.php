<?php
if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {

    date_default_timezone_set("Europe/Moscow");

    if (isset($_POST['token']) && isset($_POST['status'])) {

        include '../credentials.php';
        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
            exit;
        }

        // Escape inputs for SQL safety
        $token = $conn->real_escape_string($_POST['token']);
        $status = $conn->real_escape_string($_POST['status']);

        $sql = "UPDATE users SET status='$status' WHERE token='$token'";

        if ($conn->query($sql) === TRUE) {
            echo "{\"result\":\"success\"}";
        } else {
            echo "{\"result\":\"failure\",\"error\":\"wrong_token\"}";
        }

        $conn->close();
    } else {
        echo "{\"result\":\"failure\",\"error\":\"Параметры token, status обязательны\"}";
    }
}
