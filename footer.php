<script>$(function(){
    var notificationCounter = 0;
    window.setInterval(function(){
        $.post("updateAsync.php", { type: 2 }, function(data){
            if (data > 0) {
                $("#newMessageDot").show(500);
                $("#newMessageDot").html(data);
                if (notificationCounter != data) {
                    new Notification("Новых сообщений в Невке: " + data, {
                        tag: "newMessageAlert"
                    });
                    notificationCounter = data;
                }
            }
        });
    },2500);
});</script>
</div>
</body>
</html>