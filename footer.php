<script>$(function(){
    var isLogged = <?php error_reporting(0); if (isset($_COOKIE["user_token"])) { echo "true"; } else { echo "false"; }; ?>;

    if (isLogged) {
        var notificationCounter = 0;
    window.setInterval(function(){
        $.post("updateAsync.php", { type: 2 }, (data) => {
            if (data > 0) {
                if (data <= 9) $("#newMessageDot").html(data);
                else $("#newMessageDot").html("9");
                $("#newMessageDot").show(500);
                if (notificationCounter != data) {
                    new Notification("Новых сообщений в Невке: " + data, {
                        tag: "newMessageAlert"
                    });
                    notificationCounter = data;
                }
            } else {
                $("#newMessageDot").hide(500);
            }
        });
    },2500);
    };
});</script>
</div>
</body>
</html>