import mysql from 'mysql2/promise';
import moment from 'moment';
import { WebSocketServer } from 'ws';

const server = new WebSocketServer({ port: 7190 });

server.on("connection", async (ws) => {
    let connected = true;

    console.log("New connection!");

    ws.on("close", () => {
        connected = false;
        console.log("Connection closed!");
    })

    ws.on("message", async (message) => {
        try {
            const data = JSON.parse(message);

            if (data.token && data.subscribeToChat) {
                // authorize

                // current IP: 217.107.219.234
                const connection = await mysql.createConnection({
                    host: 'localhost',
                    user: 'nevka',
                    password: "DB_PASSWORD_HERE",
                    database: 'nevka',
                });

                const [auth] = await connection.execute(
                    'SELECT id FROM `users` WHERE `token` = ?', [data.token]
                );

                if (auth.length > 0) {
                    const checkedUser = auth[0].id;
                    console.log("Authorized user: " + checkedUser);
                    console.log("Requested chat with: " + data.subscribeToChat);

                    let query;

                    if (checkedUser < data.subscribeToChat) {
                        query = "M" + checkedUser + "M" + data.subscribeToChat + "M";
                    } else {
                        query = "M" + data.subscribeToChat + "M" + checkedUser + "M";
                    }

                    let oldJson;

                    setInterval(async () => {
                        if (connected) {
                            const [msg] = await connection.execute(
                                'SELECT * FROM (SELECT id, query, author, text, image, timestamp, unread, reply FROM messages WHERE query = ? ORDER BY id DESC LIMIT 100) X ORDER BY id ASC;', [query]
                            );

                            const getReplyText = async (replyId) => {
                                const [reply] = await connection.execute(
                                    'SELECT author, text FROM messages WHERE id = ? LIMIT 1', [replyId]
                                );

                                if (reply[0].author === checkedUser) {
                                    return "Вы: " + reply[0].text;
                                } else {
                                    return reply[0].text;
                                }
                            }

                            let mobileAppFormat = await Promise.all(msg.map(async item => {
                                let format = {
                                    id: String(item.id),
                                    me: undefined,
                                    text: item.text,
                                    image: item.image.toString(),
                                    timestamp: String(item.timestamp),
                                    formattedTime: undefined,
                                    isUnread: String(item.unread),
                                    reply: undefined
                                };

                                if (item.author === checkedUser) {
                                    format['me'] = "true";
                                } else {
                                    format['me'] = "false";
                                }

                                if (item.reply === null) {
                                    format['reply'] = "";
                                } else {
                                    format['reply'] = await getReplyText(item.reply);
                                }

                                format['formattedTime'] = moment.unix(item.timestamp).format("D.MM в h:mm a");

                                return format;
                            }));

                            const json = JSON.stringify(mobileAppFormat);

                            if (json !== oldJson) {
                                console.log("Sending chat update with: " + data.subscribeToChat + ", to user: " + checkedUser);
                                ws.send(json);
                                oldJson = json;
                            }
                        }
                    }, 333);
                } else {
                    ws.send("Token is incorrect");
                }
            } else {
                ws.send("Required parameters are not specified");
            }
        } catch (e) {
            ws.send("Message format is incorrect");
        }
    });
});