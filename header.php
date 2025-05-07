<!DOCTYPE HTML>
<html lang="ru">
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta charset="utf-8">
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
      integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
      crossorigin="anonymous"></script>
    <title>Невка</title>
    <meta name="description" content="Социальная сеть Невка">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  </head>
<body style='background-color: <?php error_reporting(0); echo htmlspecialchars($_COOKIE["theme"])?>'>
  <header id="header">
    <span id="mainlogo">Невка</span>
    <button class="button" id="togglemenu">Меню</button>
    <div id="mainmenu">
      <a id="profilemenu" href="profile.php">Моя страница</a>
      <a id="peoplemenu" href="people.php">Люди</a>
      <a id="messagesmenu" href="messages.php">Сообщения<span id="newMessageDot"></span></a>
      <a id="discussionsmenu" href="discussions.php">Обсуждения</a>
      <a id="settingsmenu" href="settings.php">Настройки</a>
      <a id="logoutbutton" href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"])."?logout";?>">Выход</a>
    </div>
  </header>
  <script>
    $(function(){
      $("#togglemenu").click(function(){
        $("#mainmenu").slideToggle();
        $("header").toggleClass("solid");
      });
    });
  </script>
  <?php
    session_start();
    if (!isset($_COOKIE["theme"])) { $_COOKIE["theme"] = "rgb(20, 41, 48)";}
  ?>
<div class="content">