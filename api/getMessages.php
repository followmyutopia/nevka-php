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
        $chat  = validate($_POST['chat']);

        include '../credentials.php';
        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
            exit;
        }

        // escape token and chat
        $token = $conn->real_escape_string($token);
        $chat  = $conn->real_escape_string($chat);

        $query = $checkedUser = "";

        // get user ID
        $sql = "SELECT id FROM users WHERE token='$token' LIMIT 1";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_row();
            $checkedUser = $conn->real_escape_string($row[0]);
        } else {
            echo "{\"result\":\"failure\",\"error\":\"wrong_token\"}";
            $conn->close();
            exit;
        }

        // build chat query key
        if ($checkedUser < $chat) {
            $query = "M" . $checkedUser . "M" . $chat . "M";
        } else {
            $query = "M" . $chat . "M" . $checkedUser . "M";
        }

        $query = $conn->real_escape_string($query);

        $sql = "SELECT * FROM 
                (SELECT id, query, author, text, image, timestamp, unread, reply 
                 FROM messages 
                 WHERE query='$query' 
                 ORDER BY id DESC 
                 LIMIT 100) X 
                ORDER BY id ASC;";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            echo "{\"result\": \"success\", \"messages\": [";

            while ($row = $result->fetch_assoc()) {

                $interlocutor = str_replace("M" . $checkedUser . "M", "", $row["query"]);
                $interlocutor = str_replace("M", "", $interlocutor);
                $interlocutor = $conn->real_escape_string($interlocutor);

                $formattedTime = date("d.m в h:i a", $row['timestamp']);
                $processedText = prepareText($row['text']);
                $rowId = $conn->real_escape_string($row['id']);
                $rowText = $conn->real_escape_string($row['text']);
                $rowImage = $conn->real_escape_string($row['image']);
                $rowTimestamp = $conn->real_escape_string($row['timestamp']);
                $rowUnread = $conn->real_escape_string($row['unread']);
                $rowAuthor = $conn->real_escape_string($row['author']);
                $rowReply = $conn->real_escape_string($row['reply']);

                $reply = "";
                if ($rowReply) {
                    $replyId = $rowReply;
                    $getReplyText = "SELECT author, text FROM messages WHERE id='$replyId' LIMIT 1";
                    $textResult = $conn->query($getReplyText);
                    $textRow = $textResult->fetch_row();

                    $replyAuthor = $conn->real_escape_string($textRow[0]);
                    $replyText   = $conn->real_escape_string($textRow[1]);

                    if ($replyText == "") {
                        $preview = "(изображение)";
                    } else {
                        $preview = $replyText;
                    }

                    if ($checkedUser == $replyAuthor) {
                        $reply = "Вы: " . $preview;
                    } else {
                        $reply = $preview;
                    }
                }

                if ($chat == $interlocutor) {
                    if ($rowAuthor == $checkedUser) {
                        echo "{\"id\": \"$rowId\", \"me\": \"true\", \"text\": \"$processedText\", \"image\": \"$rowImage\", \"timestamp\": \"$rowTimestamp\", \"formattedTime\": \"$formattedTime\", \"isUnread\": \"$rowUnread\", \"reply\": \"$reply\"}, ";
                    } else if ($rowAuthor == $chat) {
                        echo "{\"id\": \"$rowId\", \"me\": \"false\", \"text\": \"$processedText\", \"image\": \"$rowImage\", \"timestamp\": \"$rowTimestamp\", \"formattedTime\": \"$formattedTime\", \"isUnread\": \"$rowUnread\", \"reply\": \"$reply\"}, ";
                    } else {
                        continue;
                    }
                } else {
                    continue;
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
