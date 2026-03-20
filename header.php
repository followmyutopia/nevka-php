<!DOCTYPE HTML>
<html lang="ru">

<head>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta charset="utf-8">
  <link rel="stylesheet" href="styles.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"
    integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
    crossorigin="anonymous"></script>
  <script type="text/javascript" src="https://unpkg.com/voximplant-websdk@4.7.7-2788"></script>
  <title>Невка</title>
  <meta name="description" content="Социальная сеть Невка">
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat">
</head>

<body style='background-color: <?php echo htmlspecialchars($_COOKIE["theme"]) ?>'>
  <div class="top">
    <div class="logogroup">
      <img src="splash-icon-light.png" class="nevkaLogo">
      <span id="mainlogo">Невка</span>
    </div>
    <header id="header">
      <button class="button" id="togglemenu">Меню</button>
      <div id="mainmenu">
        <a id="profilemenu" href="profile.php">Моя страница</a>
        <a id="feedmenu" href="feed.php">Лента</a>
        <a id="peoplemenu" href="people.php">Люди</a>
        <a id="messagesmenu" href="messages.php">Сообщения<span id="newMessageDot"></span></a>
        <a id="settingsmenu" href="settings.php">Настройки</a>
        <a id="logoutbutton" href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?logout"; ?>">Выход</a>
      </div>
    </header>
    <div class="mobilegroup">
      <a id="android" href="android"><span class="fa fa-android"></span></a>
      <a id="ios" href="ios"><span class="fa fa-apple"></span></a>
    </div>
  </div>
  <script>
    $(function() {
      $("#togglemenu").click(function() {
        $("#mainmenu").slideToggle();
        $("header").toggleClass("solid");
        // $("#android").toggle();
        // $("#ios").toggle();
      });
    });
  </script>
  <?php
  session_start();
  if (!isset($_COOKIE["theme"])) {
    $_COOKIE["theme"] = "rgb(20, 41, 48)";
  }
  ?>
  <div class="content">