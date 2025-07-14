# Guide de Collaboration - Facebook Clone

## 🚀 Installation et Configuration

### 1. Cloner le projet
```bash
git clone https://github.com/Jae-San/facebook_clone.git
cd facebook_clone
```

### 2. Installer les extensions Cursor/VS Code
```bash
# Extensions essentielles
cursor --install-extension dbaeumer.vscode-eslint
cursor --install-extension esbenp.prettier-vscode
cursor --install-extension bmewburn.vscode-intelephense-client
cursor --install-extension laravel.vscode-laravel
cursor --install-extension onecentlin.laravel-blade
```

### 3. Configurer Git (si pas déjà fait)
```bash
git config --global user.name "Votre Nom"
git config --global user.email "votre.email@example.com"
```

## 🌿 Workflow avec les Branches

### Créer une nouvelle branche pour votre fonctionnalité
```bash
# Récupérer les dernières modifications
git pull origin master

# Créer et basculer sur une nouvelle branche
git checkout -b feature/nom-de-votre-fonctionnalite

# Exemples de noms de branches :
# feature/user-authentication
# feature/chat-system
# feature/post-creation
# bugfix/login-error
```

### Travailler sur votre branche
```bash
# Voir sur quelle branche vous êtes
git branch

# Ajouter vos modifications
git add .

# Créer un commit
git commit -m "Add user authentication feature"

# Pousser votre branche vers GitHub
git push origin feature/nom-de-votre-fonctionnalite
```

### Créer une Pull Request
1. Allez sur GitHub : https://github.com/Jae-San/facebook_clone
2. Cliquez sur "Compare & pull request"
3. Remplissez la description de vos changements
4. Assignez des reviewers si nécessaire
5. Cliquez "Create pull request"

## 🔄 Workflow quotidien

### Commencer une journée de travail
```bash
# Récupérer les dernières modifications
git pull origin master

# Basculer sur votre branche
git checkout feature/votre-branche

# Mettre à jour votre branche avec master
git merge master
```

### Pendant le développement
```bash
# Voir les fichiers modifiés
git status

# Voir les différences
git diff

# Ajouter des fichiers spécifiques
git add nom-du-fichier.php

# Créer un commit
git commit -m "Description claire des changements"
```

### Finir une fonctionnalité
```bash
# Pousser vos derniers changements
git push origin feature/votre-branche

# Créer une Pull Request sur GitHub
# Attendre la review et l'approbation
# Une fois approuvée, merger dans master
```

## 🎯 Règles de Collaboration

### Noms de branches
- `feature/nom-fonctionnalite` - Nouvelles fonctionnalités
- `bugfix/nom-bug` - Corrections de bugs
- `hotfix/nom-urgence` - Corrections urgentes
- `refactor/nom-refactorisation` - Refactorisation de code

### Messages de commit
- Utilisez des verbes à l'impératif : "Add", "Fix", "Update", "Remove"
- Soyez descriptif mais concis
- Exemples :
  - `Add user login functionality`
  - `Fix database connection error`
  - `Update CSS styling for mobile`

### Code Review
- Chaque Pull Request doit être reviewée
- Les tests doivent passer
- Le code doit respecter les standards ESLint/Prettier
- Documentez les changements importants

## 🛠️ Outils de développement

### ESLint et Prettier
- Les erreurs apparaissent en rouge/jaune dans l'éditeur
- Formatage automatique à la sauvegarde
- Commandes utiles :
  ```bash
  # Formater le code
  npx prettier --write .
  
  # Vérifier les erreurs
  npx eslint .
  ```

### Structure du projet
```
facebook_clone/
├── api/           # Backend PHP
├── assets/        # CSS, JS, Images
├── vues/          # Pages frontend
├── .eslintrc.json # Configuration ESLint
└── .prettierrc    # Configuration Prettier
```

## 🚨 En cas de conflits

### Résoudre les conflits Git
```bash
# Pendant un merge ou pull
git status  # Voir les fichiers en conflit

# Ouvrir les fichiers en conflit dans l'éditeur
# Résoudre manuellement les conflits
# Ajouter les fichiers résolus
git add .

# Finaliser le merge
git commit
```

### Conseils pour éviter les conflits
- Travaillez sur des branches séparées
- Communiquez avec l'équipe
- Faites des commits fréquents
- Pull régulièrement depuis master

## 📞 Communication

### Avant de commencer une fonctionnalité
- Discutez avec l'équipe
- Assurez-vous qu'il n'y a pas de conflit
- Définissez clairement les objectifs

### Pendant le développement
- Mettez à jour régulièrement votre branche
- Signalez les problèmes rencontrés
- Demandez de l'aide si nécessaire

### Après avoir terminé
- Testez votre code
- Créez une Pull Request claire
- Répondez aux commentaires de review

## 🎉 Bonnes pratiques

1. **Commits fréquents** - Plus petits et plus fréquents
2. **Tests** - Testez votre code avant de pousser
3. **Documentation** - Commentez le code complexe
4. **Communication** - Parlez avec l'équipe
5. **Backup** - Sauvegardez votre travail localement

---

**Besoin d'aide ?** Contactez l'équipe ou créez une issue sur GitHub ! 