<?php

/**
 * Nevka Project v1.15 Database inquires
 * 2025 Avicus Delacroix
 * 
 * Databases and types (MySQL):
 * Users:       id (int), login (varchar-63), password (varchar-40), token (varchar-127), avatar (mediumblob), firstname (varchar-63), lastname (varchar-63), status (varchar-127), city (varchar-63), bio (varchar-255), timestamp (int), search (varchar-127), is_banned (tinyint-1), is_admin (tinyint-1), expo-push-token (varchar-255)
 * Walls:       id (int), user (int), text (varchar-1023), image (mediumblob), timestamp (int), likes (varchar-1023)
 * Messages:    id (int), query (varchar-63), author (int), text (varchar-1023), image (mediumblob), timestamp (int), unread (tinyint-1), reply (int)
 */

include_once 'lib/ExpoCrypto.php';
include 'lib/ImageResize.php';

use \Gumlet\ImageResize;

date_default_timezone_set("Europe/Moscow");
error_reporting(0);

function validate($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// fix for PHP version less than 8
function str_starts_with_FIX($stringus, $patternus)
{
    return substr($stringus, 0, strlen($patternus)) === $patternus;
}

function str_ends_with_FIX($stringus, $patternus)
{
    return substr($stringus, -strlen($patternus)) === $patternus;
}

function getNameById($id)
{
    include 'credentials.php';

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается получить имя пользователя. Код ошибки для поддержки: 133");
    }

    $checkedId = $conn->real_escape_string($id);
    $sql = "SELECT firstname, lastname FROM users WHERE id='$checkedId' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        return $row[0]." ".$row[1];
    } else {
        return "Неизвестный пользователь";
    }

    $conn->close();
}

function getIdByToken($token)
{
    include 'credentials.php';

    $checkedToken = validate($token);

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается получить ID. Код ошибки для поддержки: 101");
    }

    $checkedToken = $conn->real_escape_string($checkedToken);
    $sql = "SELECT id FROM users WHERE token='$checkedToken' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        return $row[0];
    } else {
        logout();
    }

    $conn->close();
}

function login($login, $hashedPassword)
{
    include 'credentials.php';
    $checkedLogin = validate($login);

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается залогиниться. Код ошибки для поддержки: 102");
    }

    $checkedLogin = $conn->real_escape_string($checkedLogin);
    $sql = "SELECT id, login, password, token, is_banned FROM users WHERE login='$checkedLogin' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        if ($hashedPassword == $row[2] && !$row[4]) {
            setcookie("user_token", $row[3], time() + (86400 * 30), "/");
            header("Refresh:0; url=profile.php");
        } else if ($row[4]) {
            return "Пользователь $login заблокирован модератором";
        } else {
            return "Неправильный пароль";
        }
    } else {
        return "Пользователь $login не найден";
    }

    $conn->close();
}

function logout()
{
    setcookie("user_token", "", time() - 3600);
    header("Refresh:0");
}

function checkCookie($token)
{
    include 'credentials.php';

    $checkedToken = validate($token);

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается проверить куки. Код ошибки для поддержки: 103");
    }

    $checkedToken = $conn->real_escape_string($checkedToken);
    $sql = "SELECT id, is_banned FROM users WHERE token='$checkedToken' LIMIT 1";
    $result = $conn->query($sql);

    $conn->close();

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        if (!$row[1]) {
            return $row[0];
        } else {
            logout();
        }
    } else {
        logout();
    }
}

function changePassword($loggedUser, $token, $oldPassword, $newPassword)
{
    include 'credentials.php';
    $newToken = "User".$loggedUser."-".$newPassword;

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается сменить пароль. Код ошибки для поддержки: 104");
    }

    $checkedToken = $conn->real_escape_string($token);
    $sql = "SELECT password FROM users WHERE token='$checkedToken' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        if ($oldPassword == $row[0]) {
            // approved sorting by id, user checked
            $checkedPassword = $conn->real_escape_string($newPassword);
            $checkedNewToken = $conn->real_escape_string($newToken);
            $security = "UPDATE users SET password='$checkedPassword', token='$checkedNewToken' WHERE id='$loggedUser'";

            if ($conn->query($security) === TRUE) {
                setcookie("user_token", $newToken, time() + (86400 * 30), "/");
                return "Пароль успешно обновлён";
            } else {
                return "Ошибка базы данных: невозможно сменить пароль. Код ошибки для поддержки: 105";
            }
        } else {
            return "Неправильный пароль";
        }
    } else {
        logout();
    }

    $conn->close();
}

function getUserData($userId)
{
    include 'credentials.php';

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается получить данные пользователя. Код ошибки для поддержки: 106");
    }

    $checkedUserId = $conn->real_escape_string($userId);
    $sql = "SELECT avatar, firstname, lastname, status, city, bio, timestamp, login FROM users WHERE id='$checkedUserId' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        // compatibility with the v1.4 database format
        // this is done for removing support for storing string dates in the database
        $formattedTime = date("d.m.Y в h:i a", $row[6]);
        $row[6] = $formattedTime;
        $voxUsername = str_replace("@", "-", $row[7])."@nevka.avicus.n4.voximplant.com";
        $row[7] = $voxUsername;

        return $row;
    } else {
        header("Refresh:0; url=profile.php");
    }

    $conn->close();
}

function setAvatar($token, $blob)
{
    include 'credentials.php';

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается установить аватар. Код ошибки для поддержки: 107");
    }

    $checkedToken = $conn->real_escape_string($token);
    $checkedBlob = $conn->real_escape_string($blob);
    $sql = "UPDATE users SET avatar='$checkedBlob' WHERE token='$checkedToken'";

    if ($conn->query($sql) === TRUE) {
        header("Refresh:0");
    } else {
        return "Ошибка базы данных: невозможно обновить аватар. Код ошибки для поддержки: 108";
    }

    $conn->close();
}

