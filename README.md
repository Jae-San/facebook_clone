# Facebook Clone - API Backend

Un clone de Facebook avec une API REST complète développée en PHP, incluant toutes les fonctionnalités principales : authentification, posts, commentaires, likes, amis, chat, et administration.

## 🚀 Fonctionnalités

### 🔐 Authentification & Sécurité
- **Inscription/Connexion** avec validation robuste
- **Hachage sécurisé** des mots de passe (password_hash)
- **Tokens JWT** pour l'authentification
- **Réinitialisation de mot de passe** par email
- **Validation des entrées** et protection contre les injections SQL
- **Gestion des rôles** (Utilisateur, Modérateur, Administrateur)

### 👥 Gestion des Utilisateurs
- **Profils utilisateurs** complets avec avatars
- **Système d'amis** avec demandes d'amitié
- **Recherche d'utilisateurs**
- **Gestion des permissions** basée sur les rôles

### 📝 Posts & Contenu
- **Création de posts** avec texte, images et vidéos
- **Système de commentaires** avec réponses imbriquées
- **Likes** sur posts et commentaires
- **Gestion de la confidentialité** (public, amis, privé)
- **Modération de contenu** par les administrateurs

### 💬 Chat en Temps Réel
- **Messages privés** entre utilisateurs
- **Conversations de groupe**
- **Historique des messages**
- **Envoi de fichiers** et images
- **Notifications** en temps réel

### 🛡️ Administration
- **Tableau de bord** avec statistiques
- **Gestion des utilisateurs** (bannir, promouvoir)
- **Modération de contenu** (supprimer posts/commentaires)
- **Système de rapports** pour contenu inapproprié
- **Statistiques détaillées** de la plateforme

## 🏗️ Architecture

### Structure des Fichiers
```
facebook_clone/
├── api/                          # API Backend
│   ├── config/
│   │   └── database.php          # Configuration PDO
│   ├── lib/
│   │   ├── AuthHelper.php        # Gestion des rôles/permissions
│   │   ├── EmailSender.php       # Envoi d'emails
│   │   └── Utils.php             # Utilitaires généraux
│   ├── models/                   # Modèles de données
│   │   ├── User.php              # Gestion des utilisateurs
│   │   ├── Post.php              # Gestion des posts
│   │   ├── Comment.php           # Gestion des commentaires
│   │   ├── Like.php              # Gestion des likes
│   │   └── Message.php           # Gestion des messages
│   ├── auth.php                  # API d'authentification
│   ├── admin.php                 # API d'administration
│   ├── chat.php                  # API de chat
│   ├── posts.php                 # API des posts
│   ├── comments.php              # API des commentaires
│   ├── likes.php                 # API des likes
│   ├── friends.php               # API des amis
│   └── profile.php               # API des profils
├── assets/                       # Ressources statiques
│   ├── css/                      # Styles CSS
│   ├── js/                       # JavaScript
│   └── images/                   # Images et avatars
├── vues/                         # Interface utilisateur
│   ├── clients/                  # Pages utilisateurs
│   └── back-office/              # Interface d'administration
├── database.sql                  # Schéma de base de données
└── README.md                     # Documentation
```

### Base de Données
Le schéma inclut toutes les tables nécessaires :
- **users** : Utilisateurs et profils
- **roles** : Rôles et permissions
- **posts** : Articles et publications
- **comments** : Commentaires avec réponses
- **likes** : Système de likes
- **friendships** : Relations d'amitié
- **conversations** : Conversations de chat
- **messages** : Messages privés
- **notifications** : Système de notifications
- **reports** : Rapports de contenu
- **auth_tokens** : Tokens d'authentification

## 🛠️ Installation

### Prérequis
- **PHP 7.4+** avec extensions PDO, JSON, mbstring
- **MySQL 5.7+** ou **MariaDB 10.2+**
- **Serveur web** (Apache/Nginx)
- **Composer** (optionnel, pour les dépendances)

### Étapes d'Installation

1. **Cloner le projet**
   ```bash
   git clone https://github.com/votre-username/facebook-clone.git
   cd facebook-clone
   ```

2. **Configurer la base de données**
   ```bash
   # Importer le schéma
   mysql -u root -p < database.sql
   ```

3. **Configurer la connexion**
   ```php
   // Modifier api/config/database.php
   private $host = 'localhost';
   private $dbname = 'facebook_clone';
   private $username = 'votre_username';
   private $password = 'votre_password';
   ```

4. **Configurer les permissions**
   ```bash
   chmod 755 logs/
   chmod 644 api/config/database.php
   ```

