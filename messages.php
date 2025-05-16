<?php
/**
 * Nevka Project v1.9
 * 2025 Avicus Delacroix
 */

$loggedUser = "";
include 'db.php';
include 'header.php';

if(!isset($_COOKIE["user_token"])) { header("Refresh:0; url=index.php"); exit; }
else { $loggedUser = checkCookie($_COOKIE["user_token"]); updateOnline($_COOKIE["user_token"]); }
if(isset($_GET['logout'])) { logout(); }
$loggedUserData = getUserData($loggedUser);
if (!isset($loggedUserData[1]) || !isset($loggedUserData[2])) {header("Refresh:0; url=settings.php");}

$currentChat = $messageStatus = $textarea = $leftSection = $rightSection = "";

if(isset($_GET['id'])) {
    if (userExists(intval($_GET['id']))) {
        $currentChat = validate(intval($_GET['id']));
        $leftSection = "mobile-hidden";
    } else {
        $currentChat = lastId($loggedUser); $rightSection = "mobile-hidden";
    }
}
else { $currentChat = lastId($loggedUser); $rightSection = "mobile-hidden"; }

if ($currentChat == 0) { $rightSection = "hidden"; $leftSection = "wide"; }
?>

<div class="left nomargin-right mobile-expand <?php echo $leftSection ?>">
    <div class="peopleWindow">
        <table style="float:left" id="chatsTable"></table>
    </div>
</div>
<div class="right mobile-expand <?php echo $rightSection ?>">
    <?php currentUserHeader($currentChat) ?>

    <div class="activeChat">
        <div id="chatWindow"></div>
        <form class="<?php echo $textarea ?>" id="messageComposer" action="sendAsync.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="sendMessage"><input id="reply" name="reply" type="hidden">
            <textarea id="msg" name="message"></textarea><input class="hidden" name="interlocutor" value="<?php echo $currentChat ?>">
            <button id="send" class="button mobile-expand send" type="submit">Отправить</button>
            <input class="fileInput attachment mobile-expand send" type="file" name="fileToUpload" id="fileToUpload">
            <span id="sendStatus" class="console send" style="margin-top:1px"></span>
        </form>
    </div>
</div>

<script>
$(function(){
    const urlParams = new URLSearchParams(window.location.search);
    const currentChatSelector = "#" + urlParams.get('id');

    $("#chatsTable").on('click', '.a', function(){ window.location = $(this).data("href"); });
    $("#chatWindow").on('click', '.replyButton', function(){
        $("#sendStatus").html("Ответ на сообщение <b>×</b>");
        $("#reply").val($(this).data("id"));
    });
    $("#sendStatus").click(() => {
        $("#sendStatus").html("");
        $("#reply").val("");
    })
    document.addEventListener('dragover', (e) => { e.preventDefault() });
    document.addEventListener('drop', (e) => { document.getElementById('fileToUpload').files = e.dataTransfer.files; e.preventDefault() });

    // Panamar proDUCKtion
    var needsUpdate = false;
    var sending = false;
    $('form').submit(function(e) {
        e.preventDefault();
        var form = new FormData($(this)[0]);
        var uploadfile = $("#fileToUpload").val();
        var text_msg = $("#msg").val();
        if (!sending) {
            sending = true;
            $.ajax({
                url: $(this).attr('action'),
                method: "POST",
                data: form,
                processData: false,
                contentType: false,
                success: function(data) {
                    if(data != "OK" && data != "") {
                        $("#sendStatus").html(data);
                    }
                    else {
                        if($("#sendStatus").html() != "") $("#sendStatus").html("");
                        $("#msg").val("");
                        $("#fileToUpload").val("");
                        $("#reply").val("");
                    }
                    $.post("updateAsync.php", { currentChat: <?php echo $currentChat ?>, type: 0 }, function(data){
                        if (data != $("#chatWindow").html()) {
                            $("#chatWindow").html(data).scrollTop($('#chatWindow')[0].scrollHeight);
                        }
                    });
                    sending = false;
                    needsUpdate = true;
                }
            }); 
        }
    });

    $("#messagesmenu").addClass("active");
    $('#chatWindow').scrollTop($('#chatWindow')[0].scrollHeight);
    $(".a").click(function() { window.location = $(this).data("href"); });
    $.post("updateAsync.php", { type: 3 }, function(data){
        if (data != $("#chatsTable").html()) {
            $("#chatsTable").html(data);
            const headerId = "#" + $(".messagesHeader").attr('id');
            $("#chatsTable").find(headerId).addClass("activechat");
        }
    });
    $.post("updateAsync.php", { currentChat: <?php echo $currentChat ?>, type: 0 }, function(data){
        if (data != $("#chatWindow").html()) { $("#chatWindow").html(data).scrollTop($('#chatWindow')[0].scrollHeight); }
    });
    var buffer = 0;
    window.setInterval(function(){
        $.post("updateAsync.php", { type: 2 }, (data) => {
            if (data > 0 || needsUpdate) {
                if (data != buffer || needsUpdate) {
                    $.post("updateAsync.php", { currentChat: <?php echo $currentChat ?>, type: 0 }, function(data){
                        $("#chatWindow").html(data).scrollTop($('#chatWindow')[0].scrollHeight);
                    });
                    needsUpdate = false;
                }
                buffer = data;
            }
        });
    },333);
    window.setInterval(function(){
        $.post("updateAsync.php", { type: 3 }, function(data){
            if (data != $("#chatsTable").html()) {
                $("#chatsTable").html(data);
                const headerId = "#" + $(".messagesHeader").attr('id');
                $("#chatsTable").find(headerId).addClass("activechat");
            }
        });
    },2500);
    $('textarea').keypress(function (e) { if(e.which == 13) { $('#send').click(); e.preventDefault(); } });
    if (<?php echo $currentChat ?> != 0) { $("#pplNotice").hide(); }
});</script>

<?php include 'footer.php'; ?>