<?php
/**
 * Nevka Project v1.6 API
 * 2025 Avicus Delacroix
 * 
 * Receives the wall for the specified user.
 * Accepts: id (multipart/form-data)
 * Returns: result, wall?[], error? (json)
 * wall?[]: {"id": "", "text": "", "image": "", "timestamp": "", "formattedTime": ""}
 * The last element of the array is always empty. Remove it from the result by JavaScript pop() function.
 */

date_default_timezone_set("Europe/Moscow");
$query = "";

function validate($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if (isset($_POST['id'])) {
    $userId = validate($_POST['id']);

    include '../credentials.php';
    include 'prepareText.php';

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
    }

    $sql = "SELECT id, text, image, timestamp FROM walls WHERE user='$userId' ORDER BY id DESC LIMIT 100";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "{\"result\": \"success\", \"wall\": [";

        while($row = $result->fetch_assoc()) {
            $formattedTime = date("d.m.Y в h:i a", $row['timestamp']);

            $processedText = prepareText($row['text']);
            
            echo "{\"id\": \"".$row['id']."\", \"text\": \"".$processedText."\", \"image\": \"".$row['image']."\", \"timestamp\": \"".$row['timestamp']."\", \"formattedTime\": \"".$formattedTime."\"}, ";
        }

        echo "{\"id\": \"\", \"text\": \"\", \"image\": \"\", \"timestamp\": \"\", \"formattedTime\": \"\"}]}";
    } else {
        echo "{\"result\":\"empty\",\"error\":\"Записей на стене нет\"}";
    }

    $conn->close();
} else {
    echo "{\"result\":\"failure\",\"error\":\"ID обязателен\"}";
}
?>