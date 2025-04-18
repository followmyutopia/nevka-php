<?php
/**
 * Nevka Project v1.4
 * 2024-2025 Avicus Delacroix
 */

$loggedUser = "";
include 'db.php';
include 'header.php';

if(!isset($_COOKIE["user_token"])) { header("Refresh:0; url=index.php"); exit; }
else { $loggedUser = checkCookie($_COOKIE["user_token"]); updateOnline($_COOKIE["user_token"]); }
if(isset($_GET['logout'])) { logout(); }
$loggedUserData = getUserData($loggedUser);
if (!isset($loggedUserData[1]) || !isset($loggedUserData[2])) {header("Refresh:0; url=settings.php");}


?>

<div class="admin">
    <div id="admin_logo" style="display: none">Nevka Project v1.4 Admin panel<br>2024-2025 Avicus Delacroix<br></div>
    <br><br>
    <div id="admin_menu" style="display: none">
        Выберите желаемое действие:<br><br>
        <span id="#">1. Забанить пользователя</span><br>
        <span id="#">2. Разбанить пользователя</span><br>
        <span id="#">3. Удалить обсуждение (безвозвратно)</span><br><br>
    </div>
    <div id="admin_ban" style="display: none">
        Ваш выбор: 1. Забанить пользователя.<br><br>
        <span style="color: red">Внимание: забаненный пользователь будет разлогинен и не сможет войти в Невку.</span><br><br>
        Введите ID пользователя, которого нужно забанить:<br><br>
    </div>
    <div id="admin_unban" style="display: none">
        Ваш выбор: 2. Разбанить пользователя<br><br>
        <span style="color: orange">Внимание: разбаненный пользователь сможет снова войти в Невку со старым паролем.</span><br><br>
        Введите ID пользователя, которого нужно разбанить:<br><br>
    </div>
    <div id="admin_deldisc" style="display: none">
        Ваш выбор: 3. Удалить обсуждение (безвозвратно)<br><br>
        <span style="color: red">Внимание: будет БЕЗВОЗВРАТНО удалено обсуждение и все его ответы.</span><br><br>
        Введите ID обсуждения, которое нужно удалить:<br><br>
    </div>
    <span id="admin_progress"></span>
    <input class="admininput" type="text" id="admininput" style="display:none">
    <input class="admininput" type="text" id="usertoban" style="display:none">
    <input class="admininput" type="text" id="usertounban" style="display:none">
    <input class="admininput" type="text" id="discussiontodelete" style="display:none">
</div>

<script>$(function(){
    // $("#profilemenu").addClass("active");
    setTimeout(() => {$("#admin_logo").show()}, 1000);
    setTimeout(() => {$("#admin_progress").html("_")}, 1500);
    setTimeout(() => {$("#admin_progress").html("")}, 1750);
    setTimeout(() => {$("#admin_progress").html("_")}, 2000);
    setTimeout(() => {$("#admin_progress").html("")}, 2250);
    setTimeout(() => {$("#admin_progress").html("_")}, 2500);
    setTimeout(() => {$("#admin_progress").html("")}, 2750);
    setTimeout(() => {$("#admin_menu").show()}, 3250);
    setTimeout(() => {$("#admininput").show().focus()}, 4000);
    $('#admininput').keypress(function (e) { if(e.which == 13) {
        if ($('#admininput').val() == 1) {
            $("#admininput").hide().val("");
            setTimeout(() => { $("#admin_ban").show(); }, 1000);
            setTimeout(() => { $("#usertoban").show().focus(); }, 1000);
        } else if ($('#admininput').val() == 2) {
            $("#admininput").hide().val("");
            setTimeout(() => { $("#admin_unban").show(); }, 1000);
            setTimeout(() => { $("#usertounban").show().focus(); }, 1000);
        } else if ($('#admininput').val() == 3) {
            $("#admininput").hide().val("");
            setTimeout(() => { $("#admin_deldisc").show(); }, 1000);
            setTimeout(() => { $("#discussiontodelete").show().focus(); }, 1000);
        } else if ($('#admininput').val() == "hack") {
            window.open("https://nevka.site/hack/","_self")
        } else {
            $("#admininput").hide().val("");
            setTimeout(() => { $("#admin_progress").css('color', 'red'); $("#admin_progress").html("Команда не найдена!"); }, 500);
            setTimeout(() => { $("#admin_progress").css('color', 'green'); $("#admin_progress").html(""); $("#admininput").show().focus(); }, 5000);
        }
    }});
    $('#usertoban').keypress(function (e) { if(e.which == 13) {
        if(Math.floor($('#usertoban').val()) == $('#usertoban').val() && $.isNumeric($('#usertoban').val())) {
            $.post("updateAsync.php", { currentChat: $('#usertoban').val(), type: 3 }, function(data){
                alert(data);
            });
        } else {
            $("#usertoban").hide().val("");
            setTimeout(() => { $("#admin_progress").css('color', 'red'); $("#admin_progress").html("Введена перехуйница!"); }, 500);
            setTimeout(() => { $("#admin_progress").css('color', 'green'); $("#admin_progress").html(""); $("#usertoban").show().focus(); }, 5000);
        };
    }});
    $('#usertounban').keypress(function (e) { if(e.which == 13) {}});
    $('#discussiontodelete').keypress(function (e) { if(e.which == 13) {}});
});</script>

<?php include 'footer.php'; ?>