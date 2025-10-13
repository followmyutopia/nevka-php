import mysql from 'mysql2/promise';
import moment from 'moment';
import { WebSocketServer } from 'ws';
import { safeFilterArray, noAvatar } from './variables.js';

const server = new WebSocketServer({ port: 7190 });

const prepareText = (text) => {
    return text
        .replaceAll("<br>", "")
        .replaceAll("<ul>", "")
        .replaceAll("<li>", "  •  ")
        .replaceAll("</li>", "")
        .replaceAll("</ul>", "")
        .replaceAll("&quot;", '\"')
        .replaceAll("&lt;", '<')
        .replaceAll("&gt;", '>')
        .replaceAll("&#039;", "'")
        .replaceAll("&amp;", "&")
        .replaceAll("	", "    ")
}

server.on("connection", async (ws) => {
    let connected = true;

    // current IP: 217.107.219.234
    const connection = await mysql.createConnection({
        host: 'localhost',
        user: 'nevka',
        password: "DB_PASSWORD_HERE",
        database: 'nevka',
    });

    ws.on("close", async () => {
        await connection.end();
        connected = false;
    })

    ws.on("message", async (message) => {
        try {
            const data = JSON.parse(message);

            if (data.token && data.subscribeToChat) {
                // authorize for receiving messages

                const [auth] = await connection.execute(
                    'SELECT id FROM `users` WHERE `token` = ?', [data.token]
                );

                if (auth.length > 0) {
                    const checkedUser = auth[0].id;

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

                                let preview;

                                if (reply[0].text === "") {
                                    preview = "(изображение)";
                                } else {
                                    preview = reply[0].text;
                                }

                                if (reply[0].author === checkedUser) {
                                    return "Вы: " + preview;
                                } else {
                                    return preview;
                                }
                            }

                            let mobileAppFormat = await Promise.all(msg.map(async item => {
                                let format = {
                                    id: String(item.id),
                                    me: undefined,
                                    text: prepareText(item.text),
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
                                    format['reply'] = prepareText(await getReplyText(item.reply));
                                }

                                format['formattedTime'] = moment.unix(item.timestamp).format("D.MM в h:mm a");

                                return format;
                            }));

                            const json = JSON.stringify(mobileAppFormat);

                            if (json !== oldJson) {
                                console.log("Nevka WSS: Sending chat: " + data.subscribeToChat + ", to: " + checkedUser);
                                ws.send(json);
                                oldJson = json;
                                await connection.execute(
                                    "UPDATE messages SET unread='0' WHERE query = ? AND author = ?", [query, data.subscribeToChat]
                                );
                            }
                        }
                    }, 333);
                } else {
                    ws.send("Token is incorrect");
                }
            } else if (data.token && data.subscribeToChatList) {
                // authorize for receiving chat list

                const [auth] = await connection.execute(
                    'SELECT id FROM `users` WHERE `token` = ?', [data.token]
                );

                if (auth.length > 0) {
                    const checkedUser = auth[0].id;

                    const signature = "M" + checkedUser + "M";
                    let oldJson;

                    setInterval(async () => {
                        if (connected) {
                            const [chats] = await connection.execute(
                                'SELECT query, MAX(id) AS maxid FROM messages WHERE query LIKE ? GROUP BY query ORDER BY MAX(id) DESC LIMIT 75', ["%" + signature + "%"]
                            );

                            const getInterlocutorData = async (interlocutor) => {
                                const [data] = await connection.execute(
                                    'SELECT id, avatar, firstname, lastname, timestamp FROM users WHERE id = ? LIMIT 1', [interlocutor]
                                );

                                return data[0];
                            }

                            const getLastText = async (textId) => {
                                const [data] = await connection.execute(
                                    'SELECT text, author, unread FROM messages WHERE id = ? LIMIT 1', [textId]
                                );

                                return data[0];
                            }

                            let mobileAppFormat = await Promise.all(chats.map(async item => {
                                const interlocutor = item.query.replace(signature, "").replace("M", "");
                                const interlocutorData = await getInterlocutorData(interlocutor);
                                const lastText = await getLastText(item.maxid);

                                const now = Math.floor(Date.now() / 1000);
                                const diff = now - interlocutorData.timestamp; // difference IN SECONDS

                                let format = {
                                    id: interlocutor,
                                    firstname: interlocutorData.firstname,
                                    lastname: interlocutorData.lastname,
                                    lastText: undefined,
                                    isUnread: lastText.unread,
                                    avatar: interlocutorData.avatar.toString(),
                                    online: undefined,
                                    lastTextAuthor: lastText.author
                                };

                                let preview;

                                if (lastText.text === "") {
                                    format['lastText'] = "(изображение)"
                                } else {
                                    format['lastText'] = prepareText(lastText.text)
                                }

                                // if (lastText.author === checkedUser) {
                                //     format['lastText'] = "Вы: " + preview;
                                // } else {
                                //     format['lastText'] = preview;
                                // }

                                if (diff <= 300) {
                                    format['online'] = "online";
                                } else if (diff <= 3600) {
                                    const minutes = Math.floor(diff / 60);
                                    format['online'] = minutes + " минут";
                                } else if (diff <= 86400) {
                                    format['online'] = moment.unix(interlocutorData.timestamp).format("h:mm a");
                                } else {
                                    format['online'] = moment.unix(interlocutorData.timestamp).format("D.MM");
                                }

                                if (checkedUser === 1) {
                                    safeFilterArray.forEach(item => {
                                        if (interlocutorData.firstname.toLowerCase().includes(item.toLowerCase())) {
                                            format['firstname'] = "***";
                                            format['avatar'] = noAvatar;
                                        }
                                        if (interlocutorData.lastname.toLowerCase().includes(item.toLowerCase())) {
                                            format['lastname'] = "***";
                                            format['avatar'] = noAvatar;
                                        }
                                    });
                                }

                                return format;
                            }));

                            const json = JSON.stringify(mobileAppFormat);

                            if (json !== oldJson) {
                                console.log("Nevka WSS: Sending chat list to: " + checkedUser);
                                ws.send(json);
                                oldJson = json;
                            }
                        }
                    }, 1000);
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