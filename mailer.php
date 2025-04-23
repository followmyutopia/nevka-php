<?php
require 'lib/PHPMailer.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendMail($address, $code) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.yandex.ru';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'avideltest';
        $mail->Password   = '***REMOVED***';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
    
        $mail->setFrom('avideltest@yandex.ru', 'Nevka Network');
        $mail->addAddress($address);

        $mail->isHTML(true);
        $mail->Subject = $code." — код подтверждения";
        $mail->Body    = '<!DOCTYPE html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"></head><body style="background-color: rgb(20, 41, 48); color: rgba(255, 255, 255, 0.9); font-family: Verdana, sans-serif; padding: 2%"><h1>Невка</h1><p>Благодарю за регистрацию в моей небольшой социальной сети Невка!<br><br>Для подтверждения регистрации, введите следующий код подтверждения на сайте <a href="https://nevka.site" style="color: white;">nevka.site</a>: <b>'.$code.'</b><br><br>Формы сброса пароля на сайте пока что нет, так что храните пароль надёжно. Если всё же забудете пароль, отправьте запрос на восстановление ответом на это письмо.<br><br>Желаю прекрасного дня!</p></body></html>';
        $mail->AltBody = 'Ваш код подтверждения для регистрации на сайте nevka.site: '.$code.'.';
    
        $mail->send();
        return true;
    } catch (Exception $e) {
        // $mail->ErrorInfo
        return false;
    }
}
?>