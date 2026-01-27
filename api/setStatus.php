<?php if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {
    date_default_timezone_set("Europe/Moscow");
    $token = $status = "";
    function validate($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    if (isset($_POST['token']) && isset($_POST['status'])) {
        $token = validate($_POST['token']);
        $status = validate($_POST['status']);
        include '../credentials.php';
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
        }
        $sql = "UPDATE users SET status='$status' WHERE token='$token'";
        if ($conn->query($sql) === TRUE) {
            echo "{\"result\":\"success\"}";
        } else {
            echo "{\"result\":\"failure\",\"error\":\"wrong_token\"}";
        }
        $conn->close();
    } else {
        echo "{\"result\":\"failure\",\"error\":\"Параметры token, status обязательны\"}";
    }
}
