<?php require '../lib/PHPMailer.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if ($_POST['api-key'] == "qonxod-Kicmad-4puvhy") {
    date_default_timezone_set("Europe/Moscow");
    $email = $code = "";
    function validate($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    function sendMail($address, $code)
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.yandex.ru';
            $mail->SMTPAuth = true;
            $mail->Username = 'avideltest';
            $mail->Password = '***REMOVED***';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            $mail->setFrom('avideltest@yandex.ru', 'Nevka Network');
            $mail->addAddress($address);
            $mail->isHTML(true);
            $mail->Subject = $code . " — код подтверждения";
            $mail->Body = '<!DOCTYPE html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"></head><body style="background-color: rgb(13, 28, 36); color: rgb(163, 182, 194); font-family: Verdana, sans-serif; margin: 3%"><h1>Невка</h1><p>Благодарю за регистрацию в моей небольшой социальной сети Невка!<br><br>Для подтверждения регистрации, введите следующий код подтверждения в приложении: <b>' . $code . '</b><br><br>Желаю прекрасного дня!</p></body></html>';
            $mail->AltBody = 'Ваш код подтверждения для регистрации на сайте nevka.site: ' . $code . '.';
            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    if (isset($_POST['email'])) {
        $email = validate($_POST['email']);
        $code = mt_rand(10000, 99999);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            include '../credentials.php';
            $conn = new mysqli($servername, $username, $password, $dbname);
            if ($conn->connect_error) {
                echo "{\"result\":\"failure\",\"error\":\"Нет доступа к базе данных\"}";
            }
            $sql = "SELECT login FROM users WHERE login='$email' LIMIT 1";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                echo "{\"result\":\"failure\",\"error\":\"Пользователь с такой почтой уже существует\"}";
            } else {
                $sent = sendMail($email, $code);
                if ($sent) {
                    echo "{\"result\":\"success\", \"code\": \"" . $code . "\"}";
                } else {
                    echo "{\"result\":\"failure\",\"error\":\"Ошибка почтового сервиса.\"}";
                }
            }
            $conn->close();
        } else {
            echo "{\"result\":\"failure\",\"error\":\"Не похоже на электронную почту\"}";
        }
    } else {
        echo "{\"result\":\"failure\",\"error\":\"Параметр email обязателен\"}";
    }
}
