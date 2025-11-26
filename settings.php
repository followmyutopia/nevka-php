<?php
/**
 * Nevka Project v1.13
 * 2025 Avicus Delacroix
 */

$loggedUser = "";
include 'db.php';
include 'header.php';

if(!isset($_COOKIE["user_token"])) { header("Refresh:0; url=index.php"); exit; }
else { $loggedUser = checkCookie($_COOKIE["user_token"]); updateOnline($_COOKIE["user_token"]); }
if(isset($_GET['logout'])) { logout(); }

$newFirstName = $newLastName = $nameStatus = $cityStatus = $bioStatus = $passwordStatus = "";

if(isset($_POST['setName'])) {
    $newFirstName = validate($_POST["firstname"]);
    $newLastName = validate($_POST["lastname"]);
    if (mb_strlen($newFirstName) > 63 || strlen($newLastName) > 63) {$nameStatus = "Поля максимум 63 символа";}
    else {
        if (empty($newFirstName) && empty($newLastName)) {
            $nameStatus = "Введите данные";
        } else if (empty($newFirstName)) {
            $nameStatus = "Имя обязательно";
        } else if (empty($newLastName)) {
            $nameStatus = "Фамилия обязательна";
        } else {
            $nameStatus = setName($_COOKIE["user_token"],$newFirstName,$newLastName);
        }
    }
}

if(isset($_POST['setCity'])) {
    $newCity = validate($_POST["city"]);
    if (mb_strlen($newCity) > 63) {$cityStatus = "Максимум 63 символа";}
    else {
        $cityStatus = setCity($_COOKIE["user_token"],$newCity);
    }
}

if(isset($_POST['setBio'])) {
    $newBio = validate($_POST["bio"]);
    if (mb_strlen($newBio) > 255) {$bioStatus = "Максимум 255 символов";}
    else {
        $bioStatus = setBio($_COOKIE["user_token"],$newBio);
    }
}

if(isset($_POST['changePassword'])) {
    $oldPassword = validate($_POST["oldPassword"]);
    $newPassword = validate($_POST["newPassword"]);
    $confirmPassword = validate($_POST["confirmPassword"]);

    if ($oldPassword == "" || $newPassword == "" || $confirmPassword == "") {
        $passwordStatus = "Все поля обязательны";
    } else if ($newPassword != $confirmPassword) {
        $passwordStatus = "Пароли не совпадают";
    } else {
        $passwordStatus = changePassword($loggedUser,$_COOKIE["user_token"],sha1($oldPassword),sha1($newPassword));
    }
}

if(isset($_POST['setTheme'])) {
    $_SESSION['theme'] = "#141E30";
    echo "<script>alert('тема');</script>";
}
?>

<div class="equal mobile-expand">
    <h2>Личная информация</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
        * Имя: <input type="text" name="firstname"><br>
        * Фамилия: <input type="text" name="lastname"><br>
        <button class="button" type="submit" name="setName">Установить</button>
        <span class="status"><?php echo $nameStatus;?></span><br>
        </form><br>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
        Город: <input type="text" name="city"><br>
        <button class="button" type="submit" name="setCity">Установить</button>
        <span class="status"><?php echo $cityStatus;?></span><br>
        </form><br>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
        Информация о себе: <input type="text" name="bio"><br>
        <button class="button" type="submit" name="setBio">Установить</button>
        <span class="status"><?php echo $bioStatus;?></span><br>
        </form>
</div>

<div class="equal mobile-expand">
    <h2>Безопасность</h2>
        <p style="color: orange">Внимание! При смене пароля все сессии на других устройствах будут автоматически завершены!</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
        Старый пароль: <input type="password" name="oldPassword"><br>
        Новый пароль: <input type="password" name="newPassword"><br>
        Повторите новый пароль: <input type="password" name="confirmPassword"><br>
        <button class="button" type="submit" name="changePassword">Сменить пароль</button>
        <span class="status"><?php echo $passwordStatus;?></span><br>
        </form>
    <h2>Установить тему</h2>
        
    <div class="setTheme" style="background-color:rgb(3, 27, 39)" onclick='setTheme("rgb(3, 27, 39)")'></div>
    <div class="setTheme" style="background-color:rgb(39, 3, 3, 1)" onclick='setTheme("rgb(39, 3, 3, 1)")'></div>
    <div class="setTheme" style="background-color:rgb(27, 3, 39, 1)" onclick='setTheme("rgb(27, 3, 39, 1)")'></div>
    <div class="setTheme" style="background-color:rgba(13, 39, 3, 1)" onclick='setTheme("rgb(13, 39, 3, 1)")'></div>
    <div class="setTheme" style="background-color:rgba(39, 16, 3, 1)" onclick='setTheme("rgb(39, 16, 3, 1)")'></div>
    <div class="setTheme" style="background-color:rgba(10, 10, 10, 1)" onclick='setTheme("rgb(10, 10, 10, 1)")'></div>

    <span class="clearfix"></span>
    <p id="notificationButton" style="color: orange; text-decoration: underline">Beta: включить уведомления о сообщениях</p>
</div>
<span class="clearfix"></span>
<p class="signature">Nevka v1.13.2<br><a href="https://nevka.site/profile.php?id=1" style="text-decoration: none">Авикус Делакруа</a></p>

<script>
function setTheme(color) {
    document.cookie = "theme="+color;
    window.location.reload();
}
$(function(){
    $("#settingsmenu").addClass("active");
    $("#notificationButton").click(() => {
        Notification.requestPermission().then(perm => {
            if (perm === "granted") {
                alert("Внимание! Уведомления находятся в стадии разработки.\n\nЧтобы они работали, держите любую открытую вкладку Невки в фоне.\n\nЖелательно, не вкладку сообщений, потому что она будет автоматически отмечать прочитанными сообщения в открытом чате.");
            }
        });
    });
});</script>

<?php include 'footer.php'; ?>