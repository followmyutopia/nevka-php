<?php
error_reporting(0);

include '../lib/ImageResize.php';

use \Gumlet\ImageResize;

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle !== '' && strpos($haystack, $needle) !== false;
    }
}

if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {

    date_default_timezone_set("Europe/Moscow");

    $token = "";

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

    if (isset($_POST['token'])) {

        $token = validate($_POST['token']);

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

        // escape token for SQL
        $token = $conn->real_escape_string($token);

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

        $signature = "M" . $checkedUser . "M";

        // escape signature for LIKE query
        $signatureEscaped = $conn->real_escape_string($signature);

        $sql = "SELECT query, MAX(id) FROM messages WHERE query LIKE '%$signatureEscaped%' GROUP BY query ORDER BY MAX(id) DESC LIMIT 75";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            echo "{\"result\": \"success\", \"chats\": [";

            while ($row = $result->fetch_assoc()) {

                $interlocutor = str_replace($signature, "", $row["query"]);
                $interlocutor = str_replace("M", "", $interlocutor);
                $interlocutor = $conn->real_escape_string($interlocutor);

                $lastTextId = $conn->real_escape_string($row['MAX(id)']);

                $getLastText = "SELECT text, author, unread FROM messages WHERE id='$lastTextId' LIMIT 1";
                $lastTextResult = $conn->query($getLastText);
                $lastTextRow = $lastTextResult->fetch_row();

                $lastText = $conn->real_escape_string($lastTextRow[0]);
                $lastTextAuthor = $conn->real_escape_string($lastTextRow[1]);
                $isUnread = $conn->real_escape_string($lastTextRow[2]);

                $getName = "SELECT id, avatar, firstname, lastname, timestamp FROM users WHERE id='$interlocutor' LIMIT 500";
                $entryResult = $conn->query($getName);
                $secondRow = $entryResult->fetch_row();

                $secondRow[0] = $conn->real_escape_string($secondRow[0]);
                $secondRow[1] = $conn->real_escape_string($secondRow[1]);
                $secondRow[2] = $conn->real_escape_string($secondRow[2]);
                $secondRow[3] = $conn->real_escape_string($secondRow[3]);
                $secondRow[4] = $conn->real_escape_string($secondRow[4]);

                $formattedTime = date("d.m.Y в h:i a", $secondRow[4]);
                $online = "";
                $now = new DateTime();
                $lastOnline = date_create_from_format("d.m.Y в h:i a", $formattedTime);
                $difference = ($lastOnline->diff($now)->days * 1440) + ($lastOnline->diff($now)->h * 60) + $lastOnline->diff($now)->i;

                if ($difference <= 5) {
                    $online = "online";
                } else if ($difference < 60) {
                    $online = $lastOnline->diff($now)->i . " минут";
                } else if ($difference < 1440) {
                    $online = date("g:i a", $secondRow[4]);
                } else {
                    $online = date("j.m", $secondRow[4]);
                }

                $image = new ImageResize($secondRow[1]);
                $image->quality_jpg = 90;
                $image->crop(100, 100);
                $blob = 'data:image/jpeg;base64,' . base64_encode($image);

                $processedText = prepareText($lastText);

                if ($safe == "true") {
                    if (!str_contains($secondRow[2], $trigger1) || !str_contains($secondRow[3], $trigger1) || !str_contains($secondRow[2], $trigger2) || !str_contains($secondRow[3], $trigger2) || !str_contains($secondRow[2], $trigger3) || !str_contains($secondRow[3], $trigger3) || !str_contains($secondRow[2], $trigger4) || !str_contains($secondRow[3], $trigger4)) {
                        echo "{\"id\": \"" . $secondRow[0] . "\", \"firstname\": \"" . $secondRow[2] . "\", \"lastname\": \"" . $secondRow[3] . "\", \"lastText\": \"" . $processedText . "\", \"isUnread\": \"" . $isUnread . "\", \"avatar\": \"" . $blob . "\", \"online\": \"" . $online . "\", \"lastTextAuthor\": \"" . $lastTextAuthor . "\"}, ";
                    }
                } else {
                    echo "{\"id\": \"" . $secondRow[0] . "\", \"firstname\": \"" . $secondRow[2] . "\", \"lastname\": \"" . $secondRow[3] . "\", \"lastText\": \"" . $processedText . "\", \"isUnread\": \"" . $isUnread . "\", \"avatar\": \"" . $blob . "\", \"online\": \"" . $online . "\", \"lastTextAuthor\": \"" . $lastTextAuthor . "\"}, ";
                }
            }

            echo "{\"id\": \"\", \"firstname\": \"\", \"lastname\": \"\", \"lastText\": \"\", \"isUnread\": \"\", \"avatar\": \"\", \"online\": \"\", \"lastTextAuthor\": \"\"}]}";
        } else {
            echo "{\"result\":\"empty\",\"error\":\"Нет чатов\"}";
        }

        $conn->close();
    } else {
        echo "{\"result\":\"failure\",\"error\":\"Token обязателен\"}";
    }
}