function setName($token, $newFirstName, $newLastName)
{
    include 'credentials.php';

    $query = $newFirstName." ".$newLastName;

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается установить имя. Код ошибки для поддержки: 109");
    }

    $checkedToken = $conn->real_escape_string($token);
    $checkedFirstName = $conn->real_escape_string($newFirstName);
    $checkedLastName = $conn->real_escape_string($newLastName);
    $checkedQuery = $conn->real_escape_string($query);
    $sql = "UPDATE users SET firstname='$checkedFirstName', lastname='$checkedLastName', search='$checkedQuery' WHERE token='$checkedToken'";

    if ($conn->query($sql) === TRUE) {
        $getNameSql = "SELECT login FROM users WHERE token='$checkedToken'";
        $getNameResult = $conn->query($getNameSql);
        $getNameRow = $getNameResult->fetch_row();
        $voxUsername = str_replace("@", "-", $getNameRow[0]);

        $params = array(
            'api_key' => 'PASTE_VOX_API_KEY_HERE',
            'user_name' => $voxUsername,
            'application_id' => '11329284',
            'user_display_name' => $newFirstName.' '.$newLastName,
            'account_id' => '3831061'
        );

        $url = "https://api.voximplant.com/platform_api/SetUserInfo/?".http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
        $requestResult = curl_exec($ch);
        curl_close($ch);

        return "Имя успешно установлено";
    } else {
        return "Ошибка базы данных: невозможно обновить имя. Код ошибки для поддержки: 110";
    }

    $conn->close();
}

function setCity($token, $newCity)
{
    include 'credentials.php';

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается установить город. Код ошибки для поддержки: 111");
    }

    $checkedToken = $conn->real_escape_string($token);
    $checkedCity = $conn->real_escape_string($newCity);
    $sql = "UPDATE users SET city='$checkedCity' WHERE token='$checkedToken'";

    if ($conn->query($sql) === TRUE) {
        return "Город успешно установлен";
    } else {
        return "Ошибка базы данных: невозможно обновить город. Код ошибки для поддержки: 112";
    }

    $conn->close();
}

function setBio($token, $newBio)
{
    include 'credentials.php';

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается установить информацию. Код ошибки для поддержки: 113");
    }

    $checkedToken = $conn->real_escape_string($token);
    $checkedBio = $conn->real_escape_string($newBio);
    $sql = "UPDATE users SET bio='$checkedBio' WHERE token='$checkedToken'";

    if ($conn->query($sql) === TRUE) {
        return "Описание успешно установлено";
    } else {
        return "Ошибка базы данных: невозможно обновить информацию. Код ошибки для поддержки: 114";
    }

    $conn->close();
}

function setStatus($token, $newStatus)
{
    include 'credentials.php';

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка. Код ошибки для поддержки: 115");
    }

    $checkedToken = $conn->real_escape_string($token);
    $checkedStatus = $conn->real_escape_string($newStatus);
    $sql = "UPDATE users SET status='$checkedStatus' WHERE token='$checkedToken'";

    if ($conn->query($sql) === TRUE) {
        return $newStatus;
    } else {
        return "Ошибка базы данных: невозможно обновить статус. Код ошибки для поддержки: 116";
    }

    $conn->close();
}

function updateOnline($token)
{
    include 'credentials.php';

    $timestamp = time();

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается обновить онлайн. Код ошибки для поддержки: 117");
    }

    $checkedToken = $conn->real_escape_string($token);
    $sql = "UPDATE users SET timestamp='$timestamp' WHERE token='$checkedToken'";

    if ($conn->query($sql) === TRUE) {
        return;
    } else {
        return "Ошибка базы данных: невозможно обновить онлайн. Код ошибки для поддержки: 118";
    }

    $conn->close();
}

function addWallEntry($token, $wallText, $wallImage)
{
    include 'credentials.php';

    $loggedUser = getIdByToken($token);
    $timestamp = time();

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается опубликовать стену. Код ошибки для поддержки: 119");
    }

    $checkedText = $conn->real_escape_string($wallText);
    $checkedImage = $conn->real_escape_string($wallImage);
    $sql = "INSERT INTO walls (user, text, image, timestamp, likes)
        VALUES ('$loggedUser', '$checkedText', '$checkedImage', '$timestamp', '[]')";

    if ($conn->query($sql) === TRUE) {
        header("Refresh:0");
    } else {
        return "Ошибка базы данных: невозможно опубликовать запись на стене. Код ошибки для поддержки: 120";
    }

    $conn->close();
}

function deleteWallEntry($token, $entryId)
{
    include 'credentials.php';

    $checkedUser = getIdByToken($token);

    $conn = new mysqli($servername, $username, $password, $dbname);
    $checkedID = $conn->real_escape_string(validate($entryId));
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается удалить со стены. Код ошибки для поддержки: 121");
    }

    $sql = "SELECT user FROM walls WHERE id='$checkedID' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        if ($checkedUser == $row[0]) {
            // approved sorting by id, user checked
            $del = "DELETE FROM walls WHERE id='$checkedID'";
            if ($conn->query($del) === TRUE) {
                header("Refresh:0");
            } else {
                return "Ошибка базы данных: невозможно удалить со стены. Код ошибки для поддержки: 122";
            }
        } else {
            return "Недостаточно прав";
        }
    } else {
        return "Запись удалена или не существует";
    }

    $conn->close();
}

