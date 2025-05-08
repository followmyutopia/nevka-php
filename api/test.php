<?php
/**
 * Nevka Project v1.7 API
 * 2025 Avicus Delacroix
 */

$sender = "";

function validate($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if (isset($_POST['sender'])) { $sender = validate($_POST['sender']); echo "{ \"result\": \"The request has been successful\", \"sender\": \"".$sender."\" }"; }
else { echo "{ \"result\": \"The request has been successful\", \"sender\": \"Sender field has not been received\" }"; }
?>