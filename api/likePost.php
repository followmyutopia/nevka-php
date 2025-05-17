<?php
/**
 * Nevka PHP API v1.7
 * 2025 Avicus Delacroix
 * 
 * Likes/unlikes the post on the wall.
 * Accepts: token, postId (multipart/form-data)
 * Returns: result, error? (json)
 */

if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {
    date_default_timezone_set("Europe/Moscow");

    $token = $post = $likes = $likesPHPArray = "";

    function validate($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    if (isset($_POST['token']) && isset($_POST['postId'])) {
        $token = validate($_POST['token']);
        $post = validate($_POST['postId']);

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

            // get the post likes

            $sql = "SELECT likes FROM walls WHERE id='$post' LIMIT 1";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $row = $result->fetch_row();
                $likes = $row[0];
                $likesPHPArray = json_decode($likes);

                if (in_array($loggedUser, $likesPHPArray)) {
                    // remove like
                    $key = array_search($loggedUser, $likesPHPArray);
                    unset($likesPHPArray[$key]);
                } else {
                    // like post
                    $likesPHPArray[] = $loggedUser;
                }

                $updatedLikes = json_encode($likesPHPArray, JSON_NUMERIC_CHECK);

                $sql = "UPDATE walls SET likes='$updatedLikes' WHERE id='$post'";
                if ($conn->query($sql) === TRUE) {
                    echo "{\"result\":\"success\"}";
                } else {
                    echo "{\"result\":\"failure\",\"error\":\"Ошибка записи в БД\"}";
                }
            } else {
                echo "{\"result\":\"failure\",\"error\":\"Пост не существует\"}";
            }
        } else {
            echo "{\"result\":\"failure\",\"error\":\"wrong_token\"}";
        }

        $conn->close();
    } else {
        echo "{\"result\":\"failure\",\"error\":\"Параметры token, postId обязательны\"}";
    }
}
?>