function getWallEntries($loggedUser, $me)
{
    include 'credentials.php';

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается получить стену. Код ошибки для поддержки: 123");
    }

    $checkedLoggedUser = $conn->real_escape_string($loggedUser);
    if ($loggedUser == "all") {
        $sql = "SELECT id, user, text, image, timestamp, likes FROM walls ORDER BY id DESC LIMIT 200";
    } else {
        $sql = "SELECT id, user, text, image, timestamp, likes FROM walls WHERE user='$checkedLoggedUser' ORDER BY id DESC LIMIT 100";
    }
    
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $authorName = getNameById($row["user"]);
            $likesArray = json_decode($row["likes"]);
            $redMarker = "";
            if (in_array($me, $likesArray)) $redMarker = "style=\"color: crimson\" ";
            $numberOfLikes = count($likesArray);
            if ($numberOfLikes == 0) $numberOfLikes = "";

            // compatibility with the v1.4 database format
            $formattedTime = date("d.m.Y в h:i a", $row["timestamp"]);

            if ($loggedUser == "all") {
                $authorId = $row["user"];
                $getAvatar = "SELECT avatar FROM users WHERE id='$authorId' LIMIT 1";
                $avatarResult = $conn->query($getAvatar);
                $avatarRow = $avatarResult->fetch_row();

                $image = new ImageResize($avatarRow[0]);
                $image->quality_jpg = 90;
                $image->crop(100, 100);
                $blob = 'data:image/jpeg;base64,'.base64_encode($image);
                
                if ($row["image"] != "") {
                    echo "<div class=\"wallPost\"><img class=\"thumbnail\" style=\"float:left;margin:12px;\" src=\"".$blob."\"><p style=\"padding: 12px\"><a href=\"profile.php?id=".$row["user"].'">'.$authorName.'</a><br>'.$formattedTime.'</p><span class="clearfix"></span><p>'.$row["text"].'</p><img class="postImage" src="'.$row["image"]."\"><div data-id=\"".$row["id"]."\" class=\"likeButton\"><span ".$redMarker."class=\"fa fa-heart\" id=\"like-".$row["id"]."\"></span><span style=\"padding-right: 10px\" id=\"likeCount-".$row["id"]."\"> ".$numberOfLikes."</span></div></div>";
                } else {
                    echo "<div class=\"wallPost\"><img class=\"thumbnail\" style=\"float:left;margin:12px;\" src=\"".$blob."\"><p style=\"padding: 12px\"><a href=\"profile.php?id=".$row["user"].'">'.$authorName.'</a><br>'.$formattedTime.'</p><span class="clearfix"></span><p>'.$row["text"]."</p><div data-id=\"".$row["id"]."\" class=\"likeButton\"><span ".$redMarker."class=\"fa fa-heart\" id=\"like-".$row["id"]."\"></span><span style=\"padding-right: 10px\" id=\"likeCount-".$row["id"]."\"> ".$numberOfLikes."</span></div></div>";
                }
            } else {
                if ($row["image"] != "") {
                    echo "<div class=\"wallPost\"><p>".$formattedTime." <a href=\"profile.php?id=".$loggedUser.'">'.$authorName.'</a> написал(а):<span id="'.$row["id"].'" class="delPostButton fa fa-times"></span></p><p>'.$row["text"].'</p><img class="postImage" src="'.$row["image"]."\"><div data-id=\"".$row["id"]."\" class=\"likeButton\"><span ".$redMarker."class=\"fa fa-heart\" id=\"like-".$row["id"]."\"></span><span style=\"padding-right: 10px\" id=\"likeCount-".$row["id"]."\"> ".$numberOfLikes."</span></div></div>";
                } else {
                    echo "<div class=\"wallPost\"><p>".$formattedTime." <a href=\"profile.php?id=".$loggedUser.'">'.$authorName.'</a> написал(а):<span id="'.$row["id"].'" class="delPostButton fa fa-times"></span></p><p>'.$row["text"]."</p><div data-id=\"".$row["id"]."\" class=\"likeButton\"><span ".$redMarker."class=\"fa fa-heart\" id=\"like-".$row["id"]."\"></span><span style=\"padding-right: 10px\" id=\"likeCount-".$row["id"]."\"> ".$numberOfLikes."</span></div></div>";
                }
            }
        }
    } else {
        echo "<p class=\"console\">Записей на стене нет<br><br><br>Вы можете использовать стену, как персональный блог.<br><br>Длина сообщения на стене не может быть больше 1023 символа, а так же на стену можно публиковать изображения в форматах JPG/JPEG/PNG.<p>";
    }

    $conn->close();
}

function signUp($newLogin, $hashedPassword, $apiKey)
{
    if ($apiKey == "***REMOVED***") {
        include 'credentials.php';

        $newToken = "NevkaToken2.1[".$newLogin."-".$hashedPassword."]";

        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            die("Ошибка подключения: не получается зарегистрироваться. Код ошибки для поддержки: 124");
        }

        $checkedLogin = $conn->real_escape_string($newLogin);
        $sql = "SELECT id FROM users WHERE login='$checkedLogin' LIMIT 1";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            return "Логин занят";
        } else {
            include 'defaultImage.php';
            $checkedPassword = $conn->real_escape_string($hashedPassword);
            $checkedToken = $conn->real_escape_string($newToken);
            $checkedDefaultImage = $conn->real_escape_string($defaultImage);
            $sql = "INSERT INTO users (login, password, token, avatar)
            VALUES ('$checkedLogin', '$checkedPassword', '$checkedToken', '$checkedDefaultImage')";

            if ($conn->query($sql) === TRUE) {
                $voxUsername = str_replace("@", "-", $newLogin);
                $params = array(
                    'api_key' => 'PASTE_VOX_API_KEY_HERE',
                    'application_id' => '11329284',
                    'user_display_name' => 'Новый пользователь',
                    'user_name' => $voxUsername,
                    'user_password' => 'PASTE_VOX_PASSWORD_HERE',
                    'account_id' => '3831061'
                );

                $url = "https://api.voximplant.com/platform_api/AddUser/?".http_build_query($params, '', '&', PHP_QUERY_RFC3986);
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
                $result = curl_exec($ch);
                curl_close($ch);

                return "Новый пользователь успешно создан!";
            } else {
                return "Ошибка создания пользователя! Код ошибки для поддержки: 125<script>console.log($sql $conn->error)</script>";
            }
        }

        $conn->close();
    }
}

function resetPassword($login, $hashedPassword, $apiKey)
{
    if ($apiKey == "***REMOVED***") {
        include 'credentials.php';

        $newToken = "NevkaToken2.1[".$login."-".$hashedPassword."]";

        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            die("Ошибка подключения: не получается зарегистрироваться. Код ошибки для поддержки: 147");
        }

        $checkedLogin = $conn->real_escape_string($login);
        $checkedPassword = $conn->real_escape_string($hashedPassword);
        $checkedToken = $conn->real_escape_string($newToken);
        $sql = "UPDATE users SET password='$checkedPassword', token='$checkedToken' WHERE login='$checkedLogin'";

        if ($conn->query($sql) === TRUE) {
            return "Пароль успешно сброшен!";
        } else {
            return "Ошибка сброса пароля! Код ошибки для поддержки: 148<script>console.log($sql $conn->error)</script>";
        }

        $conn->close();
    }
}

