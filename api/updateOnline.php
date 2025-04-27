<?php
/**
 * Nevka Project v1.6 API
 * 2025 Avicus Delacroix
 * 
 * Updates online for the specified user.
 * Accepts: token (multipart/form-data)
 * Returns: result, error? (json)
 */

date_default_timezone_set("Europe/Moscow");
$token = "";

function validate($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if (isset($_POST['token'])) {
    $token = validate($_POST['token']);
    $timestamp = time();

    include '../credentials.php';

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
    }

    $sql = "UPDATE users SET timestamp='$timestamp' WHERE token='$token'";

    if ($conn->query($sql) === TRUE) {
        echo "{\"result\":\"success\"}";
    } else {
        echo "{\"result\":\"failure\",\"error\":\"Невозможно обновить онлайн\"}";
    }

    $conn->close();
} else {
    echo "{\"result\":\"failure\",\"error\":\"Token обязателен\"}";
}
?>