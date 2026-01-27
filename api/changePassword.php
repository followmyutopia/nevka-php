<?php if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {
    date_default_timezone_set("Europe/Moscow");
    $token = $pushToken = "";
    function validate($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    if (isset($_POST['token']) && isset($_POST['id']) && isset($_POST['old_password']) && isset($_POST['new_password'])) {
        $token = validate($_POST['token']);
        $userId = validate($_POST['id']);
        $oldPassword = validate($_POST['old_password']);
        $newPassword = validate($_POST['new_password']);
        $oldPasswordHash = sha1($oldPassword);
        $newPasswordHash = sha1($newPassword);
        $newToken = "NevkaToken2.2[User" . $userId . "-" . $newPasswordHash . "]";
        include '../credentials.php';
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
        }
        $sql = "SELECT password FROM users WHERE token='$token' LIMIT 1";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_row();
            if ($oldPasswordHash == $row[0]) {
                $security = "UPDATE users SET password='$newPasswordHash', token='$newToken' WHERE token='$token'";
                if ($conn->query($security) === TRUE) {
                    echo "{\"result\":\"success\", \"newToken\": \"" . $newToken . "\"}";
                } else {
                    echo "{\"result\":\"failure\",\"error\":\"Ошибка записи в БД\"}";
                }
            } else {
                echo "{\"result\":\"failure\",\"error\":\"Неправильный пароль\"}";
            }
        } else {
            echo "{\"result\":\"failure\",\"error\":\"wrong_token\"}";
        }
        $conn->close();
    } else {
        echo "{\"result\":\"failure\",\"error\":\"Параметры token, id, old_password, new_password обязательны\"}";
    }
}
