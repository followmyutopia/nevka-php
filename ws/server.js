/*
 * Nevka WebSocket Server 2.0
 * Avicus Delacroix
 */

import mysql from "mysql2/promise";
import moment from "moment";
import { WebSocketServer } from "ws";
import { safeFilterArray, noAvatar } from "./variables.js";

const pool = mysql.createPool({
  host: "localhost",
  user: "nevka",
  password: "DB_PASSWORD_HERE",
  database: "nevka",
  waitForConnections: true,
  connectionLimit: 300,
  queueLimit: 0,
});

const server = new WebSocketServer({ port: 7190 });

const prepareText = (text) => {
  return text
    .replaceAll("<br>", "")
    .replaceAll("<ul>", "")
    .replaceAll("<li>", "  •  ")
    .replaceAll("</li>", "")
    .replaceAll("</ul>", "")
    .replaceAll("&quot;", '\"')
    .replaceAll("&lt;", "<")
    .replaceAll("&gt;", ">")
    .replaceAll("&#039;", "'")
    .replaceAll("&amp;", "&")
    .replaceAll("	", "    ");
};

server.on("connection", async (ws) => {
  let connected = true;
  const intervals = [];

  ws.on("close", async () => {
    connected = false;
    intervals.forEach((id) => clearInterval(id));
  });

  ws.on("message", async (message) => {
    try {
      const data = JSON.parse(message);

      if (data.token && data.subscribeToChat) {
        // authorize for receiving messages
        const [auth] = await pool.execute(
          "SELECT id FROM `users` WHERE `token` = ?",
          [data.token],
        );

        if (auth.length === 0) {
          ws.send("Token is incorrect");
          return;
        }

        const checkedUser = auth[0].id;

        let query;
        if (checkedUser < data.subscribeToChat) {
          query = "M" + checkedUser + "M" + data.subscribeToChat + "M";
        } else {
          query = "M" + data.subscribeToChat + "M" + checkedUser + "M";
        }

        let oldJson;

        const poll = async () => {
          if (!connected) return;
          try {
            const [msg] = await pool.execute(
              "SELECT id, query, author, text, image, timestamp, unread, reply FROM messages WHERE query = ? ORDER BY id DESC LIMIT 100",
              [query],
            );

            // restore ascending order as before
            msg.reverse();

            // collect reply ids and batch fetch
            const replyIds = [
              ...new Set(
                msg.filter((m) => m.reply !== null).map((m) => m.reply),
              ),
            ];
            let repliesMap = {};
            if (replyIds.length > 0) {
              const [replyRows] = await pool.execute(
                `SELECT id, author, text FROM messages WHERE id IN (${replyIds.map(() => "?").join(",")})`,
                replyIds,
              );
              replyRows.forEach((r) => {
                let preview = r.text === "" ? "(изображение)" : r.text;
                repliesMap[r.id] = { author: r.author, preview };
              });
            }

            const mobileAppFormat = msg.map((item) => {
              const isMe = item.author === checkedUser;
              const reply =
                item.reply === null
                  ? ""
                  : (() => {
                      const r = repliesMap[item.reply];
                      if (!r) return "";
                      return r.author === checkedUser
                        ? "Вы: " + r.preview
                        : r.preview;
                    })();

              return {
                id: String(item.id),
                me: isMe ? "true" : "false",
                text: prepareText(item.text),
                image: item.image.toString(),
                timestamp: String(item.timestamp),
                formattedTime: moment
                  .unix(item.timestamp)
                  .format("D.MM в h:mm a"),
                isUnread: String(item.unread),
                reply,
              };
            });

            const json = JSON.stringify(mobileAppFormat);

            if (json !== oldJson) {
              console.log(
                "Nevka WSS: Sending chat: " +
                  data.subscribeToChat +
                  ", to: " +
                  checkedUser,
              );
              ws.send(json);
              oldJson = json;
              await pool.execute(
                "UPDATE messages SET unread='0' WHERE query = ? AND author = ?",
                [query, data.subscribeToChat],
              );
            }
          } catch (err) {
            // log and continue polling; avoid crashing interval
            console.error("Poll error (chat):", err);
          }
        };

        // immediate first poll, then interval
        await poll();
        intervals.push(setInterval(poll, 333));
      } else if (data.token && data.subscribeToChatList) {
        // authorize for receiving chat list
        const [auth] = await pool.execute(
          "SELECT id FROM `users` WHERE `token` = ?",
          [data.token],
        );

        if (auth.length === 0) {
          ws.send("Token is incorrect");
          return;
        }

        const checkedUser = auth[0].id;
        const signature = "M" + checkedUser + "M";
        let oldJson;

        const pollList = async () => {
          if (!connected) return;
          try {
            const [chats] = await pool.execute(
              "SELECT query, MAX(id) AS maxid FROM messages WHERE query LIKE ? GROUP BY query ORDER BY MAX(id) DESC LIMIT 75",
              ["%" + signature + "%"],
            );

            if (!chats.length) {
              const jsonEmpty = JSON.stringify([]);
              if (jsonEmpty !== oldJson) {
                ws.send(jsonEmpty);
                oldJson = jsonEmpty;
              }
              return;
            }

            // extract interlocutor ids and max message ids
            const interlocutors = [];
            const maxIds = [];
            for (const item of chats) {
              const interlocutor = item.query
                .replace(signature, "")
                .replace("M", "");
              interlocutors.push(interlocutor);
              maxIds.push(item.maxid);
            }

            // batch fetch interlocutor data
            const [usersRows] = await pool.execute(
              `SELECT id, avatar, firstname, lastname, timestamp FROM users WHERE id IN (${interlocutors.map(() => "?").join(",")})`,
              interlocutors,
            );
            const usersMap = {};
            usersRows.forEach((u) => (usersMap[String(u.id)] = u));

            // batch fetch last texts
            const [lastTextRows] = await pool.execute(
              `SELECT id, text, author, unread FROM messages WHERE id IN (${maxIds.map(() => "?").join(",")})`,
              maxIds,
            );
            const lastMap = {};
            lastTextRows.forEach((l) => (lastMap[String(l.id)] = l));

            const now = Math.floor(Date.now() / 1000);

            const mobileAppFormat = chats.map((item) => {
              const interlocutor = item.query
                .replace(signature, "")
                .replace("M", "");
              const interlocutorData = usersMap[interlocutor] || {
                firstname: "",
                lastname: "",
                avatar: noAvatar,
                timestamp: 0,
              };
              const lastText = lastMap[String(item.maxid)] || {
                text: "",
                author: 0,
                unread: 0,
              };

              const diff = now - interlocutorData.timestamp;

              let online;
              if (diff <= 300) {
                online = "online";
              } else if (diff <= 3600) {
                const minutes = Math.floor(diff / 60);
                online = minutes + " минут";
              } else if (diff <= 86400) {
                online = moment
                  .unix(interlocutorData.timestamp)
                  .format("h:mm a");
              } else {
                online = moment.unix(interlocutorData.timestamp).format("D.MM");
              }

              let lastTextPreview =
                lastText.text === ""
                  ? "(изображение)"
                  : prepareText(lastText.text);

              let firstname = interlocutorData.firstname;
              let lastname = interlocutorData.lastname;
              let avatar = interlocutorData.avatar.toString();

              if (checkedUser === 1) {
                safeFilterArray.forEach((itemFilter) => {
                  if (
                    firstname.toLowerCase().includes(itemFilter.toLowerCase())
                  ) {
                    firstname = "***";
                    avatar = noAvatar;
                  }
                  if (
                    lastname.toLowerCase().includes(itemFilter.toLowerCase())
                  ) {
                    lastname = "***";
                    avatar = noAvatar;
                  }
                });
              }

              return {
                id: interlocutor,
                firstname,
                lastname,
                lastText: lastTextPreview,
                isUnread: lastText.unread,
                avatar,
                online,
                lastTextAuthor: lastText.author,
              };
            });

            const json = JSON.stringify(mobileAppFormat);
            if (json !== oldJson) {
              console.log("Nevka WSS: Sending chat list to: " + checkedUser);
              ws.send(json);
              oldJson = json;
            }
          } catch (err) {
            console.error("Poll error (chat list):", err);
          }
        };

        await pollList();
        intervals.push(setInterval(pollList, 1000));
      } else {
        ws.send("Required parameters are not specified");
      }
    } catch (e) {
      ws.send("Message format is incorrect");
    }
  });
});
