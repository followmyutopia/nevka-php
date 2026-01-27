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
        }
        $sql = "SELECT id, topic, author, message, time FROM discussions WHERE header='1' AND topic LIKE '%$query%' ORDER BY activity DESC LIMIT 500";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            echo "{\"result\": \"success\", \"discussions\": [";
            while ($row = $result->fetch_assoc()) {
                $formattedTime = date("d.m.Y в h:i a", $row['time']);
                $id = $row['author'];
                $name = "";
                $nameSql = "SELECT firstname, lastname FROM users WHERE id='$id' LIMIT 1";
                $nameResult = $conn->query($nameSql);
                if ($nameResult->num_rows > 0) {
                    $nameRow = $nameResult->fetch_row();
                    $name = $nameRow[0] . " " . $nameRow[1];
                } else {
                    $name = "Неизвестный пользователь";
                }
                echo "{\"id\": \"" . $row['id'] . "\", \"topic\": \"" . $row['topic'] . "\", \"name\": \"" . $name . "\", \"message\": \"" . $row['message'] . "\", \"timestamp\": \"" . $row['time'] . "\", \"formattedTime\": \"" . $formattedTime . "\"}, ";
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
