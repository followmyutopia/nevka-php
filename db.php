<?php
/**
 * Nevka Project v1.6 Database inquires
 * 2025 Avicus Delacroix
 * 
 * Databases and types (MySQL):
 * Users:       id (int), login (varchar-63), password (varchar-40), token (varchar-127), avatar (mediumblob), firstname (varchar-63), lastname (varchar-63), status (varchar-127), city (varchar-63), bio (varchar-255), timestamp (int), search (varchar-127), is_banned (tinyint-1), is_admin (tinyint-1)
 * Walls:       id (int), user (int), text (varchar-1023), image (mediumblob), timestamp (int)
 * Messages:    id (int), query (varchar-25), author (int), text (varchar-1023), image (mediumblob), timestamp (int)
 * Discussions: id (int), topic (varchar-255), author (int), message (varchar-1023), time (int), header (tinyint-1), activity (int)
 */

date_default_timezone_set("Europe/Moscow");
error_reporting(0);

function validate($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function getNameById($id) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается получить имя пользователя. Код ошибки для поддержки: 133");}

    $sql = "SELECT firstname, lastname FROM users WHERE id='$id' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        return $row[0]." ".$row[1];
    } else {
        return "Неизвестный пользователь";
    }

    $conn->close();
}

function getIdByToken($token) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается получить ID. Код ошибки для поддержки: 101");}
    
    $sql = "SELECT id FROM users WHERE token='$token' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        return $row[0];
    } else {
        logout();
    }

    $conn->close();
}

function login($login,$hashedPassword) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается залогиниться. Код ошибки для поддержки: 102");}

    $sql = "SELECT id, login, password, token, is_banned FROM users WHERE login='$login' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        if ($hashedPassword == $row[2] && !$row[4]) {
            setcookie("user_token", $row[3], time() + (86400 * 30), "/");
            header("Refresh:0; url=profile.php");
        }
        else if ($row[4]) { return "Пользователь $login заблокирован модератором"; }
        else { return "Неправильный пароль"; }
    } else {
        return "Пользователь $login не найден";
    }

    $conn->close();
}

function logout() {
    setcookie("user_token", "", time() - 3600);
    header("Refresh:0");
}

function checkCookie($token) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается проверить куки. Код ошибки для поддержки: 103");}

    $sql = "SELECT id, is_banned FROM users WHERE token='$token' LIMIT 1";
    $result = $conn->query($sql);

    $conn->close();

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        if (!$row[1]) { return $row[0]; }
        else { logout(); }
    } else {
        logout();
    }
}

function changePassword($loggedUser,$token,$oldPassword,$newPassword) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname
    $newToken = "User".$loggedUser."-".$newPassword;

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается сменить пароль. Код ошибки для поддержки: 104");}
    
    $sql = "SELECT password FROM users WHERE token='$token' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        if ($oldPassword == $row[0]) {
            // approved sorting by id, user checked
            $security = "UPDATE users SET password='$newPassword', token='$newToken' WHERE id='$loggedUser'";

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
}

function getUserData($userId) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается получить данные пользователя. Код ошибки для поддержки: 106");}

    $sql = "SELECT avatar, firstname, lastname, status, city, bio, timestamp FROM users WHERE id='$userId' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        // compatibility with the v1.4 database format
        // this is done for removing support for storing string dates in the database
        $formattedTime = date("d.m.Y в h:i a", $row[6]);
        $row[6] = $formattedTime;

        return $row;
    } else {
        header("Refresh:0; url=profile.php");
    }

    $conn->close();
}

function setAvatar($token,$blob) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается установить аватар. Код ошибки для поддержки: 107");}

    $sql = "UPDATE users SET avatar='$blob' WHERE token='$token'";

    if ($conn->query($sql) === TRUE) {
        header("Refresh:0");
    } else {
        return "Ошибка базы данных: невозможно обновить аватар. Код ошибки для поддержки: 108";
    }

    $conn->close();
}

function setName($token,$newFirstName,$newLastName) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $query = $newFirstName." ".$newLastName;

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается установить имя. Код ошибки для поддержки: 109");}

    $sql = "UPDATE users SET firstname='$newFirstName', lastname='$newLastName', search='$query' WHERE token='$token'";

    if ($conn->query($sql) === TRUE) {
        return "Имя успешно установлено";
    } else {
        return "Ошибка базы данных: невозможно обновить имя. Код ошибки для поддержки: 110";
    }

    $conn->close();
}

