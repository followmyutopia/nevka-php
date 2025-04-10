<script>$(function(){
    window.setInterval(function(){
        $.post("updateAsync.php", { type: 2 }, function(data){
            if (data > 0) {
                $("#newMessageDot").show(333);
                $("#newMessageDot").html(data);
            }
        });
    },2000);
});</script>
</div>
</body>
</html>