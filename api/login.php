<?php
if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {

    $login = $rawPassword = "";

    function validate($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    if (isset($_POST['login']) && isset($_POST['password'])) {

        $login = validate($_POST['login']);
        $rawPassword = validate($_POST['password']);
        $hashedPassword = sha1($rawPassword);

        include '../credentials.php';
        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
            exit;
        }

        // экранируем login
        $loginEsc = $conn->real_escape_string($login);

        $sql = "SELECT id, login, password, token, is_banned
                FROM users
                WHERE login='$loginEsc'
                LIMIT 1";

        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {

            $row = $result->fetch_row();

            if ($hashedPassword == $row[2] && !$row[4]) {
                echo "{\"result\":\"success\",\"token\":\"" . $row[3] . "\", \"userId\": \"" . $row[0] . "\"}";
            } else if ($row[4]) {
                echo "{\"result\":\"failure\",\"error\":\"Пользователь " . $login . " заблокирован модератором\"}";
            } else {
                echo "{\"result\":\"failure\",\"error\":\"Неправильный пароль\"}";
            }

        } else {
            echo "{\"result\":\"failure\",\"error\":\"Пользователь " . $login . " не найден\"}";
        }

        $conn->close();

    } else {
        echo "{\"result\":\"failure\",\"error\":\"Логин и пароль обязательны\"}";
    }
}