function setCity($token,$newCity) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается установить город. Код ошибки для поддержки: 111");}

    $sql = "UPDATE users SET city='$newCity' WHERE token='$token'";

    if ($conn->query($sql) === TRUE) {
        return "Город успешно установлен";
    } else {
        return "Ошибка базы данных: невозможно обновить город. Код ошибки для поддержки: 112";
    }

    $conn->close();
}

function setBio($token,$newBio) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается установить информацию. Код ошибки для поддержки: 113");}

    $sql = "UPDATE users SET bio='$newBio' WHERE token='$token'";

    if ($conn->query($sql) === TRUE) {
        return "Описание успешно установлено";
    } else {
        return "Ошибка базы данных: невозможно обновить информацию. Код ошибки для поддержки: 114";
    }

    $conn->close();
}

function setStatus($token,$newStatus) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка. Код ошибки для поддержки: 115");}

    $sql = "UPDATE users SET status='$newStatus' WHERE token='$token'";

    if ($conn->query($sql) === TRUE) {
        return $newStatus;
    } else {
        return "Ошибка базы данных: невозможно обновить статус. Код ошибки для поддержки: 116";
    }

    $conn->close();
}

function updateOnline($token) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname
    
    $timestamp = time();

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается обновить онлайн. Код ошибки для поддержки: 117");}

    $sql = "UPDATE users SET timestamp='$timestamp' WHERE token='$token'";

    if ($conn->query($sql) === TRUE) {
        return;
    } else {
        return "Ошибка базы данных: невозможно обновить онлайн. Код ошибки для поддержки: 118";
    }

    $conn->close();
}

function addWallEntry($token, $wallText, $wallImage) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $loggedUser = getIdByToken($token);
    $timestamp = time();

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается опубликовать стену. Код ошибки для поддержки: 119");}

    $sql = "INSERT INTO walls (user, text, image, timestamp)
        VALUES ('$loggedUser', '$wallText', '$wallImage', '$timestamp')";

    if ($conn->query($sql) === TRUE) {
        header("Refresh:0");
    } else {
        return "Ошибка базы данных: невозможно опубликовать запись на стене. Код ошибки для поддержки: 120";
    }

    $conn->close();
}

function deleteWallEntry($token, $entryId) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $checkedUser = getIdByToken($token);

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается удалить со стены. Код ошибки для поддержки: 121");}

    $sql = "SELECT user FROM walls WHERE id='$entryId' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        if ($checkedUser == $row[0]) {
            // approved sorting by id, user checked
            $del = "DELETE FROM walls WHERE id='$entryId'";
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

function getWallEntries($loggedUser) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается получить стену. Код ошибки для поддержки: 123");}

    $sql = "SELECT id, user, text, image, timestamp FROM walls WHERE user='$loggedUser' ORDER BY id DESC LIMIT 100";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $authorName = getNameById($row["user"]);
            // compatibility with the v1.4 database format
            $formattedTime = date("d.m.Y в h:i a", $row["timestamp"]);
            if ($row["image"] != "") {
                echo "<div class=\"wallPost\"><p>".$formattedTime." <a href=\"profile.php?id=".$loggedUser.'">'.$authorName.'</a> написал(а):<span id="'.$row["id"].'" class="delPostButton">×</span></p><img class="postImage" src="'.$row["image"]."\"><p>".$row["text"]."</p></div>";
            } else {
                echo "<div class=\"wallPost\"><p>".$formattedTime." <a href=\"profile.php?id=".$loggedUser.'">'.$authorName.'</a> написал(а):<span id="'.$row["id"].'" class="delPostButton">×</span></p><p>'.$row["text"]."</p></div>";
            }
        }
    } else {
        echo "<p class=\"console\">Записей на стене нет<p>";
    }

    $conn->close();
}

