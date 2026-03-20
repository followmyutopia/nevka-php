<?php
if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {

    date_default_timezone_set("Europe/Moscow");
    $token = $post = $likes = $likesPHPArray = "";

    function validate($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    if (isset($_POST['token']) && isset($_POST['postId'])) {

        $token = validate($_POST['token']);
        $post  = validate($_POST['postId']);

        include '../credentials.php';
        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
            exit;
        }

        // экранируем SQL‑переменные
        $tokenEsc = $conn->real_escape_string($token);
        $postEsc  = $conn->real_escape_string($post);

        $sql = "SELECT id FROM users WHERE token='$tokenEsc' LIMIT 1";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {

            $row = $result->fetch_row();
            $loggedUser = $row[0];

            $sql = "SELECT likes FROM walls WHERE id='$postEsc' LIMIT 1";
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {

                $row = $result->fetch_row();
                $likes = $row[0];
                $likesPHPArray = json_decode($likes, true);

                if (in_array($loggedUser, $likesPHPArray)) {
                    $key = array_search($loggedUser, $likesPHPArray);
                    unset($likesPHPArray[$key]);
                    $likesPHPArray = array_values($likesPHPArray);
                } else {
                    $likesPHPArray[] = $loggedUser;
                }

                $updatedLikes = json_encode($likesPHPArray, JSON_NUMERIC_CHECK);
                $updatedLikesEsc = $conn->real_escape_string($updatedLikes);

                $sql = "UPDATE walls SET likes='$updatedLikesEsc' WHERE id='$postEsc'";

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
