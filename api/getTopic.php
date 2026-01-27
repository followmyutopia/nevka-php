<?php
if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {

    date_default_timezone_set("Europe/Moscow");
    $discussion = "";

    function validate($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    if (isset($_POST['id'])) {

        $discussion = validate($_POST['id']);

        include '../credentials.php';
        include 'prepareText.php';

        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
            exit;
        }

        // escape input
        $discussion = $conn->real_escape_string($discussion);

        $sql = "SELECT topic, author, message, time, header FROM discussions WHERE id='$discussion' LIMIT 1";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_row();
            if ($row[4]) {
                $header = $row;
            } else {
                echo "{\"result\":\"failure\",\"error\":\"Такой темы не существует\"}";
                exit;
            }
        } else {
            echo "{\"result\":\"failure\",\"error\":\"Такой темы не существует\"}";
            exit;
        }

        // values reused in SQL → escape them too
        $topic     = $conn->real_escape_string($header[0]);
        $authorId  = $conn->real_escape_string($header[1]);
        $message   = $conn->real_escape_string($header[2]);
        $time      = $conn->real_escape_string($header[3]);

        $nameSql = "SELECT firstname, lastname FROM users WHERE id='$authorId' LIMIT 1";
        $nameResult = $conn->query($nameSql);
        $nameRow = $nameResult->fetch_row();
        $authorName = $nameRow[0] . " " . $nameRow[1];

        $formattedTime = date("d.m.Y в h:i a", $header[3]);

        $sql = "SELECT author, message, time, header FROM discussions WHERE topic='$topic' ORDER BY id DESC LIMIT 500";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {

            echo "{\"result\": \"success\", \"header\": \"" . $header[0] . "\", \"author\": \"" . $authorName . "\", \"timestamp\": \"" . $header[3] . "\", \"formattedTime\": \"" . $formattedTime . "\", \"topicMessage\": \"" . $header[2] . "\", \"messages\": [";

            while ($row = $result->fetch_assoc()) {

                if ($row["header"] == false) {

                    $msgAuthorId = $conn->real_escape_string($row['author']);
                    $msgTime     = $conn->real_escape_string($row['time']);

                    $nameSql = "SELECT firstname, lastname FROM users WHERE id='$msgAuthorId' LIMIT 1";
                    $nameResult = $conn->query($nameSql);
                    $nameRow = $nameResult->fetch_row();
                    $messageAuthor = $nameRow[0] . " " . $nameRow[1];

                    $formattedTime = date("d.m.Y в h:i a", $row['time']);
                    $processedText = prepareText($row['message']);

                    echo "{\"name\": \"" . $messageAuthor . "\", \"text\": \"" . $processedText . "\", \"timestamp\": \"" . $msgTime . "\", \"formattedTime\": \"" . $formattedTime . "\"}, ";
                }
            }

            echo "{\"name\": \"\", \"text\": \"\", \"timestamp\": \"\", \"formattedTime\": \"\"}]}";

        } else {
            echo "{\"result\":\"empty\", \"header\": \"" . $header[0] . "\", \"author\": \"" . $authorName . "\", \"timestamp\": \"" . $header[3] . "\", \"formattedTime\": \"" . $formattedTime . "\", \"topicMessage\": \"" . $header[2] . "\", \"error\":\"Нет ответов\"}";
        }

        $conn->close();

    } else {
        echo "{\"result\":\"failure\",\"error\":\"ID обязателен\"}";
    }
}
