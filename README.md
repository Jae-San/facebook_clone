# Facebook Clone

## Description du projet
Ce projet est un clone simplifié de Facebook, développé en PHP avec une base de données MySQL. Il permet aux utilisateurs de s'inscrire, de se connecter, de publier des messages (avec ou sans image), d'ajouter des amis, d'envoyer des messages privés, de liker des publications et de suivre les tendances. L'interface s'inspire du réseau social Facebook pour offrir une expérience utilisateur familière.

## Mode de fonctionnement
- **Inscription & Connexion** : Les utilisateurs peuvent créer un compte, confirmer leur email, puis se connecter.
- **Mur d'actualité** : Les utilisateurs voient les publications de leurs amis et peuvent publier du texte ou des images.
- **Amis** : Possibilité d'ajouter, supprimer des amis et de voir les amis en commun.
- **Messagerie** : Envoi de messages privés entre amis.
- **Likes & Commentaires** : Les utilisateurs peuvent liker et commenter les publications.
- **Tendances** : Affichage des sujets tendances selon l'activité.
- **Gestion de profil** : Modification des informations et de la photo de profil.
- **Rôles** : Utilisateurs, administrateurs et modérateurs (gestion via la base de données).

## Identifiants de test

### Utilisateur client
- **Email** : groupdeux@gmail.com
- **Mot de passe** : b1e2e2e2e2e2e2e2e2e2e2e2e2e2e2e2 (hashé, à réinitialiser ou à remplacer par un mot de passe connu si besoin)
- **Nom d'utilisateur** : groupedeux

### Administrateur
- **Email** : groupedeuxesgis@gmail.com
- **Mot de passe** : b8e2e2e2e2e2e2e2e2e2e2e2e2e2e2e2 (hashé, à réinitialiser ou à remplacer par un mot de passe connu si besoin)
- **Nom d'utilisateur** : groupedeuxesgis

### Autres comptes de test
- **Lauriane Degan** : Laurianelaurie029@gmail.com / username : lauriane_degan
- **Modérateur** : tpesgis@gmail.com / username : tpesgis

> **Remarque :** Les mots de passe sont stockés hashés dans la base de données. Pour tester en local, vous pouvez soit réinitialiser le mot de passe via la fonctionnalité "mot de passe oublié", soit insérer un mot de passe connu en base.

## Liste des membres du groupe
  GROUPE 10:
- AYISSO Lauriane
- HOUNDENOU Conceptia
- LOVI Jae-San
- MASSENON Alex

## Structure du projet
```
facebook_clone/
├── api/           # Backend PHP
├── assets/        # CSS, JS, Images
├── vues/          # Pages frontend
├── .eslintrc.json # Configuration ESLint
└── .prettierrc    # Configuration Prettier
```

## Installation rapide
1. Cloner le dépôt :
   ```bash
   git clone https://github.com/Jae-San/facebook_clone.git
   cd facebook_clone
   ```
2. Importer le fichier `social .sql` dans votre base de données MySQL.
3. Configurer la connexion à la base dans `config/config.php` si besoin.
4. Lancer le serveur local (ex : XAMPP, WAMP) et accéder à `index.php`.

## Contact & Support
Pour toute question, ouvrez une issue sur GitHub ou contactez un membre du groupe au +2290153531653.
