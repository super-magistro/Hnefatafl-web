# Hnefatafl Online ⚔️

**Hnefatafl Online** est une plateforme web moderne permettant de jouer au célèbre jeu de stratégie asymétrique Viking, le *Hnefatafl* (le jeu de plateau des rois), ainsi qu'à ses multiples variantes historiques (Tablut, Copenhagen, etc.). 

Le projet est conçu avec une architecture découplée moderne : un front-end réactif développé avec **Nuxt 4**, une API REST performante propulsée par **Symfony 7.4** et **API Platform v4**, le tout conteneurisé sous **Docker** et orchestré avec **Docker Compose**.

---

## 🛠️ Stack Technique

### Front-end
- **Framework** : [Nuxt 4](https://nuxt.com/) (mode `app` structure, Composition API `<script setup>`).
- **Langage** : [TypeScript](https://www.typescriptlang.org/) (mode strict).
- **UI Framework** : [Nuxt UI v4](https://ui.nuxt.com/) & [Tailwind CSS](https://tailwindcss.com/) pour une interface élégante et thématique.
- **Thème Visuel** : Palette sémantique personnalisée centralisée dans `app.config.ts` :
  - `primary` : Vert de la forêt (ambiance Viking).
  - `neutral` : Spring-wood (Beige/Papyrus pour les fonds).
  - `warning` : Golden-grass (Or/Butin pour les accents et mises en avant).
  - `error` : Red (Sang/Alerte pour les actions critiques et camps attaquants).

### Back-end & API
- **Framework** : [Symfony 7.4](https://symfony.com/) avec [API Platform v4](https://api-platform.com/).
- **Authentification** : JWT sécurisé via [LexikJWTAuthenticationBundle](https://github.com/lexik/LexikJWTAuthenticationBundle) avec session persistante longue durée (30 jours).
- **Base de Données** : MySQL 8.
- **Moteur d'ORM** : Doctrine ORM.

### Infrastructure & Conteneurisation
- Orchestration complète via **Docker Compose** :
  - `nginx` : Serveur web servant l'API Symfony.
  - `sfapi` : Conteneur PHP 8.2+ faisant tourner le backend Symfony.
  - `database` : Conteneur MySQL (port local `3307`).
  - `front` : Conteneur Node.js (Alpine) servant l'application Nuxt sur le port `3000` avec hot-reload.

---

## 📂 Structure du Projet

Le dépôt est découpé de la manière suivante :

```text
├── build/                 # Configuration et Dockerfiles (nginx, sfapi, database)
├── front/                 # Application Front-end Nuxt 4
│   ├── app/               # Code source Nuxt 4 (pages, components, layouts, utils)
│   ├── public/            # Assets publics statiques
│   ├── nuxt.config.ts     # Configuration de Nuxt
│   └── package.json       # Dépendances Node.js
├── sfapi/                 # API Back-end Symfony 7.4
│   ├── config/            # Configuration de l'application Symfony
│   ├── src/               # Code source PHP (Entities, Controllers, Repositories)
│   ├── migrations/        # Migrations de la base de données SQL
│   └── composer.json      # Dépendances PHP
├── composables/           # Composables partagés (ex: useAuth.ts)
├── scratch/               # Scripts et données de test (fichiers JSON)
├── compose.yaml           # Fichier d'orchestration Docker Compose
├── GEMINI.md              # Conventions de développement et de style pour l'IA
└── README.md              # Documentation principale du projet (ce fichier)
```

---

## Installation et Lancement

### Prérequis
Assurez-vous d'avoir installé :
- **Docker** et **Docker Compose**
- Un client Git

### Étape 1 : Démarrage des Conteneurs
À la racine du projet, lancez la commande suivante pour construire et démarrer les conteneurs :

```bash
docker compose up -d --build
```

- Le **Front-end** sera accessible sur : [http://localhost:3000](http://localhost:3000) (il installe automatiquement les dépendances `npm` lors du premier lancement).
- L'**API Platform Symfony** sera accessible sur : [http://localhost:8000](http://localhost:8000) (avec la documentation Swagger interactive sur `/api`).

### Étape 2 : Initialisation de la Base de Données
Une fois les conteneurs démarrés, appliquez les migrations et chargez les fixtures de test (variantes de plateaux, utilisateurs, etc.) :

```bash
# Se connecter au conteneur PHP et installer les dépendances composer si nécessaire
docker compose exec sfapi composer install

# Exécuter les migrations de base de données
docker compose exec sfapi php bin/console doctrine:migrations:migrate --no-interaction

# Charger les fixtures de départ (Variantes historiques, Utilisateurs de test)
docker compose exec sfapi php bin/console doctrine:fixtures:load --no-interaction
```

---

## ⚔Fonctionnalités Clés Implémentées

1. **Manuel de Règles Dynamique (`/rules`)** :
   - Fiches de règles adaptées en temps réel à chaque variante de plateau (Tablut, Hnefatafl, etc.).
   - Traduction centralisée de la configuration de l'API (ex: Roi fort/faible, trône hostile ou non) via un utilitaire partagé [boardRules.ts](file:///home/romain-guillon/Bureau/Camileia/prv/Hnefatafl-web/front/app/utils/boardRules.ts).
   - Rendu interactif du plateau grâce au composant `MoleculesBoardPreview`.

2. **Système de Matchmaking ELO Asynchrone** :
   - Recherche rapide d'adversaire avec extension automatique et progressive de la plage d'ELO autorisée (+30 ELO toutes les 500 ms) pour garantir un matchmaking équitable et réactif.

3. **Création et Partage de Défis Amis** :
   - Configuration personnalisée de défis (camp, cadence de jeu).
   - Génération d'un lien unique de combat copié directement dans le presse-papiers (`navigator.clipboard` avec fallback text-area).

4. **Authentification JWT Robuste** :
   - Persistance longue durée configurée pour **30 jours** (côté serveur JWT et cookie client `auth_token`).
   - Détection automatique d'expiration et déconnexion réactive en cas de réponse `401 Unauthorized` de l'API.

5. **Indexation & Cache des Joueurs** :
   - Utilisation du composable réactif `useUserMap` stockant localement sous forme de `Map` l'ensemble des profils pour éviter les requêtes API redondantes lors de l'affichage des parties.

---

## Conventions de Développement

Toutes les modifications du projet doivent respecter les consignes définies dans le fichier [GEMINI.md](file:///home/romain-guillon/Bureau/Camileia/prv/Hnefatafl-web/GEMINI.md), en particulier :
- **Pas de CSS Inline** : Utilisez exclusivement les classes de Tailwind CSS.
- **Thème centralisé** : Utilisez uniquement les clés sémantiques de couleurs (`primary`, `neutral`, `warning`, `error`).
- **Composants Nuxt UI** : Utilisez les composants natifs de Nuxt UI (`<UCard>`, `<UContainer>`, etc.) en privilégiant la configuration via `app.config.ts`.
- **Typage Strict** : Pas de type `any` en TypeScript, typez rigoureusement les props et les refs.
- **Conventions de Commit** :
  - Format : `[Type] Description`
  - Types : `[Feat]`, `[Fix]`, `[Refa]`, `[Docs]`, `[Style]`
