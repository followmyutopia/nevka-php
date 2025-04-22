<?php
/**
 * Nevka Project v1.5 API
 * 2025 Avicus Delacroix
 * 
 * Receives the data for the specified user.
 * Accepts: id (multipart/form-data)
 * Returns: result, avatar?, firstname?, lastname?, status?, city?, bio?, timestamp?, error? (json)
 */

$id = "";

function validate($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if (isset($_POST['id'])) {
    $userId = validate($_POST['id']);

    $servername = "localhost";
    $username = "nevka";
    $password = "DB_PASSWORD_HERE";
    $dbname = "nevka";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
    }

    $sql = "SELECT firstname, lastname, status, city, bio, timestamp, avatar FROM users WHERE id='$userId' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        echo "{\"result\": \"success\", \"firstname\": \"".$row[0]."\", \"lastname\": \"".$row[1]."\", \"status\": \"".$row[2]."\", \"city\": \"".$row[3]."\", \"bio\": \"".$row[4]."\", \"timestamp\": \"".$row[5]."\", \"avatar\": \"".$row[6]."\"}";
    } else {
        echo "{\"result\":\"failure\",\"error\":\"Пользователь не найден\"}";
    }

    $conn->close();
} else {
    echo "{\"result\":\"failure\",\"error\":\"ID обязателен\"}";
}
?>