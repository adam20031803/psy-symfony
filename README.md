# 🧠 PsyApp - Module Motivation & Défis (Symfony)

Bienvenue sur le dépôt du projet **PsyApp**, une plateforme complète et moderne développée en **Symfony**. Ce projet intègre une interface de gestion ultra-professionnelle (design Glassmorphism, animations fluides) dédiée à la santé mentale, la motivation, la forme physique et les habitudes.

Ce dépôt met particulièrement en avant la branche et le module **Motivation (Challenge Manager Pro)**.

---

## 🎯 Fonctionnalités Principales

- **Dashboard Centralisé** : Un écran de contrôle (`/dashboard`) élégant donnant accès aux différents piliers (Motivation, Habitude, Fitness, Posts, Mentalité).
- **Gestion des Challenges (Défis)** : 
  - Pilotage des statuts (Actifs, Terminés, Annulés).
  - Tri, filtrage riche et génération dynamique de rapports **PDF** de haute qualité détaillant les coaches et récompenses attribués.
- **Gestion des Coaches** :
  - Ajout de mentors professionnels avec photo de profil dynamique et import de CV.
  - Système de **mailing intégré** pour communiquer directement avec eux.
- **Catalogue des Récompenses** : Création de systèmes de points et de trophées rattachables aux défis.
- **Design "Premium"** : Utilisation exclusive du CSS moderne (Glassmorphism), requêtes de base de données avancées, et prévention rigoureuse avec validation sécurisée.

---

## ⚙️ Prérequis

Avant de lancer le projet, assurez-vous d'avoir installé sur votre machine :
- **PHP** (v8.1 ou supérieur)
- **Composer** (Gestionnaire de dépendances PHP)
- **Symfony CLI**
- **MySQL / MariaDB** (ou équivalent)

---

## 🚀 Installation & Lancement

Suivez ces étapes pour exécuter le projet localement :

### 1. Cloner le Projet
```bash
git clone https://github.com/adam20031803/psy-symfony.git
cd psy-symfony
git checkout motivation # (ou la branche pertinente)
```

### 2. Installer les Dépendances
```bash
composer install
```

### 3. Configurer l'Environnement
Dupliquez le fichier `.env` pour créer un fichier local (ou modifiez directement le `.env.local` si présent). Configurez-y les accès à votre base de données et à votre serveur d'envoi d'e-mails :

```dotenv
# .env (exemples)
DATABASE_URL="mysql://root:@127.0.0.1:3306/psy_symfony?serverVersion=8&charset=utf8mb4"
MAILER_DSN="smtp://votre_utilisateur:votre_motdepasse@serveur_smtp:port"
MAILER_FROM="no-reply@psyapp.com"
```

### 4. Créer et Migrer la Base de Données
Ces commandes préparent la base de données de l'application selon la configuration Doctrine :
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 5. Démarrer l'Application
Lancez le serveur local de Symfony :
```bash
symfony server:start
```
L'application sera accessible sur : `http://127.0.0.1:8000/`

---

## 📖 Guide d'Utilisation (Logique Métier)

### 1. Accès au Panel de Contrôle
Rendez-vous sur **`http://127.0.0.1:8000/dashboard`**. Vous y trouverez une barre latérale ("Sidebar") designée qui vous permettra de naviguer vers la section de votre choix. Pour gérer le module central, cliquez sur **Motivation**.

### 2. Création et Édition de Coaches
- Naviguez vers `http://127.0.0.1:8000/coach/`.
- Ajoutez un coach via le formulaire latéral. Vous pouvez **uploader une photo de profil** et un document PDF pour le **CV**.
- Si un coach est Inactif, il n'apparaîtra pas dans les listes de sélection ; assurez-vous de cocher "Actif".
- Vous pouvez directement envoyer un E-mail au coach depuis sa fiche. L'adresse "Répondre à" s'adaptera automatiquement à l'utilisateur connecté !

### 3. Création de Récompenses
- Naviguez vers `http://127.0.0.1:8000/recompense/`.
- Définissez des lots ou des trophées virtuels en spécifiant l'intitulé (ex: *Livre de psychologie positive*) et leur **valeur en points**.

### 4. Assemblage d'un Challenge
Le cœur du système de motivation !
- Naviguez vers `http://127.0.0.1:8000/challenge/`.
- Remplissez les règles du défi : Nom, description stricte (mini 3 caractères, pas de titres uniquement numériques), et ses intervalles de temps.
- **Liaison :** Affectez ensuite vos **Coaches** et **Récompenses** préalablement créés, directement à ce défi !
- **Suivi :** Utilisez la barre de filtre professionnelle en haut pour chercher par nom, catégorie, ou statut d'exécution.

### 5. Reporting et Export PDF
Vous avez besoin d'une évaluation rapide pour une réunion ou un compte-rendu technique ? 
- Depuis la gestion des challenges, cliquez sur le bouton **EXPORT PDF**. 
- Vous obtiendrez un rapport premium téléchargeable immédiatement (`challenges_export.pdf`), généré avec *Dompdf*, présentant le détail exact de chaque challenge, y compris les coaches impliqués (Nom + Email) et le coût en points des récompenses affiliées.

---
*Conçu avec créativité et expertise logicielle pour le bien-être.* 💙
