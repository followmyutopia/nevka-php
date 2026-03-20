<?php if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {

    $token = "";

    function validate($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    if (isset($_POST['token'])) {

        include '../credentials.php';
        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            echo "{\"result\":\"0\"}";
            exit;
        }

        // validate first (existing logic)
        $token = validate($_POST['token']);

        // escape for SQL
        $token = $conn->real_escape_string($token);

        $sql = "SELECT id FROM users WHERE token='$token' LIMIT 1";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_row();
            echo "{\"result\":\"" . $row[0] . "\"}";
        } else {
            echo "{\"result\":\"0\"}";
        }

        $conn->close();
    } else {
        echo "{\"result\":\"0\"}";
    }
}
