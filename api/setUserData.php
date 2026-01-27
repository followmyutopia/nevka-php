<?php if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {
    date_default_timezone_set("Europe/Moscow");
    $token = "";
    function validate($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    if (isset($_POST['token']) && isset($_POST['firstname']) && isset($_POST['lastname']) && isset($_POST['city']) && isset($_POST['bio'])) {
        $token = validate($_POST['token']);
        $newFirstName = validate($_POST['firstname']);
        $newLastName = validate($_POST['lastname']);
        $newCity = validate($_POST['city']);
        $newBio = validate($_POST['bio']);
        $query = $newFirstName . " " . $newLastName;
        include '../credentials.php';
        if ($newFirstName != "" && $newLastName != "") {
            $conn = new mysqli($servername, $username, $password, $dbname);
            if ($conn->connect_error) {
                echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
            }

            $getNameSql = "SELECT login FROM users WHERE token='$token'";
            $getNameResult = $conn->query($getNameSql);
            $getNameRow = $getNameResult->fetch_row();
            $voxUsername = str_replace("@", "-", $getNameRow[0]);

            $sql = "UPDATE users SET firstname='$newFirstName', lastname='$newLastName', city='$newCity', bio='$newBio', search='$query' WHERE token='$token'";
            if ($conn->query($sql) === TRUE) {
                echo "{\"result\":\"success\"}";

                $params = array(
                    'api_key' => 'PASTE_VOX_API_KEY_HERE',
                    'user_name' => $voxUsername,
                    'application_id' => '11329284',
                    'user_display_name' => $newFirstName . ' ' . $newLastName,
                    'account_id' => '3831061'
                );

                $url = "https://api.voximplant.com/platform_api/SetUserInfo/?" . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
                $requestResult = curl_exec($ch);
                curl_close($ch);
            } else {
                echo "{\"result\":\"failure\",\"error\":\"wrong_token\"}";
            }
            $conn->close();
        }
    } else {
        echo "{\"result\":\"failure\",\"error\":\"Параметры token, firstname, lastname, city, bio обязательны\"}";
    }
}
