<?php
/**
 * Nevka PHP API v1.4.1
 * 2025 Avicus Delacroix
 * 
 * Deletes a post from the wall.
 * Accepts: token, postId (multipart/form-data)
 * Returns: result, error? (json)
 */

if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {
    date_default_timezone_set("Europe/Moscow");

    $token = $postId = $loggedUser = "";

    function validate($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    if (isset($_POST['token']) && isset($_POST['postId'])) {
        $token = validate($_POST['token']);
        $postId = validate($_POST['postId']);

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

        $sql = "SELECT user FROM walls WHERE id='$postId' LIMIT 1";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_row();
            if ($loggedUser == $row[0]) {
                // approved sorting by id, user checked
                $del = "DELETE FROM walls WHERE id='$postId'";
                if ($conn->query($del) === TRUE) {
                    echo "{\"result\":\"success\"}";
                } else {
                    echo "{\"result\":\"failure\",\"error\":\"Ошибка записи в БД\"}";
                }
            } else {
                echo "{\"result\":\"failure\",\"error\":\"Недостаточно прав\"}";
            }
        } else {
            echo "{\"result\":\"failure\",\"error\":\"Запись удалена или не существует\"}";
        }

        $conn->close();
    } else {
        echo "{\"result\":\"failure\",\"error\":\"Параметры token, postId обязательны\"}";
    }
}
?>