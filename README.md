# Nevka 1 PHP website

A social network written fully in PHP and MySQL by Avicus Delacroix as a hobby project for fun.

Contains the backend API for the Nevka 1 [cross-platform mobile app](https://github.com/followmyutopia/nevka-react/tree/main).

> **Status**: Abandonware. Currently not developed anymore. The network is still available, but maintenance passed to my friend. At the moment, I am busy developing a more proffessional 2nd version of the social network, available at [https://nevka2.ru](https://nevka2.ru).

## License

This project is licensed under the **GNU Affero General Public License v3.0 (AGPL-3.0)** — see the [LICENSE](LICENSE) file for details.

You are free to fork my repo, make any changes to it and use as your own project, change its name and features, but make a copyright notice of my repository and make your project open-souce. You are also free to open Pull Requests in this repository with improvements, they will be reviewed and published.

## Features

The social network website features:

* User profiles with avatars and information
* Wall with posts, images, and likes for each profile
* Scrollable feed containing posts and likes from all users
* Private messages with replies and encryption (though a weak one)
* Registered users list with profile views, quick messages and calls
* Peer-to-peer audio calls based on Voximplant

## History

In December 2024 I started learning PHP and realized that my knowledge is efficient to write a simple social network similar to early VK. I wrote a simple local project called "Ne VK" (meaning "Not a VK" in Russian), and tested it only locally for fun. Then I showed it to my friends, who genuinely liked the idea, so on April 6, 2025 I published my project under a slightly changed name "Nevka", referring to a river in St. Petersburg, where I lived for most part of my life, and resembling the original name, avoiding copyright issues.

Since then the project was constantly improving, bugs were fixed, vulnerabilities were closed, features were added, design was improved constantly. I devoted all my free time to this hobby project, with the goal to make a nice social network, and to improve my knowledge in programming.

On April 17, 2025 I started learning React Native and my practice project was building a cross-platform mobile app for my social network with the Expo framework. I released the app both for iOS and Android on May 17, and since then it was constantly improving as well. Both the app and the website share the same features and design. The backend for the app remains written in PHP, with a small JS server for WebSocket messaging (it has a PHP fallback if WS server is down), and for P2P calling via Voximplant.

Both the website and the mobile app were written entirely by me, without any AI help.

After the project was finalized, I liked how it looks, feels, and works, but it still remained a non-proffessional hobby project, made initially for fun and learning. So I decided to move on, and create a fully independent social network project, called Nevka 2, written in Svelte Kit (which I was learning at that moment in 2025) and made as a PWA. The Nevka 2 project is independent and features a different login system (only OAuth, and it does not keep users' emails and password hashes in the DB), authorization via JWT, translated into 3 languages, featuring fully rewritten design, group chats, AES-256-GCM encryption for messages, and self-hosted P2P calls.

Since I'm concentrated on the [Nevka 2](https://followmyutopia.github.io) project, I decided to open source my Nevka 1 hobby project, for educational and fun purposes.

## Deployment

This project requires PHP and MySQL installed on the server.

Create a `credentials.php` file in the root directory with the following credentials: `$servername`, `$username`, `$password`, `$dbname`. Then create the database with the following tables:

* **Users**: id (int), login (varchar-63), password (varchar-40), token (varchar-127), avatar (mediumblob), firstname (varchar-63), lastname (varchar-63), status (varchar-127), city (varchar-63), bio (varchar-255), timestamp (int), search (varchar-127), is_banned (tinyint-1), is_admin (tinyint-1), expo-push-token (varchar-255)
* **Walls**: id (int), user (int), text (varchar-1023), image (mediumblob), timestamp (int), likes (varchar-1023)
* **Messages**: id (int), query (varchar-63), author (int), text (varchar-1023), image (mediumblob), timestamp (int), unread (tinyint-1), reply (int)

Run the server, and it should catch it up. You are free to make any changes you want.

For running the mobile app, refer to the [nevka-react](https://github.com/followmyutopia/nevka-react) repository.
