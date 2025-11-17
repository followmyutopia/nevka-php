<?php
/**
 * Nevka Project v1.13
 * 2025 Avicus Delacroix
 */

include 'db.php';
include 'header.php';
include 'mailer.php';

if(isset($_COOKIE["user_token"])) { header("Refresh:0; url=profile.php"); }

$login = $password = $newLogin = $newPassword = $confirmPassword = $status = $email = $captcha = $emailSuccess = $loginClass = "";
$afterConfirmation = $signupClass = $resetClass = "hidden";

if(isset($_POST['authorize'])) {
    $login = validate($_POST["login"]);
    $password = validate($_POST["password"]);
    if (empty($login) && empty($password)) {
        $status = "Введите данные";
    } else if (empty($login)) {
        $status = "Логин обязателен";
    } else if (empty($password)) {
        $status = "Пароль обязателен";
    } else {
        $status = login($login,sha1($password));
    }
}

if(isset($_POST['confirmEmail'])) {
    $email = validate($_POST["newLogin"]);
    if (mb_strlen($email) > 63) {$status = "Максимум 63 символа";}
    else {
        $loginClass = "hidden";
        $signupClass = "";
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $allowEmail = allowEmail($email);
            if ($allowEmail) {
                $_SESSION['captcha'] = mt_rand(10000, 99999);
                $_SESSION['email'] = $email;
                $sent = sendRegistrationMail($email, $_SESSION['captcha']);
                if ($sent) {
                    $status = "Код выслан на почту!";
                    $emailSuccess = "hidden";
                    $afterConfirmation = "";
                } else {
                    $status = "Ошибка почтового сервиса. Код подтверждения: ".$_SESSION['captcha'];
                    $emailSuccess = "hidden";
                    $afterConfirmation = "";
                }
            } else { $status = "Пользователь с такой почтой уже существует!"; }
        } else { $status = "Не похоже на электронную почту"; }
    }
}

if(isset($_POST['confirmEmailForPassword'])) {
    $email = validate($_POST["newLogin"]);
    if (mb_strlen($email) > 63) {$status = "Максимум 63 символа";}
    else {
        $loginClass = "hidden";
        $resetClass = "";
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $allowEmail = allowEmail($email);
            if (!$allowEmail) {
                $_SESSION['captcha'] = mt_rand(10000, 99999);
                $_SESSION['email'] = $email;
                $sent = sendPasswordResetMail($email, $_SESSION['captcha']);
                if ($sent) {
                    $status = "Код выслан на почту!";
                    $emailSuccess = "hidden";
                    $afterConfirmation = "";
                } else {
                    $status = "Ошибка почтового сервиса. Код подтверждения: ".$_SESSION['captcha'];
                    $emailSuccess = "hidden";
                    $afterConfirmation = "";
                }
            } else { $status = "Пользователь с такой почтой не существует!"; }
        } else { $status = "Не похоже на электронную почту"; }
    }
}

if(isset($_POST['signup'])) {
    $newPassword = validate($_POST["newPassword"]);
    $confirmPassword = validate($_POST["confirmPassword"]);
    $captcha = validate($_POST['captcha']);
    if ($captcha != $_SESSION['captcha']) {
        $status = "Неправильно введена капча";
    } else if (empty($newLogin) && empty($newPassword) && empty($confirmPassword)) {
        $status = "Введите данные";
    } else if (empty($newPassword) || empty($confirmPassword)) {
        $status = "Пароль обязателен";
    } else if ($newPassword != $confirmPassword) {
        $status = "Пароли не совпадают";
    } else {
        $status = "Создание пользователя...";
        $status = signup($_SESSION['email'],sha1($newPassword),"***REMOVED***");
    }
}

if(isset($_POST['reset'])) {
    $newPassword = validate($_POST["newPassword"]);
    $confirmPassword = validate($_POST["confirmPassword"]);
    $captcha = validate($_POST['captcha']);
    if ($captcha != $_SESSION['captcha']) {
        $status = "Неправильно введена капча";
    } else if (empty($newLogin) && empty($newPassword) && empty($confirmPassword)) {
        $status = "Введите данные";
    } else if (empty($newPassword) || empty($confirmPassword)) {
        $status = "Пароль обязателен";
    } else if ($newPassword != $confirmPassword) {
        $status = "Пароли не совпадают";
    } else {
        $status = "Сброс пароля...";
        $status = resetPassword($_SESSION['email'],sha1($newPassword),"***REMOVED***");
    }
}
?>
<script>
$(function(){
    $("#mainmenu").hide();
    $("#togglemenu").hide();
});
</script>

<div class="right mobile-expand" id="main">
    <h1 style="text-align:center">Добро пожаловать!</h1>
    <p style="text-align:center">Приветствую Вас в своей маленькой социальной сети "Невка"!</p>
    <img src="1.png" style="width:33%; border: none; box-shadow: none"><img src="2.png" style="width:33%; border: none; box-shadow: none"><img src="3.png" style="width:33%; border: none; box-shadow: none">
    <p style="text-align:center">Всё началось с того, что я начал изучать PHP и MySQL.
        А затем я подумал, что этих знаний, полученных из самоучителей в интернете, достаточно
        для создания полноценной социальной сети прямо у себя в блокноте,
        без использования каких-либо сервисов и конструкторов.</p>
    <p style="text-align:center">На выходе получилась простая, но лаконичная социальная сеть
        с личными страничками, сообщениями и обсуждениями, а так же с мобильной версией сайта.</p>
    <p style="text-align:center">Надеюсь, вам здесь будет уютно!</p>
</div>

