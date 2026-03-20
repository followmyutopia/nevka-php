<?php
if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {

    date_default_timezone_set("Europe/Moscow");
    $token = $pushToken = "";

    function validate($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    if (isset($_POST['email']) && isset($_POST['password'])) {

        $login    = validate($_POST['email']);
        $password = validate($_POST['password']);

        $passwordHash = sha1($password);
        $newToken = "NevkaToken2.2[" . $login . "-" . $passwordHash . "]";

        include '../credentials.php';
        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
            exit;
        }

        // SQL escaping
        $loginEsc        = $conn->real_escape_string($login);
        $passwordHashEsc = $conn->real_escape_string($passwordHash);
        $newTokenEsc     = $conn->real_escape_string($newToken);

        $sql = "UPDATE users
                SET password='$passwordHashEsc', token='$newTokenEsc'
                WHERE login='$loginEsc'";

        if ($conn->query($sql) === TRUE) {
            echo "{\"result\":\"success\"}";
        } else {
            echo "{\"result\":\"failure\",\"error\":\"Ошибка записи в БД\"}";
        }

        $conn->close();
    } else {
        echo "{\"result\":\"failure\",\"error\":\"Параметры email, password обязательны\"}";
    }
}
