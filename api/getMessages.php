<?php
error_reporting(0);

if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {
    date_default_timezone_set("Europe/Moscow");
    $token = $chat = "";
    function validate($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    function prepareText($verifiedText)
    {
        $processedText = str_replace("<br>", "", $verifiedText);
        $processedText = str_replace("<ul>", "", $processedText);
        $processedText = str_replace("<li>", "  •  ", $processedText);
        $processedText = str_replace("</li>", "", $processedText);
        $processedText = str_replace("</ul>", "", $processedText);
        $processedText = str_replace("&quot;", '\"', $processedText);
        $processedText = str_replace("&lt;", '<', $processedText);
        $processedText = str_replace("&gt;", '>', $processedText);
        $processedText = str_replace("&#039;", "'", $processedText);
        $processedText = str_replace("&amp;", "&", $processedText);
        $processedText = str_replace("	", "    ", $processedText);
        return $processedText;
    }
    if (isset($_POST['token']) && isset($_POST['chat'])) {
        $token = validate($_POST['token']);
        $chat = validate($_POST['chat']);
        include '../credentials.php';

        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
        }
        $query = $checkedUser = "";
        $sql = "SELECT id FROM users WHERE token='$token' LIMIT 1";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_row();
            $checkedUser = $row[0];
        } else {
            echo "{\"result\":\"failure\",\"error\":\"wrong_token\"}";
        }
        if ($checkedUser < $chat) {
            $query = "M" . $checkedUser . "M" . $chat . "M";
        } else {
            $query = "M" . $chat . "M" . $checkedUser . "M";
        }
        $sql = "SELECT * FROM (SELECT id, query, author, text, image, timestamp, unread, reply FROM messages WHERE query='$query' ORDER BY id DESC LIMIT 100) X ORDER BY id ASC;";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            echo "{\"result\": \"success\", \"messages\": [";
            while ($row = $result->fetch_assoc()) {
                $interlocutor = str_replace("M" . $checkedUser . "M", "", $row["query"]);
                $interlocutor = str_replace("M", "", $interlocutor);
                $formattedTime = date("d.m в h:i a", $row['timestamp']);
                $processedText = prepareText($row['text']);
                if ($row['reply']) {
                    $replyId = $row['reply'];
                    $getReplyText = "SELECT author, text FROM messages WHERE id ='$replyId' LIMIT 1";
                    $textResult = $conn->query($getReplyText);
                    $textRow = $textResult->fetch_row();
                    if ($textRow[1] == "") {
                        $preview = "(изображение)";
                    } else {
                        $preview = $textRow[1];
                    }
                    if ($checkedUser == $textRow[0]) {
                        $reply = "Вы: " . $preview;
                    } else {
                        $reply = $preview;
                    }
                } else {
                    $reply = "";
                }
                if ($chat == $interlocutor) {
                    if ($row["author"] == $checkedUser) {
                        echo "{\"id\": \"" . $row['id'] . "\", \"me\": \"true\", \"text\": \"" . $processedText . "\", \"image\": \"" . $row['image'] . "\", \"timestamp\": \"" . $row['timestamp'] . "\", \"formattedTime\": \"" . $formattedTime . "\", \"isUnread\": \"" . $row['unread'] . "\", \"reply\": \"" . $reply . "\"}, ";
                    } else if ($row["author"] == $chat) {
                        echo "{\"id\": \"" . $row['id'] . "\", \"me\": \"false\", \"text\": \"" . $processedText . "\", \"image\": \"" . $row['image'] . "\", \"timestamp\": \"" . $row['timestamp'] . "\", \"formattedTime\": \"" . $formattedTime . "\", \"isUnread\": \"" . $row['unread'] . "\", \"reply\": \"" . $reply . "\"}, ";
                    } else {
                        return;
                    }
                } else {
                    return;
                }
            }
            $readRequest = "UPDATE messages SET unread='0' WHERE query='$query' AND author='$interlocutor'";
            $conn->query($readRequest);
            echo "{\"id\": \"\", \"me\": \"\", \"text\": \"\", \"image\": \"\", \"timestamp\": \"\", \"formattedTime\": \"\", \"isUnread\": \"\", \"reply\": \"\"}]}";
        } else {
            echo "{\"result\":\"empty\",\"error\":\"Нет сообщений\"}";
        }
        $conn->close();
    } else {
        echo "{\"result\":\"failure\",\"error\":\"Token и chat обязательны\"}";
    }
}
