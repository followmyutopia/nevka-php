<?php
/**
 * NeVK Project v1.1
 * 2024-2025 Avicus Delacroix
 */

include 'db.php';
$user = validate($_POST['currentChat']);
$type = validate($_POST['type']);
$loggedUser = checkCookie($_COOKIE["user_token"]);

if ($type == 0) { getMessages($_COOKIE["user_token"],$user); }
else if ($type == 1) { getTopicMessages($user); }
else { logout(); }
?>