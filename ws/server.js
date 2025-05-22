import mysql from 'mysql2/promise';
import moment from 'moment';
import { WebSocketServer } from 'ws';

const server = new WebSocketServer({ port: 769 });

server.on("connection", async (ws) => {
    let connected = true;

    console.log("New connection!");
    ws.send("Welcome to Nevka WS");

    ws.on("close", () => {
        connected = false;
        console.log("Connection closed!");
    })

    ws.on("message", async (message) => {
        try {
            const data = JSON.parse(message);

            if (data.token && data.subscribeToChat) {
                // authorize

                const connection = await mysql.createConnection({
                    host: '217.107.219.234',
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
                                'SELECT * FROM (SELECT id, query, author, text, image, timestamp, unread, reply FROM messages WHERE query = ? ORDER BY id DESC LIMIT 2) X ORDER BY id ASC;', [query]
                            );

                            let mobileAppFormat = msg.map(item => {
                                let format = {
                                    id: item.id,
                                    me: undefined,
                                    text: item.text,
                                    image: item.image.toString(),
                                    timestamp: item.timestamp,
                                    formattedTime: undefined,
                                    isUnread: item.unread,
                                    reply: item.reply
                                };

                                if (item.author === checkedUser) {
                                    format['me'] = true;
                                } else {
                                    format['me'] = false;
                                }

                                format['formattedTime'] = moment.unix(item.timestamp).format("D.MM в h:mm a");

                                return format;
                            });

                            const json = JSON.stringify(mobileAppFormat);

                            if (json !== oldJson) {
                                ws.send(json);
                                oldJson = json;
                            }
                        }
                    }, 500);
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