<?php
include '../lib/ImageResize.php';

use \Gumlet\ImageResize;

if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {

    date_default_timezone_set("Europe/Moscow");

    $query = "";

    function validate($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    if (isset($_POST['page'])) {

        $page = validate($_POST['page']);

        if (isset($_POST['query'])) {
            $query = validate($_POST['query']);
        } else {
            $query = "";
        }

        include '../credentials.php';
        include 'prepareText.php';

        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
            exit;
        }

        // escape variables for SQL
        $page  = $conn->real_escape_string($page);
        $query = $conn->real_escape_string($query);

        $limit = $page * 10;
        $sql = "SELECT id, text, image, timestamp, likes, user 
                FROM walls 
                WHERE text LIKE '%$query%' 
                ORDER BY id DESC 
                LIMIT $limit";

        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            echo "{\"result\": \"success\", \"feed\": [";

            while ($row = $result->fetch_assoc()) {

                $userId = $conn->real_escape_string($row['user']);

                $getUserSql = "SELECT firstname, lastname, avatar 
                               FROM users 
                               WHERE id='$userId' LIMIT 1";
                $getUserResult = $conn->query($getUserSql);
                $getUserRow = $getUserResult->fetch_row();

                $firstname = $conn->real_escape_string($getUserRow[0]);
                $lastname  = $conn->real_escape_string($getUserRow[1]);
                $username  = $firstname . " " . $lastname;
                $avatarPath = $conn->real_escape_string($getUserRow[2]);

                $avatar = new ImageResize($avatarPath);
                $avatar->quality_jpg = 90;
                $avatar->crop(64, 64);
                $avatarBlob = 'data:image/jpeg;base64,' . base64_encode($avatar);

                $discId    = $conn->real_escape_string($row['id']);
                $text      = $conn->real_escape_string($row['text']);
                $image     = $conn->real_escape_string($row['image']);
                $timestamp = $conn->real_escape_string($row['timestamp']);
                $likes     = $conn->real_escape_string($row['likes']);
                $author    = $conn->real_escape_string($row['user']);

                $formattedTime = date("d.m.Y в h:i a", $row['timestamp']);
                $processedText = prepareText($text);

                echo "{\"id\": \"$discId\", \"text\": \"$processedText\", \"image\": \"$image\", \"timestamp\": \"$timestamp\", \"formattedTime\": \"$formattedTime\", \"likes\": \"$likes\", \"author\": \"$author\", \"authorName\": \"$username\", \"authorAvatar\": \"$avatarBlob\"}, ";
            }

            echo "{\"id\": \"\", \"text\": \"\", \"image\": \"\", \"timestamp\": \"\", \"formattedTime\": \"\", \"likes\": \"\", \"author\": \"\", \"authorName\": \"\", \"authorAvatar\": \"\"}]}";
        } else {
            echo "{\"result\":\"empty\",\"error\":\"Лента пуста\"}";
        }

        $conn->close();
    } else {
        echo "{\"result\":\"failure\",\"error\":\"ID обязателен\"}";
    }
}
