<?php
/**
 * Nevka PHP API v1.0
 * 2025 Avicus Delacroix
 * 
 * Sets user data for the currently logged user.
 * Accepts: token, firstname, lastname, city, bio (multipart/form-data)
 * Returns: result, error? (json)
 */

if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {
    date_default_timezone_set("Europe/Moscow");

    $token = "";

    function validate($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    if (isset($_POST['token']) && isset($_POST['firstname']) && isset($_POST['lastname']) && isset($_POST['city']) && isset($_POST['bio'])) {
        $token = validate($_POST['token']);
        $newFirstName = validate($_POST['firstname']);
        $newLastName = validate($_POST['lastname']);
        $newCity = validate($_POST['city']);
        $newBio = validate($_POST['bio']);
        $query = $newFirstName." ".$newLastName;

        include '../credentials.php';

        if ($newFirstName != "" && $newLastName != "") {
            $conn = new mysqli($servername, $username, $password, $dbname);
            if ($conn->connect_error) {
                echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
            }

            $sql = "UPDATE users SET firstname='$newFirstName', lastname='$newLastName', city='$newCity', bio='$newBio', search='$query' WHERE token='$token'";

            if ($conn->query($sql) === TRUE) {
                echo "{\"result\":\"success\"}";
            } else {
                echo "{\"result\":\"failure\",\"error\":\"Ошибка записи в БД\"}";
            }

            $conn->close();
        }
    } else {
        echo "{\"result\":\"failure\",\"error\":\"Параметры token, firstname, lastname, city, bio обязательны\"}";
    }
}
?>