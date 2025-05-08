<?php
/**
 * Nevka Project v1.7 API
 * 2025 Avicus Delacroix
 * 
 * Gets all active chats for the specified user.
 * Accepts: token (multipart/form-data)
 * Returns: result, chats?[], error? (json)
 * chats?[]: {"id": "", "firstname": "", "lastname": "", "lastText": "", "isUnread": "", "avatar": "", "online": ""}
 * The last element of the array is always empty. Remove it from the result by JavaScript pop() function.
 */

date_default_timezone_set("Europe/Moscow");
include '../lib/ImageResize.php';
use \Gumlet\ImageResize;

$token = "";

function validate($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if (isset($_POST['token'])) {
    $token = validate($_POST['token']);

    include '../credentials.php';
    include 'prepareText.php';

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
    }

    // get ID by token and record it to checkedUser

    $sql = "SELECT id FROM users WHERE token='$token' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        $checkedUser = $row[0];
    } else {
        echo "{\"result\":\"failure\",\"error\":\"token_not_valid\"}";
    }

    // get latest messages for each interlocutor

    $signature = "M".$checkedUser."M";

    $sql = "SELECT query, MAX(id) FROM messages WHERE query LIKE '%$signature%' GROUP BY query ORDER BY MAX(id) DESC LIMIT 75";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "{\"result\": \"success\", \"chats\": [";

        while($row = $result->fetch_assoc()) {
            $interlocutor = str_replace($signature, "", $row["query"]);
            $interlocutor = str_replace("M", "", $interlocutor);
            $lastTextId = $row['MAX(id)'];

            $getLastText = "SELECT text, author, unread FROM messages WHERE id='$lastTextId' LIMIT 1";
            $lastTextResult = $conn->query($getLastText);
            $lastTextRow = $lastTextResult->fetch_row();
            
            $lastText = $lastTextRow[0];
            $lastTextAuthor = $lastTextRow[1];
            $isUnread = $lastTextRow[2];

            // get the interlocutor data
            $getName = "SELECT id, avatar, firstname, lastname, timestamp FROM users WHERE id='$interlocutor' LIMIT 500";
            $entryResult = $conn->query($getName);
            $secondRow = $entryResult->fetch_row();

            // compatibility with the v1.4 database format
            $formattedTime = date("d.m.Y в h:i a", $secondRow[4]);
            $online = ""; $now = new DateTime();
            $lastOnline = date_create_from_format("d.m.Y в h:i a", $formattedTime);
            $difference = ($lastOnline->diff($now)->days * 1440) + ($lastOnline->diff($now)->h * 60) + $lastOnline->diff($now)->i;
            if ($difference <= 5) { $online = "online"; }
            else if ($difference < 60) { $online = $lastOnline->diff($now)->i." минут"; }
            else if ($difference < 1440) { $online = date("h:i a", $secondRow[4]); }
            else { $online = date("d.m.Y", $secondRow[4]); }

            $image = new ImageResize($secondRow[1]);
            $image->quality_jpg = 90;
            $image->crop(100, 100);
            $blob = 'data:image/jpeg;base64,'.base64_encode($image);

            $processedText = prepareText($lastText);

            echo "{\"id\": \"".$secondRow[0]."\", \"firstname\": \"".$secondRow[2]."\", \"lastname\": \"".$secondRow[3]."\", \"lastText\": \"".$processedText."\", \"isUnread\": \"".$isUnread."\", \"avatar\": \"".$blob."\", \"online\": \"".$online."\", \"lastTextAuthor\": \"".$lastTextAuthor."\"}, ";
        }
        echo "{\"id\": \"\", \"firstname\": \"\", \"lastname\": \"\", \"lastText\": \"\", \"isUnread\": \"\", \"avatar\": \"\", \"online\": \"\", \"lastTextAuthor\": \"\"}]}";
    } else {
        echo "{\"result\":\"empty\",\"error\":\"Нет чатов\"}";
    }

    $conn->close();
} else {
    echo "{\"result\":\"failure\",\"error\":\"Token обязателен\"}";
}
?>