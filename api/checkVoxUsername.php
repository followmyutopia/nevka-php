<?php if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {

    date_default_timezone_set("Europe/Moscow");

    $id = "";

    function validate($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    if (isset($_POST['vox_username'])) {

        // validate first (existing logic)
        $voxUsername = validate($_POST['vox_username']);
        $nevkaLogin = str_replace("-", "@", $voxUsername);

        include '../credentials.php';
        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
            exit;
        }

        // escape for SQL
        $nevkaLogin = $conn->real_escape_string($nevkaLogin);

        $sql = "SELECT id FROM users WHERE login='$nevkaLogin' LIMIT 1";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_row();
            echo "{\"result\": \"success\", \"id\": \"" . $row[0] . "\"}";
        } else {
            echo "{\"result\":\"failure\",\"error\":\"Пользователь не найден\"}";
        }

        $conn->close();
    } else {
        echo "{\"result\":\"failure\",\"error\":\"ID обязателен\"}";
    }
}