function showUsersTable($query)
{
    include 'credentials.php';

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается показать таблицу пользователей. Код ошибки для поддержки: 126");
    }

    $checkedQuery = $conn->real_escape_string($query);
    $sql = "SELECT id, avatar, firstname, lastname, timestamp, search FROM users
    WHERE (firstname LIKE '%$checkedQuery%' OR lastname LIKE '%$checkedQuery%' OR search LIKE '%$checkedQuery%') AND (NOT is_banned OR is_banned IS NULL)
    ORDER BY timestamp DESC LIMIT 500";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // compatibility with the v1.4 database format
            $formattedTime = date("d.m.Y в h:i a", $row["timestamp"]);
            $online = "";
            $lastOnline = date_create_from_format("d.m.Y в h:i a", $formattedTime);
            $now = new DateTime();
            $difference = ($lastOnline->diff($now)->days * 1440) + ($lastOnline->diff($now)->h * 60) + $lastOnline->diff($now)->i;
            if ($difference <= 5) {
                $online = "<span style=\"color:greenyellow; font-size: 14px\">Онлайн</span>";
            } else if ($difference < 60) {
                $online = "<span style=\"color:lightslategray; font-size: 14px\">Был(а) в сети: ".$lastOnline->diff($now)->i." минут назад</span>";
            } else {
                $online = "<span style=\"color:lightslategray; font-size: 14px\">Был(а) в сети: ".$formattedTime."</span>";
            }

            $image = new ImageResize($row["avatar"]);
            $image->quality_jpg = 90;
            $image->crop(100, 100);
            $blob = 'data:image/jpeg;base64,'.base64_encode($image);

            echo "<tr><td class=\"a b\" data-href=\"profile.php?id=".$row["id"]."\"><img class=\"thumbnail\" src=\"".$blob."\"></td><td class=\"a\" data-href=\"profile.php?id=".$row["id"]."\">".$row["firstname"]." ".$row["lastname"]."<br>".$online."</td><td class=\"a b\" data-href=\"messages.php?id=".$row["id"]."\"><button class=\"button\">Написать сообщение</button></td></tr>";
        }
    } else {
        echo "<tr><td>Никого не найдено</td></tr>";
    }

    $conn->close();
}

function showChatsTable($token)
{
    include 'credentials.php';
    $crypto = new ExpoCrypto();
    $key = pack("H*", $keyhash);

    $checkedUser = getIdByToken($token);
    $signature = "M".$checkedUser."M";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается показать таблицу чатов. Код ошибки для поддержки: 127");
    }

    $checkedSignature = $conn->real_escape_string($signature);
    $sql = "SELECT query, MAX(id) FROM messages WHERE query LIKE '%$checkedSignature%' GROUP BY query ORDER BY MAX(id) DESC LIMIT 75";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $interlocutor = str_replace($signature, "", $row["query"]);
            $interlocutor = str_replace("M", "", $interlocutor);
            $lastTextId = $row['MAX(id)'];

            $getLastText = "SELECT text, author, unread FROM messages WHERE id='$lastTextId' LIMIT 1";
            $lastTextResult = $conn->query($getLastText);
            $lastTextRow = $lastTextResult->fetch_row();

            // approved sorting by id, user checked
            $getName = "SELECT id, avatar, firstname, lastname, timestamp FROM users WHERE id='$interlocutor' LIMIT 500";
            $entryResult = $conn->query($getName);
            $row = $entryResult->fetch_row();

            if ($checkedUser < $row[0]) {
                $chat = $checkedUser."-".$row[0];
            } else {
                $chat = $row[0]."-".$checkedUser;
            }

            $lastText = $lastTextRow[0];
            // if (preg_match('/^NE[1-9]\[.*\]$/', $lastText)) $lastText = "<i>[Зашифрованное сообщение]</i>";
            if (strpos($lastText, 'NE5[') === 0) {
                $clean_content = mb_substr($lastText, 4, -1);
                $decrypted = $crypto->decode($clean_content, $key.$chat);
                $lastText = $decrypted;
            } else if ($lastText == "") {
                $lastText = "(изображение)";
            }
            $lastTextAuthor = $lastTextRow[1];
            $isUnread = $lastTextRow[2];

            if ($lastTextAuthor != $checkedUser && $isUnread == "1") {
                $unreadMarker = "<span class=\"fa fa-circle\" style=\"color: orange; margin-left: 10px\"></span>";
            } else {
                $unreadMarker = "";
            }
            if ($lastTextAuthor == $checkedUser) {
                $preview = "<p class=\"preview\">Вы: ".$lastText."</p>";
            } else {
                $preview = "<p class=\"preview\">".$lastText."</p>";
            }
            // compatibility with the v1.4 database format
            $formattedTime = date("d.m.Y в h:i a", $row["4"]);
            $online = "";
            $lastOnline = date_create_from_format("d.m.Y в h:i a", $formattedTime);
            $now = new DateTime();
            $difference = ($lastOnline->diff($now)->days * 1440) + ($lastOnline->diff($now)->h * 60) + $lastOnline->diff($now)->i;
            $shortDate = str_replace(".".$lastOnline->format("Y"), "", $formattedTime);
            if ($difference <= 5) {
                $online = "<span style=\"color:greenyellow; font-size: 14px\">Онлайн</span>";
            } else if ($difference < 60) {
                $online = "<span style=\"color:lightslategray; font-size: 14px\">Был(а): ".$lastOnline->diff($now)->i." минут назад</span>";
            } else {
                $online = "<span style=\"color:lightslategray; font-size: 14px\">Был(а): ".$shortDate."</span>";
            }

            $image = new ImageResize($row["1"]);
            $image->quality_jpg = 90;
            $image->crop(100, 100);
            $blob = 'data:image/jpeg;base64,'.base64_encode($image);

            echo "<tr id=\"".$row["0"]."\"><td class=\"a c\" data-href=\"messages.php?id=".$row["0"]."\"><img class=\"thumbnail\" style=\"margin-top: 7px; margin-left: 7px\" src=\"".$blob."\"></td><td class=\"a d\" data-href=\"messages.php?id=".$row["0"]."\">".$row["2"]." ".$row["3"].$unreadMarker."<br>".$online.$preview."</td></tr>";
        }
    } else {
        echo "<p class=\"console\">Нет чатов.</p><p id=\"pplNotice\" class=\"console\">Перейдите в раздел <a href=\"people.php\">Люди</a> для выбора собеседника.</p>";
    }

    $conn->close();
}

