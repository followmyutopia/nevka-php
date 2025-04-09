<script>$(function(){
    window.setInterval(function(){
        $.post("updateAsync.php", { type: 2 }, function(data){
            $("#newMessageDot").html(data);
        });
    },2000);
});</script>
</div>
</body>
</html>