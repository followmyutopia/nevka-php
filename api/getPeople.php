<?php
/**
 * Nevka PHP API v1.0
 * 2025 Avicus Delacroix
 * 
 * Receives the list of all people with last onlines and avatars.
 * Accepts: id (multipart/form-data)
 * Returns: result, people?[], error? (json)
 * people?[]: {"id": "", "firstname": "", "lastname": "", "timestamp": "", "online": "", "avatar": ""}
 * The last element of the array is always empty. Remove it from the result by JavaScript pop() function.
 */

include '../lib/ImageResize.php';
use \Gumlet\ImageResize;

if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {
    date_default_timezone_set("Europe/Moscow");

    $query = "";

    function validate($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    if (isset($_POST['query'])) {
        $query = validate($_POST['query']);

        include '../credentials.php';

        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
        }

        $sql = "SELECT id, avatar, firstname, lastname, timestamp, search FROM users
        WHERE (firstname LIKE '%$query%' OR lastname LIKE '%$query%' OR search LIKE '%$query%') AND (NOT is_banned OR is_banned IS NULL)
        ORDER BY timestamp DESC LIMIT 500";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo "{\"result\": \"success\", \"people\": [";

            while($row = $result->fetch_assoc()) {

                $formattedTime = date("d.m.Y в h:i a", $row['timestamp']);
                $lastOnline = date_create_from_format("d.m.Y в h:i a", $formattedTime);
                $now = new DateTime(); $online = "";
                $difference = ($lastOnline->diff($now)->days * 1440) + ($lastOnline->diff($now)->h * 60) + $lastOnline->diff($now)->i;

                if ($difference <= 5) { $online = "online"; }
                else if ($difference < 60) { $online = $lastOnline->diff($now)->i." минут назад"; }
                else { $online = date("d.m в h:i a", $row['timestamp']); }

                $image = new ImageResize($row['avatar']);
                $image->quality_jpg = 90;
                $image->crop(100, 100);
                $blob = 'data:image/jpeg;base64,'.base64_encode($image);

                echo "{\"id\": \"".$row['id']."\", \"firstname\": \"".$row['firstname']."\", \"lastname\": \"".$row['lastname']."\", \"timestamp\": \"".$row['timestamp']."\", \"online\": \"".$online."\", \"avatar\": \"".$blob."\"}, ";
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
?>