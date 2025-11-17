<div class="call" id="incomingcall" style="display: none;">
    <h1 id="bigtext" style="font-size: 40px; margin-bottom: 0px"></h1>
    <p id="smalltext" style="font-size: 25px;"></p>
    <div id="newcallbuttons" style="display: none;">
        <button id="answer"><span class="fa fa-phone"></span></button>
        <button id="decline"><span class="fa fa-phone" style="transform: rotate(135deg); margin-right: 2px"></span></button>
    </div>
    <div id="callbuttons" style="display: none;">
        <button id="hangup"><span class="fa fa-phone" style="transform: rotate(135deg); margin-right: 2px"></span></button>
    </div>
</div>
<script>
    $(function() {
        var isLogged = <?php error_reporting(0); if (isset($_COOKIE["user_token"])) {echo "true";} else {echo "false";}; ?>;

        if (isLogged) {
            $.post("updateAsync.php", { type: 5 }, async (response) => {
                const data = JSON.parse(response);
                const sdk = VoxImplant.getInstance();
                let callActive = false;
                let callTime = 0;
                setInterval(() => {
                    if (callActive) {
                        callTime++;
                        const minutes = Math.floor(callTime / 60);
                        const seconds = callTime % 60;
                        const displaySeconds = seconds.toString().padStart(2, '0');
                        document.getElementById('smalltext').textContent = `${minutes}:${displaySeconds}`;
                    }
                }, 1000);

                try {
                    await sdk.init();
                    await sdk.connect();
                    await sdk.login(data[0], data[1]);
                    console.log("Calls: Voximplant logged in");
                } catch (e) {
                    console.log(e);
                }

                sdk.on(VoxImplant.Events.IncomingCall, (e) => {
                    document.getElementById("bigtext").innerHTML = "Входящий вызов...";
                    document.getElementById("smalltext").innerHTML = "Вам звонит " + e.call.settings.displayName;
                    document.getElementById("newcallbuttons").style.display = "block";
                    document.getElementById("callbuttons").style.display = "none";
                    document.getElementById("incomingcall").style.display = "flex";
                    document.body.style.overflow = "hidden";

                    document.getElementById("answer").onclick = () => {
                        e.call.answer();
                        document.getElementById("bigtext").innerHTML = e.call.settings.displayName;
                        document.getElementById("smalltext").innerHTML = "0:00";
                        document.getElementById("newcallbuttons").style.display = "none";
                        document.getElementById("callbuttons").style.display = "block";
                    };

                    document.getElementById("decline").onclick = () => {
                        e.call.decline();
                        document.getElementById("incomingcall").style.display = "none";
                        document.body.style.overflow = "";
                    };

                    document.getElementById("hangup").onclick = () => {
                        e.call.hangup();
                        document.getElementById("incomingcall").style.display = "none";
                        document.body.style.overflow = "";
                    };

                    e.call.addEventListener(VoxImplant.CallEvents.Connected, () => {
                        callTime = 0;
                        callActive = true;
                    });

                    e.call.addEventListener(VoxImplant.CallEvents.Disconnected, () => {
                        document.getElementById("incomingcall").style.display = "none";
                        document.body.style.overflow = "";
                        callActive = false;
                    });
                });
            });

            var notificationCounter = 0;
            window.setInterval(function() {
                $.post("updateAsync.php", {
                    type: 2
                }, (response) => {
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
            }, 2500);
        };
    });
</script>
</div>
</body>

</html>