5. **Créer les dossiers d'upload**
   ```bash
   mkdir -p assets/images/profiles
   mkdir -p assets/images/uploads
   chmod 755 assets/images/profiles
   chmod 755 assets/images/uploads
   ```

## 📡 API Endpoints

### Authentification
- `POST /api/auth.php?action=register` - Inscription
- `POST /api/auth.php?action=login` - Connexion
- `POST /api/auth.php?action=logout` - Déconnexion
- `POST /api/auth.php?action=forgot-password` - Mot de passe oublié
- `POST /api/auth.php?action=reset-password` - Réinitialisation
- `GET /api/auth.php?action=profile` - Profil utilisateur

### Posts
- `GET /api/posts.php?action=feed` - Fil d'actualité
- `POST /api/posts.php?action=create` - Créer un post
- `PUT /api/posts.php?action=update` - Modifier un post
- `DELETE /api/posts.php?action=delete` - Supprimer un post

### Commentaires
- `POST /api/comments.php?action=add` - Ajouter un commentaire
- `GET /api/comments.php?action=list` - Liste des commentaires
- `PUT /api/comments.php?action=update` - Modifier un commentaire
- `DELETE /api/comments.php?action=delete` - Supprimer un commentaire

### Likes
- `POST /api/likes.php?action=toggle` - Like/Unlike
- `GET /api/likes.php?action=count` - Nombre de likes

### Chat
- `POST /api/chat.php?action=conversation` - Créer une conversation
- `POST /api/chat.php?action=message` - Envoyer un message
- `GET /api/chat.php?action=conversations` - Liste des conversations
- `GET /api/chat.php?action=messages` - Messages d'une conversation

### Administration
- `GET /api/admin.php?action=dashboard` - Tableau de bord
- `GET /api/admin.php?action=users` - Liste des utilisateurs
- `POST /api/admin.php?action=ban` - Bannir un utilisateur
- `POST /api/admin.php?action=delete-post` - Supprimer un post
- `GET /api/admin.php?action=statistics` - Statistiques

## 🔒 Sécurité

### Mesures Implémentées
- **Requêtes préparées PDO** contre les injections SQL
- **Validation des entrées** côté serveur
- **Hachage sécurisé** des mots de passe
- **Tokens JWT** pour l'authentification
- **Protection CSRF** sur les formulaires
- **Validation des types** et formats
- **Logs de sécurité** pour audit

### Bonnes Pratiques
- Toutes les requêtes utilisent des requêtes préparées
- Validation stricte des données d'entrée
- Gestion d'erreurs sans exposition d'informations sensibles
- Logs détaillés pour le debugging et la sécurité
- Permissions granulaires basées sur les rôles

## 📊 Performance

### Optimisations
- **Index de base de données** optimisés
- **Requêtes optimisées** avec JOINs appropriés
- **Pagination** pour les listes volumineuses
- **Cache des requêtes** fréquentes
- **Compression** des réponses JSON

### Monitoring
- Logs détaillés des performances
- Statistiques d'utilisation
- Monitoring des erreurs
- Métriques de base de données

## 🧪 Tests

### Tests Manuels
1. **Authentification** : Inscription, connexion, déconnexion
2. **Posts** : Création, modification, suppression
3. **Commentaires** : Ajout, modification, suppression
4. **Likes** : Like/Unlike posts et commentaires
5. **Chat** : Envoi et réception de messages
6. **Administration** : Gestion des utilisateurs et contenu

### Tests de Sécurité
- Injection SQL
- XSS (Cross-Site Scripting)
- CSRF (Cross-Site Request Forgery)
- Authentification et autorisation
- Validation des entrées

## 🤝 Contribution

### Guide de Contribution
1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

### Standards de Code
- **PSR-12** pour le style de code PHP
- **Commentaires** en français
- **Noms de variables** explicites
- **Gestion d'erreurs** complète
- **Documentation** des fonctions

## 📝 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 🆘 Support

### Problèmes Courants
1. **Erreur de connexion BD** : Vérifier les paramètres dans `database.php`
2. **Permissions refusées** : Vérifier les droits sur les dossiers logs/ et uploads/
3. **Erreurs 500** : Vérifier les logs dans `logs/api.log`

### Contact
- **Issues** : [GitHub Issues](https://github.com/votre-username/facebook-clone/issues)
- **Email** : support@facebook-clone.com

## 🔄 Changelog

### Version 1.0.0 (2024-01-XX)
- ✅ Authentification complète
- ✅ Gestion des posts et commentaires
- ✅ Système de likes
- ✅ Chat en temps réel
- ✅ Interface d'administration
- ✅ Sécurité renforcée
- ✅ Documentation complète

---

**Développé avec ❤️ pour l'apprentissage et la démonstration des bonnes pratiques de développement web.**
