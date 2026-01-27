<?php
if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {
    date_default_timezone_set("Europe/Moscow");

    if (isset($_POST['email'], $_POST['password'])) {

        include '../credentials.php';
        include '../defaultImage.php';
        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            echo '{"result":"failure","error":"Нет доступа к базе данных"}';
            exit;
        }

        // Sanitize inputs
        $login = $conn->real_escape_string(trim($_POST['email']));
        $rawPassword = trim($_POST['password']);
        $passwordHash = sha1($rawPassword); // consider switching to password_hash()
        $newToken = "NevkaToken2.2[" . $login . "-" . $passwordHash . "]";

        // Check if user exists
        $sqlCheck = "SELECT id FROM users WHERE login='$login' LIMIT 1";
        $resultCheck = $conn->query($sqlCheck);
        if ($resultCheck->num_rows > 0) {
            echo '{"result":"failure","error":"Пользователь с такой почтой уже существует"}';
            exit;
        }

        // Insert user into DB
        $sqlInsert = "INSERT INTO users (login, password, token, avatar) 
                      VALUES ('$login', '$passwordHash', '$newToken', '$defaultImage')";
        if ($conn->query($sqlInsert) === TRUE) {

            // Prepare Voximplant user
            $voxUsername = str_replace("@", "-", $login);
            $params = [
                'api_key' => 'PASTE_VOX_API_KEY_HERE',
                'application_id' => '11329284',
                'user_display_name' => 'Новый пользователь',
                'user_name' => $voxUsername,
                'user_password' => 'PASTE_VOX_PASSWORD_HERE',
                'account_id' => '3831061'
            ];

            $url = "https://api.voximplant.com/platform_api/AddUser/?" . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
            $voxResult = curl_exec($ch);
            curl_close($ch);

            echo '{"result":"success"}';

        } else {
            echo '{"result":"failure","error":"Ошибка записи в БД"}';
        }

        $conn->close();

    } else {
        echo '{"result":"failure","error":"Параметры email, password обязательны"}';
    }
}
