<?php
/**
 * Nevka PHP API v1.0
 * 2025 Avicus Delacroix
 * 
 * Checks the token and returns the logged user ID. If the token is invalid, returns 0.
 * Accepts: token (multipart/form-data)
 * Returns: result (json)
 */

if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {
    $token = "";

    function validate($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    if (isset($_POST['token'])) {
        $token = validate($_POST['token']);

        include '../credentials.php';

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
            echo "{\"result\":\"0\"}";
        }

        $conn->close();
    } else {
        echo "{\"result\":\"0\"}";
    }
}
?>