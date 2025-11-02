<script>$(function(){
    var isLogged = <?php error_reporting(0); if (isset($_COOKIE["user_token"])) { echo "true"; } else { echo "false"; }; ?>;

    if (isLogged) {
        var notificationCounter = 0;
        window.setInterval(function(){
            $.post("updateAsync.php", { type: 2 }, (response) => {
                const data = JSON.parse(response);

                if (data.counter > 0) {
                    if (data.counter <= 9) $("#newMessageDot").html(data.counter);
                    else $("#newMessageDot").html("9");
                    $("#newMessageDot").show(500);
                    data.notifications.pop();
                    console.log(data.notifications);

                    if (notificationCounter != data.counter) {
                        data.notifications.forEach((item) => {
                            if ((item.message.startsWith("NE1[") || item.message.startsWith("NE2[") || item.message.startsWith("NE3[") || item.message.startsWith("NE4[")) && item.message.endsWith("]")) {
                                item.message = "Сообщение с устаревшим шифрованием.";
                            }
                            if (item.message.startsWith("NE5[") && item.message.endsWith("]")) {
                                item.message = "Зашифрованное сообщение. Прочитайте на мобильном!";
                            }
                            var notification = new Notification(item.author, {
                                tag: "nevkaMessage: " + item.id,
                                body: item.message
                            });
                            notification.onclick = () => window.open("messages.php?id=" + item.id, "_self");
                        });
                        notificationCounter = data.counter;
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