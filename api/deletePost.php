<?php if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {

    date_default_timezone_set("Europe/Moscow");

    $token = $postId = $loggedUser = "";

    function validate($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    if (isset($_POST['token']) && isset($_POST['postId'])) {

        include '../credentials.php';
        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
            exit;
        }

        // validate first
        $token  = validate($_POST['token']);
        $postId = validate($_POST['postId']);

        // escape for SQL
        $token  = $conn->real_escape_string($token);
        $postId = $conn->real_escape_string($postId);

        // get logged user
        $sql = "SELECT id FROM users WHERE token='$token' LIMIT 1";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_row();
            $loggedUser = $conn->real_escape_string($row[0]);
        } else {
            echo "{\"result\":\"failure\",\"error\":\"wrong_token\"}";
            $conn->close();
            exit;
        }

        // check post owner
        $sql = "SELECT user FROM walls WHERE id='$postId' LIMIT 1";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_row();

            if ($loggedUser === $row[0]) {

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
