<?php
/* 
 * Nevka API 2.0
 * Avicus Delacroix
*/

include '../lib/ImageResize.php';

use \Gumlet\ImageResize;

if (!isset($_POST['api-key']) || $_POST['api-key'] !== "qonxod-Kicmad-4puvhy") {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['result' => 'failure', 'error' => 'Invalid API key']);
    exit;
}

date_default_timezone_set("Europe/Moscow");

function validate(string $data): string
{
    return htmlspecialchars(stripslashes(trim($data)));
}

function starts_with(string $haystack, string $needle): bool
{
    return $needle === "" || strpos($haystack, $needle) === 0;
}

function ends_with(string $haystack, string $needle): bool
{
    if ($needle === "") return true;
    return substr($haystack, -strlen($needle)) === $needle;
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST['token']) || !isset($_POST['chat']) || !isset($_POST['text'])) {
    http_response_code(400);
    echo json_encode(['result' => 'failure', 'error' => 'Параметры token, chat, text обязательны']);
    exit;
}

$token = validate($_POST['token']);
$currentChat = validate($_POST['chat']);
$messageText = validate($_POST['text']);
if (preg_match('/^NE[1234]\[.*\]$/', $messageText)) $messageText = "[Сообщение не отправлено! Устаревшее шифрование.]";
$reply = isset($_POST['reply']) ? validate($_POST['reply']) : null;
$timestamp = time();
$messageImage = "";

if (!empty($_POST['image'])) {
    try {
        $image = new ImageResize($_POST['image']);
        $image->quality_jpg = 90;
        $image->resizeToLongSide(800);
        $messageImage = 'data:image/jpeg;base64,' . base64_encode($image->getImageAsString(IMAGETYPE_JPEG));
    } catch (Exception $e) {
        $messageImage = "";
    }
}

include '../credentials.php';

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id, firstname, lastname FROM users WHERE token = ? LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $pdo->rollBack();
        http_response_code(401);
        echo json_encode(['result' => 'failure', 'error' => 'wrong_token']);
        exit;
    }

    $loggedUser = (int)$user['id'];
    $senderName = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));

    $stmt = $pdo->prepare("UPDATE users SET timestamp = ? WHERE id = ?");
    $stmt->execute([$timestamp, $loggedUser]);

    $stmt = $pdo->prepare("SELECT id, expo_push_token FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$currentChat]);
    $target = $stmt->fetch();

    if (!$target) {
        $targetToken = "";
    } else {
        $targetToken = $target['expo_push_token'] ?? "";
    }

    if ($loggedUser < (int)$currentChat) {
        $query = "M{$loggedUser}M{$currentChat}M";
    } else {
        $query = "M{$currentChat}M{$loggedUser}M";
    }

    // Insert message (reply may be null)
    if ($reply !== null && $reply !== "") {
        $stmt = $pdo->prepare("INSERT INTO messages (query, author, text, image, timestamp, unread, reply) VALUES (?, ?, ?, ?, ?, 1, ?)");
        $stmt->execute([$query, $loggedUser, $messageText, $messageImage, $timestamp, $reply]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO messages (query, author, text, image, timestamp, unread) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([$query, $loggedUser, $messageText, $messageImage, $timestamp]);
    }

    $pdo->commit();

    // Prepare push payload
    $pushHeader = $senderName ?: "Новое сообщение";
    if ($messageText !== "") {
        $processedText = $messageText;
        // If message appears encrypted pattern
        if (preg_match('/^NE[1-9]\[.*\]$/', $processedText)) {
            $pushBody = "[Зашифрованное сообщение]";
        } else {
            $pushBody = $processedText;
        }
    } else {
        $pushBody = "(изображение)";
    }

    $pushData = ["url" => "/(tabs)/messages/chat?id={$loggedUser}&name=" . urlencode($senderName)];

    // Send push if token available
    if (!empty($targetToken)) {
        $ch = curl_init("https://exp.host/--/api/v2/push/send");
        $payload = json_encode(["to" => $targetToken, "title" => $pushHeader, "body" => $pushBody, "data" => $pushData], JSON_UNESCAPED_UNICODE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        $curlResult = curl_exec($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);
    }

    echo json_encode(['result' => 'success']);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['result' => 'failure', 'error' => 'Нет доступа к базе данных']);
}
