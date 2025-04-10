<?php
/**
 * Nevka Project v1.3
 * 2024-2025 Avicus Delacroix
 */

include 'db.php';
$user = $type = "";
$type = validate($_POST['type']);
$loggedUser = checkCookie($_COOKIE["user_token"]);

if ($type == 0) {
    $user = validate($_POST['currentChat']);
    getMessages($_COOKIE["user_token"],$user);
}
else if ($type == 1) {
    $user = validate($_POST['currentChat']);
    getTopicMessages($user);
}
else if ($type == 2) {
    newMessageAvailable($loggedUser);
}
else { logout(); }
?>