function signUp($newLogin,$hashedPassword) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $newToken = $newLogin.$hashedPassword;

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается зарегистрироваться. Код ошибки для поддержки: 124");}

    $sql = "SELECT id FROM users WHERE login='$newLogin' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        return "Логин занят";
    } else {
        $sql = "INSERT INTO users (login, password, token)
        VALUES ('$newLogin', '$hashedPassword', '$newToken')";

        if ($conn->query($sql) === TRUE) {
            return "Новый пользователь успешно создан!";
        } else {
            return "Ошибка создания пользователя! Код ошибки для поддержки: 125<script>console.log($sql $conn->error)</script>";
        }
    }

    $conn->close();
}

function showUsersTable($query) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается показать таблицу пользователей. Код ошибки для поддержки: 126");}

    $sql = "SELECT id, avatar, firstname, lastname, timestamp, search FROM users
    WHERE (firstname LIKE '%$query%' OR lastname LIKE '%$query%' OR search LIKE '%$query%') AND (NOT is_banned OR is_banned IS NULL)
    ORDER BY timestamp DESC LIMIT 500";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // compatibility with the v1.4 database format
            $formattedTime = date("d.m.Y в h:i a", $row["timestamp"]);
            $online = "";
            $lastOnline = date_create_from_format("d.m.Y в h:i a", $formattedTime); $now = new DateTime();
            $difference = ($lastOnline->diff($now)->days * 1440) + ($lastOnline->diff($now)->h * 60) + $lastOnline->diff($now)->i;
            if ($difference <= 5) { $online = "<span style=\"color:greenyellow\">Онлайн</span>"; }
            else if ($difference < 60) { $online = "<span style=\"color:lightslategray\">Был(а) в сети: ".$lastOnline->diff($now)->i." минут назад</span>"; }
            else { $online = "<span style=\"color:lightslategray\">Был(а) в сети: ".$formattedTime."</span>"; }
            echo "<tr><td class=\"a b\" data-href=\"profile.php?id=".$row["id"]."\"><img class=\"thumbnail\" src=\"".$row["avatar"]."\"></td><td class=\"a\" data-href=\"profile.php?id=".$row["id"]."\">".$row["firstname"]." ".$row["lastname"]."<br>".$online."</td><td class=\"a b\" data-href=\"messages.php?id=".$row["id"]."\"><button class=\"button\">Написать сообщение</button></td></tr>";
        }
    } else {
        echo "<tr><td>Никого не найдено</td></tr>";
    }

    $conn->close();
}

function showChatsTable($token) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $checkedUser = getIdByToken($token);
    $signature = "M".$checkedUser."M";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается показать таблицу чатов. Код ошибки для поддержки: 127");}

    $sql = "SELECT query, MAX(id) FROM messages WHERE query LIKE '%$signature%' GROUP BY query ORDER BY MAX(id) DESC LIMIT 75";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $interlocutor = str_replace($signature, "", $row["query"]);
            $interlocutor = str_replace("M", "", $interlocutor);

            // approved sorting by id, user checked
            $getName = "SELECT id, avatar, firstname, lastname, timestamp FROM users WHERE id='$interlocutor' LIMIT 500";
            $entryResult = $conn->query($getName);
            $row = $entryResult->fetch_row();
            // compatibility with the v1.4 database format
            $formattedTime = date("d.m.Y в h:i a", $row["4"]);
            $online = "";
            $lastOnline = date_create_from_format("d.m.Y в h:i a", $formattedTime); $now = new DateTime();
            $difference = ($lastOnline->diff($now)->days * 1440) + ($lastOnline->diff($now)->h * 60) + $lastOnline->diff($now)->i;
            $shortDate = str_replace(".".$lastOnline->format("Y"), "", $formattedTime);
            if ($difference <= 5) { $online = "<span style=\"color:greenyellow\">Онлайн</span>"; }
            else if ($difference < 60) { $online = "<span style=\"color:lightslategray\">Был(а): ".$lastOnline->diff($now)->i." минут назад</span>"; }
            else { $online = "<span style=\"color:lightslategray\">Был(а): ".$shortDate."</span>"; }
            echo "<tr><td class=\"a c\" data-href=\"messages.php?id=".$row["0"]."\"><img class=\"thumbnail\" src=\"".$row["1"]."\"></td><td class=\"a d\" data-href=\"messages.php?id=".$row["0"]."\">".$row["2"]." ".$row["3"]."<br>".$online."</td></tr>";
        }
    } else {
        echo "<p class=\"console\">Нет чатов.</p><p id=\"pplNotice\" class=\"console\">Перейдите в раздел <a href=\"people.php\">Люди</a> для выбора собеседника.</p>";
    }

    $conn->close();
}

