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
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $login = validate($_POST['email']);
        $password = validate($_POST['password']);
        $passwordHash = sha1($password);
        $newToken = "NevkaToken2.2[" . $login . "-" . $passwordHash . "]";
        include '../credentials.php';
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
        }
        $sql = "SELECT id FROM users WHERE login='$login' LIMIT 1";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            echo "{\"result\":\"failure\",\"error\":\"Пользователь с такой почтой уже существует\"}";
        } else {
            include '../defaultImage.php';
            $sql = "INSERT INTO users (login, password, token, avatar) VALUES ('$login', '$passwordHash', '$newToken', '$defaultImage')";
            if ($conn->query($sql) === TRUE) {
                echo "{\"result\":\"success\"}";

                $voxUsername = str_replace("@", "-", $login);
                $params = array(
                    'api_key' => 'PASTE_VOX_API_KEY_HERE',
                    'application_id' => '11329284',
                    'user_display_name' => 'Новый пользователь',
                    'user_name' => $voxUsername,
                    'user_password' => 'PASTE_VOX_PASSWORD_HERE',
                    'account_id' => '3831061'
                );

                $url = "https://api.voximplant.com/platform_api/AddUser/?" . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
                $result = curl_exec($ch);
                curl_close($ch);
            } else {
                echo "{\"result\":\"failure\",\"error\":\"Ошибка записи в БД\"}";
            }
        }
        $conn->close();
    } else {
        echo "{\"result\":\"failure\",\"error\":\"Параметры email, password обязательны\"}";
    }
}
