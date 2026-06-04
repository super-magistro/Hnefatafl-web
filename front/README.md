# Hnefatafl Online - Front-end Application 🛡️

Cette application front-end est développée avec **Nuxt 4**, **Vue 3** (Composition API, `<script setup>`), **TypeScript** (mode strict), **Nuxt UI v4** et **Tailwind CSS**.

Elle interagit avec l'API Symfony conteneurisée. Dans notre environnement Docker Compose standard, tout est géré automatiquement. Cependant, si vous souhaitez travailler en dehors de Docker ou en comprendre les commandes, voici le guide d'utilisation.

---

## 🚀 Démarrage Rapide

### Installation des dépendances

```bash
# Avec npm (recommandé pour ce projet)
npm install
```

### Serveur de Développement
Pour lancer l'application en mode développement avec rechargement à chaud (hot-reload) sur [http://localhost:3000](http://localhost:3000) :

```bash
npm run dev
```

### Production Build
Générer l'application optimisée pour la production :

```bash
npm run build
```

Pour prévisualiser localement le build de production :

```bash
npm run preview
```

---

## 📂 Architecture du Code Front-end

L'application suit la structure de répertoires recommandée par **Nuxt 4** (avec le dossier racine `/app`) :

```text
front/
├── app/
│   ├── assets/            # Fichiers CSS globaux et polices (Cinzel, Outfit)
│   ├── components/        # Composants Vue réutilisables (Atoms, Molecules, Organisms)
│   ├── composables/       # Composables Nuxt (ex: useApi, useAuth, useUserMap)
│   ├── game-types/        # Types TypeScript spécifiques au jeu Hnefatafl
│   ├── layouts/           # Mises en page (default.vue, sidebar.vue)
│   ├── pages/             # Pages de l'application (index, main, login, games, rules, settings)
│   ├── utils/             # Fonctions utilitaires partagées (ex: boardRules.ts pour la traduction)
│   ├── app.config.ts      # Fichier central de configuration thématique de Nuxt UI
│   ├── app.vue            # Point d'entrée de l'application Vue
│   └── router.options.ts  # Options de routage avancées
├── public/                # Fichiers statiques servis à la racine (icônes, images)
├── nuxt.config.ts         # Configuration système de Nuxt
├── tsconfig.json          # Configuration strict de TypeScript
└── package.json           # Dépendances et scripts de build
```

---

## 🧬 Règle du Thème Visuel et Centralisation

Comme indiqué dans le code du guerrier (`GEMINI.md`) :
- **CSS Inline interdit** : Utilisez uniquement Tailwind CSS.
- **Thème centralisé** : Toute modification esthétique globale d'un composant Nuxt UI doit être enregistrée dans `app.config.ts`.
- **Couleurs sémantiques** : Utilisez exclusivement les clés suivantes :
  - `primary` : Vert forêt
  - `neutral` : Spring-wood (Beige/Papyrus)
  - `warning` : Golden-grass (Or/Butin)
  - `error` : Red (Sang/Alerte)
- **Typographie** : Les polices `Cinzel` (pour les titres runiques et scandinaves) et `Outfit`/`Inter` (pour le texte fluide) sont chargées et configurées.