function currentUserHeader($userId) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается отобразить заголовок чата. Код ошибки для поддержки: 128");}

    // approved sorting by id, public data
    $sql = "SELECT firstname, lastname, timestamp FROM users WHERE id='$userId' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        // compatibility with the v1.4 database format
        $formattedTime = date("d.m.Y в h:i a", $row["2"]);
        $online = "";
        $lastOnline = date_create_from_format("d.m.Y в h:i a", $formattedTime); $now = new DateTime();
        $difference = ($lastOnline->diff($now)->days * 1440) + ($lastOnline->diff($now)->h * 60) + $lastOnline->diff($now)->i;
        if ($difference <= 5) { $online = "<span style=\"color:greenyellow\">Онлайн</span>"; }
        else if ($difference < 60) { $online = "<span style=\"color:lightslategray\">Был(а): ".$lastOnline->diff($now)->i." минут назад</span>"; }
        else { $online = "<span style=\"color:lightslategray\">Был(а): ".$formattedTime."</span>"; }
        echo "<p class=\"messagesHeader\"><a id=\"msgBack\" href=\"messages.php\">← Назад</a><b><a href=\"profile.php?id=".$userId."\">".$row["0"]." ".$row["1"]."</a></b><br>".$online."</p>";
    } else {
        return;
    }

    $conn->close();
}

function getMessages($token,$chatUser) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $query = "";
    $loggedUser = getIdByToken($token);
    if ($loggedUser < $chatUser) {
        $query = "M".$loggedUser."M".$chatUser."M";
    } else {
        $query = "M".$chatUser."M".$loggedUser."M";
    }
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается отобразить сообщения. Код ошибки для поддержки: 129");}

    $sql = "SELECT * FROM (SELECT id, query, author, text, image, timestamp FROM messages WHERE query='$query' ORDER BY id DESC LIMIT 75) X ORDER BY id ASC;";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $interlocutor = str_replace("M".$loggedUser."M", "", $row["query"]);
            $interlocutor = str_replace("M", "", $interlocutor);
            // compatibility with the v1.4 database format
            $formattedTime = date("d.m в h:i a", $row["timestamp"]);

            if ($chatUser == $interlocutor) {
                if ($row["author"] == $loggedUser) {
                    if ($row["image"] == "") {
                        echo "<div class=\"message me\">".$row["text"]."<span class=\"time\">".$formattedTime."</span></div><span class=\"clearfix\"></span>";
                    } else {
                        echo "<div class=\"message me\">".$row["text"]."<img class=\"messageImage\" src=\"".$row["image"]."\"><span class=\"time\">".$formattedTime."</span></div><span class=\"clearfix\"></span>";
                    }
                } else if ($row["author"] == $chatUser) {
                    if ($row["image"] == "") {
                        echo "<div class=\"message\">".$row["text"]."<span class=\"time\">".$formattedTime."</span></div><span class=\"clearfix\"></span>";
                    } else {
                        echo "<div class=\"message\">".$row["text"]."<img class=\"messageImage\" src=\"".$row["image"]."\"><span class=\"time\">".$formattedTime."</span></div><span class=\"clearfix\"></span>";
                    }
                } else { return; }
            } else { return; }
        }
    } else {
        echo "<p class=\"console\">Нет сообщений<p>";
    }
}

function sendMessage($token, $currentChat, $messageText, $messageImage) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $loggedUser = getIdByToken($token);
    $query = "";
    $timestamp = time();

    if (userExists($currentChat)) {
        if ($loggedUser < $currentChat) {
            $query = "M".$loggedUser."M".$currentChat."M";
        } else {
            $query = "M".$currentChat."M".$loggedUser."M";
        }
    
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) { die("Ошибка подключения: не получается отправить сообщение. Код ошибки для поддержки: 130");}
    
        $sql = "INSERT INTO messages (query, author, text, image, timestamp)
            VALUES ('$query', '$loggedUser', '$messageText', '$messageImage', '$timestamp')";
    
        if ($conn->query($sql) === TRUE) {
            updateOnline($token);
            return;
        } else {
            return "Ошибка базы данных: невозможно отправить сообщение. Код ошибки для поддержки: 131";
        }
    
        $conn->close();
    } else {
        echo "<script>alert('Панамарь, опять балуешься?');</script>";
    }
}