function currentUserHeader($userId)
{
    include 'credentials.php';

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается отобразить заголовок чата. Код ошибки для поддержки: 128");
    }

    // approved sorting by id, public data
    $checkedUserId = $conn->real_escape_string($userId);
    $sql = "SELECT firstname, lastname, timestamp FROM users WHERE id='$checkedUserId' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        // compatibility with the v1.4 database format
        $formattedTime = date("d.m.Y в h:i a", $row["2"]);
        $online = "";
        $lastOnline = date_create_from_format("d.m.Y в h:i a", $formattedTime);
        $now = new DateTime();
        $difference = ($lastOnline->diff($now)->days * 1440) + ($lastOnline->diff($now)->h * 60) + $lastOnline->diff($now)->i;
        if ($difference <= 5) {
            $online = "<span style=\"color:greenyellow\">Онлайн</span>";
        } else if ($difference < 60) {
            $online = "<span style=\"color:lightslategray\">Был(а): ".$lastOnline->diff($now)->i." минут назад</span>";
        } else {
            $online = "<span style=\"color:lightslategray\">Был(а): ".$formattedTime."</span>";
        }
        echo "<p class=\"messagesHeader\" id=\"".$userId."\"><a id=\"msgBack\" href=\"messages.php\">← Назад</a><b><a href=\"profile.php?id=".$userId."\">".$row["0"]." ".$row["1"]."</a></b><br>".$online."</p>";
    } else {
        return;
    }

    $conn->close();
}

