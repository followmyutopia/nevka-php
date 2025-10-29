<?php
/* 
 * Nevka API 2.0
 * Avicus Delacroix
*/

include '../lib/ImageResize.php';
use \Gumlet\ImageResize;

if ($_POST['api-key'] !== "qonxod-Kicmad-4puvhy") {
    http_response_code(401);
    exit(json_encode(['result' => 'failure', 'error' => 'Invalid API key']));
}

date_default_timezone_set("Europe/Moscow");

function validate($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

if (!isset($_POST['query'])) {
    http_response_code(400);
    exit(json_encode(['result' => 'failure', 'error' => 'Query обязателен']));
}

$query = validate($_POST['query']);
$safe = isset($_POST['safe']) ? validate($_POST['safe']) === 'true' : false;

if ($safe) {
    include 'safeFilter.php';
}

include '../credentials.php';

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $whereSafe = '';
    $params = ["%$query%", "%$query%", "%$query%"];
    
    if ($safe) {
        $whereSafe = "AND (NOT search LIKE ?) AND (NOT search LIKE ?) 
                      AND (NOT search LIKE ?) AND (NOT search LIKE ?)";
        $params = array_merge($params, ["%$trigger1%", "%$trigger2%", "%$trigger3%", "%$trigger4%"]);
    }
    
    $sql = "SELECT id, avatar, firstname, lastname, timestamp 
            FROM users 
            WHERE (firstname LIKE ? OR lastname LIKE ? OR search LIKE ?) 
            AND (NOT is_banned OR is_banned IS NULL) 
            $whereSafe 
            ORDER BY timestamp DESC LIMIT 500";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $people = [];
    $now = new DateTime();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $difference = time() - $row['timestamp'];
        
        if ($difference <= 300) { // 5 minutes
            $online = "online";
        } elseif ($difference < 3600) { // 1 hour
            $online = floor($difference / 60) . " минут назад";
        } else {
            $online = date("d.m в h:i a", $row['timestamp']);
        }
        
        try {
            $image = new ImageResize($row['avatar']);
            $image->quality_jpg = 90;
            $image->crop(100, 100);
            $avatar = 'data:image/jpeg;base64,' . base64_encode($image);
        } catch (Exception $e) {
            $avatar = ''; // Default avatar or error handling
        }
        
        $people[] = [
            'id' => $row['id'],
            'firstname' => $row['firstname'],
            'lastname' => $row['lastname'],
            'timestamp' => $row['timestamp'],
            'online' => $online,
            'avatar' => $avatar
        ];
    }
    
    if (!empty($people)) {
        echo json_encode([
            'result' => 'success',
            'people' => $people
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'result' => 'empty',
            'error' => 'Никого не найдено'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'result' => 'failure',
        'error' => 'Нет доступа к базе данных'
    ]);
}
