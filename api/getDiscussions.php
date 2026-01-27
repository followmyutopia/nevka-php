<?php if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {

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

        include '../credentials.php';
        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
            exit;
        }

        // escape query for SQL
        $query = $conn->real_escape_string($query);

        $sql = "SELECT id, topic, author, message, time 
                FROM discussions 
                WHERE header='1' AND topic LIKE '%$query%' 
                ORDER BY activity DESC LIMIT 500";

        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            echo "{\"result\": \"success\", \"discussions\": [";

            while ($row = $result->fetch_assoc()) {

                $formattedTime = date("d.m.Y в h:i a", $row['time']);

                $id = $conn->real_escape_string($row['author']); // escape author ID
                $name = "";

                $nameSql = "SELECT firstname, lastname FROM users WHERE id='$id' LIMIT 1";
                $nameResult = $conn->query($nameSql);

                if ($nameResult && $nameResult->num_rows > 0) {
                    $nameRow = $nameResult->fetch_row();
                    $firstname = $conn->real_escape_string($nameRow[0]);
                    $lastname  = $conn->real_escape_string($nameRow[1]);
                    $name = $firstname . " " . $lastname;
                } else {
                    $name = "Неизвестный пользователь";
                }

                // escape discussion fields before output
                $discId    = $conn->real_escape_string($row['id']);
                $topicEsc  = $conn->real_escape_string($row['topic']);
                $message   = $conn->real_escape_string($row['message']);
                $timestamp = $conn->real_escape_string($row['time']);

                echo "{\"id\": \"$discId\", \"topic\": \"$topicEsc\", \"name\": \"$name\", \"message\": \"$message\", \"timestamp\": \"$timestamp\", \"formattedTime\": \"$formattedTime\"}, ";
            }

            echo "{\"id\": \"\", \"topic\": \"\", \"name\": \"\", \"message\": \"\", \"timestamp\": \"\", \"formattedTime\": \"\"}]}";

        } else {
            echo "{\"result\":\"empty\",\"error\":\"Нет обсуждений\"}";
        }

        $conn->close();

    } else {
        echo "{\"result\":\"failure\",\"error\":\"Query обязателен\"}";
    }
}