function getMessages($token, $chatUser)
{
    include 'credentials.php';
    $crypto = new ExpoCrypto();
    $key = pack("H*", $keyhash);

    $query = "";
    $loggedUser = getIdByToken($token);
    if ($loggedUser < $chatUser) {
        $query = "M".$loggedUser."M".$chatUser."M";
        $chat = $loggedUser."-".$chatUser;
    } else {
        $query = "M".$chatUser."M".$loggedUser."M";
        $chat = $chatUser."-".$loggedUser;
    }

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается отобразить сообщения. Код ошибки для поддержки: 129");
    }

    $checkedQuery = $conn->real_escape_string($query);
    $sql = "SELECT * FROM (SELECT id, query, author, text, image, timestamp, unread, reply FROM messages WHERE query='$checkedQuery' ORDER BY id DESC LIMIT 100) X ORDER BY id ASC;";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $interlocutor = str_replace("M".$loggedUser."M", "", $row["query"]);
            $interlocutor = str_replace("M", "", $interlocutor);
            // compatibility with the v1.4 database format
            $formattedTime = date("d.m в h:i a", $row["timestamp"]);

            if ($row['reply']) {
                $replyId = $row['reply'];
                $checkedReplyId = $conn->real_escape_string($replyId);
                $getReplyText = "SELECT author, text FROM messages WHERE id ='$checkedReplyId' LIMIT 1";
                $textResult = $conn->query($getReplyText);
                $textRow = $textResult->fetch_row();
                // if (preg_match('/^NE[1-9]\[.*\]$/', $textRow[1])) $preview = "<i>[Зашифрованное сообщение]</i>";
                if (strpos($textRow[1], 'NE5[') === 0) {
                    $clean_content = mb_substr($textRow[1], 4, -1);
                    $decrypted = $crypto->decode($clean_content, $key.$chat);
                    $preview = mb_strimwidth($decrypted, 0, 115, '...');
                } else if ($textRow[1] == "") $preview = "(изображение)";
                else $preview = mb_strimwidth($textRow[1], 0, 115, '...');
                if ($checkedUser == $textRow[0]) $reply = "Вы: ".$preview;
                else $reply = $preview;
            } else $reply = "";


            if ($reply != "") $replyDOM = "<span class=\"reply\"><span class=\"fa fa-reply\" style=\"margin-right: 8px\"></span>".$reply."</span><hr>";
            else $replyDOM = "";

            $isEncrypted = false;
            if (strpos($row["text"], 'NE5[') === 0) {
                $clean_content = mb_substr($row["text"], 4, -1);
                $decrypted = $crypto->decode($clean_content, $key.$chat);
                $row["text"] = $decrypted;
                $isEncrypted = true;
            }

            if ($chatUser == $interlocutor) {
                if ($isEncrypted) {
                    if ($row["author"] == $loggedUser) {
                        // my messages
                        if ($row["unread"]) {
                            // unread messages
                            if ($row["image"] == "") echo "<div class=\"message me\">".$replyDOM.$row["text"]."<span class=\"isUnread fa fa-circle\"><span class=\"encrypted fa fa-lock\"></span></span><span class=\"time\">".$formattedTime."</span></div><span data-id=\"".$row["id"]."\" class=\"replyButton fa fa-reply\" style=\"float: right\"></span><span class=\"clearfix\"></span>";
                            else echo "<div class=\"message me\">".$replyDOM.$row["text"]."<img class=\"messageImage\" src=\"".$row["image"]."\"><span class=\"isUnread fa fa-circle\"><span class=\"encrypted fa fa-lock\"></span></span><span class=\"time\">".$formattedTime."</span></div><span data-id=\"".$row["id"]."\" class=\"replyButton fa fa-reply\" style=\"float: right\"></span><span class=\"clearfix\"></span>";
                        } else {
                            // read messages
                            if ($row["image"] == "") echo "<div class=\"message me\">".$replyDOM.$row["text"]."<span class=\"isUnread fa fa-check-circle\"><span class=\"encrypted fa fa-lock\"></span></span><span class=\"time\">".$formattedTime."</span></div><span data-id=\"".$row["id"]."\" class=\"replyButton fa fa-reply\" style=\"float: right\"></span><span class=\"clearfix\"></span>";
                            else echo "<div class=\"message me\">".$replyDOM.$row["text"]."<img class=\"messageImage\" src=\"".$row["image"]."\"><span class=\"isUnread fa fa-check-circle\"><span class=\"encrypted fa fa-lock\"></span></span><span class=\"time\">".$formattedTime."</span></div><span data-id=\"".$row["id"]."\" class=\"replyButton fa fa-reply\" style=\"float: right\"></span><span class=\"clearfix\"></span>";
                        }
                    } else if ($row["author"] == $chatUser) {
                        // interlocutor messages
                        if ($row["unread"]) {
                            // unread messages
                            if ($row["image"] == "") echo "<div class=\"message\">".$replyDOM.$row["text"]."<span class=\"isUnread fa fa-circle\"><span class=\"encrypted fa fa-lock\"></span></span><span class=\"time\">".$formattedTime."</span></div><span data-id=\"".$row["id"]."\" class=\"replyButton fa fa-reply\" style=\"float: left\"></span><span class=\"clearfix\"></span>";
                            else echo "<div class=\"message\">".$replyDOM.$row["text"]."<img class=\"messageImage\" src=\"".$row["image"]."\"><span class=\"isUnread fa fa-circle\"><span class=\"encrypted fa fa-lock\"></span><span class=\"time\">".$formattedTime."</span></div><span data-id=\"".$row["id"]."\" class=\"replyButton fa fa-reply\" style=\"float: left\"></span><span class=\"clearfix\"></span>";
                        } else {
                            // read messages
                            if ($row["image"] == "") echo "<div class=\"message\">".$replyDOM.$row["text"]."<span class=\"isUnread fa fa-check-circle\"><span class=\"encrypted fa fa-lock\"></span></span><span class=\"time\">".$formattedTime."</span></div><span data-id=\"".$row["id"]."\" class=\"replyButton fa fa-reply\" style=\"float: left\"></span><span class=\"clearfix\"></span>";
                            else echo "<div class=\"message\">".$replyDOM.$row["text"]."<img class=\"messageImage\" src=\"".$row["image"]."\"><span class=\"isUnread fa fa-check-circle\"><span class=\"encrypted fa fa-lock\"></span></span><span class=\"time\">".$formattedTime."</span></div><span data-id=\"".$row["id"]."\" class=\"replyButton fa fa-reply\" style=\"float: left\"></span><span class=\"clearfix\"></span>";
                        }
                    } else {
                        return;
                    }
                } else {
                    if ($row["author"] == $loggedUser) {
                        // my messages
                        if ($row["unread"]) {
                            // unread messages
                            if ($row["image"] == "") echo "<div class=\"message me\">".$replyDOM.$row["text"]."<span class=\"isUnread fa fa-circle\"><span class=\"encrypted fa fa-unlock\"></span></span><span class=\"time\">".$formattedTime."</span></div><span data-id=\"".$row["id"]."\" class=\"replyButton fa fa-reply\" style=\"float: right\"></span><span class=\"clearfix\"></span>";
                            else echo "<div class=\"message me\">".$replyDOM.$row["text"]."<img class=\"messageImage\" src=\"".$row["image"]."\"><span class=\"isUnread fa fa-circle\"><span class=\"encrypted fa fa-unlock\"></span></span><span class=\"time\">".$formattedTime."</span></div><span data-id=\"".$row["id"]."\" class=\"replyButton fa fa-reply\" style=\"float: right\"></span><span class=\"clearfix\"></span>";
                        } else {
                            // read messages
                            if ($row["image"] == "") echo "<div class=\"message me\">".$replyDOM.$row["text"]."<span class=\"isUnread fa fa-check-circle\"><span class=\"encrypted fa fa-unlock\"></span></span><span class=\"time\">".$formattedTime."</span></div><span data-id=\"".$row["id"]."\" class=\"replyButton fa fa-reply\" style=\"float: right\"></span><span class=\"clearfix\"></span>";
                            else echo "<div class=\"message me\">".$replyDOM.$row["text"]."<img class=\"messageImage\" src=\"".$row["image"]."\"><span class=\"isUnread fa fa-check-circle\"><span class=\"encrypted fa fa-unlock\"></span></span><span class=\"time\">".$formattedTime."</span></div><span data-id=\"".$row["id"]."\" class=\"replyButton fa fa-reply\" style=\"float: right\"></span><span class=\"clearfix\"></span>";
                        }
                    } else if ($row["author"] == $chatUser) {
                        // interlocutor messages
                        if ($row["unread"]) {
                            // unread messages
                            if ($row["image"] == "") echo "<div class=\"message\">".$replyDOM.$row["text"]."<span class=\"isUnread fa fa-circle\"><span class=\"encrypted fa fa-unlock\"></span></span><span class=\"time\">".$formattedTime."</span></div><span data-id=\"".$row["id"]."\" class=\"replyButton fa fa-reply\" style=\"float: left\"></span><span class=\"clearfix\"></span>";
                            else echo "<div class=\"message\">".$replyDOM.$row["text"]."<img class=\"messageImage\" src=\"".$row["image"]."\"><span class=\"isUnread fa fa-circle\"><span class=\"encrypted fa fa-unlock\"></span><span class=\"time\">".$formattedTime."</span></div><span data-id=\"".$row["id"]."\" class=\"replyButton fa fa-reply\" style=\"float: left\"></span><span class=\"clearfix\"></span>";
                        } else {
                            // read messages
                            if ($row["image"] == "") echo "<div class=\"message\">".$replyDOM.$row["text"]."<span class=\"isUnread fa fa-check-circle\"><span class=\"encrypted fa fa-unlock\"></span></span><span class=\"time\">".$formattedTime."</span></div><span data-id=\"".$row["id"]."\" class=\"replyButton fa fa-reply\" style=\"float: left\"></span><span class=\"clearfix\"></span>";
                            else echo "<div class=\"message\">".$replyDOM.$row["text"]."<img class=\"messageImage\" src=\"".$row["image"]."\"><span class=\"isUnread fa fa-check-circle\"><span class=\"encrypted fa fa-unlock\"></span></span><span class=\"time\">".$formattedTime."</span></div><span data-id=\"".$row["id"]."\" class=\"replyButton fa fa-reply\" style=\"float: left\"></span><span class=\"clearfix\"></span>";
                        }
                    } else {
                        return;
                    }
                }
            } else {
                return;
            }
        }

        $checkedInterlocutor = $conn->real_escape_string($interlocutor);
        $readRequest = "UPDATE messages SET unread='0' WHERE query='$checkedQuery' AND author='$checkedInterlocutor'";
        $conn->query($readRequest);
    } else {
        echo "<p class=\"console\">Нет сообщений<p>";
    }

    $conn->close();
}

