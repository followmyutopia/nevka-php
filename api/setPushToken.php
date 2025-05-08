<?php
/**
 * Nevka Project v1.7 API
 * 2025 Avicus Delacroix
 * 
 * Sets the push token for the currently logged user.
 * Accepts: token, push_token (multipart/form-data)
 * Returns: result, error? (json)
 */

date_default_timezone_set("Europe/Moscow");

$token = $pushToken = "";

function validate($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if (isset($_POST['token']) && isset($_POST['push_token'])) {
    $token = validate($_POST['token']);
    $pushToken = validate($_POST['push_token']);

    include '../credentials.php';

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
    }

    $sql = "UPDATE users SET expo_push_token='$pushToken' WHERE token='$token'";

    if ($conn->query($sql) === TRUE) {
        echo "{\"result\":\"success\"}";
    } else {
        echo "{\"result\":\"failure\",\"error\":\"Ошибка записи в БД\"}";
    }

    $conn->close();
} else {
    echo "{\"result\":\"failure\",\"error\":\"Параметры token, push_token обязательны\"}";
}
?>