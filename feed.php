<?php

/**
 * Nevka Project v1.15
 * 2025 Avicus Delacroix
 */

$loggedUser = "";
include 'db.php';
include 'header.php';

if (!isset($_COOKIE["user_token"])) {
    header("Refresh:0; url=index.php");
    exit;
} else {
    $loggedUser = checkCookie($_COOKIE["user_token"]);
    updateOnline($_COOKIE["user_token"]);
}
if (isset($_GET['logout'])) {
    logout();
}

$wallStatus = "";
?>

<div class="feed mobile-expand">
    <div style="margin-top: 2%; margin-bottom: 2%">
        <button id="postbutton" class="button wide">Написать пост</button>
        <form id="postform" style="display: none;" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>"
            method="post" enctype="multipart/form-data">
            <textarea name="message"></textarea>
            <button class="button mobile-expand" style="margin-right:-3px" type="submit" name="postWall">Опубликовать пост</button>
            <input class="fileInput attachment mobile-expand" type="file" name="fileToUpload" id="fileToUpload">
            <span id="wallStatus"><?php echo $wallStatus ?></span>
        </form>
    </div>

    <?php getWallEntries("all", $loggedUser) ?>
</div>

<script>
    $(function() {
        $("#feedmenu").addClass("active");
        $(".a").click(function() {
            window.location = $(this).data("href");
        });
        $(".likeButton").click(function() {
            var heartId = "#like-" + $(this).data("id");
            var counterId = "#likeCount-" + $(this).data("id");
            var counter = parseInt($(counterId).text());
            $.post("updateAsync.php", {
                currentChat: $(this).data("id"),
                type: 4
            }, function(data) {
                if (data == 1) {
                    $(heartId).css("color", "crimson");
                    if (counter > 0) $(counterId).text(" " + (counter + 1));
                    else $(counterId).text(" " + (1));
                } else if (data == 0) {
                    $(heartId).css("color", "rgba(255, 255, 255, 0.85)");
                    if (counter == 1) $(counterId).text("");
                    else if (counter > 0) $(counterId).text(" " + (counter - 1));
                }
            });
        });
        $("#postbutton").click(function() {
            $("#postbutton").hide();
            $("#postform").show();
        });
    });
</script>

<?php include 'footer.php'; ?>