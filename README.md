# infiltré pour Nextcloud Talk

L'infiltré pour [Nextcloud Talk](https://github.com/nextcloud/spreed)!

## Installation

Copier infiltre.php, mot.php, config.php et le mots.json dans /data et  sur votre serveur and suivre le guide: https://github.com/nextcloud/spreed/blob/master/docs/commands.md

Lancer la commande pour initialiser les commandes `/game` et `/mot` :
```
sudo -u www-data php occ talk:command:add game infiltre "/path/to/your/directory/infiltre.php {ARGUMENTS} {ROOM} {USER}" 2 3
sudo -u www-data php ./occ talk:command:add mot mot "/path/to/your/directory/mot.php {ARGUMENTS} {ROOM} {USER}" 1 3
```

création de l'utilisateur bot_infiltre pour que le bot envoie des messages avec une coloration au pseudo du type @pseudo et d'obtenir la liste des participants à la room.
Modifier le fichier de config avec le mot de passe de votre bot_infiltre

## Utilisation

Saisir `/game start` pour lancer la partie.

Saisir `/mot` permet à chaque joueur de récupérer son mot secret

Saisir `/game vote [joueur]` pour éliminer le joueur qui a reçu le plus de vote.

Saisir `/game score` pour afficher les scores de tous les joueurs

Saisir `/game --help` pour de l'aide.

Inspiration pour l'infiltré : https://play.google.com/store/apps/details?id=com.yanstarstudio.joss.undercover <img src="https://lh3.googleusercontent.com/jtdsLb6b1oycRQuMaRAhUXITmHhOhZZdzidy6LhyRquO5bBfnD0ksY_M7hToB7S8gQ=s180-rw" width=20px height=20px>