function lastId($loggedUser) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $signature = "M".$loggedUser."M";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается получить ID сообщения. Код ошибки для поддержки: 132");}

    $sql = "SELECT query, MAX(id) FROM messages WHERE query LIKE '%$signature%' GROUP BY query ORDER BY MAX(id) DESC LIMIT 1";
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

function getDiscussions($query) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается запросить обсуждения. Код ошибки для поддержки: 134");}

    $sql = "SELECT id, topic, author, message, time FROM discussions
    WHERE header='1' AND topic LIKE '%$query%'
    ORDER BY activity DESC LIMIT 500";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $formattedTime = date("d.m.Y в h:i a", $row["time"]);
            $user = getNameById($row["author"]);
            echo "<tr style=\"padding-bottom: 100px\"> <td style=\"text-align: left; width: 100%\"><span style=\"color: lightslategray\">Тема: </span>".$row["topic"]."<br><span class=\"discInfo\">Автор: ".$user."</span><span class=\"discInfo mobile-hidden\">Создано: ".$formattedTime."</span></td><td><button class=\"button a\" data-href=\"topic.php?id=".$row["id"]."\" style=\"float: right;white-space: nowrap;\">Открыть обсуждение</button></td></tr>";
        }
    } else {
        echo "<p class=\"console\">Обсуждений нет<p>";
    }

    $conn->close();
}

function getTopicHeader($headerId) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается запросить обсуждение. Код ошибки для поддержки: 135");}

    $sql = "SELECT topic, author, message, time, header FROM discussions WHERE id='$headerId' LIMIT 1";
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

function getTopicMessages($header) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается запросить сообщения в обсуждении. Код ошибки для поддержки: 136");}

    $sql = "SELECT author, message, time, header FROM discussions WHERE topic='$header' ORDER BY id DESC LIMIT 500";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
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

function sendReply($topic, $who, $what) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $loggedUser = getIdByToken($who);
    $when = time();

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается отправить ответ на обсуждение. Код ошибки для поддержки: 137");}

    $sql = "INSERT INTO discussions (topic, author, message, time, header, activity)
        VALUES ('$topic', '$loggedUser', '$what', '$when', '0', $when)";

    if ($conn->query($sql) === TRUE) {
        $sqlGetHeader = "SELECT id FROM discussions WHERE topic='$topic' ORDER BY id ASC LIMIT 1";
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

function createTopic($token, $name, $message) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $loggedUser = getIdByToken($token);
    $when = time();

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается создать обсуждение. Код ошибки для поддержки: 139");}

    $sql = "INSERT INTO discussions (topic, author, message, time, header, activity)
        VALUES ('$name', '$loggedUser', '$message', '$when', '1', $when)";

    if ($conn->query($sql) === TRUE) {
        return;
    } else {
        return "Ошибка базы данных: невозможно создать обсуждение. Код ошибки для поддержки: 140";
    }

    $conn->close();
}

function allowEmail($login) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается получить имя пользователя. Код ошибки для поддержки: 143");}

    $sql = "SELECT login FROM users WHERE login='$login' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        return false;
    } else {
        return true;
    }

    $conn->close();
}

function newMessageAvailable($id) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $lastonline = 0;
    $lastmessage = 0;
    $signature = "M".$id."M";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается проверить новые сообщения. Код ошибки для поддержки: 144");}

    $sql = "SELECT timestamp FROM users WHERE id='$id' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        $lastonline = $row["0"];

        $newSql = "SELECT count(timestamp) AS countNewMsg FROM messages WHERE query LIKE '%$signature%' AND timestamp > '$lastonline'
            ORDER BY id DESC";
        $newResult = $conn->query($newSql);
        $newRow = mysqli_fetch_assoc($newResult);
        echo $newRow["countNewMsg"];
    } else {
        echo "";
    }
}

function userExists($id) {
    include 'credentials.php'; // Contains 4 credentials: $servername, $username, $password, $dbname

    $checkedId = validate($id);

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Ошибка подключения: не получается проверить наличие пользователя. Код ошибки для поддержки: 145");}

    $sql = "SELECT id FROM users WHERE id='$checkedId' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        return true;
    } else {
        return false;
    }
}
?>