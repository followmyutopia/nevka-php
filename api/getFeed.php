<?php include '../lib/ImageResize.php';
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
        include '../credentials.php';
        include 'prepareText.php';
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
        }
        $sql = "SELECT id, text, image, timestamp, likes, user FROM walls ORDER BY id DESC LIMIT " . ($page * 10);
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            echo "{\"result\": \"success\", \"feed\": [";
            while ($row = $result->fetch_assoc()) {
                $userId = $row['user'];
                $getUserSql = "SELECT firstname, lastname, avatar FROM users WHERE id='$userId' LIMIT 1";
                $getUserResult = $conn->query($getUserSql);
                $getUserRow = $getUserResult->fetch_row();
                $username = $getUserRow[0] . " " . $getUserRow[1];

                $avatar = new ImageResize($getUserRow[2]);
                $avatar->quality_jpg = 90;
                $avatar->crop(64, 64);
                $avatarBlob = 'data:image/jpeg;base64,' . base64_encode($avatar);

                $formattedTime = date("d.m.Y в h:i a", $row['timestamp']);
                $processedText = prepareText($row['text']);
                echo "{\"id\": \"" . $row['id'] . "\", \"text\": \"" . $processedText . "\", \"image\": \"" . $row['image'] . "\", \"timestamp\": \"" . $row['timestamp'] . "\", \"formattedTime\": \"" . $formattedTime . "\", \"likes\": \"" . $row['likes'] . "\", \"author\": \"" . $row['user'] . "\", \"authorName\": \"".$username."\", \"authorAvatar\": \"".$avatarBlob."\"}, ";
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
