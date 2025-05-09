<?php
/**
 * Nevka PHP API v1.0
 * 2025 Avicus Delacroix
 * 
 * Gets all active chats for the specified user.
 * Accepts: token, chat (multipart/form-data)
 * Returns: result, messages?[], error? (json)
 * messages?[]: {"id": "", "me": "", "text": "", "image": "", "timestamp": "", "formattedTime": "", "isUnread": ""}
 * The last element of the array is always empty. Remove it from the result by JavaScript pop() function.
 */

if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {
    date_default_timezone_set("Europe/Moscow");
    $token = $chat = "";

    function validate($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    if (isset($_POST['token']) && isset($_POST['chat'])) {
        $token = validate($_POST['token']);
        $chat = validate($_POST['chat']);

        include '../credentials.php';
        include 'prepareText.php';

        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
        }

        $query = $checkedUser = "";

        // get ID by token and record it to checkedUser

        $sql = "SELECT id FROM users WHERE token='$token' LIMIT 1";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_row();
            $checkedUser = $row[0];
        } else {
            echo "{\"result\":\"failure\",\"error\":\"token_not_valid\"}";
        }

        // form query

        if ($checkedUser < $chat) {
            $query = "M".$checkedUser."M".$chat."M";
        } else {
            $query = "M".$chat."M".$checkedUser."M";
        }

        $sql = "SELECT * FROM (SELECT id, query, author, text, image, timestamp, unread FROM messages WHERE query='$query' ORDER BY id DESC LIMIT 100) X ORDER BY id ASC;";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo "{\"result\": \"success\", \"messages\": [";

            while($row = $result->fetch_assoc()) {
                $interlocutor = str_replace("M".$checkedUser."M", "", $row["query"]);
                $interlocutor = str_replace("M", "", $interlocutor);
                // compatibility with the v1.4 database format
                $formattedTime = date("d.m в h:i a", $row['timestamp']);

                $processedText = prepareText($row['text']);

                if ($chat == $interlocutor) {
                    if ($row["author"] == $checkedUser) { // my message
                        echo "{\"id\": \"".$row['id']."\", \"me\": \"true\", \"text\": \"".$processedText."\", \"image\": \"".$row['image']."\", \"timestamp\": \"".$row['timestamp']."\", \"formattedTime\": \"".$formattedTime."\", \"isUnread\": \"".$row['unread']."\"}, ";
                    } else if ($row["author"] == $chat) { // interlocutor message
                        echo "{\"id\": \"".$row['id']."\", \"me\": \"false\", \"text\": \"".$processedText."\", \"image\": \"".$row['image']."\", \"timestamp\": \"".$row['timestamp']."\", \"formattedTime\": \"".$formattedTime."\", \"isUnread\": \"".$row['unread']."\"}, ";
                    } else { return; }
                } else { return; }
            }

            // set requested as read
            $readRequest = "UPDATE messages SET unread='0' WHERE query='$query' AND author='$interlocutor'";
            $conn->query($readRequest);

            echo "{\"id\": \"\", \"me\": \"\", \"text\": \"\", \"image\": \"\", \"timestamp\": \"\", \"formattedTime\": \"\", \"isUnread\": \"\"}]}";
        } else {
            echo "{\"result\":\"empty\",\"error\":\"Нет сообщений\"}";
        }

        $conn->close();
    } else {
        echo "{\"result\":\"failure\",\"error\":\"Token и chat обязательны\"}";
    }
}
?>