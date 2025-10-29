<?php
/* 
 * Nevka API 2.0
 * Avicus Delacroix
*/

if ($_POST['api-key'] !== "qonxod-Kicmad-4puvhy") {
    http_response_code(401);
    exit(json_encode(['result' => 'failure', 'error' => 'Invalid API key']));
}

date_default_timezone_set("Europe/Moscow");

function validate($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

if (!isset($_POST['id']) || !isset($_POST['token'])) {
    http_response_code(400);
    exit(json_encode(['result' => 'failure', 'error' => 'ID обязателен']));
}

$token = validate($_POST['token']);
$userId = validate($_POST['id']);
$timestamp = time();

include '../credentials.php';

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Combine both queries into one transaction
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("UPDATE users SET timestamp = ? WHERE token = ?");
    $stmt->execute([$timestamp, $token]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("wrong_token");
    }
    
    $stmt = $pdo->prepare("SELECT firstname, lastname, status, city, bio, 
        timestamp, avatar, id, expo_push_token, login 
        FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pdo->commit();
        
        $lastTime = (int)$row['timestamp'];
        $difference = time() - $lastTime;
        
        if ($difference <= 300) { // 5 minutes
            $online = "online";
        } elseif ($difference < 3600) { // 1 hour
            $online = floor($difference / 60) . " минут назад";
        } else {
            $online = date("d.m.Y в h:i a", $lastTime);
        }
        
        $response = [
            'result' => 'success',
            'id' => $row['id'],
            'firstname' => $row['firstname'] ?? '',
            'lastname' => $row['lastname'] ?? '',
            'status' => $row['status'] ?? '',
            'city' => $row['city'] ?? '',
            'bio' => $row['bio'] ?? '',
            'timestamp' => $row['timestamp'],
            'avatar' => $row['avatar'] ?? '',
            'online' => $online,
            'push_token' => $row['expo_push_token'] ?? '',
            'vox_username' => str_replace("@", "-", $row['login'])
        ];
        
        echo json_encode($response);
    } else {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['result' => 'failure', 'error' => 'Пользователь не найден']);
    }
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'result' => 'failure',
        'error' => $e->getMessage() === 'wrong_token' ? 'wrong_token' : 'Нет доступа к базе данных'
    ]);
}
