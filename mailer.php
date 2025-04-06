<?php
require 'mailerLibrary.php';
require 'mailerLibrarySMTP.php';
require 'mailerLibraryException.php';

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
    
        $mail->setFrom('avideltest@yandex.ru', 'Nevka is best Network');
        $mail->addAddress($address);

        $mail->isHTML(true);
        $mail->Subject = $code." — код подтверждения";
        $mail->Body    = 'Благодарю за регистрацию в моей небольшой социальной сети Невка!<br><br>Для подтверждения регистрации, введите следующий код подтверждения на сайте <a href="https://nevka.is-best.net">nevka.is-best.net</a>: <b>'.$code.'</b><br><br>Формы сброса пароля на сайте пока что нет, так что храните пароль надёжно. Если всё же забудете пароль, отправьте запрос на восстановление ответом на это письмо.<br><br>Желаю прекрасного дня!';
        $mail->AltBody = 'Ваш код подтверждения для регистрации на сайте nevka.is-best.net: '.$code.'.';
    
        $mail->send();
        return true;
    } catch (Exception $e) {
        // $mail->ErrorInfo
        return false;
    }
}
?>