<div class="left mobile-expand" id="dialogs">
    <div id="login" class="<?php echo $loginClass ?>">
        <h2>Войти:</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
            <label for="login">Электронная почта:</label><br>
            <input type="email" name="login"><br>
            <label for="password">Пароль:</label><br>
            <input type="password" name="password"><br>
            <br>
            <button class="button wide" type="submit" name="authorize">Войти</button>
        </form>
        <button class="button wide" style="margin-top:7px" id="signUpButton">Зарегистрироваться</button>
        <p class="console"><?php echo $status ?></p>
        <p style="text-align: center; margin-top: -15px; color: lightslategray; text-decoration: underline" id="resetPasswordButton">Забыли пароль?</p>
    </div>
    <div id="agreement" class="<?php echo $signupClass ?>">
        <h2 style="text-align: center;">Соглашение об использовании персональных данных</h2>
        <p>Невка — некоммерческий продукт, созданный физическим лицом, с целью попрактиковаться в навыках PHP, MySQL и React Native.</p>
        <p>Тем не менее, Невка может собирать персональные данные, такие, как:</p>
        <ul>
            <li>Имя</li>
            <li>Фамилия</li>
            <li>Город</li>
            <li>Электронная почта</li>
        </ul>
        <p>Указывать настоящие персональные данные можно, но не обязательно. По этим данным другие пользователи Невки смогут Вас найти или не найти. Поменять эти данные Вы можете в любой момент.</p>
        <p>Имя, фамилия и город собираются с единственной целью: отображение в Вашем личном профиле. Электронная почта не отображается в профиле, а используется исключительно для авторизации. Пароли пользователей не хранятся и передаются в хэшированном виде по алгоритму SHA1. Все данные хранятся только на защищённом российском сервере и могут быть недоступны при использовании VPN. Никаким сторонним сервисам и компаниям, включая Яндекс Метрику и Google Аналитику, персональные данные не передаются.</p>
        <p>Для регистрации и использования Невки, необходимо подтвердить, что любые персональные данные, которые Вы укажете в Вашем профиле, указаны добровольно.</p>
        <button class="button agree" style="margin-top:7px" id="agreeButton">Согласен</button>
        <button class="button disagree" style="margin-top:7px" id="disagreeButton">Не согласен</button>
        <p class="wallPost wide" style="margin-top: 5%; margin-bottom: 5%">Внимание! Сайт использует файлы cookie для хранения данных об авторизации.</p>
    </div>
    <div id="signup" class="<?php echo $signupClass ?>">
        <h2>Зарегистрироваться:</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post" class="<?php echo $emailSuccess ?>">
            Электронная почта: <input type="text" name="newLogin"><br>
            <br>
            <button class="button wide" type="submit" name="confirmEmail">Выслать код подтверждения</button>
        </form>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post" class="<?php echo $afterConfirmation ?>">
            Электронная почта: <input type="email" name="newLogin" value="<?php echo $_SESSION['email'] ?>" disabled><br>
            Код подтверждения: <input type="text" name="captcha"><br>
            Новый пароль: <input type="password" name="newPassword"><br>
            Повторите пароль: <input type="password" name="confirmPassword"><br>
            <br>
            <button class="button wide" type="submit" name="signup">Зарегистрироваться</button>
        </form>
        <button class="button wide" style="margin-top:7px" id="backToLoginButton">Вернуться ко входу</button>
        <p class="console"><?php echo $status ?></p>
    </div>
    <div id="resetPassword" class="<?php echo $resetClass ?>">
        <h2>Сбросить пароль:</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post" class="<?php echo $emailSuccess ?>">
            Электронная почта: <input type="text" name="newLogin"><br>
            <br>
            <button class="button wide" type="submit" name="confirmEmailForPassword">Выслать код подтверждения</button>
        </form>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post" class="<?php echo $afterConfirmation ?>">
            Электронная почта: <input type="email" name="newLogin" value="<?php echo $_SESSION['email'] ?>" disabled><br>
            Код подтверждения: <input type="text" name="captcha"><br>
            Новый пароль: <input type="password" name="newPassword"><br>
            Повторите пароль: <input type="password" name="confirmPassword"><br>
            <br>
            <button class="button wide" type="submit" name="reset">Сбросить пароль</button>
        </form>
        <button class="button wide" style="margin-top:7px" id="backToLoginFromReset">Вернуться ко входу</button>
        <p class="console"><?php echo $status ?></p>
    </div>
</div>

<script>$(function(){
    $("#signUpButton").click(() => { $("#login").toggleClass("hidden"); $("#agreement").toggleClass("hidden"); $("#main").toggleClass("hidden"); $("#dialogs").toggleClass("left agreement"); });
    $("#resetPasswordButton").click(() => { $("#login").toggleClass("hidden"); $("#resetPassword").toggleClass("hidden"); });
    $("#backToLoginButton").click(() => { $("#login").toggleClass("hidden"); $("#signup").toggleClass("hidden"); });
    $("#agreeButton").click(() => { $("#agreement").toggleClass("hidden"); $("#signup").toggleClass("hidden"); $("#main").toggleClass("hidden"); $("#dialogs").toggleClass("left agreement"); });
    $("#disagreeButton").click(() => { $("#login").toggleClass("hidden"); $("#agreement").toggleClass("hidden"); $("#main").toggleClass("hidden"); $("#dialogs").toggleClass("left agreement"); });
    $("#backToLoginFromReset").click(() => { $("#login").toggleClass("hidden"); $("#resetPassword").toggleClass("hidden"); });
});</script>

<?php include 'footer.php'; ?>