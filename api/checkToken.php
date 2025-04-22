<?php
/**
 * Nevka Project v1.6 API
 * 2025 Avicus Delacroix
 * 
 * Checks the token and returns the logged user ID. If the token is invalid, returns 0.
 * Accepts: token (multipart/form-data)
 * Returns: result (json)
 */

$token = "";

function validate($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if (isset($_POST['token'])) {
    $token = validate($_POST['token']);

    $servername = "localhost";
    $username = "nevka";
    $password = "DB_PASSWORD_HERE";
    $dbname = "nevka";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        echo "{\"result\":\"0\"}";
    }

    $sql = "SELECT id FROM users WHERE token='$token' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        echo "{\"result\":\"".$row[0]."\"}";
    } else {
        logout();
    }

    $conn->close();
} else {
    echo "{\"result\":\"0\"}";
}
?>