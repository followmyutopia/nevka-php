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

    if (isset($_POST['query'])) {

        $query = validate($_POST['query']);

        if (isset($_POST['safe'])) {
            $safe = validate($_POST['safe']);
            include 'safeFilter.php';
        } else {
            $safe = "";
        }

        include '../credentials.php';
        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
            exit;
        }

        // escape inputs
        $query = $conn->real_escape_string($query);
        $safe  = $conn->real_escape_string($safe);

        // escape safeFilter triggers if present
        if (isset($trigger1)) $trigger1 = $conn->real_escape_string($trigger1);
        if (isset($trigger2)) $trigger2 = $conn->real_escape_string($trigger2);
        if (isset($trigger3)) $trigger3 = $conn->real_escape_string($trigger3);
        if (isset($trigger4)) $trigger4 = $conn->real_escape_string($trigger4);

        if ($safe == "true") {
            $sql = "
                SELECT id, avatar, firstname, lastname, timestamp, search 
                FROM users 
                WHERE 
                    (firstname LIKE '%$query%' 
                    OR lastname LIKE '%$query%' 
                    OR search LIKE '%$query%')
                AND (NOT is_banned OR is_banned IS NULL)
                AND (NOT search LIKE '%$trigger1%')
                AND (NOT search LIKE '%$trigger2%')
                AND (NOT search LIKE '%$trigger3%')
                AND (NOT search LIKE '%$trigger4%')
                ORDER BY timestamp DESC 
                LIMIT 500
            ";
        } else {
            $sql = "
                SELECT id, avatar, firstname, lastname, timestamp, search 
                FROM users 
                WHERE 
                    (firstname LIKE '%$query%' 
                    OR lastname LIKE '%$query%' 
                    OR search LIKE '%$query%')
                AND (NOT is_banned OR is_banned IS NULL)
                ORDER BY timestamp DESC 
                LIMIT 500
            ";
        }

        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {

            echo "{\"result\": \"success\", \"people\": [";

            while ($row = $result->fetch_assoc()) {

                $formattedTime = date("d.m.Y в h:i a", $row['timestamp']);
                $lastOnline = date_create_from_format("d.m.Y в h:i a", $formattedTime);
                $now = new DateTime();

                $difference =
                    ($lastOnline->diff($now)->days * 1440) +
                    ($lastOnline->diff($now)->h * 60) +
                    $lastOnline->diff($now)->i;

                if ($difference <= 5) {
                    $online = "online";
                } else if ($difference < 60) {
                    $online = $lastOnline->diff($now)->i . " минут назад";
                } else {
                    $online = date("d.m в h:i a", $row['timestamp']);
                }

                // escape DB output used in JSON
                $id        = $conn->real_escape_string($row['id']);
                $firstname = $conn->real_escape_string($row['firstname']);
                $lastname  = $conn->real_escape_string($row['lastname']);
                $timestamp = $conn->real_escape_string($row['timestamp']);

                $image = new ImageResize($row['avatar']);
                $image->quality_jpg = 90;
                $image->crop(100, 100);
                $blob = 'data:image/jpeg;base64,' . base64_encode($image);

                echo "{\"id\": \"$id\", \"firstname\": \"$firstname\", \"lastname\": \"$lastname\", \"timestamp\": \"$timestamp\", \"online\": \"$online\", \"avatar\": \"$blob\"}, ";
            }

            echo "{\"id\": \"\", \"firstname\": \"\", \"lastname\": \"\", \"timestamp\": \"\", \"online\": \"\", \"avatar\": \"\"}]}";
        } else {
            echo "{\"result\":\"empty\",\"error\":\"Никого не найдено\"}";
        }

        $conn->close();
    } else {
        echo "{\"result\":\"failure\",\"error\":\"Query обязателен\"}";
    }
}