function sendMessage($token, $currentChat, $messageText, $messageImage, $replyTo)
{
    include 'credentials.php';
    include 'api/prepareText.php';
    $crypto = new ExpoCrypto();
    $key = pack("H*", $keyhash);

    $loggedUser = getIdByToken($token);
    $query = "";
    $timestamp = time();
    $targetToken = "";

    if (userExists($currentChat)) {
        $senderName = getNameById($loggedUser);
        $targetToken = getPushTokenById($currentChat);

        if ($loggedUser < $currentChat) {
            $query = "M".$loggedUser."M".$currentChat."M";
            $chat = $loggedUser."-".$currentChat;
        } else {
            $query = "M".$currentChat."M".$loggedUser."M";
            $chat = $currentChat."-".$loggedUser;
        }

        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            die("Ошибка подключения: не получается отправить сообщение. Код ошибки для поддержки: 130");
        }

        $checkedQuery = $conn->real_escape_string($query);
        $checkedText = $conn->real_escape_string($messageText);
        $checkedImage = $conn->real_escape_string($messageImage);
        $checkedReplyTo = $conn->real_escape_string($replyTo);

        $encodedText = $crypto->encode($checkedText, $key.$chat);
        $checkedText = "NE5[".$encodedText."]";

        if ($replyTo == "") {
            $sql = "INSERT INTO messages (query, author, text, image, timestamp, unread)
            VALUES ('$checkedQuery', '$loggedUser', '$checkedText', '$checkedImage', '$timestamp', '1')";
        } else {
            $sql = "INSERT INTO messages (query, author, text, image, timestamp, unread, reply)
            VALUES ('$checkedQuery', '$loggedUser', '$checkedText', '$checkedImage', '$timestamp', '1', '$checkedReplyTo')";
        }

        if ($conn->query($sql) === TRUE) {
            updateOnline($token);

            $pushHeader = $senderName;
            if ($messageText != "") {
                $processedText = prepareText($messageText);
                $pushBody = $processedText;
            } else {
                $pushBody = "(изображение)";
            }
            $pushData = "{\"url\": \"/(tabs)/messages/chat?id=".$loggedUser."&name=".$senderName."\"}";

            if ($targetToken != "" && $targetToken != NULL) {
                $ch = curl_init("https://exp.host/--/api/v2/push/send");
                $payload = json_encode(array("to" => $targetToken, "title" => $pushHeader, "body" => $pushBody, "data" => $pushData));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $result = curl_exec($ch);
                curl_close($ch);
            }

            return;
        } else {
            return "Ошибка базы данных: невозможно отправить сообщение. Код ошибки для поддержки: 131";
        }

        $conn->close();
    } else {
        echo "<script>alert('Панамарь, опять балуешься?');</script>";
    }
}

function lastId($loggedUser)
{
    include 'credentials.php';

    $signature = "M".$loggedUser."M";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается получить ID сообщения. Код ошибки для поддержки: 132");
    }

    $checkedSignature = $conn->real_escape_string($signature);
    $sql = "SELECT query, MAX(id) FROM messages WHERE query LIKE '%$checkedSignature%' GROUP BY query ORDER BY MAX(id) DESC LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        $interlocutor = str_replace($signature, "", $row["0"]);
        $interlocutor = str_replace("M", "", $interlocutor);
        return $interlocutor;
    } else {
        return 0;
    }

    $conn->close();
}

function getDiscussions($query)
{
    include 'credentials.php';

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается запросить обсуждения. Код ошибки для поддержки: 134");
    }

    $checkedQuery = $conn->real_escape_string($query);
    $sql = "SELECT id, topic, author, message, time FROM discussions
    WHERE header='1' AND topic LIKE '%$checkedQuery%'
    ORDER BY activity DESC LIMIT 500";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $formattedTime = date("d.m.Y в h:i a", $row["time"]);
            $user = getNameById($row["author"]);
            echo "<tr style=\"padding-bottom: 100px\"> <td style=\"text-align: left; width: 100%\"><span style=\"color: lightslategray\">Тема: </span>".$row["topic"]."<br><span class=\"discInfo\">Автор: ".$user."</span><span class=\"discInfo mobile-hidden\">Создано: ".$formattedTime."</span></td><td><button class=\"button a\" data-href=\"topic.php?id=".$row["id"]."\" style=\"float: right;white-space: nowrap;\">Открыть обсуждение</button></td></tr>";
        }
    } else {
        echo "<p class=\"console\">Обсуждений нет<p>";
    }

    $conn->close();
}

function getTopicHeader($headerId)
{
    include 'credentials.php';

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается запросить обсуждение. Код ошибки для поддержки: 135");
    }

    $checkedHeaderId = $conn->real_escape_string($headerId);
    $sql = "SELECT topic, author, message, time, header FROM discussions WHERE id='$checkedHeaderId' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        if ($row["4"]) {
            return $row;
        } else {
            header("Refresh:0; url=discussions.php");
        }
    } else {
        header("Refresh:0; url=discussions.php");
    }
}

function getTopicMessages($header)
{
    include 'credentials.php';

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается запросить сообщения в обсуждении. Код ошибки для поддержки: 136");
    }

    $checkedHeader = $conn->real_escape_string($header);
    $sql = "SELECT author, message, time, header FROM discussions WHERE topic='$checkedHeader' ORDER BY id DESC LIMIT 500";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if ($row["header"] == false) {
                $formattedName = getNameById($row["author"]);
                $formattedTime = date("d.m.Y в h:i a", $row["time"]);
                echo "<tr><td class=\"e\"><span>".$formattedTime."</span></td><td style=\"text-align:left\"><a href=\"#\"><span>".$formattedName."</a>: ".$row["message"]."</span></td></tr>";
            }
        }
    } else {
        echo "<p class=\"console\">Ответов нет<p>";
    }

    $conn->close();
}

