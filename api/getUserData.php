<?php
if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {

    date_default_timezone_set("Europe/Moscow");
    $id = "";

    function validate($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    if (isset($_POST['id']) && isset($_POST['token'])) {

        $token   = validate($_POST['token']);
        $userId  = validate($_POST['id']);
        $timestamp = time();

        include '../credentials.php';
        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
            exit;
        }

        // escape inputs
        $tokenEsc  = $conn->real_escape_string($token);
        $userIdEsc = $conn->real_escape_string($userId);
        $timestampEsc = $conn->real_escape_string($timestamp);

        $sql = "UPDATE users SET timestamp='$timestampEsc' WHERE token='$tokenEsc'";
        if ($conn->query($sql) === TRUE) {

            $sql = "SELECT firstname, lastname, status, city, bio, timestamp, avatar, id, expo_push_token, login
                    FROM users
                    WHERE id='$userIdEsc'
                    LIMIT 1";

            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {

                $row = $result->fetch_row();

                $formattedTime = date("d.m.Y в h:i a", $row[5]);
                $lastOnline = date_create_from_format("d.m.Y в h:i a", $formattedTime);
                $now = new DateTime();

                $difference = ($lastOnline->diff($now)->days * 1440)
                    + ($lastOnline->diff($now)->h * 60)
                    + $lastOnline->diff($now)->i;

                if ($difference <= 5) {
                    $online = "online";
                } else if ($difference < 60) {
                    $online = $lastOnline->diff($now)->i . " минут назад";
                } else {
                    $online = $formattedTime;
                }

                $voxUsername = str_replace("@", "-", $row[9]);

                echo "{\"result\": \"success\", \"id\": \"" . $row[7] . "\", \"firstname\": \"" . $row[0] . "\", \"lastname\": \"" . $row[1] . "\", \"status\": \"" . $row[2] . "\", \"city\": \"" . $row[3] . "\", \"bio\": \"" . $row[4] . "\", \"timestamp\": \"" . $row[5] . "\", \"avatar\": \"" . $row[6] . "\", \"online\": \"" . $online . "\", \"push_token\": \"" . $row[8] . "\", \"vox_username\": \"" . $voxUsername . "\"}";
            } else {
                echo "{\"result\":\"failure\",\"error\":\"Пользователь не найден\"}";
            }
        } else {
            echo "{\"result\":\"failure\",\"error\":\"wrong_token\"}";
        }

        $conn->close();
    } else {
        echo "{\"result\":\"failure\",\"error\":\"ID обязателен\"}";
    }
}
