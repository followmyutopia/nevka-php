<?php
/**
 * Nevka Project v1.11
 * 2025 Avicus Delacroix
 */

if(isset($_COOKIE["user_token"])) {
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
        newMessageAvailable($_COOKIE["user_token"]);
    }
    else if ($type == 3) {
        showChatsTable($_COOKIE["user_token"]);
    }
    else if ($type == 4) {
        $user = validate($_POST['currentChat']);
        likePost($_COOKIE["user_token"],$user);
    }
    else { logout(); }
}
?>

