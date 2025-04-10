<script>$(function(){
    window.setInterval(function(){
        $.post("updateAsync.php", { type: 2 }, function(data){
            if (data > 0) {
                $("#newMessageDot").show(500);
                $("#newMessageDot").html(data);
                new Notification("Новых сообщений в Невке: " + data, { tag: "newMessageAlert" });
            }
        });
    },2500);
});</script>
</div>
</body>
</html>