function sendReply($topic, $who, $what)
{
    include 'credentials.php';

    $loggedUser = getIdByToken($who);
    $when = time();

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается отправить ответ на обсуждение. Код ошибки для поддержки: 137");
    }

    $checkedTopic = $conn->real_escape_string($topic);
    $checkedWhat = $conn->real_escape_string($what);
    $sql = "INSERT INTO discussions (topic, author, message, time, header, activity)
        VALUES ('$checkedTopic', '$loggedUser', '$checkedWhat', '$when', '0', $when)";

    if ($conn->query($sql) === TRUE) {
        $sqlGetHeader = "SELECT id FROM discussions WHERE topic='$checkedTopic' ORDER BY id ASC LIMIT 1";
        $resultHeader = $conn->query($sqlGetHeader);
        if ($resultHeader->num_rows > 0) {
            $headerRow = $resultHeader->fetch_row();
            $headerID = $headerRow["0"];
            $sqlUpdateHeader = "UPDATE discussions SET activity='$when' WHERE id='$headerID'";
            if ($conn->query($sqlUpdateHeader) === TRUE) {
                return;
            } else {
                return "Ошибка базы данных: невозможно обновить активити обсуждения. Код ошибки для поддержки: 142";
            }
        } else {
            return "Ошибка базы данных: невозможно найти хедер обсуждения. Код ошибки для поддержки: 141";
        }
        return;
    } else {
        return "Ошибка базы данных: невозможно отправить ответ на обсуждение. Код ошибки для поддержки: 138";
    }

    $conn->close();
}

function createTopic($token, $name, $message)
{
    include 'credentials.php';

    $loggedUser = getIdByToken($token);
    $when = time();

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается создать обсуждение. Код ошибки для поддержки: 139");
    }

    $checkedName = $conn->real_escape_string($name);
    $checkedMessage = $conn->real_escape_string($message);
    $sql = "INSERT INTO discussions (topic, author, message, time, header, activity)
        VALUES ('$checkedName', '$loggedUser', '$checkedMessage', '$when', '1', $when)";

    if ($conn->query($sql) === TRUE) {
        return;
    } else {
        return "Ошибка базы данных: невозможно создать обсуждение. Код ошибки для поддержки: 140";
    }

    $conn->close();
}

function allowEmail($login)
{
    include 'credentials.php';

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается получить имя пользователя. Код ошибки для поддержки: 143");
    }

    $checkedLogin = $conn->real_escape_string($login);
    $sql = "SELECT login FROM users WHERE login='$checkedLogin' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        return false;
    } else {
        return true;
    }

    $conn->close();
}

function newMessageAvailable($token)
{
    include 'credentials.php';

    // change timestamp logic to unread logic
    $checkedUser = getIdByToken($token);
    $signature = "M".$checkedUser."M";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается показать таблицу чатов. Код ошибки для поддержки: 149");
    }

    $checkedSignature = $conn->real_escape_string($signature);
    $sql = "SELECT query, MAX(id) FROM messages WHERE query LIKE '%$checkedSignature%' GROUP BY query ORDER BY MAX(id) DESC LIMIT 75";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $unreadCounter = 0;
        $notifArrayString = "[";

        while ($row = $result->fetch_assoc()) {
            $interlocutor = str_replace($signature, "", $row["query"]);
            $interlocutor = str_replace("M", "", $interlocutor);
            $lastTextId = $row['MAX(id)'];

            $getLastText = "SELECT unread, author, text FROM messages WHERE id='$lastTextId' LIMIT 1";
            $lastTextResult = $conn->query($getLastText);
            $lastTextRow = $lastTextResult->fetch_row();

            $isUnread = $lastTextRow[0];
            $author = $lastTextRow[1];
            $text = $lastTextRow[2];



            if ($isUnread == 1 && $author != $checkedUser) {
                $name = getNameById($author);
                if ($text == "") $text = "изображение";
                $unreadCounter = $unreadCounter + 1;
                $notifArrayString = $notifArrayString."{\"author\": \"".$name."\", \"message\": \"".$text."\", \"id\": \"".$author."\"}, ";
            }
        }

        $notifArrayString = $notifArrayString."{}]";
        echo "{\"counter\": ".$unreadCounter.", \"notifications\": ".$notifArrayString."}";
    } else {
        echo "{\"counter\": \"0\"}";
    }

    $conn->close();
}

function userExists($id)
{
    include 'credentials.php';

    $conn = new mysqli($servername, $username, $password, $dbname);
    $checkedId = $conn->real_escape_string(validate(intval($id)));
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается проверить наличие пользователя. Код ошибки для поддержки: 145");
    }

    $sql = "SELECT id FROM users WHERE id='$checkedId' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        return true;
    } else {
        return false;
    }

    $conn->close();
}

function getPushTokenById($id)
{
    include 'credentials.php';

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается проверить наличие пользователя. Код ошибки для поддержки: 146");
    }

    $checkedId = $conn->real_escape_string(validate(intval($id)));
    $sql = "SELECT expo_push_token FROM users WHERE id='$checkedId' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        $pushToken = $row[0];
        return $pushToken;
    } else {
        return "";
    }

    $conn->close();
}

function likePost($token, $postId)
{
    include 'credentials.php';

    $loggedUser = getIdByToken($token);
    $status = "not_processed";

    $conn = new mysqli($servername, $username, $password, $dbname);
    $checkedId = $conn->real_escape_string(validate(intval($postId)));
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается лайкнуть пост. Код ошибки для поддержки: 150");
    }

    $sql = "SELECT likes FROM walls WHERE id='$checkedId' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        $likes = $row[0];
        $likesPHPArray = json_decode($likes);

        if (in_array($loggedUser, $likesPHPArray)) {
            // remove like
            $status = 0;
            $key = array_search($loggedUser, $likesPHPArray);
            unset($likesPHPArray[$key]);
            $likesPHPArray = array_values($likesPHPArray);
        } else {
            // like post
            $status = 1;
            $likesPHPArray[] = $loggedUser;
        }

        $updatedLikes = json_encode($likesPHPArray, JSON_NUMERIC_CHECK);

        $sql = "UPDATE walls SET likes='$updatedLikes' WHERE id='$checkedId'";
        if ($conn->query($sql) === TRUE) {
            echo $status;
        }
    }
}

function getVoxCredentials($token)
{
    include 'credentials.php';

    $checkedToken = validate($token);

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения: не получается получить данные. Код ошибки для поддержки: 151");
    }

    $checkedToken = $conn->real_escape_string($checkedToken);
    $sql = "SELECT login FROM users WHERE token='$checkedToken' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        $voxUsername = str_replace("@", "-", $row[0])."@nevka.avicus.n4.voximplant.com";
        $data = [$voxUsername, "PASTE_VOX_PASSWORD_HERE"];
        echo json_encode($data);
    } else {
        return "";
    }

    $conn->close();
}
