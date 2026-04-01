<?php
if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {

    date_default_timezone_set("Europe/Moscow");

    if (isset($_POST['token'], $_POST['firstname'], $_POST['lastname'], $_POST['city'], $_POST['bio'])) {

        include '../credentials.php';
        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            echo '{"result":"failure","error":"Нет доступа к базе данных"}';
            exit;
        }

        // Escape inputs for SQL safety
        $token = $conn->real_escape_string($_POST['token']);
        $newFirstName = $conn->real_escape_string($_POST['firstname']);
        $newLastName = $conn->real_escape_string($_POST['lastname']);
        $newCity = $conn->real_escape_string($_POST['city']);
        $newBio = $conn->real_escape_string($_POST['bio']);
        $searchQuery = $newFirstName . ' ' . $newLastName;

        if ($newFirstName === "" || $newLastName === "") {
            echo '{"result":"failure","error":"Firstname и lastname не могут быть пустыми"}';
            exit;
        }

        // Get login for Voximplant
        $getNameSql = "SELECT login FROM users WHERE token='$token' LIMIT 1";
        $getNameResult = $conn->query($getNameSql);

        if ($getNameResult->num_rows === 0) {
            echo '{"result":"failure","error":"wrong_token"}';
            exit;
        }

        $voxUsername = str_replace("@", "-", $getNameResult->fetch_row()[0]);

        // Update user in DB
        $sql = "UPDATE users 
                SET firstname='$newFirstName', lastname='$newLastName', city='$newCity', bio='$newBio', search='$searchQuery' 
                WHERE token='$token'";

        if ($conn->query($sql) === TRUE) {
            echo '{"result":"success"}';

            // Update Voximplant user info
            $params = array(
                'api_key' => $voxApiKey,
                'user_name' => $voxUsername,
                'application_id' => '11329284',
                'user_display_name' => $newFirstName . ' ' . $newLastName,
                'account_id' => '3831061'
            );

            $url = "https://api.voximplant.com/platform_api/SetUserInfo/?" . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
            curl_exec($ch);
            curl_close($ch);
        } else {
            echo '{"result":"failure","error":"Ошибка обновления данных"}';
        }

        $conn->close();
    } else {
        echo '{"result":"failure","error":"Параметры token, firstname, lastname, city, bio обязательны"}';
    }
}
